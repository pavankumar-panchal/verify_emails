<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require 'db.php';

// Error reporting configuration
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '512M');

// Enhanced console output with colors
function consoleOutput($message, $type = 'INFO')
{
    $colors = [
        'INFO' => "\033[36m",  // Cyan
        'SMTP' => "\033[33m",  // Yellow
        'ERROR' => "\033[31m", // Red
        'SUCCESS' => "\033[32m", // Green
        'WARNING' => "\033[35m", // Purple
        'RESET' => "\033[0m"   // Reset
    ];

    $timestamp = date('Y-m-d H:i:s');
    $color = $colors[$type] ?? $colors['INFO'];
    $formatted = "{$colors['RESET']}[$timestamp]{$color} [$type] $message{$colors['RESET']}";

    if (php_sapi_name() === 'cli') {
        echo $formatted . PHP_EOL;
    } else {
        error_log(strip_tags($message));
    }
}

// Check if domain is excluded
function isExcludedDomain($domain, $conn)
{
    $stmt = $conn->prepare("SELECT 1 FROM exclude_domains WHERE domain = ?");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Get MX or A record IP for a domain
function getDomainIP($domain, $conn)
{
    // First check if domain is excluded
    if (isExcludedDomain($domain, $conn)) {
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
    $mxhosts = [];
    if (getmxrr($domain, $mxhosts)) {
        foreach ($mxhosts as $mxhost) {
            $mxIp = gethostbyname($mxhost);
            if ($mxIp !== $mxhost && filter_var($mxIp, FILTER_VALIDATE_IP)) {
                return $mxIp;
            }
        }
    }

    // Fallback to A record
    $aRecord = gethostbyname($domain);
    return ($aRecord !== $domain && filter_var($aRecord, FILTER_VALIDATE_IP)) ? $aRecord : false;
}

// Helper function to read full SMTP response
function getSmtpResponse($socket)
{
    $response = '';
    while ($str = fgets($socket, 4096)) {
        $response .= $str;
        if (substr($str, 3, 1) == ' ')
            break;
    }
    return $response;
}

// Perform SMTP verification with enhanced checks
function verifySmtp($domain, $ip, $email)
{
    if (empty($ip)) {
        return ['valid' => false, 'response' => 'No valid IP address found for domain'];
    }

    $portsToTry = [25, 587, 465]; // Try common SMTP ports
    $timeout = 20; // Increased timeout
    $responseLog = [];
    $valid = false;

    foreach ($portsToTry as $smtp_port) {
        try {
            consoleOutput("Trying $domain ($email) on $ip:$smtp_port", 'SMTP');

            // 1. Connect to SMTP server
            $smtp = fsockopen($ip, $smtp_port, $errno, $errstr, $timeout);
            if (!$smtp) {
                $responseLog[] = "Connection to $ip:$smtp_port failed: $errstr ($errno)";
                continue; // Try next port
            }

            stream_set_timeout($smtp, $timeout);
            $response = getSmtpResponse($smtp);
            $responseLog[] = "CONNECT: " . trim($response);

            // Check welcome message (220 or 221)
            if (!preg_match('/^2[20] /', $response)) {
                fclose($smtp);
                $responseLog[] = "Invalid welcome response on port $smtp_port";
                continue;
            }

            // 2. Send EHLO/HELO
            $ehlo = "EHLO verify.email";
            fputs($smtp, "$ehlo\r\n");
            $response = getSmtpResponse($smtp);
            $responseLog[] = "$ehlo => " . trim($response);

            if (!preg_match('/^2[50] /', $response)) {
                // Try HELO if EHLO fails
                $helo = "HELO verify.email";
                fputs($smtp, "$helo\r\n");
                $response = getSmtpResponse($smtp);
                $responseLog[] = "$helo => " . trim($response);
                if (!preg_match('/^2[50] /', $response)) {
                    fclose($smtp);
                    $responseLog[] = "HELO/EHLO failed on port $smtp_port";
                    continue;
                }
            }

            // 3. MAIL FROM command
            $mailFrom = "MAIL FROM: <verify@$domain>";
            fputs($smtp, "$mailFrom\r\n");
            $response = getSmtpResponse($smtp);
            $responseLog[] = "$mailFrom => " . trim($response);
            if (!preg_match('/^2[50] /', $response)) {
                fclose($smtp);
                $responseLog[] = "MAIL FROM failed on port $smtp_port";
                continue;
            }

            // 4. RCPT TO command (actual verification)
            $rcptTo = "RCPT TO: <$email>";
            fputs($smtp, "$rcptTo\r\n");
            $response = getSmtpResponse($smtp);
            $responseText = trim($response);
            $responseLog[] = "$rcptTo => $responseText";

            // Check response codes (250 or 251 are valid)
            $valid = preg_match('/^2(50|51) /', $response);

            // 5. QUIT command
            $quit = "QUIT";
            fputs($smtp, "$quit\r\n");
            fclose($smtp);
            $responseLog[] = "$quit => Connection closed";

            if ($valid) {
                break; // Stop trying other ports if successful
            }

        } catch (Exception $e) {
            $responseLog[] = "Exception on port $smtp_port: " . $e->getMessage();
        }
    }

    return [
        'valid' => $valid,
        'response' => implode("\n", $responseLog),
        'ip' => $ip,
        'port' => $smtp_port ?? null
    ];
}

// Main processing function with batch processing
function processDomains($conn, $batchSize = 50, $reverify = false)
{
    $totalProcessed = 0;
    $validDomains = 0;

    consoleOutput("Starting domain verification process (" . ($reverify ? "re-verification" : "initial verification") . ")");

    // Prepare statements
    $selectStmt = $conn->prepare("
        SELECT id, raw_emailid, sp_account, sp_domain 
        FROM emails 
        WHERE " . ($reverify ? "domain_status = 1" : "domain_verified = 0") . " 
        ORDER BY id ASC 
        LIMIT ?
    ");

    $updateStmt = $conn->prepare("
        UPDATE emails 
        SET domain_verified = 1, 
            domain_status = ?, 
            validation_response = ?,
            last_verified = NOW(),
            verification_count = verification_count + 1
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
                $response = $result['valid'] ? "Valid (IP: {$result['ip']}, Port: {$result['port']})" : $result['response'];

                if ($result['valid']) {
                    $validDomains++;
                    consoleOutput("Domain $domain is VALID (IP: {$result['ip']}, Port: {$result['port']})", 'SUCCESS');
                } else {
                    consoleOutput("Domain $domain is INVALID: " . $result['response'], 'ERROR');
                }
            } else {
                $status = 0;
                $response = 'No valid DNS records found';
                consoleOutput("No valid DNS records found for $domain", 'ERROR');
            }

            $updateStmt->bind_param("isi", $status, $response, $email['id']);
            $updateStmt->execute();
            $totalProcessed++;
        }

        $conn->commit();
        consoleOutput("Batch completed. Total: $totalProcessed | Valid: $validDomains", 'INFO');

        // Small delay between batches to avoid overwhelming servers
        sleep(1);
    } while (true);

    $conn->autocommit(true);
    return ['total' => $totalProcessed, 'valid' => $validDomains];
}

// Function to get all verified domains for re-checking
function getVerifiedDomains($conn)
{
    $query = "SELECT id, raw_emailid, sp_domain, validation_response 
              FROM emails 
              WHERE domain_status = 1 
              ORDER BY id ASC";

    $result = $conn->query($query);
    $domains = [];

    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }

    return $domains;
}

// Main execution
try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Determine if we're doing initial verification or re-verification
    $action = $_GET['action'] ?? 'verify';
    $reverify = ($action === 'reverify');

    $start = microtime(true);
    $result = processDomains($conn, 50, $reverify);
    $time = microtime(true) - $start;

    echo json_encode([
        "status" => "success",
        "action" => $reverify ? "reverification" : "verification",
        "total_processed" => $result['total'],
        "valid_domains" => $result['valid'],
        "invalid_domains" => $result['total'] - $result['valid'],
        "time_seconds" => round($time, 2),
        "rate_per_second" => $result['total'] > 0 ? round($result['total'] / $time, 2) : 0,
        "message" => "Operation completed successfully"
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->autocommit === false) {
        $conn->rollback();
    }
    consoleOutput("Error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>