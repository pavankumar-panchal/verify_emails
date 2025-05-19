<?php
// DB Connection with error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1); // Log instead of display
error_reporting(E_ALL);


// error_reporting(0);
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get active SMTP servers
$smtps = $db->query("SELECT id, name, host, email, password FROM smtp_servers WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

function fetchReplies($smtp)
{
    if (!function_exists('imap_open')) {
        return ["error" => "IMAP extension is not enabled."];
    }

    if (!isset($smtp['host'], $smtp['email'], $smtp['password'])) {
        return ["error" => "SMTP details are missing."];
    }

    $host = trim($smtp['host']);
    $email = trim($smtp['email']);
    $password = trim($smtp['password']);

    // Use POP3 SSL connection string
    $mailbox = "{" . $host . ":995/pop3/ssl/novalidate-cert}INBOX";

    // Timeout settings
    ini_set('default_socket_timeout', 10);
    set_time_limit(15); // Total execution time cap

    $messages = [
        'regular' => [],
        'unsubscribes' => [],
        'bounces' => []
    ];

    try {
        $inbox = @imap_open($mailbox, $email, $password, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI'
        ]);

        if (!$inbox) {
            return ["error" => imap_last_error() ?: "IMAP connection failed."];
        }

        // Fetch unseen emails from last 7 days
        $emails = @imap_search($inbox, 'UNSEEN SINCE "' . date('j F Y', strtotime('-7 days')) . '"');

        if ($emails && is_array($emails)) {
            $emails = array_slice($emails, 0, 20); // Limit for performance

            foreach ($emails as $email_number) {
                $overview = @imap_fetch_overview($inbox, $email_number, 0)[0] ?? null;
                $headers_raw = @imap_fetchheader($inbox, $email_number) ?: '';
                if (!$overview)
                    continue;

                $subject = $overview->subject ?? '';
                $from = $overview->from ?? '';
                $date = $overview->date ?? '';
                $uid = $overview->uid ?? $email_number;

                // Get from email
                $from_email = '';
                if (
                    preg_match('/From:.*<([^>]+)>/i', $headers_raw, $matches) ||
                    preg_match('/From:[^\n]*([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/i', $headers_raw, $matches)
                ) {
                    $from_email = trim($matches[1]);
                }

                // Detect bounce
                $is_bounce = false;
                $bounce_reason = '';
                if (
                    stripos($subject, 'undeliverable') !== false ||
                    stripos($subject, 'returned') !== false ||
                    stripos($subject, 'failure') !== false ||
                    stripos($subject, 'bounce') !== false
                ) {
                    $is_bounce = true;
                    $bounce_reason = 'Detected in subject';
                } elseif (preg_match('/X-Failed-Recipients:\s*(.*)/i', $headers_raw, $matches)) {
                    $is_bounce = true;
                    $bounce_reason = 'Detected in headers';
                }

                $body_text = '';
                $is_unsubscribe = false;
                $unsubscribe_method = '';

                if (!$is_bounce) {
                    if (preg_match('/List-Unsubscribe:\s*(.*)/i', $headers_raw)) {
                        $is_unsubscribe = true;
                        $unsubscribe_method = 'List-Unsubscribe header';
                    } else {
                        // Try part 1 and fallback
                        $body = @imap_fetchbody($inbox, $email_number, 1);
                        if (empty($body)) {
                            $body = @imap_fetchbody($inbox, $email_number, 1.1);
                        }
                        $body_text = quoted_printable_decode($body);

                        if (
                            stripos($body_text, 'unsubscribe') !== false ||
                            stripos($subject, 'unsubscribe') !== false
                        ) {
                            $is_unsubscribe = true;
                            $unsubscribe_method = 'Email content';
                        }
                    }
                }

                $message = [
                    "from" => $from,
                    "from_email" => $from_email,
                    "subject" => $subject,
                    "date" => $date,
                    "body" => substr(strip_tags($body_text), 0, 500) . "...",
                    "uid" => $uid,
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

    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
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
            max-height: 300px;
            overflow-y: auto;
        }

        .progress-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .tab-loader {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 8px;
        }

        .account-content {
            display: none;
        }

        .account-content.active {
            display: block;
        }

        .unsubscribe-badge {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .bounce-badge {
            background-color: #fef3c7;
            color: #92400e;
        }

        .email-item.unseen {
            background-color: #f0f9ff;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require "navbar.php"; ?>

    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-inbox text-blue-500"></i> Email Reply Dashboard
                </h1>
                <p class="text-sm text-gray-500 mt-1">Manage email replies and bounces</p>
            </div>
            <button onclick="refreshData()" id="refreshBtn" class="btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        <!-- Account Tabs -->
        <div class="mb-6 overflow-x-auto">
            <div class="flex gap-2 pb-2">
                <?php foreach ($smtps as $index => $smtp): ?>
                    <button onclick="switchAccount(<?= $index ?>)"
                        class="account-tab px-4 py-2 rounded-md border transition flex items-center
                                   <?= $index === 0 ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200 hover:bg-gray-50' ?>">
                        <i class="fas fa-envelope mr-2"></i>
                        <?= htmlspecialchars($smtp['name']) ?>
                        <span id="tab-loader-<?= $index ?>" class="tab-loader hidden"></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Account Content -->
        <?php foreach ($smtps as $index => $smtp): ?>
            <div id="account-<?= $index ?>" class="account-content <?= $index === 0 ? 'active' : '' ?>">
                <!-- Content will be loaded dynamically -->
                <div class="text-center py-12 bg-white rounded-lg shadow border border-gray-200">
                    <div class="spinner mx-auto mb-4"></div>
                    <p>Loading email data...</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Progress Modal -->
    <div id="progressModal" class="progress-overlay hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl">
            <div class="flex items-center gap-4">
                <div class="spinner"></div>
                <div>
                    <h3 class="font-medium text-gray-900" id="progressTitle">Processing</h3>
                    <p class="text-sm text-gray-500" id="progressMessage">Please wait...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentAccount = 0;
        let emailData = {};

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            // Load first account immediately
            loadAccountData(0);

            // Set up click handlers for other tabs
            document.querySelectorAll('.account-tab').forEach((tab, index) => {
                if (index !== 0) {
                    tab.addEventListener('click', () => {
                        if (!emailData[index]) {
                            loadAccountData(index);
                        }
                    });
                }
            });
        });

        // Load account data via AJAX
        function loadAccountData(accountIndex) {
            const tabLoader = document.getElementById(`tab-loader-${accountIndex}`);
            const accountContent = document.getElementById(`account-${accountIndex}`);

            tabLoader.classList.remove('hidden');
            accountContent.innerHTML = `
                <div class="text-center py-12 bg-white rounded-lg shadow border border-gray-200">
                    <div class="spinner mx-auto mb-4"></div>
                    <p>Loading email data...</p>
                </div>
            `;

            fetch(`received_email/get_emails.php?account_id=${accountIndex}`)
                .then(response => response.json())
                .then(data => {
                    emailData[accountIndex] = data;
                    renderAccountContent(accountIndex, data);
                    tabLoader.classList.add('hidden');
                })
                .catch(error => {
                    accountContent.innerHTML = `
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>Error loading data:</strong> ${error.message}
                            </div>
                        </div>
                    `;
                    tabLoader.classList.add('hidden');
                });
        }

        // Render account content from data
        function renderAccountContent(accountIndex, data) {
            const accountContent = document.getElementById(`account-${accountIndex}`);

            if (data.error) {
                accountContent.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Connection Error:</strong> ${data.error}
                        </div>
                    </div>
                `;
                return;
            }

            if (!data.regular?.length && !data.unsubscribes?.length && !data.bounces?.length) {
                accountContent.innerHTML = `
                    <div class="text-center py-12 bg-white rounded-lg shadow border border-gray-200">
                        <i class="fas fa-envelope-open text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-1">No new emails</h3>
                        <p class="text-gray-500">There are no unread messages in this inbox.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="space-y-6">';

            // Regular Messages
            if (data.regular?.length) {
                html += renderEmailSection(
                    'regular',
                    'fa-inbox text-blue-500',
                    'Regular Messages',
                    data.regular,
                    accountIndex
                );
            }

            // Unsubscribe Requests
            if (data.unsubscribes?.length) {
                html += renderEmailSection(
                    'unsubscribes',
                    'fa-user-minus text-red-500',
                    'Unsubscribe Requests',
                    data.unsubscribes,
                    accountIndex,
                    'border-l-red-500 bg-red-50',
                    true
                );
            }

            // Bounced Emails
            if (data.bounces?.length) {
                html += renderEmailSection(
                    'bounces',
                    'fa-exclamation-triangle text-yellow-500',
                    'Bounced Emails',
                    data.bounces,
                    accountIndex,
                    'border-l-yellow-500 bg-yellow-50',
                    false,
                    true
                );
            }

            html += '</div>';
            accountContent.innerHTML = html;

            // Add event listeners for toggling email bodies
            document.querySelectorAll(`#account-${accountIndex} .email-item`).forEach(item => {
                const uid = item.dataset.uid;
                item.addEventListener('click', () => {
                    const body = document.getElementById(`email-body-${accountIndex}-${uid}`);
                    body.classList.toggle('hidden');

                    // Mark as read visually
                    if (item.classList.contains('unseen')) {
                        item.classList.remove('unseen');
                    }
                });
            });
        }

        // Render an email section
        function renderEmailSection(type, icon, title, emails, accountIndex, sectionClasses = '', isUnsubscribe = false, isBounce = false) {
            let html = `
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-700 flex items-center gap-2">
                        <i class="fas ${icon}"></i> ${title} (${emails.length})
                    </h3>
                    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 ${sectionClasses}">
                        <div class="grid grid-cols-12 gap-4 p-4 border-b border-gray-200 font-medium text-gray-600">
                            <div class="col-span-1"></div>
                            <div class="col-span-3 md:col-span-2">Sender</div>
                            <div class="col-span-6 md:col-span-7">Subject</div>
                            <div class="col-span-2 md:col-span-2 text-right">Date</div>
                        </div>
            `;

            emails.forEach(email => {
                const initials = getInitials(email.from || email.from_email);
                const date = formatDate(email.date);
                const isUnseen = !email.seen;

                html += `
                    <div class="email-item grid grid-cols-12 gap-4 p-4 border-b border-gray-200 hover:bg-gray-50 transition cursor-pointer ${isUnseen ? 'unseen' : ''}"
                         data-uid="${email.uid}">
                        <div class="col-span-1 flex items-center">
                            <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500">
                        </div>
                        <div class="col-span-3 md:col-span-2 flex items-center gap-2 overflow-hidden">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-medium text-sm">
                                ${initials}
                            </div>
                            <span class="truncate" title="${escapeHtml(email.from || email.from_email)}">
                                ${escapeHtml(email.from || email.from_email)}
                            </span>
                        </div>
                        <div class="col-span-6 md:col-span-7 flex items-center font-medium truncate">
                            ${escapeHtml(email.subject)}
                            ${isUnsubscribe ? `
                                <span class="ml-2 text-xs px-2 py-1 rounded-full unsubscribe-badge">
                                    <i class="fas fa-ban mr-1"></i>Unsubscribe
                                </span>
                            ` : ''}
                            ${isBounce ? `
                                <span class="ml-2 text-xs px-2 py-1 rounded-full bounce-badge">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Bounced
                                </span>
                            ` : ''}
                        </div>
                        <div class="col-span-2 md:col-span-2 text-right text-sm text-gray-500">
                            ${date}
                        </div>
                    </div>
                    
                    <div class="email-body hidden" id="email-body-${accountIndex}-${email.uid}">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-bold mb-2">${escapeHtml(email.subject)}</h3>
                                    <p class="text-gray-700 mb-1">
                                        <strong class="text-gray-600">From:</strong>
                                        ${escapeHtml(email.from ? email.from + ' &lt;' + email.from_email + '&gt;' : email.from_email)}
                                    </p>
                                    <p class="text-gray-700">
                                        <strong class="text-gray-600">Date:</strong>
                                        ${formatDate(email.date, true)}
                                    </p>
                                    ${isUnsubscribe ? `
                                        <p class="text-red-600 mt-2">
                                            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Unsubscribe Request:</strong>
                                            Detected via ${escapeHtml(email.unsubscribe_method)}
                                        </p>
                                    ` : ''}
                                    ${isBounce ? `
                                        <p class="text-yellow-600 mt-2">
                                            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Bounce Reason:</strong>
                                            ${escapeHtml(email.bounce_reason)}
                                        </p>
                                    ` : ''}
                                </div>
                                <div class="flex gap-2 mt-4 md:mt-0">
                                    ${isUnsubscribe ? `
                                        <button onclick="processUnsubscribe('${escapeHtml(email.from_email)}', ${accountIndex}, '${email.uid}')"
                                            class="btn-danger">
                                            <i class="fas fa-user-minus"></i> Process Unsubscribe
                                        </button>
                                    ` : ''}
                                    ${isBounce ? `
                                        <button onclick="processBounce('${escapeHtml(email.from_email)}', ${accountIndex}, '${email.uid}')"
                                            class="btn-warning">
                                            <i class="fas fa-trash-alt"></i> Remove Bounced
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="email-body-content bg-gray-50 p-4 rounded-md">
                                ${nl2br(escapeHtml(email.body))}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div></div>`;
            return html;
        }

        // Switch between accounts
        function switchAccount(index) {
            if (index === currentAccount) return;

            // Update UI
            document.querySelectorAll('.account-tab').forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                    tab.classList.remove('bg-white', 'border-gray-200', 'hover:bg-gray-50');
                } else {
                    tab.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                    tab.classList.add('bg-white', 'border-gray-200', 'hover:bg-gray-50');
                }
            });

            document.querySelectorAll('.account-content').forEach((content, i) => {
                content.classList.toggle('active', i === index);
            });

            currentAccount = index;

            // Load data if not already loaded
            if (!emailData[index]) {
                loadAccountData(index);
            }
        }

        // Refresh all data
        function refreshData() {
            const btn = document.getElementById('refreshBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing';
            btn.disabled = true;

            // Reload current account
            loadAccountData(currentAccount);

            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                btn.disabled = false;
            }, 1000);
        }

        // Process unsubscribe
        function processUnsubscribe(email, accountIndex, emailUid) {
            if (!confirm(`Are you sure you want to unsubscribe ${email}? This will add them to your blocklist.`)) {
                return;
            }

            showProgress('Processing unsubscribe', `Removing ${email} from mailing lists...`);

            // Simulate API call
            setTimeout(() => {
                hideProgress();

                // Remove from UI
                const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                emailItem.remove();
                document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();

                // Show success message
                alert(`Success: ${email} has been unsubscribed.`);
            }, 1500);
        }

        // Process bounce
        function processBounce(email, accountIndex, emailUid) {
            if (!confirm(`This email to ${email} bounced. Remove from mailing list?`)) {
                return;
            }

            showProgress('Processing bounce', `Removing ${email} due to bounce...`);

            // Simulate API call
            setTimeout(() => {
                hideProgress();

                // Remove from UI
                const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                emailItem.remove();
                document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();

                // Show success message
                alert(`Success: ${email} has been removed due to bounce.`);
            }, 1500);
        }

        // Helper functions
        function showProgress(title, message) {
            document.getElementById('progressTitle').textContent = title;
            document.getElementById('progressMessage').textContent = message;
            document.getElementById('progressModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideProgress() {
            document.getElementById('progressModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function getInitials(name) {
            if (!name) return '?';
            const parts = name.split(' ');
            let initials = '';
            for (const part of parts) {
                if (/[a-zA-Z]/.test(part.charAt(0))) {
                    initials += part.charAt(0).toUpperCase();
                    if (initials.length >= 2) break;
                }
            }
            return initials || '?';
        }

        function formatDate(dateString, full = false) {
            if (!dateString) return '';

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

                if (full) {
                    return date.toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                }

                if (diffDays === 0) {
                    return date.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                } else if (diffDays === 1) {
                    return 'Yesterday';
                } else if (diffDays < 7) {
                    return date.toLocaleDateString('en-US', { weekday: 'short' });
                } else {
                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric'
                    });
                }
            } catch (e) {
                return dateString;
            }
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function nl2br(str) {
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
        }



        // Process unsubscribe
        function processUnsubscribe(email, accountIndex, emailUid) {
            if (!confirm(`Are you sure you want to unsubscribe ${email}? This will add them to your blocklist.`)) {
                return;
            }

            showProgress('Processing unsubscribe', `Removing ${email} from mailing lists...`);

            fetch('process_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=unsubscribe&email=${encodeURIComponent(email)}&account_id=${accountIndex}&uid=${emailUid}`
            })
                .then(response => response.json())
                .then(data => {
                    hideProgress();
                    if (data.success) {
                        // Remove from UI
                        const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                        emailItem.remove();
                        document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();

                        // Show success message
                        alert(`Success: ${email} has been unsubscribed.`);
                    } else {
                        alert(`Error: ${data.error || 'Failed to process unsubscribe'}`);
                    }
                })
                .catch(error => {
                    hideProgress();
                    alert(`Error: ${error.message}`);
                });
        }

        // Process bounce
        function processBounce(email, accountIndex, emailUid) {
            if (!confirm(`This email to ${email} bounced. Remove from mailing list?`)) {
                return;
            }

            showProgress('Processing bounce', `Removing ${email} due to bounce...`);

            fetch('process_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=bounce&email=${encodeURIComponent(email)}&account_id=${accountIndex}&uid=${emailUid}`
            })
                .then(response => response.json())
                .then(data => {
                    hideProgress();
                    if (data.success) {
                        // Remove from UI
                        const emailItem = document.querySelector(`#email-body-${accountIndex}-${emailUid}`).previousElementSibling;
                        emailItem.remove();
                        document.getElementById(`email-body-${accountIndex}-${emailUid}`).remove();

                        // Show success message
                        alert(`Success: ${email} has been removed due to bounce.`);
                    } else {
                        alert(`Error: ${data.error || 'Failed to process bounce'}`);
                    }
                })
                .catch(error => {
                    hideProgress();
                    alert(`Error: ${error.message}`);
                });
        }
    </script>
</body>

</html>