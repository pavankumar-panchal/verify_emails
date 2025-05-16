<?php
// DB Connection
error_reporting(0);
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get active SMTP servers
$smtps = $db->query("SELECT * FROM smtp_servers WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

function fetchReplies($smtp)
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Reply Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
            </div>
        </div>

        <!-- Account Content -->
        <?php foreach ($smtps as $index => $smtp):
            $replies = fetchReplies($smtp);
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
                                                    <!-- <div class="flex gap-2">
                                                        <button
                                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-reply"></i> Reply
                                                        </button>
                                                    </div> -->
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
                                                        <!-- <button
                                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                                                            <i class="fas fa-reply"></i> Reply
                                                        </button> -->
                                                        <button
                                                            onclick="event.stopPropagation(); processUnsubscribe('<?= $reply['from_email'] ?>', <?= $index ?>, '<?= $reply['uid'] ?>')"
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
                                                            onclick="event.stopPropagation(); processBounce('<?= $reply['from_email'] ?>', <?= $index ?>, '<?= $reply['uid'] ?>')"
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
    </div>

    <!-- Progress Overlay -->
    <div id="progressOverlay" class="progress-overlay hidden">
        <div class="circle-loader">
            <svg width="180" height="180">
                <circle class="circle-bg" cx="90" cy="90" r="75"></circle>
                <circle class="circle-progress" cx="90" cy="90" r="75" stroke-dasharray="471" stroke-dashoffset="471">
                </circle>
            </svg>
            <div class="loader-text" id="progressText">0%</div>
        </div>
        <div class="progress-label" id="progressLabel">Processing Request</div>
    </div>

    <script>
        function switchAccount(index) {
            // Hide all account contents
            document.querySelectorAll('.account-content').forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('block');
            });

            // Reset all tabs
            document.querySelectorAll('.account-tab').forEach(tab => {
                tab.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'hover:bg-blue-700');
                tab.classList.add('bg-white', 'hover:bg-gray-50');
            });

            // Show selected account content
            document.getElementById(`account-${index}`).classList.remove('hidden');
            document.getElementById(`account-${index}`).classList.add('block');

            // Style selected tab
            const selectedTab = document.querySelectorAll('.account-tab')[index];
            selectedTab.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'hover:bg-blue-700');
            selectedTab.classList.remove('bg-white', 'hover:bg-gray-50');
        }

        function toggleEmailBody(element, accountIndex, emailUid) {
            const emailBody = document.getElementById(`email-body-${accountIndex}-${emailUid}`);
            emailBody.classList.toggle('hidden');

            // Mark as read
            if (element.classList.contains('bg-blue-50')) {
                element.classList.remove('bg-blue-50');
            }
        }

        function processUnsubscribe(email, accountIndex, emailUid) {
            if (!confirm(`Are you sure you want to unsubscribe ${email}? This will add them to your blocklist.`)) {
                return;
            }

            showProgress('Processing unsubscribe...');
            simulateProgress();

            // In a real application, you would make an AJAX call here
            setTimeout(() => {
                hideProgress();
                alert(`Success: ${email} has been unsubscribed.`);

                // Remove the email from view
                const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                emailItem.remove();
                document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();
            }, 2000);
        }

        function processBounce(email, accountIndex, emailUid) {
            if (!confirm(`This email to ${email} bounced. Do you want to remove this email from your mailing list?`)) {
                return;
            }

            showProgress('Processing bounce...');
            simulateProgress();

            // In a real application, you would make an AJAX call here
            setTimeout(() => {
                hideProgress();
                alert(`Success: ${email} has been removed due to bounce.`);

                // Remove the email from view
                const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                emailItem.remove();
                document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();
            }, 2000);
        }

        function showProgress(label) {
            document.getElementById('progressLabel').textContent = label;
            document.getElementById('progressOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideProgress() {
            document.getElementById('progressOverlay').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function simulateProgress() {
            let percent = 0;
            const interval = setInterval(() => {
                percent += 5;
                if (percent > 100) {
                    clearInterval(interval);
                    return;
                }
                updateProgress(percent);
            }, 100);
        }

        function updateProgress(percent) {
            const circumference = 471;
            const offset = circumference - (circumference * percent / 100);
            document.querySelector('.circle-progress').style.strokeDashoffset = offset;
            document.getElementById('progressText').textContent = `${percent}%`;
        }
    </script>
</body>

</html>