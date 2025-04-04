<?php
require 'db.php';

// Set maximum execution time
set_time_limit(0);
ini_set('memory_limit', '4096M');

function processDomains() {
    global $conn;
    $batchSize = 500;
    $totalProcessed = 0;

    $excludedDomains = getExcludedDomainsWithIPs();

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
            validation_response = ?,
            last_verified = NOW()
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
            $domainName = strtolower(trim($domain['sp_domain']));
            
            if (array_key_exists($domainName, $excludedDomains)) {
                $status = 1;
                $response = "Excluded: " . $excludedDomains[$domainName];
            } else {
                $ip = getDomainIP($domainName);
                $status = $ip ? 1 : 0;
                $response = $ip ?: 'No records found';
            }
            
            $updateStmt->bind_param("isi", $status, $response, $domain['id']);
            $updateStmt->execute();
            $totalProcessed++;
        }

        $conn->commit();
    } while (true);

    $conn->autocommit(true);
    return $totalProcessed;
}

// Run the verification
processDomains();