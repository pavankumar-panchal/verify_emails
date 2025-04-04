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

// Enhanced console output
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

// Check if domain is excluded
function isExcludedDomain($domain, $conn) {
    $stmt = $conn->prepare("SELECT 1 FROM exclude_domains WHERE domain = ?");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Get MX or A record IP for a domain
function getDomainIP($domain, $conn) {
    // First check if domain is excluded
    if (isExcludedDomain($domain, $conn)) {
        // Get IP from exclude_domains table if available
        $stmt = $conn->prepare("SELECT ip_address FROM exclude_domains WHERE domain = ?");
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return trim($row['ip_address']);
        }
        return false;
    }

    // Check MX records first
    if (getmxrr($domain, $mxhosts)) {
        $mxIp = @gethostbyname($mxhosts[0]);
        if ($mxIp !== $mxhosts[0]) {
            return $mxIp;
        }
    }
    
    // Fallback to A record
    $aRecord = @gethostbyname($domain);
    return ($aRecord !== $domain) ? $aRecord : false;
}

// Perform SMTP verification
function verifySmtp($domain, $ip, $email) {
    if (empty($ip)) {
        return ['valid' => false, 'response' => 'No IP address found'];
    }

    $smtp_port = 25;
    $timeout = 15;
    $responseLog = [];

    try {
        // 1. Connect to SMTP server
        $smtp = @fsockopen($ip, $smtp_port, $errno, $errstr, $timeout);
        if (!$smtp) {
            return ['valid' => false, 'response' => "Connection failed: $errstr ($errno)"];
        }
        
        stream_set_timeout($smtp, $timeout);
        
        // 2. Check welcome message (220)
        $response = fgets($smtp, 4096);
        $responseLog[] = "Server: " . trim($response);
        if (substr($response, 0, 3) != '220') {
            fclose($smtp);
            return ['valid' => false, 'response' => "Invalid welcome response: " . trim($response)];
        }
        
        // 3. Send EHLO
        $ehlo = "EHLO server.relyon.co.in";
        fputs($smtp, "$ehlo\r\n");
        $response = fgets($smtp, 4096);
        $responseLog[] = "$ehlo => " . trim($response);
        if (substr($response, 0, 3) != '250') {
            fclose($smtp);
            return ['valid' => false, 'response' => "EHLO failed: " . trim($response)];
        }
        
        // 4. MAIL FROM command
        $mailFrom = "MAIL FROM: <info@relyon.co.in>";
        fputs($smtp, "$mailFrom\r\n");
        $response = fgets($smtp, 4096);
        $responseLog[] = "$mailFrom => " . trim($response);
        if (substr($response, 0, 3) != '250') {
            fclose($smtp);
            return ['valid' => false, 'response' => "MAIL FROM failed: " . trim($response)];
        }
        
        // 5. RCPT TO command (the actual verification)
        $rcptTo = "RCPT TO: <$email>";
        fputs($smtp, "$rcptTo\r\n");
        $response = fgets($smtp, 4096);
        $responseText = trim($response);
        $responseLog[] = "$rcptTo => $responseText";
        
        // Check response codes
        $isValid = (substr($response, 0, 3) == '250');
        
        // 6. QUIT command
        $quit = "QUIT";
        fputs($smtp, "$quit\r\n");
        fclose($smtp);
        $responseLog[] = "$quit => Connection closed";
        
        return [
            'valid' => $isValid,
            'response' => implode("\n", $responseLog),
            'ip' => $ip
        ];
        
    } catch (Exception $e) {
        return ['valid' => false, 'response' => "Exception: " . $e->getMessage()];
    }
}

// Main processing function
function processDomains($conn) {
    $batchSize = 50;
    $totalProcessed = 0;
    $validDomains = 0;

    consoleOutput("Starting domain verification process");

    // Prepare statements
    $selectStmt = $conn->prepare("
        SELECT id, raw_emailid, sp_account, sp_domain 
        FROM emails 
        WHERE domain_verified = 0 
        ORDER BY id ASC 
        LIMIT ?
    ");
    
    $updateStmt = $conn->prepare("
        UPDATE emails 
        SET domain_verified = 1, 
            domain_status = ?, 
            validation_response = ? 
        WHERE id = ?
    ");

    $conn->autocommit(false);

    do {
        // Fetch batch
        $selectStmt->bind_param("i", $batchSize);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row;
        }

        if (empty($emails)) {
            consoleOutput("No more domains to process", 'INFO');
            break;
        }

        consoleOutput("Processing batch of " . count($emails) . " domains", 'INFO');
        
        foreach ($emails as $email) {
            $domain = $email['sp_domain'];
            $emailAddr = $email['raw_emailid'];
            
            consoleOutput("Processing $emailAddr (Domain: $domain)", 'INFO');
            
            // Get IP for domain (checks excluded domains first)
            $ip = getDomainIP($domain, $conn);
            
            if ($ip) {
                // Perform SMTP verification
                $result = verifySmtp($domain, $ip, $emailAddr);
                $status = $result['valid'] ? 1 : 0;
                $response = $result['valid'] ? $result['ip'] : $result['response'];
                
                if ($result['valid']) {
                    $validDomains++;
                    consoleOutput("Domain $domain is VALID (IP: $ip)", 'SUCCESS');
                } else {
                    consoleOutput("Domain $domain is INVALID: " . $result['response'], 'ERROR');
                }
            } else {
                $status = 0;
                $response = 'No MX records found';
                consoleOutput("No DNS records found for $domain", 'ERROR');
            }
            
            $updateStmt->bind_param("isi", $status, $response, $email['id']);
            $updateStmt->execute();
            $totalProcessed++;
        }

        $conn->commit();
        consoleOutput("Batch completed. Total: $totalProcessed | Valid: $validDomains", 'INFO');
    } while (true);

    $conn->autocommit(true);
    return ['total' => $totalProcessed, 'valid' => $validDomains];
}

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $start = microtime(true);
    $result = processDomains($conn);
    $time = microtime(true) - $start;

    echo json_encode([
        "status" => "success",
        "total_processed" => $result['total'],
        "valid_domains" => $result['valid'],
        "invalid_domains" => $result['total'] - $result['valid'],
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