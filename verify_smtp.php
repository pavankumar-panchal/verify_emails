<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require 'db.php';

// Optimization settings
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '512M');

// Enhanced console output with colors
function consoleOutput($message, $type = 'INFO') {
    $colors = [
        'INFO' => "\033[36m",  // Cyan
        'SMTP' => "\033[33m",  // Yellow
        'ERROR' => "\033[31m", // Red
        'SUCCESS' => "\033[32m", // Green
        'RESET' => "\033[0m"   // Reset
    ];
    
    $timestamp = date('Y-m-d H:i:s');
    $color = $colors[$type] ?? $colors['INFO'];
    $formatted = "{$colors['RESET']}[$timestamp]{$color} [$type] $message{$colors['RESET']}";
    
    if (php_sapi_name() === 'cli') {
        echo $formatted . PHP_EOL;
    }
}

// Perform complete SMTP verification exactly like telnet
function verifySmtp($domain, $ip, $testEmail) {
    if (empty($ip) || empty($testEmail)) {
        consoleOutput("Invalid parameters for SMTP check", 'ERROR');
        return ['valid' => false, 'response' => 'Invalid parameters'];
    }

    $smtp_port = 25;
    $timeout = 15;
    $responseLog = [];

    try {
        // 1. Connect to SMTP server
        consoleOutput("Connecting to $ip:$smtp_port...", 'SMTP');
        $smtp = @fsockopen($ip, $smtp_port, $errno, $errstr, $timeout);
        if (!$smtp) {
            consoleOutput("Connection failed: $errstr ($errno)", 'ERROR');
            return ['valid' => false, 'response' => "Connection failed: $errstr"];
        }
        
        stream_set_timeout($smtp, $timeout);
        $responseLog[] = "Connected to $ip:$smtp_port";
        
        // 2. Check welcome message (220)
        $response = fgets($smtp, 4096);
        consoleOutput("Server: " . trim($response), 'SMTP');
        $responseLog[] = "Server: " . trim($response);
        if (substr($response, 0, 3) != '220') {
            fclose($smtp);
            consoleOutput("Invalid welcome response", 'ERROR');
            return ['valid' => false, 'response' => "Invalid welcome response"];
        }
        
        // 3. Send EHLO
        $ehlo = "EHLO server.relyon.co.in";
        fputs($smtp, "$ehlo\r\n");
        $response = fgets($smtp, 4096);
        consoleOutput("$ehlo => " . trim($response), 'SMTP');
        $responseLog[] = "$ehlo => " . trim($response);
        if (substr($response, 0, 3) != '250') {
            fclose($smtp);
            consoleOutput("EHLO command failed", 'ERROR');
            return ['valid' => false, 'response' => "EHLO failed"];
        }
        
        // 4. MAIL FROM command
        $mailFrom = "MAIL FROM: <info@relyon.co.in>";
        fputs($smtp, "$mailFrom\r\n");
        $response = fgets($smtp, 4096);
        consoleOutput("$mailFrom => " . trim($response), 'SMTP');
        $responseLog[] = "$mailFrom => " . trim($response);
        if (substr($response, 0, 3) != '250') {
            fclose($smtp);
            consoleOutput("MAIL FROM command failed", 'ERROR');
            return ['valid' => false, 'response' => "MAIL FROM failed"];
        }
        
        // 5. RCPT TO command (the actual verification)
        $rcptTo = "RCPT TO: <$testEmail>";
        fputs($smtp, "$rcptTo\r\n");
        $response = fgets($smtp, 4096);
        $responseText = trim($response);
        consoleOutput("$rcptTo => $responseText", 'SMTP');
        $responseLog[] = "$rcptTo => $responseText";
        
        // Check response codes
        $isValid = (substr($response, 0, 3) == '250'); // 550 would be invalid
        
        // 6. QUIT command
        $quit = "QUIT";
        fputs($smtp, "$quit\r\n");
        fclose($smtp);
        consoleOutput("$quit => Connection closed", 'SMTP');
        $responseLog[] = "$quit => Connection closed";
        
        if ($isValid) {
            consoleOutput("Email $testEmail is VALID", 'SUCCESS');
            return ['valid' => true, 'response' => implode("\n", $responseLog)];
        } else {
            consoleOutput("Email $testEmail is INVALID", 'ERROR');
            return ['valid' => false, 'response' => implode("\n", $responseLog)];
        }
        
    } catch (Exception $e) {
        consoleOutput("Exception: " . $e->getMessage(), 'ERROR');
        return ['valid' => false, 'response' => "Exception: " . $e->getMessage()];
    }
}

// Main processing function
function processSmtpVerification($conn) {
    $batchSize = 50; // Smaller batch for better logging
    $processed = 0;
    $validEmails = 0;

    consoleOutput("Starting SMTP email verification process", 'INFO');

    $selectStmt = $conn->prepare("
        SELECT id, sp_domain, sp_email, validation_response 
        FROM emails 
        WHERE domain_status = 1 
          AND domain_verified = 1
          AND email_verified = 0
          AND validation_response NOT LIKE 'No MX records found'
        ORDER BY id ASC 
        LIMIT ?
    ");
    
    $updateStmt = $conn->prepare("
        UPDATE emails 
        SET email_verified = 1, 
            email_status = ?,
            email_response = ?,
            verification_time = NOW()
        WHERE id = ?
    ");

    $conn->autocommit(false);

    do {
        $selectStmt->bind_param("i", $batchSize);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row;
        }

        if (empty($emails)) {
            consoleOutput("No more emails to verify", 'INFO');
            break;
        }

        consoleOutput("Processing batch of " . count($emails) . " emails", 'INFO');
        
        foreach ($emails as $email) {
            $domain = $email['sp_domain'];
            $emailAddr = $email['sp_email'];
            $ip = $email['validation_response']; // IP from DNS verification
            
            consoleOutput("Verifying $emailAddr (Domain: $domain, IP: $ip)", 'INFO');
            
            $result = verifySmtp($domain, $ip, $emailAddr);
            
            $status = $result['valid'] ? 1 : 0;
            if ($result['valid']) $validEmails++;
            
            $updateStmt->bind_param("isi", $status, $result['response'], $email['id']);
            $updateStmt->execute();
            $processed++;
        }

        $conn->commit();
        consoleOutput("Batch completed. Total: $processed | Valid: $validEmails", 'INFO');
    } while (true);

    $conn->autocommit(true);
    return ['total' => $processed, 'valid' => $validEmails];
}

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $start = microtime(true);
    $result = processSmtpVerification($conn);
    $time = microtime(true) - $start;

    echo json_encode([
        "status" => "success",
        "total_processed" => $result['total'],
        "valid_emails" => $result['valid'],
        "invalid_emails" => $result['total'] - $result['valid'],
        "time_seconds" => round($time, 2),
        "rate_per_second" => round($result['total']/$time, 2)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    consoleOutput("Error: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>