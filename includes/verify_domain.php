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

// Command line output
function consoleOutput($message) {
    if (php_sapi_name() === 'cli') {
        echo $message . PHP_EOL;
    }
}

// Get first IP for a domain (MX or A record)
function getDomainIP($domain) {
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

// Main processing
function processDomains($conn) {
    $batchSize = 100; // Larger batch size for efficiency
    $totalProcessed = 0;

    consoleOutput("Starting optimized domain verification...");

    // Prepare statements
    $selectStmt = $conn->prepare("
        SELECT id, sp_domain 
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

    $conn->autocommit(false); // Faster transactions

    do {
        // Fetch batch
        $selectStmt->bind_param("i", $batchSize);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }

        if (empty($domains)) break;

        // Process batch
        foreach ($domains as $domain) {
            $ip = false;
            
            // Basic hostname validation
            if (filter_var($domain['sp_domain'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                $ip = getDomainIP($domain['sp_domain']);
            }
            
            $status = $ip ? 1 : 0;
            $response = $ip ?: 'Invalid response';
            
            $updateStmt->bind_param("isi", $status, $response, $domain['id']);
            $updateStmt->execute();
            
            $totalProcessed++;
        }

        $conn->commit();
        
        consoleOutput(sprintf(
            "Processed: %d | Total: %d",
            count($domains),
            $totalProcessed
        ));

    } while (true);

    $conn->autocommit(true);
    return $totalProcessed;
}

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $start = microtime(true);
    $processed = processDomains($conn);
    $time = microtime(true) - $start;

    echo json_encode([
        "status" => "success",
        "processed" => $processed,
        "time_seconds" => round($time, 2),
        "rate_per_second" => round($processed/$time, 2)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();

require_once 'verify_smtp.php';

?>