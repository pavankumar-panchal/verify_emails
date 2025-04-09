<?php
// Strict error handling and output control
declare(strict_types=1);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Start output buffering immediately
ob_start();

require 'db.php';

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    if ($conn->connect_error) {
        throw new RuntimeException("Database connection failed: " . $conn->connect_error);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $response = match ($method) {
        'POST' => processEmailUpload($conn),
        'GET' => fetchAllEmails($conn),
        'DELETE' => deleteEmail($conn, (int) ($_GET['id'] ?? 0)),
        default => ["status" => "error", "message" => "Method not allowed"]
    };

    // Ensure clean JSON output
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    ob_clean();
    http_response_code($e instanceof RuntimeException ? 500 : 400);
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "trace" => DEBUG_MODE ? $e->getTrace() : null
    ]);
} finally {
    $conn->close();
    ob_end_flush();
    exit;
}

// Main processing functions
function processEmailUpload(mysqli $conn): array
{
    if (empty($_FILES['csv_file']['tmp_name'])) {
        throw new InvalidArgumentException("No file uploaded");
    }

    $file = $_FILES['csv_file']['tmp_name'];
    if (!is_uploaded_file($file)) {
        throw new RuntimeException("File upload failed or not valid");
    }

    $excludedAccounts = getExclusionList($conn, 'exclude_accounts', 'account');
    $excludedDomains = getExclusionList($conn, 'exclude_domains', 'domain', 'ip_address');

    $results = [
        'inserted' => 0,
        'excluded' => 0,
        'invalid' => 0,
        'skipped' => 0,
        'duplicate' => 0
    ];

    $conn->begin_transaction();
    try {
        $insertStmt = $conn->prepare(
            "INSERT INTO emails 
            (raw_emailid, sp_account, sp_domain, domain_verified, domain_status, validation_response) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id=id"
        );

        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new RuntimeException("Failed to open CSV file");
        }

        $batchCount = 0;
        while (($data = fgetcsv($handle))) {
            $email = sanitizeEmail($data[0] ?? '');
            if (!$email) {
                $results['skipped']++;
                continue;
            }

            $parts = explode('@', $email);
            if (count($parts) !== 2) {
                $results['skipped']++;
                continue;
            }

            [$account, $domain] = $parts;
            $status = determineEmailStatus($account, $domain, $excludedAccounts, $excludedDomains);
            $results[$status['result']]++;

            $insertStmt->bind_param(
                "ssssss",
                $email,
                $account,
                $domain,
                $status['verified'],
                $status['valid'],
                $status['response']
            );
            $insertStmt->execute();

            // Commit in batches to balance performance and memory
            if (++$batchCount % 100 === 0) {
                $conn->commit();
                $conn->begin_transaction();
            }
        }

        $conn->commit();
        fclose($handle);

        return [
            "status" => "success",
            "message" => "CSV processed successfully",
            ...$results
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function fetchAllEmails(mysqli $conn): array
{
    $stmt = $conn->prepare("SELECT * FROM emails");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteEmail(mysqli $conn, int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException("Invalid ID");
    }

    $stmt = $conn->prepare("DELETE FROM emails WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    return [
        "status" => "success",
        "message" => "Email deleted",
        "deleted" => $stmt->affected_rows
    ];
}

// Helper functions
function getExclusionList(mysqli $conn, string $table, string $keyField, ?string $valueField = null): array
{
    $query = $valueField
        ? "SELECT LOWER(TRIM(`$keyField`)) as `key`, `$valueField` as `value` FROM `$table`"
        : "SELECT LOWER(TRIM(`$keyField`)) as `key` FROM `$table`";

    $result = $conn->query($query);
    return $valueField
        ? array_column($result->fetch_all(MYSQLI_ASSOC), 'value', 'key')
        : array_column($result->fetch_all(MYSQLI_ASSOC), 'key');
}

function sanitizeEmail(string $email): ?string
{
    $email = strtolower(trim($email));
    $clean = preg_replace('/[^\x20-\x7E]/', '', $email);
    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null;
}

function isValidAccount(string $account): bool
{
    return preg_match('/^[a-z0-9](?!.*[._-]{2})[a-z0-9._-]*[a-z0-9]$/i', $account)
        && strlen($account) <= 64
        && !preg_match('/^[0-9]+$/', $account);
}

function determineEmailStatus(string $account, string $domain, array $excludedAccounts, array $excludedDomains): array
{
    if (in_array($account, $excludedAccounts, true)) {
        return [
            'verified' => 1,
            'valid' => 1,
            'response' => "Excluded: Account",
            'result' => 'excluded'
        ];
    }

    if (array_key_exists($domain, $excludedDomains)) {
        return [
            'verified' => 1,
            'valid' => 1,
            'response' => $excludedDomains[$domain],
            'result' => 'excluded'
        ];
    }

    if (!isValidAccount($account)) {
        return [
            'verified' => 1,
            'valid' => 0,
            'response' => "Invalid account",
            'result' => 'invalid'
        ];
    }

    return [
        'verified' => 0,
        'valid' => 0,
        'response' => "Pending verification",
        'result' => 'inserted'
    ];
}