<?php
// Database connection
require 'db.php';
global $conn;

// Configuration
define('MAX_WORKERS', 8); // Number of parallel processes
define('BATCH_SIZE', 100); // Emails per worker
define('WORKER_SCRIPT', __DIR__ . '/smtp_worker.php');

// Updated function using stream_socket_client()
function verifyEmailViaSMTP($email, $domain)
{
    if (!getmxrr($domain, $mxhosts)) {
        echo " No MX record found for domain: $domain\n";
        return ["status" => "error", "message" => "No MX record found"];
    }

    $mxIP = gethostbyname($mxhosts[0]);
    $port = 25;
    $timeout = 30;

    echo " Connecting to: $mxIP on port $port...\n";

    // Using stream_socket_client() instead of fsockopen()
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
        echo " Could not connect to $mxIP: $errstr ($errno)\n";
        return ["status" => "error", "message" => "Connection failed"];
    }

    stream_set_timeout($smtp, $timeout);

    $response = fgets($smtp, 4096);
    echo "SERVER: $response";

    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ["status" => "error", "message" => "SMTP server not ready"];
    }

    fputs($smtp, "EHLO server.relyon.co.in\r\n");
    echo " Sent: EHLO server.relyon.co.in\n";
    while ($line = fgets($smtp, 4096)) {
        echo " $line";
        if (substr($line, 3, 1) == ' ')
            break;
    }

    fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
    echo " Sent: MAIL FROM:<info@relyon.co.in>\n";
    $response = fgets($smtp, 4096);
    echo " Response: $response";

    fputs($smtp, "RCPT TO:<$email>\r\n");
    echo " Sent: RCPT TO:<$email>\n";
    $response = fgets($smtp, 4096);
    echo " Response: $response";

    $result = (substr($response, 0, 3) == '250') ? 1 : 0;

    fputs($smtp, "QUIT\r\n");
    fclose($smtp);

    if ($result === 1) {
        return ["status" => "success", "result" => 1, "message" => $mxIP];
    } else {
        return ["status" => "success", "result" => 0, "message" => "Invalid response"];
    }
}

// Create worker script with domain processing tracking
function createWorkerScript()
{
    $workerCode = '<?php
    require __DIR__.\'/db.php\';
    $offset = $argv[1] ?? 0;
    $limit = $argv[2] ?? ' . BATCH_SIZE . ';
    
    // Only process emails that need verification and haven\'t been processed yet
    $emails = $conn->query("SELECT id, raw_emailid, sp_domain FROM emails WHERE domain_status=1 AND domain_processed=0 LIMIT $offset, $limit");
    
    while ($row = $emails->fetch_assoc()) {
        $email = $row[\'raw_emailid\'];
        $domain = $row[\'sp_domain\'];
        
        echo "\n Processing Email: $email (Domain: $domain)\n";
        
        $verification = verifyEmailViaSMTP($email, $domain);
        
        if ($verification[\'status\'] === \'success\') {
            $status = $verification[\'result\'];
            $message = $conn->real_escape_string($verification[\'message\']);
        } else {
            $status = 0;
            $message = "Verification failed";
        }
        
        echo "domain_status = $status, validation_response = $message\n";
        
        // Mark this domain as processed regardless of verification result
        $conn->query("UPDATE emails SET 
                     domain_status = $status,
                     validation_response = \'$message\',
                     domain_processed = 1
                     WHERE id = {$row[\'id\']}");
    }
    
    function verifyEmailViaSMTP($email, $domain) {
        if (!getmxrr($domain, $mxhosts)) {
            echo " No MX record found for domain: $domain\n";
            return ["status" => "error", "message" => "No MX record found"];
        }

        $mxIP = gethostbyname($mxhosts[0]);
        $port = 25;
        $timeout = 30;

        echo " Connecting to: $mxIP on port $port...\n";

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
            echo " Could not connect to $mxIP: $errstr ($errno)\n";
            return ["status" => "error", "message" => "Connection failed"];
        }

        stream_set_timeout($smtp, $timeout);

        $response = fgets($smtp, 4096);
        echo "SERVER: $response";

        if (substr($response, 0, 3) != \'220\') {
            fclose($smtp);
            return ["status" => "error", "message" => "SMTP server not ready"];
        }

        fputs($smtp, "EHLO server.relyon.co.in\r\n");
        echo " Sent: EHLO server.relyon.co.in\n";
        while ($line = fgets($smtp, 4096)) {
            echo " $line";
            if (substr($line, 3, 1) == \' \') break;
        }

        fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
        echo " Sent: MAIL FROM:<info@relyon.co.in>\n";
        $response = fgets($smtp, 4096);
        echo " Response: $response";

        fputs($smtp, "RCPT TO:<$email>\r\n");
        echo " Sent: RCPT TO:<$email>\n";
        $response = fgets($smtp, 4096);
        echo " Response: $response";

        $result = (substr($response, 0, 3) == \'250\') ? 1 : 0;

        fputs($smtp, "QUIT\r\n");
        fclose($smtp);

        if ($result === 1) {
            return ["status" => "success", "result" => 1, "message" => $mxIP];
        } else {
            return ["status" => "success", "result" => 0, "message" => "Invalid response"];
        }
    }
    ?>';

    file_put_contents(WORKER_SCRIPT, $workerCode);
}

// Parallel processing function with domain processing tracking
function processEmailsInParallel()
{
    global $conn;

    if (!file_exists(WORKER_SCRIPT)) {
        createWorkerScript();
    }

    // Count only unprocessed emails
    $total = $conn->query("SELECT COUNT(*) FROM emails WHERE domain_status = 1 AND domain_processed = 0")->fetch_row()[0];
    echo " Total emails to process: $total\n";

    if ($total == 0) {
        echo " All domains have already been processed.\n";
        return;
    }

    $batches = ceil($total / BATCH_SIZE);
    $workers = min(MAX_WORKERS, $batches);
    $procs = [];

    for ($i = 0; $i < $batches; $i++) {
        $offset = $i * BATCH_SIZE;
        $cmd = "php " . WORKER_SCRIPT . " $offset " . BATCH_SIZE;
        $procs[] = proc_open($cmd, [], $pipes);

        if (count($procs) >= $workers) {
            proc_close(array_shift($procs));
        }
    }

    while (count($procs) > 0) {
        proc_close(array_shift($procs));
    }
}

// Function to reset processed status if needed
function resetProcessedStatus() {
    global $conn;
    $conn->query("UPDATE emails SET domain_processed = 0");
    echo "Reset processing status for all domains.\n";
}

// Main execution
try {
    $conn->query("SET NET_WRITE_TIMEOUT = 3600");
    $conn->query("SET NET_READ_TIMEOUT = 3600");

    // Uncomment the next line if you need to reset processing status
    // resetProcessedStatus();
    
    processEmailsInParallel();

    echo "\nProcessing complete!\n";
  
} catch (Exception $e) {
    echo " Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?>