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

function consoleOutput($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp][$type] $message";
    if (php_sapi_name() === 'cli') {
        echo $formatted . PHP_EOL;
    }
}

function getDomainIP($domain) {
    if (getmxrr($domain, $mxhosts)) {
        $mxIp = @gethostbyname($mxhosts[0]);
        if ($mxIp !== $mxhosts[0]) return $mxIp;
    }
    return false;
}

function processDomains($conn) {
    $batchSize = 500;
    $totalProcessed = 0;

    $selectStmt = $conn->prepare("
        SELECT id, sp_domain 
        FROM emails 
        WHERE domain_verified = 0 
        AND domain_status = 1
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
        $selectStmt->bind_param("i", $batchSize);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }

        if (empty($domains)) break;

        foreach ($domains as $domain) {
            $ip = getDomainIP($domain['sp_domain']);
            $status = $ip ? 1 : 0;
            $response = $ip ?: 'No MX records found';
            
            $updateStmt->bind_param("isi", $status, $response, $domain['id']);
            $updateStmt->execute();
            $totalProcessed++;
        }

        $conn->commit();
        consoleOutput("Processed: $totalProcessed domains");
    } while (true);

    $conn->autocommit(true);
    return $totalProcessed;
}

function startBackgroundSmtpVerification() {
    $phpPath = 'C:\xampp\php\php.exe';
    $scriptPath = 'C:\xampp\htdocs\email\verify_smtp.php';
    $cmd = "cmd /c start \"\" \"$phpPath\" \"$scriptPath\"";
    pclose(popen($cmd, 'r'));
}

try {
    if ($conn->connect_error) throw new Exception("Database connection failed");

    $start = microtime(true);
    $processed = processDomains($conn);
    
    // Start SMTP verification in background
    startBackgroundSmtpVerification();

    echo json_encode([
        "status" => "success",
        "dns_processed" => $processed,
        "message" => "DNS verification completed. SMTP verification started in background."
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>87654