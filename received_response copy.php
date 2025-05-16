<?php
// DB Connection
error_reporting(0);
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Function to get geo and device info
function getGeoDeviceInfo()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Simple device detection
    $deviceType = 'Desktop';
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
        $deviceType = 'Mobile';
    } elseif (preg_match('/Tablet|iPad/i', $userAgent)) {
        $deviceType = 'Tablet';
    }

    // Browser detection
    $browser = 'Unknown';
    if (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/Opera/i', $userAgent)) {
        $browser = 'Opera';
    }

    // OS detection
    $os = 'Unknown';
    if (preg_match('/Windows/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
        $os = 'Mac';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
        $os = 'iOS';
    }

    // For country detection - in production use MaxMind GeoIP or similar
    $country = 'Unknown';
    if (function_exists('geoip_country_code_by_name') && $ip) {
        $country = geoip_country_code_by_name($ip);
    } elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) { // Cloudflare
        $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
    }

    return [
        'ip' => $ip,
        'country' => $country,
        'device_type' => $deviceType,
        'browser' => $browser,
        'os' => $os,
        'user_agent' => $userAgent
    ];
}

// Function to track email opens
function trackEmailOpen($db, $emailId, $recipientEmail)
{
    $info = getGeoDeviceInfo();

    $stmt = $db->prepare("INSERT INTO email_tracking 
        (email_id, recipient_email, ip_address, country, device_type, browser, os, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssssss",
        $emailId,
        $recipientEmail,
        $info['ip'],
        $info['country'],
        $info['device_type'],
        $info['browser'],
        $info['os'],
        $info['user_agent']
    );
    $stmt->execute();
    $stmt->close();
}

// Function to record email actions
function recordEmailAction($db, $emailId, $fromEmail, $actionType, $headers, $actionData = null)
{
    $info = getGeoDeviceInfo();

    $stmt = $db->prepare("INSERT INTO email_actions 
        (email_id, from_email, action_type, headers, action_data, ip_address, country, device_info)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $deviceInfo = json_encode([
        'device_type' => $info['device_type'],
        'browser' => $info['browser'],
        'os' => $info['os'],
        'user_agent' => $info['user_agent']
    ]);

    $stmt->bind_param(
        "ssssssss",
        $emailId,
        $fromEmail,
        $actionType,
        $headers,
        $actionData,
        $info['ip'],
        $info['country'],
        $deviceInfo
    );
    $stmt->execute();
    $stmt->close();
}

// Get active SMTP servers
$smtps = $db->query("SELECT * FROM smtp_servers WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

// Function to fetch email replies
function fetchReplies($smtp, $db)
{
    $host = trim($smtp['host']);
    $port = 995; // Secure POP3
    $protocol = 'pop3';
    $encryption = 'ssl';

    // Mailbox connection string
    $mailbox = "{" . $host . ":" . $port . "/" . $protocol . "/" . $encryption . "/novalidate-cert}INBOX";

    // Open mailbox
    $inbox = @imap_open($mailbox, $smtp['email'], $smtp['password']);
    if (!$inbox) {
        return ["error" => imap_last_error()];
    }

    $emails = imap_search($inbox, 'UNSEEN');
    $messages = [
        'regular' => [],
        'unsubscribes' => [],
        'bounces' => []
    ];

    if ($emails) {
        rsort($emails);
        $emails = array_slice($emails, 0, 20);

        foreach ($emails as $email_number) {
            $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
            $body = imap_fetchbody($inbox, $email_number, 1);
            $headers = imap_headerinfo($inbox, $email_number);
            $headers_raw = imap_fetchheader($inbox, $email_number);
            $body_text = quoted_printable_decode($body);

            // Extract from email
            $from_email = '';
            if (isset($headers->from[0]->mailbox) && isset($headers->from[0]->host)) {
                $from_email = $headers->from[0]->mailbox . '@' . $headers->from[0]->host;
            }

            // Check for bounced emails
            $is_bounce = false;
            $bounce_reason = '';
            if (
                stripos($overview->subject ?? '', 'undeliverable') !== false ||
                stripos($overview->subject ?? '', 'returned') !== false ||
                stripos($overview->subject ?? '', 'failure') !== false ||
                stripos($overview->subject ?? '', 'bounce') !== false
            ) {
                $is_bounce = true;
                $bounce_reason = 'Bounced email detected by subject';
            } elseif (preg_match('/X-Failed-Recipients:\s*(.*)/i', $headers_raw, $matches)) {
                $is_bounce = true;
                $bounce_reason = 'Bounced email detected by headers';
            }

            // Check for unsubscribe requests
            $is_unsubscribe = false;
            $unsubscribe_method = '';
            if ($is_bounce) {
                // Skip unsubscribe checks for bounced emails
            } elseif (
                stripos($body_text, 'unsubscribe') !== false ||
                stripos($overview->subject ?? '', 'unsubscribe') !== false
            ) {
                $is_unsubscribe = true;
                $unsubscribe_method = 'Email content';
            } elseif (preg_match('/List-Unsubscribe:\s*(.*)/i', $headers_raw, $matches)) {
                $is_unsubscribe = true;
                $unsubscribe_method = 'List-Unsubscribe header';
            }

            $message = [
                "from" => $overview->from ?? '',
                "from_email" => $from_email,
                "subject" => $overview->subject ?? '(No Subject)',
                "date" => $overview->date ?? '',
                "body" => substr(strip_tags($body_text), 0, 500) . "...",
                "headers" => $headers_raw,
                "uid" => $overview->uid ?? $email_number,
                "seen" => $overview->seen ?? false,
                "is_unsubscribe" => $is_unsubscribe,
                "unsubscribe_method" => $unsubscribe_method,
                "is_bounce" => $is_bounce,
                "bounce_reason" => $bounce_reason
            ];

            if ($is_bounce) {
                $messages['bounces'][] = $message;
            } elseif ($is_unsubscribe) {
                $messages['unsubscribes'][] = $message;
            } else {
                $messages['regular'][] = $message;
            }

            // Record replies in the database
            if (!$is_bounce && !$is_unsubscribe) {
                recordEmailAction($db, $message['uid'], $from_email, 'reply', $headers_raw, $message['subject']);
            }
        }
    }

    imap_close($inbox);
    return $messages;
}

// Helper functions
function getInitials($name)
{
    $parts = explode(' ', $name);
    $initials = '';

    foreach ($parts as $part) {
        if (preg_match('/[a-zA-Z]/', substr($part, 0, 1))) {
            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2)
                break;
        }
    }

    return $initials ?: '?';
}

function formatDate($dateString, $full = false)
{
    if (empty($dateString))
        return '';

    try {
        $date = new DateTime($dateString);
        $now = new DateTime();

        if ($full) {
            return $date->format('M j, Y g:i A');
        }

        $diff = $now->diff($date);

        if ($diff->days === 0) {
            return $date->format('g:i A');
        } elseif ($diff->days === 1) {
            return 'Yesterday';
        } elseif ($diff->days < 7) {
            return $date->format('D');
        } else {
            return $date->format('M j');
        }
    } catch (Exception $e) {
        return $dateString;
    }
}

// Check if this is a tracking pixel request
if (isset($_GET['track'])) {
    $emailId = $_GET['id'] ?? '';
    $recipientEmail = $_GET['email'] ?? '';

    if ($emailId && $recipientEmail) {
        trackEmailOpen($db, $emailId, $recipientEmail);
    }

    // Return a 1x1 transparent pixel
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Check if this is an AJAX request for tracking data
if (isset($_GET['get_tracking_data'])) {
    header('Content-Type: application/json');

    try {
        // Get recent opens (last 50)
        $recentOpens = $db->query("
            SELECT recipient_email, country, device_type, browser, opened_at 
            FROM email_tracking 
            ORDER BY opened_at DESC 
            LIMIT 50
        ")->fetch_all(MYSQLI_ASSOC);

        // Get recent actions (last 50)
        $recentActions = $db->query("
            SELECT from_email, action_type, country, processed_at 
            FROM email_actions 
            ORDER BY processed_at DESC 
            LIMIT 50
        ")->fetch_all(MYSQLI_ASSOC);

        // Get country stats
        $countryStats = $db->query("
            SELECT country, COUNT(*) as count 
            FROM email_tracking 
            GROUP BY country 
            ORDER BY count DESC 
            LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'recentOpens' => $recentOpens,
            'recentActions' => $recentActions,
            'countryStats' => $countryStats
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Check if this is an action processing request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];
        $email = $_POST['email'] ?? '';
        $email_uid = $_POST['email_uid'] ?? '';
        $headers = $_POST['headers'] ?? '';
        $account_index = $_POST['account_index'] ?? 0;

        if (!$email || !$email_uid) {
            throw new Exception('Missing required parameters');
        }

        switch ($action) {
            case 'unsubscribe':
                recordEmailAction($db, $email_uid, $email, 'unsubscribe', $headers);
                // In a real app, you would also update your mailing list here
                echo json_encode(['success' => true]);
                break;

            case 'bounce':
                recordEmailAction($db, $email_uid, $email, 'bounce', $headers);
                // In a real app, you would also update your mailing list here
                echo json_encode(['success' => true]);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Reply Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .email-body-content {
            white-space: pre-wrap;
        }

        .progress-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(211, 211, 211, 0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .circle-loader {
            position: relative;
            width: 180px;
            height: 180px;
            margin-bottom: 1rem;
        }

        .circle-loader svg {
            transform: rotate(-90deg);
        }

        .circle-loader circle {
            fill: none;
            stroke-width: 10;
            stroke-linecap: round;
        }

        .circle-bg {
            stroke: #e6e6e6;
        }

        .circle-progress {
            stroke: #3b82f6;
            transition: stroke-dashoffset 0.5s ease;
        }

        .loader-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .progress-label {
            font-size: 1.2rem;
            color: #555;
            font-weight: 500;
            margin-top: 1rem;
        }

        .hidden {
            display: none !important;
        }

        .unsubscribe-badge {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .bounce-badge {
            background-color: #fef9c3;
            color: #92400e;
            border: 1px solid #fef08a;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php require "navbar.php"; ?>
    <div class="main-content container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 pb-6 border-b border-gray-200">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-inbox text-blue-500"></i> Email Reply Dashboard
                </h1>
                <p class="text-sm text-gray-500 mt-1">View and manage email replies from your SMTP accounts</p>
            </div>
            <button onclick="window.location.reload()"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </header>

        <!-- Account Tabs -->
        <div class="mb-6">
            <div class="flex overflow-x-auto pb-2 gap-2">
                <?php foreach ($smtps as $index => $smtp): ?>
                    <button onclick="switchAccount(<?= $index ?>)"
                        class="account-tab whitespace-nowrap px-4 py-2 rounded-md border border-gray-200 hover:bg-gray-50 transition <?= $index === 0 ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' : 'bg-white' ?>">
                        <i class="fas fa-envelope mr-2"></i> <?= htmlspecialchars($smtp['name']) ?>
                    </button>
                <?php endforeach; ?>
                <button onclick="showTrackingData()"
                    class="whitespace-nowrap px-4 py-2 rounded-md border border-gray-200 hover:bg-gray-50 transition bg-white">
                    <i class="fas fa-map-marker-alt mr-2"></i> Tracking Data
                </button>
            </div>
        </div>

        <!-- Account Content -->
        <?php foreach ($smtps as $index => $smtp):
            $replies = fetchReplies($smtp, $db);
            ?>
            <div class="account-content <?= $index === 0 ? 'block' : 'hidden' ?>" id="account-<?= $index ?>">
                <!-- Account Info -->
                <div
                    class="flex flex-col md:flex-row gap-4 items-start md:items-center mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-1"><?= htmlspecialchars($smtp['name']) ?></h3>
                        <p class="text-gray-600">
                            <i class="fas fa-server mr-1 text-blue-500"></i> <?= htmlspecialchars($smtp['host']) ?>
                            | <i class="fas fa-user mr-1 text-blue-500"></i> <?= htmlspecialchars($smtp['email']) ?>
                        </p>
                    </div>
                    <div class="bg-green-50 text-green-800 px-3 py-1 rounded-md flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        <span>Active</span>
                    </div>
                </div>

                <?php if (isset($replies['error'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Connection Error:</strong> <?= htmlspecialchars($replies['error']) ?>
                        </div>
                    </div>
                <?php elseif (empty($replies['regular']) && empty($replies['unsubscribes']) && empty($replies['bounces'])): ?>
                    <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                        <i class="fas fa-envelope-open text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-1">No new emails</h3>
                        <p class="text-gray-500">There are no unread messages in this inbox.</p>
                    </div>
                <?php else: ?>
                    <!-- Email Lists -->
                    <div class="space-y-8">
                        <!-- Regular Messages -->
                        <?php if (!empty($replies['regular'])): ?>
                            <div>
                                <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
                                    <i class="fas fa-inbox text-blue-500"></i> Regular Messages (<?= count($replies['regular']) ?>)
                                </h3>
                                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                                    <!-- Email Header -->
                                    <div
                                        class="grid grid-cols-12 gap-4 p-4 bg-gray-50 border-b border-gray-200 font-medium text-gray-600">
                                        <div class="col-span-1"></div>
                                        <div class="col-span-3 md:col-span-2">Sender</div>
                                        <div class="col-span-6 md:col-span-7">Subject</div>
                                        <div class="col-span-2 md:col-span-2 text-right">Date</div>
                                    </div>

                                    <?php foreach ($replies['regular'] as $reply): ?>
                                        <!-- Email Item -->
                                        <div class="email-item grid grid-cols-12 gap-4 p-4 border-b border-gray-200 hover:bg-gray-50 transition cursor-pointer <?= !$reply['seen'] ? 'bg-blue-50' : '' ?>"
                                            onclick="toggleEmailBody(this, <?= $index ?>, '<?= $reply['uid'] ?>')">
                                            <div class="col-span-1 flex items-center">
                                                <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="col-span-3 md:col-span-2 flex items-center gap-2 overflow-hidden">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-medium text-sm">
                                                    <?= getInitials($reply['from'] ?: $reply['from_email']) ?>
                                                </div>
                                                <span class="truncate"
                                                    title="<?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?>"><?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?></span>
                                            </div>
                                            <div class="col-span-6 md:col-span-7 flex items-center font-medium truncate">
                                                <?= htmlspecialchars($reply['subject']) ?>
                                            </div>
                                            <div class="col-span-2 md:col-span-2 text-right text-sm text-gray-500">
                                                <?= formatDate($reply['date']) ?>
                                            </div>
                                        </div>

                                        <!-- Email Body (Hidden by default) -->
                                        <div class="email-body hidden" id="email-body-<?= $index ?>-<?= $reply['uid'] ?>">
                                            <div class="p-6 border-b border-gray-200">
                                                <div class="flex flex-col md:flex-row justify-between gap-4 mb-4">
                                                    <div>
                                                        <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($reply['subject']) ?>
                                                        </h3>
                                                        <p class="text-gray-700 mb-1">
                                                            <strong class="text-gray-600">From:</strong>
                                                            <?= htmlspecialchars($reply['from'] ? $reply['from'] . ' &lt;' . $reply['from_email'] . '&gt;' : $reply['from_email']) ?>
                                                        </p>
                                                        <p class="text-gray-700">
                                                            <strong class="text-gray-600">Date:</strong>
                                                            <?= formatDate($reply['date'], true) ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button
                                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-reply"></i> Reply
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="email-body-content bg-gray-50 p-4 rounded-md">
                                                    <?= nl2br(htmlspecialchars($reply['body'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Unsubscribe Requests -->
                        <?php if (!empty($replies['unsubscribes'])): ?>
                            <div>
                                <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
                                    <i class="fas fa-user-minus text-red-500"></i> Unsubscribe Requests
                                    (<?= count($replies['unsubscribes']) ?>)
                                </h3>
                                <div
                                    class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 border-l-4 border-l-red-500">
                                    <!-- Email Header -->
                                    <div
                                        class="grid grid-cols-12 gap-4 p-4 bg-red-50 border-b border-gray-200 font-medium text-gray-600">
                                        <div class="col-span-1"></div>
                                        <div class="col-span-3 md:col-span-2">Sender</div>
                                        <div class="col-span-6 md:col-span-7">Subject</div>
                                        <div class="col-span-2 md:col-span-2 text-right">Date</div>
                                    </div>

                                    <?php foreach ($replies['unsubscribes'] as $reply): ?>
                                        <div class="email-item grid grid-cols-12 gap-4 p-4 border-b border-gray-200 hover:bg-gray-50 transition cursor-pointer bg-red-50"
                                            onclick="toggleEmailBody(this, <?= $index ?>, '<?= $reply['uid'] ?>')">
                                            <div class="col-span-1 flex items-center">
                                                <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="col-span-3 md:col-span-2 flex items-center gap-2 overflow-hidden">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-medium text-sm">
                                                    <?= getInitials($reply['from'] ?: $reply['from_email']) ?>
                                                </div>
                                                <span class="truncate"
                                                    title="<?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?>"><?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?></span>
                                            </div>
                                            <div class="col-span-6 md:col-span-7 flex items-center font-medium truncate">
                                                <?= htmlspecialchars($reply['subject']) ?>
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full unsubscribe-badge">
                                                    <i class="fas fa-ban mr-1"></i>Unsubscribe
                                                </span>
                                            </div>
                                            <div class="col-span-2 md:col-span-2 text-right text-sm text-gray-500">
                                                <?= formatDate($reply['date']) ?>
                                            </div>
                                        </div>

                                        <!-- Email Body -->
                                        <div class="email-body hidden" id="email-body-<?= $index ?>-<?= $reply['uid'] ?>">
                                            <div class="p-6 border-b border-gray-200">
                                                <div class="flex flex-col md:flex-row justify-between gap-4 mb-4">
                                                    <div>
                                                        <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($reply['subject']) ?>
                                                        </h3>
                                                        <p class="text-gray-700 mb-1">
                                                            <strong class="text-gray-600">From:</strong>
                                                            <?= htmlspecialchars($reply['from'] ? $reply['from'] . ' &lt;' . $reply['from_email'] . '&gt;' : $reply['from_email']) ?>
                                                        </p>
                                                        <p class="text-gray-700">
                                                            <strong class="text-gray-600">Date:</strong>
                                                            <?= formatDate($reply['date'], true) ?>
                                                        </p>
                                                        <p class="text-red-600 mt-2">
                                                            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Unsubscribe
                                                                Request:</strong>
                                                            Detected via <?= htmlspecialchars($reply['unsubscribe_method']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button
                                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-reply"></i> Reply
                                                        </button>
                                                        <button
                                                            onclick="event.stopPropagation(); processUnsubscribe('<?= $reply['from_email'] ?>', <?= $index ?>, '<?= $reply['uid'] ?>', `<?= htmlspecialchars(addslashes($reply['headers'])) ?>`)"
                                                            class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-user-minus"></i> Process Unsubscribe
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="email-body-content bg-gray-50 p-4 rounded-md">
                                                    <?= nl2br(htmlspecialchars($reply['body'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Bounced Emails -->
                        <?php if (!empty($replies['bounces'])): ?>
                            <div>
                                <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i> Bounced Emails
                                    (<?= count($replies['bounces']) ?>)
                                </h3>
                                <div
                                    class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 border-l-4 border-l-yellow-500">
                                    <!-- Email Header -->
                                    <div
                                        class="grid grid-cols-12 gap-4 p-4 bg-yellow-50 border-b border-gray-200 font-medium text-gray-600">
                                        <div class="col-span-1"></div>
                                        <div class="col-span-3 md:col-span-2">Sender</div>
                                        <div class="col-span-6 md:col-span-7">Subject</div>
                                        <div class="col-span-2 md:col-span-2 text-right">Date</div>
                                    </div>

                                    <?php foreach ($replies['bounces'] as $reply): ?>
                                        <div class="email-item grid grid-cols-12 gap-4 p-4 border-b border-gray-200 hover:bg-gray-50 transition cursor-pointer bg-yellow-50"
                                            onclick="toggleEmailBody(this, <?= $index ?>, '<?= $reply['uid'] ?>')">
                                            <div class="col-span-1 flex items-center">
                                                <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="col-span-3 md:col-span-2 flex items-center gap-2 overflow-hidden">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-medium text-sm">
                                                    <?= getInitials($reply['from'] ?: $reply['from_email']) ?>
                                                </div>
                                                <span class="truncate"
                                                    title="<?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?>"><?= htmlspecialchars($reply['from'] ?: $reply['from_email']) ?></span>
                                            </div>
                                            <div class="col-span-6 md:col-span-7 flex items-center font-medium truncate">
                                                <?= htmlspecialchars($reply['subject']) ?>
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full bounce-badge">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>Bounced
                                                </span>
                                            </div>
                                            <div class="col-span-2 md:col-span-2 text-right text-sm text-gray-500">
                                                <?= formatDate($reply['date']) ?>
                                            </div>
                                        </div>

                                        <!-- Email Body -->
                                        <div class="email-body hidden" id="email-body-<?= $index ?>-<?= $reply['uid'] ?>">
                                            <div class="p-6 border-b border-gray-200">
                                                <div class="flex flex-col md:flex-row justify-between gap-4 mb-4">
                                                    <div>
                                                        <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($reply['subject']) ?>
                                                        </h3>
                                                        <p class="text-gray-700 mb-1">
                                                            <strong class="text-gray-600">From:</strong>
                                                            <?= htmlspecialchars($reply['from'] ? $reply['from'] . ' &lt;' . $reply['from_email'] . '&gt;' : $reply['from_email']) ?>
                                                        </p>
                                                        <p class="text-gray-700">
                                                            <strong class="text-gray-600">Date:</strong>
                                                            <?= formatDate($reply['date'], true) ?>
                                                        </p>
                                                        <p class="text-yellow-600 mt-2">
                                                            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Bounce
                                                                Reason:</strong>
                                                            <?= htmlspecialchars($reply['bounce_reason']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button
                                                            onclick="event.stopPropagation(); processBounce('<?= $reply['from_email'] ?>', <?= $index ?>, '<?= $reply['uid'] ?>', `<?= htmlspecialchars(addslashes($reply['headers'])) ?>`)"
                                                            class="flex items-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-trash-alt"></i> Remove Bounced Email
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="email-body-content bg-gray-50 p-4 rounded-md">
                                                    <?= nl2br(htmlspecialchars($reply['body'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Tracking Data Section -->
        <div id="trackingData" class="hidden mt-8">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-map-marker-alt mr-2"></i>Email Tracking Data</h2>

            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3">Email Opens by Country</h3>
                <div class="bg-white rounded-lg shadow p-4">
                    <div id="countryChart" style="height: 300px;"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3">Recent Opens</h3>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Country</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Device</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Browser</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time</th>
                                    </tr>
                                </thead>
                                <tbody id="recentOpensTable" class="bg-white divide-y divide-gray-200">
                                    <!-- Data will be loaded via AJAX -->


                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-3">Recent Actions</h3>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Action</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Country</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time</th>
                                    </tr>
                                </thead>
                                <tbody id="recentActionsTable" class="bg-white divide-y divide-gray-200">
                                    <!-- Data will be loaded via AJAX -->



                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Device Distribution</h3>
                    <div id="deviceChart" style="height: 250px;"></div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Browser Usage</h3>
                    <div id="browserChart" style="height: 250px;"></div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Operating Systems</h3>
                    <div id="osChart" style="height: 250px;"></div>
                </div>
            </div>

            <button onclick="hideTrackingData()"
                class="mt-4 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Email Dashboard
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="progress-overlay hidden">
        <div class="circle-loader">
            <svg viewBox="0 0 36 36">
                <circle class="circle-bg" cx="18" cy="18" r="16" stroke-width="2"></circle>
                <circle class="circle-progress" cx="18" cy="18" r="16" stroke-width="2" stroke-dasharray="100 100"
                    stroke-dashoffset="100"></circle>
            </svg>
            <div class="loader-text">0%</div>
        </div>
        <div class="progress-label">Processing request...</div>
    </div>

    <script>
        // Global variables
        let currentAccount = 0;
        let trackingCharts = {};

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            // Set up event listeners
            document.querySelectorAll('.email-item').forEach(item => {
                item.addEventListener('click', function () {
                    const emailId = this.getAttribute('data-email-id');
                    toggleEmailBody(this, currentAccount, emailId);
                });
            });

            // Load tracking data if needed
            if (window.location.hash === '#tracking') {
                showTrackingData();
            }
        });

        // Switch between SMTP accounts
        function switchAccount(index) {
            currentAccount = index;

            // Update active tab
            document.querySelectorAll('.account-tab').forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'hover:bg-blue-700');
                    tab.classList.remove('bg-white');
                } else {
                    tab.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'hover:bg-blue-700');
                    tab.classList.add('bg-white');
                }
            });

            // Show the selected account content
            document.querySelectorAll('.account-content').forEach((content, i) => {
                content.style.display = i === index ? 'block' : 'none';
            });
        }

        // Toggle email body visibility
        function toggleEmailBody(element, accountIndex, emailUid) {
            const emailBody = document.getElementById(`email-body-${accountIndex}-${emailUid}`);

            if (emailBody.classList.contains('hidden')) {
                // Hide all other open email bodies first
                document.querySelectorAll('.email-body').forEach(body => {
                    if (body.id !== `email-body-${accountIndex}-${emailUid}`) {
                        body.classList.add('hidden');
                    }
                });

                emailBody.classList.remove('hidden');
                element.classList.add('bg-blue-50');
            } else {
                emailBody.classList.add('hidden');
                element.classList.remove('bg-blue-50');
            }
        }

        // Process unsubscribe request
        function processUnsubscribe(email, accountIndex, emailUid, headers) {
            showLoadingOverlay();

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=unsubscribe&email=${encodeURIComponent(email)}&email_uid=${encodeURIComponent(emailUid)}&account_index=${accountIndex}&headers=${encodeURIComponent(headers)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the email from the UI
                        const emailItem = document.querySelector(`.email-item[onclick*="${emailUid}"]`);
                        if (emailItem) {
                            emailItem.parentNode.removeChild(emailItem);
                        }

                        const emailBody = document.getElementById(`email-body-${accountIndex}-${emailUid}`);
                        if (emailBody) {
                            emailBody.parentNode.removeChild(emailBody);
                        }

                        showAlert('success', 'Unsubscribe processed successfully');
                    } else {
                        showAlert('error', data.message || 'Failed to process unsubscribe');
                    }
                })
                .catch(error => {
                    showAlert('error', 'An error occurred while processing the unsubscribe');
                })
                .finally(() => {
                    hideLoadingOverlay();
                });
        }

        // Process bounced email
        function processBounce(email, accountIndex, emailUid, headers) {
            showLoadingOverlay();

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=bounce&email=${encodeURIComponent(email)}&email_uid=${encodeURIComponent(emailUid)}&account_index=${accountIndex}&headers=${encodeURIComponent(headers)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the email from the UI
                        const emailItem = document.querySelector(`.email-item[onclick*="${emailUid}"]`);
                        if (emailItem) {
                            emailItem.parentNode.removeChild(emailItem);
                        }

                        const emailBody = document.getElementById(`email-body-${accountIndex}-${emailUid}`);
                        if (emailBody) {
                            emailBody.parentNode.removeChild(emailBody);
                        }

                        showAlert('success', 'Bounced email processed successfully');
                    } else {
                        showAlert('error', data.message || 'Failed to process bounced email');
                    }
                })
                .catch(error => {
                    showAlert('error', 'An error occurred while processing the bounced email');
                })
                .finally(() => {
                    hideLoadingOverlay();
                });
        }

        // Show tracking data section
        function showTrackingData() {
            // Hide all account content
            document.querySelectorAll('.account-content').forEach(content => {
                content.style.display = 'none';
            });

            // Show tracking data
            document.getElementById('trackingData').style.display = 'block';

            // Update active tab
            document.querySelectorAll('.account-tab').forEach(tab => {
                tab.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'hover:bg-blue-700');
                tab.classList.add('bg-white');
            });

            // Load tracking data via AJAX
            loadTrackingData();
        }

        // Hide tracking data section
        function hideTrackingData() {
            document.getElementById('trackingData').style.display = 'none';
            document.querySelector(`.account-content[id="account-${currentAccount}"]`).style.display = 'block';

            // Destroy charts to prevent memory leaks
            Object.values(trackingCharts).forEach(chart => {
                if (chart) chart.destroy();
            });
            trackingCharts = {};
        }

        // Load tracking data via AJAX
        function loadTrackingData() {
            showLoadingOverlay();

            fetch('?get_tracking_data=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update recent opens table
                        const opensTable = document.getElementById('recentOpensTable');
                        opensTable.innerHTML = data.recentOpens.map(open => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${open.recipient_email}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${open.country || 'Unknown'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${open.device_type}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${open.browser}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(open.opened_at).toLocaleString()}</td>
                            </tr>
                        `).join('');

                        // Update recent actions table
                        const actionsTable = document.getElementById('recentActionsTable');
                        actionsTable.innerHTML = data.recentActions.map(action => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${action.from_email}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 py-1 rounded-full ${getActionBadgeClass(action.action_type)}">
                                        ${action.action_type}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${action.country || 'Unknown'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(action.processed_at).toLocaleString()}</td>
                            </tr>
                        `).join('');

                        // Create charts
                        createTrackingCharts(data);
                    } else {
                        showAlert('error', data.message || 'Failed to load tracking data');
                    }
                })
                .catch(error => {
                    showAlert('error', 'Failed to load tracking data');
                })
                .finally(() => {
                    hideLoadingOverlay();
                });
        }

        // Get CSS class for action badge
        function getActionBadgeClass(actionType) {
            switch (actionType.toLowerCase()) {
                case 'unsubscribe':
                    return 'bg-red-100 text-red-800';
                case 'bounce':
                    return 'bg-yellow-100 text-yellow-800';
                case 'reply':
                    return 'bg-blue-100 text-blue-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        // Create tracking charts
        function createTrackingCharts(data) {
            // Country chart
            const countryCtx = document.getElementById('countryChart').getContext('2d');
            trackingCharts.country = new Chart(countryCtx, {
                type: 'bar',
                data: {
                    labels: data.countryStats.map(item => item.country || 'Unknown'),
                    datasets: [{
                        label: 'Opens by Country',
                        data: data.countryStats.map(item => item.count),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Device distribution chart (pie)
            const deviceCtx = document.getElementById('deviceChart').getContext('2d');
            trackingCharts.device = new Chart(deviceCtx, {
                type: 'pie',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: [
                            data.recentOpens.filter(o => o.device_type === 'Desktop').length,
                            data.recentOpens.filter(o => o.device_type === 'Mobile').length,
                            data.recentOpens.filter(o => o.device_type === 'Tablet').length
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Browser usage chart (doughnut)
            const browserCtx = document.getElementById('browserChart').getContext('2d');
            trackingCharts.browser = new Chart(browserCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Other'],
                    datasets: [{
                        data: [
                            data.recentOpens.filter(o => o.browser === 'Chrome').length,
                            data.recentOpens.filter(o => o.browser === 'Firefox').length,
                            data.recentOpens.filter(o => o.browser === 'Safari').length,
                            data.recentOpens.filter(o => o.browser === 'Edge').length,
                            data.recentOpens.filter(o => o.browser === 'Opera').length,
                            data.recentOpens.filter(o => !['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'].includes(o.browser)).length
                        ],
                        backgroundColor: [
                            'rgba(52, 211, 153, 0.7)',
                            'rgba(251, 191, 36, 0.7)',
                            'rgba(96, 165, 250, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(244, 63, 94, 0.7)',
                            'rgba(156, 163, 175, 0.7)'
                        ],
                        borderColor: [
                            'rgba(52, 211, 153, 1)',
                            'rgba(251, 191, 36, 1)',
                            'rgba(96, 165, 250, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(244, 63, 94, 1)',
                            'rgba(156, 163, 175, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // OS distribution chart (pie)
            const osCtx = document.getElementById('osChart').getContext('2d');
            trackingCharts.os = new Chart(osCtx, {
                type: 'pie',
                data: {
                    labels: ['Windows', 'Mac', 'Linux', 'Android', 'iOS', 'Other'],
                    datasets: [{
                        data: [
                            data.recentOpens.filter(o => o.os === 'Windows').length,
                            data.recentOpens.filter(o => o.os === 'Mac').length,
                            data.recentOpens.filter(o => o.os === 'Linux').length,
                            data.recentOpens.filter(o => o.os === 'Android').length,
                            data.recentOpens.filter(o => o.os === 'iOS').length,
                            data.recentOpens.filter(o => !['Windows', 'Mac', 'Linux', 'Android', 'iOS'].includes(o.os)).length
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(244, 63, 94, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(156, 163, 175, 0.7)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(244, 63, 94, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(156, 163, 175, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Show loading overlay
        function showLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('hidden');

            // Animate progress circle
            const circle = overlay.querySelector('.circle-progress');
            const loaderText = overlay.querySelector('.loader-text');
            let progress = 0;

            const interval = setInterval(() => {
                progress += 5;
                if (progress > 100) progress = 100;

                const dashoffset = 100 - progress;
                circle.style.strokeDashoffset = dashoffset;
                loaderText.textContent = `${progress}%`;

                if (progress === 100) {
                    clearInterval(interval);
                }
            }, 100);
        }

        // Hide loading overlay
        function hideLoadingOverlay() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        // Show alert message
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(alertDiv);

            // Remove after 5 seconds
            setTimeout(() => {
                alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => {
                    document.body.removeChild(alertDiv);
                }, 500);
            }, 5000);
        }
    </script>
</body>

</html>