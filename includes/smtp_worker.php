<?php
require __DIR__ . '/../db.php';



$offset = $argv[1] ?? 0;
$limit = $argv[2] ?? 100;

// Only process emails that need verification and haven't been processed yet
$emails = $conn->query("SELECT id, raw_emailid, sp_domain FROM emails WHERE domain_status=1 AND domain_processed=0 LIMIT $offset, $limit");

while ($row = $emails->fetch_assoc()) {
    $email = $row['raw_emailid'];
    $domain = $row['sp_domain'];

    echo "\n Processing Email: $email (Domain: $domain)\n";

    $verification = verifyEmailViaSMTP($email, $domain);

    if ($verification['status'] === 'success') {
        $status = $verification['result'];
        $message = $conn->real_escape_string($verification['message']);
    } else {
        $status = 0;
        $message = "Invalid";
    }

    echo "domain_status = $status, validation_response = $message\n";

    // Mark this domain as processed regardless of verification result
    $conn->query("UPDATE emails SET 
                     domain_status = $status,
                     validation_response = '$message',
                     domain_processed = 1
                     WHERE id = {$row['id']}");
}

function verifyEmailViaSMTP($email, $domain)
{
    if (!getmxrr($domain, $mxhosts)) {
        echo " No MX record found for domain: $domain\n";
        return ["status" => "error", "message" => "No MX record found"];
    }

    $mxIP = gethostbyname($mxhosts[0]);
    $port = 25;
    $timeout = 30;

    echo " Connecting to: $mxIP on port $port...\n";

    $context = stream_context_create();
    $smtp = stream_socket_client(
        "tcp://$mxIP:$port",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$smtp) {
        echo " Could not connect to $mxIP: $errstr ($errno)\n";
        return ["status" => "error", "message" => "Connection failed"];
    }

    stream_set_timeout($smtp, $timeout);

    $response = fgets($smtp, 4096);
    echo "SERVER: $response";

    if (substr($response, 0, 3) != '220') {
        fclose($smtp);
        return ["status" => "error", "message" => "SMTP server not ready"];
    }

    fputs($smtp, "EHLO server.relyon.co.in\r\n");
    echo " Sent: EHLO server.relyon.co.in\n";
    while ($line = fgets($smtp, 4096)) {
        echo " $line";
        if (substr($line, 3, 1) == ' ')
            break;
    }

    fputs($smtp, "MAIL FROM:<info@relyon.co.in>\r\n");
    echo " Sent: MAIL FROM:<info@relyon.co.in>\n";
    $response = fgets($smtp, 4096);
    echo " Response: $response";

    fputs($smtp, "RCPT TO:<$email>\r\n");
    echo " Sent: RCPT TO:<$email>\n";
    $response = fgets($smtp, 4096);
    echo " Response: $response";

    $result = (substr($response, 0, 3) == '250') ? 1 : 0;

    fputs($smtp, "QUIT\r\n");
    fclose($smtp);

    if ($result === 1) {
        return ["status" => "success", "result" => 1, "message" => $mxIP];
    } else {
        return ["status" => "success", "result" => 0, "message" => "Invalid"];
    }
}
?>