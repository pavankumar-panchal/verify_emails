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
function consoleOutput($message)
{
    if (php_sapi_name() === 'cli') {
        echo $message . PHP_EOL;
    }
}

// Enhanced domain verification with caching
function verifyDomain($domain)
{
    static $cache = [];

    if (isset($cache[$domain])) {
        return $cache[$domain];
    }

    $result = [
        'verified' => 1,  // Mark as verified (processed)
        'status' => 0,     // Default to wrong
        'response' => 'Not Found'
    ];

    // Basic domain format validation
    if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        $cache[$domain] = $result;
        return $result;
    }

    // First try DNS A records (most reliable)
    $dnsRecords = @dns_get_record($domain, DNS_A);
    $ip = null;

    if (!empty($dnsRecords)) {
        foreach ($dnsRecords as $record) {
            if ($record['type'] === 'A' && !empty($record['ip'])) {
                $ip = $record['ip'];
                break;
            }
        }
    }

    // If no A record found, try gethostbyname
    if (!$ip) {
        $ip = @gethostbyname($domain);
        if ($ip === $domain) {
            $ip = null; // gethostbyname failed
        }
    }

    // Only consider valid if we got an IP address
    if ($ip) {
        $result['status'] = 1;
        $result['response'] = $ip;
    }

    $cache[$domain] = $result;
    return $result;
}

// Main processing function
function processDomains($conn)
{
    $batchSize = 100; // Process 100 domains at a time
    $totalProcessed = 0;

    consoleOutput("Starting domain verification process...");
    consoleOutput("Processing in batches of 100 domains");

    // Get total count of unverified domains for progress tracking
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM emails WHERE domain_verified = 0");
    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    consoleOutput("Total domains to verify: $totalCount");

    do {
        // Fetch unverified domains in batches
        $stmt = $conn->prepare("SELECT id, sp_domain FROM emails WHERE domain_verified = 0 ORDER BY id ASC LIMIT ?");
        $stmt->bind_param("i", $batchSize);
        $stmt->execute();
        $result = $stmt->get_result();

        $domains = [];
        while ($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }

        if (empty($domains)) {
            break;
        }

        // Prepare update statement
        $updateStmt = $conn->prepare("UPDATE emails SET domain_verified = ?, domain_status = ?, validation_response = ? WHERE id = ?");

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

            // Show detailed progress for each domain in the batch
            consoleOutput(sprintf(
                "Processed %d/%d: %s - %s (%s)",
                $totalProcessed,
                $totalCount,
                $domain['sp_domain'],
                $verification['status'] ? 1: '0',
                $verification['response']
            ));
        }

        $conn->commit();
        consoleOutput("Batch completed: " . count($domains) . " domains processed");

        // Small delay between batches
        if (count($domains) === $batchSize) {
            sleep(1); // 1 second delay between batches
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
        "total_processed" => $totalProcessed
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