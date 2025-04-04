<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require 'db.php';

// Error handling setup
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_time_limit(0);
ini_set('memory_limit', '1024M');

// Command line output function
function consoleOutput($message) {
    if (php_sapi_name() === 'cli') {
        echo $message . PHP_EOL;
    }
}

// Get all IPs for a host (with timeout)
function getHostIPs($hostname) {
    $ips = [];
    $records = @dns_get_record($hostname, DNS_A + DNS_AAAA);
    if ($records) {
        foreach ($records as $record) {
            if ($record['type'] === 'A') {
                $ips[] = $record['ip'];
            } elseif ($record['type'] === 'AAAA') {
                $ips[] = $record['ipv6'];
            }
        }
    }
    return $ips;
}

// Enhanced domain verification
function verifyDomain($domain) {
    static $cache = [];
    
    if (isset($cache[$domain])) {
        return $cache[$domain];
    }

    $result = [
        'verified' => 1,
        'status' => 0,
        'response' => 'No records Found'
    ];

    // Basic domain format validation
    if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        $result['response'] = 'Invalid domain format';
        $cache[$domain] = $result;
        return $result;
    }

    // Check MX records first
    if (getmxrr($domain, $mxhosts, $mxweights)) {
        foreach ($mxhosts as $mxhost) {
            $ips = getHostIPs($mxhost);
            if (!empty($ips)) {
                $result['status'] = 1;
                $result['response'] = $ips[0];
                $cache[$domain] = $result;
                return $result;
            }
        }
    }

    // Fallback to A records
    $aRecords = @dns_get_record($domain, DNS_A);
    if (!empty($aRecords) && !empty($aRecords[0]['ip'])) {
        $result['status'] = 1;
        $result['response'] = 'A record IP: ' . $aRecords[0]['ip'];
    }

    $cache[$domain] = $result;
    return $result;
}

// Main processing function
function processDomains($conn) {
    $batchSize = 100;
    $totalProcessed = 0;

    consoleOutput("Starting enhanced domain verification...");
    consoleOutput("Processing in batches of $batchSize domains");

    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM emails WHERE domain_verified = 0");
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalCount = $result->fetch_assoc()['total'];
    $countStmt->close();

    consoleOutput("Total domains to verify: $totalCount");

    // Prepare statements
    $selectStmt = $conn->prepare("SELECT id, sp_domain FROM emails WHERE domain_verified = 0 ORDER BY id ASC LIMIT ?");
    $updateStmt = $conn->prepare("UPDATE emails SET domain_verified = ?, domain_status = ?, validation_response = ? WHERE id = ?");

    do {
        // Fetch batch
        $selectStmt->bind_param("i", $batchSize);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }

        if (empty($domains)) {
            break;
        }

        $conn->begin_transaction();

        foreach ($domains as $domain) {
            $verification = verifyDomain($domain['sp_domain']);

            $updateStmt->bind_param(
                "issi",
                $verification['verified'],
                $verification['status'],
                $verification['response'],
                $domain['id']
            );
            $updateStmt->execute();
            $totalProcessed++;

            consoleOutput(sprintf(
                "Processed %d/%d: %s - %s (%s)",
                $totalProcessed,
                $totalCount,
                $domain['sp_domain'],
                $verification['status'] ? 'Valid' : 'Invalid',
                $verification['response']
            ));
        }

        $conn->commit();
        consoleOutput("Batch completed: " . count($domains) . " domains");

        // Rate limiting
        if (count($domains) === $batchSize) {
            usleep(500000); // 0.5 second delay
        }
    } while (true);

    return $totalProcessed;
}

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $totalProcessed = processDomains($conn);

    echo json_encode([
        "status" => "success",
        "message" => "Domain verification completed",
        "total_processed" => $totalProcessed,
        "verification_method" => "Enhanced MX and A record verification"
    ]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>