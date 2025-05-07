<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php';

// Clear any previous output
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

// Set error reporting to avoid warnings in output
error_reporting(0);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $response = handlePostRequest();
            break;
        case 'GET':
            $response = handleGetRequest();
            break;
        case 'DELETE':
            $response = handleDeleteRequest();
            break;
        default:
            $response = ["status" => "error", "message" => "Method not allowed"];
    }

    // Ensure no output has been sent before this
    if (ob_get_length() > 0) {
        ob_clean();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Clean any output buffer
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

// Close connection and flush buffer
$cmd = "nohup php -q includes/verify_domain.php > /dev/null 2>&1 & echo $!";
$pid = shell_exec($cmd);

$conn->close();
ob_end_flush();
exit;


function getExcludedAccounts()
{
    global $conn;
    $result = $conn->query("SELECT account FROM exclude_accounts");
    $excludedAccounts = [];
    while ($row = $result->fetch_assoc()) {
        $excludedAccounts[] = strtolower(trim($row['account']));
    }
    return $excludedAccounts;
}

function getExcludedDomainsWithIPs()
{
    global $conn;
    $result = $conn->query("SELECT domain, ip_address FROM exclude_domains");
    $excludedDomains = [];
    while ($row = $result->fetch_assoc()) {
        $domain = strtolower(trim($row['domain']));
        $ip = trim($row['ip_address']);
        if (!empty($domain)) {
            $excludedDomains[$domain] = $ip;
        }
    }
    return $excludedDomains;
}



function isValidAccountName($account)
{
    // 1. Basic pattern match
    if (!preg_match('/^[a-z0-9](?!.*[._-]{2})[a-z0-9._-]*[a-z0-9]$/i', $account)) {
        return false;
    }

    // 2. Length check
    if (strlen($account) < 1 || strlen($account) > 64) {
        return false;
    }

    // 3. Not all digits
    if (preg_match('/^[0-9]+$/', $account)) {
        return false;
    }

    return true;
}



function normalizeGmail($email)
{
    $parts = explode('@', strtolower(trim($email)));
    if (count($parts) !== 2 || $parts[1] !== 'gmail.com') {
        return $email;
    }

    $account = $parts[0];
    // Remove dots and anything after +
    $account = str_replace('.', '', $account);
    $account = explode('+', $account)[0];

    return $account . '@gmail.com';
}
function handlePostRequest()
{
    global $conn;

    if (!isset($_FILES['csv_file'])) {
        return ["status" => "error", "message" => "No file uploaded"];
    }

    $file = $_FILES['csv_file']['tmp_name'];
    if (!file_exists($file)) {
        return ["status" => "error", "message" => "File upload failed"];
    }

    $excludedAccounts = getExcludedAccounts();
    $excludedDomains = getExcludedDomainsWithIPs();

    $batchSize = 100;
    $skipped_count = 0;
    $inserted_count = 0;
    $excluded_count = 0;
    $invalid_account_count = 0;
    $uniqueEmails = [];

    $checkStmt = $conn->prepare("SELECT id FROM emails WHERE raw_emailid = ? LIMIT 1");
    $insertStmt = $conn->prepare("INSERT INTO emails (raw_emailid, sp_account, sp_domain, domain_verified, domain_status, validation_response) VALUES (?, ?, ?, ?, ?, ?)");

    if (($handle = fopen($file, "r")) === false) {
        return ["status" => "error", "message" => "Failed to read CSV file"];
    }

    $conn->begin_transaction();

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if (empty($data[0]))
            continue;

        // Sanitize and clean email
        $email = strtolower(trim($data[0]));
        $email = preg_replace('/[^\x20-\x7E]/', '', $email); // Remove non-printable characters

        if (isset($uniqueEmails[$email])) {
            $skipped_count++;
            continue;
        }
        $uniqueEmails[$email] = true;

        $emailParts = explode("@", $email);
        if (count($emailParts) != 2) {
            $skipped_count++;
            continue;
        }

        $sp_account = $emailParts[0];
        $sp_domain = $emailParts[1];

        $domain_verified = 0;
        $domain_status = 0;
        $validation_response = "Not Verified Yet";

        // Check if already in database
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $skipped_count++;
            continue;
        }

        // Validate account name
        if (!isValidAccountName($sp_account, $sp_domain)) {
            $domain_verified = 1;
            $domain_status = 0;
            $validation_response = "Invalid";
            $invalid_account_count++;

            $insertStmt->bind_param("ssssss", $email, $sp_account, $sp_domain, $domain_verified, $domain_status, $validation_response);
            $insertStmt->execute();
            continue;
        }

        // Check for excluded
        if (in_array(strtolower($sp_account), $excludedAccounts)) {
            $domain_verified = 1;
            $domain_status = 1;
            $validation_response = "Excluded: Account";
            $excluded_count++;
        } elseif (array_key_exists(strtolower($sp_domain), $excludedDomains)) {
            $domain_verified = 1;
            $domain_status = 1;
            $validation_response = $excludedDomains[strtolower($sp_domain)];
            $excluded_count++;
        }

        // Insert valid or excluded email
        $insertStmt->bind_param("ssssss", $email, $sp_account, $sp_domain, $domain_verified, $domain_status, $validation_response);
        $insertStmt->execute();
        $inserted_count++;

        if ($inserted_count % $batchSize === 0 || $excluded_count % $batchSize === 0 || $invalid_account_count % $batchSize === 0) {
            $conn->commit();
            $conn->begin_transaction();
        }
    }

    $conn->commit();
    fclose($handle);



    return [
        "status" => "success",
        "message" => "CSV processed successfully",
        "inserted" => $inserted_count,
        "excluded" => $excluded_count,
        "invalid_accounts" => $invalid_account_count,
        "skipped" => $skipped_count
    ];
}

// function startBackgroundDomainVerification()
// {
//     // Get the absolute path to the verify_domain.php script
//     $scriptPath = realpath(__DIR__ . './includes/verify_domain.php');

//     if (!$scriptPath) {
//         error_log("verify_domain.php not found.");
//         return;
//     }

//     // Build the background command using nohup and redirect all output to /dev/null
//     $cmd = "nohup php -q " . escapeshellarg($scriptPath) . " > /dev/null 2>&1 & echo $!";

//     $pid = null;

//     // Try executing the command using shell_exec or exec
//     if (function_exists('shell_exec')) {
//         $pid = trim(shell_exec($cmd));
//     } elseif (function_exists('exec')) {
//         exec($cmd, $output, $return_var);
//         $pid = isset($output[0]) ? trim($output[0]) : null;
//     }

//     // Optional: Log the PID or error
//     if ($pid) {
//         error_log("Background process started with PID: $pid");
//     } else {
//         error_log("Failed to start background process for verify_domain.php");
//     }
// }


function handleGetRequest()
{
    global $conn;

    $stmt = $conn->prepare("SELECT id, raw_emailid, sp_account, sp_domain, 
                            COALESCE(domain_verified, 0) AS domain_verified, 
                            COALESCE(domain_status, 0) AS domain_status, 
                            COALESCE(validation_response, 'Not Verified Yet') AS validation_response
                            FROM emails");
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }

    return $emails;
}

function handleDeleteRequest()
{
    global $conn;

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        return ["status" => "error", "message" => "Invalid ID"];
    }

    $stmt = $conn->prepare("DELETE FROM emails WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        return ["status" => "success", "message" => "Email deleted"];
    } else {
        return ["status" => "error", "message" => "Deletion failed"];
    }
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

function startBackgroundDomainVerification()
{
    $scriptPath = __DIR__ . '/verify_domain.php';

    // Check if the script exists
    if (!file_exists($scriptPath)) {
        error_log("Error: verify_domain.php not found at: $scriptPath");
        return false;
    }

    // Run the script in the background depending on the OS
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - escape the script path properly
        $command = "start /B php \"" . escapeshellcmd($scriptPath) . "\"";
        pclose(popen($command, "r"));
    } else {
        // Unix/Linux - use nohup to ensure it continues after terminal is closed
        $command = "nohup php " . escapeshellcmd($scriptPath) . " > /dev/null 2>&1 &";
        exec($command);
    }

    return true;
}

// Call it after insertion
if ($inserted_count > 0) {
    startBackgroundDomainVerification();
}

$conn->close();

exec('php  ./includes/verify_domain.php > /dev/null 2>&1 &');


?>