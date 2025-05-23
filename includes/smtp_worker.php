<?php
    require __DIR__ . '/../db.php';
    
    // Configure logging for worker
    define('LOG_DIR', __DIR__ . '/../logs');
    define('LOG_FILE', LOG_DIR . '/smtp_worker_' . date('Y-m-d') . '.log');
    
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    function workerLog($message, $email = '', $domain = '') {
        $timestamp = date('[Y-m-d H:i:s]');
        $logMessage = "$timestamp [Worker " . getmypid() . "]";
        if ($email) $logMessage .= " [$email]";
        if ($domain) $logMessage .= " [$domain]";
        $logMessage .= " $message\n";
        
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
    
    $offset = $argv[1] ?? 0;
    $limit = $argv[2] ?? 100;
    
    workerLog("Starting worker process with offset: $offset, limit: $limit");
    
    $emails = $conn->query("SELECT id, raw_emailid, sp_domain FROM emails WHERE domain_status=1 AND domain_processed=0 LIMIT $offset, $limit");
    
    while ($row = $emails->fetch_assoc()) {
        $email = $row['raw_emailid'];
        $domain = $row['sp_domain'];
        
        workerLog("Processing email", $email, $domain);
        
        $verification = verifyEmailViaSMTP($email, $domain);
        
        if ($verification['status'] === 'success') {
            $status = $verification['result'];
            $message = $conn->real_escape_string($verification['message']);
        } else {
            $status = 0;
            $message = "Invalid response";
        }
        
        workerLog("Result: " . ($status ? "Valid" : "Invalid") . " - $message", $email, $domain);
        
        $conn->query("UPDATE emails SET 
                     domain_status = $status,
                     validation_response = '$message',
                     domain_processed = 1
                     WHERE id = {$row['id']}");
    }
    
    function verifyEmailViaSMTP($email, $domain) {
        workerLog("Starting verification", $email, $domain);
        
        if (!getmxrr($domain, $mxhosts)) {
            $message = "No MX record found for domain: $domain";
            workerLog($message, $email, $domain);
            return ["status" => "error", "message" => $message];
        }

        $mxIP = gethostbyname($mxhosts[0]);
        $port = 25;
        $timeout = 30;

        workerLog("Connecting to MX: $mxhosts[0] ($mxIP) on port $port", $email, $domain);

        $context = stream_context_create();
        $smtp = stream_socket_client(
            "tcp://$mxIP:$port",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$smtp) {
            $message = "Could not connect to $mxIP: $errstr ($errno)";
            workerLog($message, $email, $domain);
            return ["status" => "error", "message" => $message];
        }

        stream_set_timeout($smtp, $timeout);

        $response = fgets($smtp, 4096);
        workerLog("SERVER: " . trim($response), $email, $domain);

        if (substr($response, 0, 3) != '220') {
            fclose($smtp);
            $message = "SMTP server not ready: " . trim($response);
            workerLog($message, $email, $domain);
            return ["status" => "error", "message" => $message];
        }

        fputs($smtp, "EHLO server.relyon.co.in\r\n");
        workerLog("Sent: EHLO server.relyon.co.in", $email, $domain);
        
        $ehloResponse = '';
        while ($line = fgets($smtp, 4096)) {
            $ehloResponse .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        workerLog("EHLO Response: " . trim($ehloResponse), $email, $domain);

        fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
        workerLog("Sent: MAIL FROM:<info@relyon.co.in>", $email, $domain);
        
        $response = fgets($smtp, 4096);
        workerLog("MAIL FROM Response: " . trim($response), $email, $domain);

        fputs($smtp, "RCPT TO:<$email>\r\n");
        workerLog("Sent: RCPT TO:<$email>", $email, $domain);
        
        $response = fgets($smtp, 4096);
        workerLog("RCPT TO Response: " . trim($response), $email, $domain);

        $result = (substr($response, 0, 3) == '250') ? 1 : 0;

        fputs($smtp, "QUIT\r\n");
        fclose($smtp);

        if ($result === 1) {
            $message = "Verification successful. MX: $mxIP";
            workerLog($message, $email, $domain);
            return ["status" => "success", "result" => 1, "message" => $mxIP];
        } else {
            $message = "Invalid response";
            workerLog($message, $email, $domain);
            return ["status" => "success", "result" => 0, "message" => $message];
        }
    }
    
    workerLog("Worker process completed");
    ?>