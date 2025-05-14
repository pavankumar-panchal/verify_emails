<?php
// Database connection
require './db.php';
global $conn;

// Configuration
define('MAX_WORKERS', 5); // Number of parallel processes
define('BATCH_SIZE', 100); // Emails per worker
define('WORKER_SCRIPT', __DIR__ . '/smtp_worker.php');
define('LOG_DIR', __DIR__ . '/logs');
define('LOG_FILE', LOG_DIR . '/smtp_verification_' . date('Y-m-d') . '.log');

// Ensure log directory exists
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Log function
function logVerification($message, $email = '', $domain = '')
{
    $timestamp = date('[Y-m-d H:i:s]');
    $logMessage = "$timestamp";
    if ($email)
        $logMessage .= " [$email]";
    if ($domain)
        $logMessage .= " [$domain]";
    $logMessage .= " $message\n";

    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
    echo $logMessage;
}

// SMTP verification function
function verifyEmailViaSMTP($email, $domain)
{
    logVerification("Starting verification", $email, $domain);

    if (!getmxrr($domain, $mxhosts)) {
        $message = "No MX record found for domain: $domain";
        logVerification($message, $email, $domain);
        return ["status" => "error", "message" => $message];
    }

    $mxIP = gethostbyname($mxhosts[0]);
    $port = 25;
    $timeout = 30;

    logVerification("Connecting to MX: $mxhosts[0] ($mxIP) on port $port", $email, $domain);

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
        logVerification($message, $email, $domain);
        return ["status" => "error", "message" => $message];
    }

    stream_set_timeout($smtp, $timeout);

    $response = fgets($smtp, 4096);
    logVerification("SERVER: " . trim($response), $email, $domain);

    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        $message = "SMTP server not ready: " . trim($response);
        logVerification($message, $email, $domain);
        return ["status" => "error", "message" => $message];
    }

    fputs($smtp, "EHLO server.relyon.co.in\r\n");
    logVerification("Sent: EHLO server.relyon.co.in", $email, $domain);

    $ehloResponse = '';
    while ($line = fgets($smtp, 4096)) {
        $ehloResponse .= $line;
        if (substr($line, 3, 1) == ' ')
            break;
    }
    logVerification("EHLO Response: " . trim($ehloResponse), $email, $domain);

    fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
    logVerification("Sent: MAIL FROM:<info@relyon.co.in>", $email, $domain);

    $response = fgets($smtp, 4096);
    logVerification("MAIL FROM Response: " . trim($response), $email, $domain);

    fputs($smtp, "RCPT TO:<$email>\r\n");
    logVerification("Sent: RCPT TO:<$email>", $email, $domain);

    $response = fgets($smtp, 4096);
    logVerification("RCPT TO Response: " . trim($response), $email, $domain);

    $result = (substr($response, 0, 3) == '250') ? 1 : 0;

    fputs($smtp, "QUIT\r\n");
    fclose($smtp);

    if ($result === 1) {
        $message = "Verification successful. MX: $mxIP";
        logVerification($message, $email, $domain);
        return ["status" => "success", "result" => 1, "message" => $mxIP];
    } else {
        $message = "Invalid response";
        logVerification($message, $email, $domain);
        return ["status" => "success", "result" => 0, "message" => $message];
    }
}

// Create worker script with enhanced logging
function createWorkerScript()
{
    $workerCode = '<?php
    require __DIR__ . \'/../db.php\';
    
    // Configure logging for worker
    define(\'LOG_DIR\', __DIR__ . \'/../logs\');
    define(\'LOG_FILE\', LOG_DIR . \'/smtp_worker_\' . date(\'Y-m-d\') . \'.log\');
    
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    function workerLog($message, $email = \'\', $domain = \'\') {
        $timestamp = date(\'[Y-m-d H:i:s]\');
        $logMessage = "$timestamp [Worker " . getmypid() . "]";
        if ($email) $logMessage .= " [$email]";
        if ($domain) $logMessage .= " [$domain]";
        $logMessage .= " $message\n";
        
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
    
    $offset = $argv[1] ?? 0;
    $limit = $argv[2] ?? ' . BATCH_SIZE . ';
    
    workerLog("Starting worker process with offset: $offset, limit: $limit");
    
    $emails = $conn->query("SELECT id, raw_emailid, sp_domain FROM emails WHERE domain_processed=0 LIMIT $offset, $limit");
    
    while ($row = $emails->fetch_assoc()) {
        $email = $row[\'raw_emailid\'];
        $domain = $row[\'sp_domain\'];
        
        workerLog("Processing email", $email, $domain);
        
        $verification = verifyEmailViaSMTP($email, $domain);
        
        if ($verification[\'status\'] === \'success\') {
            $status = $verification[\'result\'];
            $message = $conn->real_escape_string($verification[\'message\']);
        } else {
            $status = 0;
            $message = "Verification failed: " . $conn->real_escape_string($verification[\'message\']);
        }
        
        workerLog("Result: " . ($status ? "Valid" : "Invalid") . " - $message", $email, $domain);
        
        $conn->query("UPDATE emails SET 
                     validation_response = \'$message\',
                     domain_processed = 1
                     WHERE id = {$row[\'id\']}");
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

        if (substr($response, 0, 3) != \'220\') {
            fclose($smtp);
            $message = "SMTP server not ready: " . trim($response);
            workerLog($message, $email, $domain);
            return ["status" => "error", "message" => $message];
        }

        fputs($smtp, "EHLO server.relyon.co.in\r\n");
        workerLog("Sent: EHLO server.relyon.co.in", $email, $domain);
        
        $ehloResponse = \'\';
        while ($line = fgets($smtp, 4096)) {
            $ehloResponse .= $line;
            if (substr($line, 3, 1) == \' \') break;
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

        $result = (substr($response, 0, 3) == \'250\') ? 1 : 0;

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
    ?>';

    file_put_contents(WORKER_SCRIPT, $workerCode);
}

// Parallel processing function
function processEmailsInParallel()
{
    global $conn;

    if (!file_exists(WORKER_SCRIPT)) {
        createWorkerScript();
    }

    // Count only unprocessed emails
    $total = $conn->query("SELECT COUNT(*) FROM emails WHERE domain_processed = 0")->fetch_row()[0];
    echo " Total emails to process: $total\n";

    if ($total == 0) {
        echo " All emails have already been processed.\n";
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
function resetProcessedStatus()
{
    global $conn;
    $conn->query("UPDATE emails SET domain_processed = 0");
    echo "Reset processing status for all emails.\n";
}

// Main execution
try {
    logVerification("Starting email verification process");
    $conn->query("SET NET_WRITE_TIMEOUT = 3600");
    $conn->query("SET NET_READ_TIMEOUT = 3600");

    // Uncomment the next line if you need to reset processing status
    // resetProcessedStatus();

    processEmailsInParallel();

    logVerification("Processing complete");

    echo "\nProcessing complete!\n";

} catch (Exception $e) {
    $errorMsg = "Error: " . $e->getMessage();
    logVerification($errorMsg);
    echo $errorMsg . "\n";
} finally {
    $conn->close();
}

require "./db.php";

// Fetch all campaigns where id matches csv_list_id in emails
$result = $conn->query("
    SELECT DISTINCT c.id
    FROM csv_list c
    JOIN emails e ON c.id = e.csv_list_id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $campaignId = $row['id'];

        // Count the valid and invalid emails for this campaign
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) AS total_emails,
                SUM(domain_status = 1) AS valid_count,
                SUM(domain_status = 0) AS invalid_count
            FROM emails
            WHERE csv_list_id = ?
        ");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $stmt->bind_result($total, $valid, $invalid);
        $stmt->fetch();
        $stmt->close();

        // Default to zero if NULL (in case no valid/invalid emails)
        $valid = $valid ?? 0;
        $invalid = $invalid ?? 0;
        $total = $total ?? 0;

        // Update the csv_list table with the new counts
        $updateStmt = $conn->prepare("
            UPDATE csv_list 
            SET total_emails = ?, valid_count = ?, invalid_count = ?
            WHERE id = ?
        ");
        $updateStmt->bind_param("iiii", $total, $valid, $invalid, $campaignId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    echo "✅ All matching csv_list records updated based on email data.";
} else {
    echo "⚠️ No matching campaigns found to update.";
}


?>