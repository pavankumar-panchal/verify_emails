<?php
require __DIR__ . '/../db.php';

$offset = $argv[1] ?? 0;
$limit = $argv[2] ?? 100;

// Only process domains that need verification
$domains = $conn->query("SELECT id, sp_domain FROM emails WHERE domain_verified = 0 LIMIT $offset, $limit");

while ($row = $domains->fetch_assoc()) {
    $domain = $row['sp_domain'];
    $ip = false;

    // Basic hostname validation
    if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        $ip = getDomainIP($domain);
    }

    $status = $ip ? 1 : 0;
    $response = $ip ?: "Invalid";

    $conn->query("UPDATE emails SET 
                     domain_verified = 1,
                     domain_status = $status,
                     validation_response = '" . $conn->real_escape_string($response) . "'
                     WHERE id = {$row['id']}");
}

function getDomainIP($domain)
{
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

?>