<?php
// Database connection
require 'db.php';
global $conn;

// Function to perform SMTP validation
function verifyEmailViaSMTP($email, $domain) {
    if (!getmxrr($domain, $mxhosts)) {
        echo "❌ No MX record found for domain: $domain\n";
        return ["status" => "error", "message" => "No record found"];
    }

    $mxIP = gethostbyname($mxhosts[0]);
    $port = 25;
    $timeout = 30;

    echo "⏳ Connecting to MX: $mxIP on port $port...\n";

    $smtp = fsockopen($mxIP, $port, $errno, $errstr, $timeout);
    if (!$smtp) {
        echo "❌ Could not connect to $mxIP\n";
        return ["status" => "error", "message" => "No record found"];
    }

    stream_set_timeout($smtp, $timeout);

    $response = fgets($smtp, 4096);
    echo "✅ SERVER: $response";

    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ["status" => "error", "message" => "No record found"];
    }

    fputs($smtp, "EHLO server.relyon.co.in\r\n");
    echo "📤 Sent: EHLO server.relyon.co.in\n";
    while ($line = fgets($smtp, 4096)) {
        echo "🌐 $line";
        if (substr($line, 3, 1) == ' ') break;
    }

    fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
    echo "📤 Sent: MAIL FROM:<info@relyon.co.in>\n";
    $response = fgets($smtp, 4096);
    echo "📥 Response: $response";

    fputs($smtp, "RCPT TO:<$email>\r\n");
    echo "📤 Sent: RCPT TO:<$email>\n";
    $response = fgets($smtp, 4096);
    echo "📥 Response: $response";

    $result = (substr($response, 0, 3) == '250') ? 1 : 0;

    fputs($smtp, "QUIT\r\n");
    fclose($smtp);

    if ($result === 1) {
        return ["status" => "success", "result" => 1, "message" => $mxIP];
    } else {
        return ["status" => "success", "result" => 0, "message" => "No record found"];
    }
}

// Function to process all emails individually
function processEmails() {
    global $conn;

    echo "🔍 Fetching all emails...\n";
    $query = "SELECT id, raw_emailid, sp_domain FROM emails";
    $result = $conn->query($query);

    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $id     = $row['id'];
        $email  = $row['raw_emailid'];
        $domain = $row['sp_domain'];

        echo "\n➡️ Processing Email: $email (Domain: $domain)\n";

        $verification = verifyEmailViaSMTP($email, $domain);

        if ($verification['status'] === 'success') {
            $status  = $verification['result'];
            $message = $conn->real_escape_string($verification['message']);
        } else {
            $status  = 0;
            $message = "No record found";
        }

        echo "🛠️ Updating DB → domain_status = $status, validation_response = $message\n";

        $update = "UPDATE emails SET 
                    domain_status = $status,
                    validation_response = '$message'
                    WHERE id = $id";

        if ($conn->query($update)) {
            echo "✅ Updated email ID $id → Status: $status, Response: $message\n";
        } else {
            echo "❌ Update failed for email ID $id: " . $conn->error . "\n";
        }
    }
}

// Start processing
processEmails();
$conn->close();

echo "\n🎉 Processing complete!\n";
?>
