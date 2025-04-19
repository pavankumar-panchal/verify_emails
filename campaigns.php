<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Initialize message variables
$message = '';
$message_type = ''; // 'success' or 'error'

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_campaign'])) {
        // Add new campaign
        $description = $conn->real_escape_string($_POST['description']);
        $mail_subject = $conn->real_escape_string($_POST['mail_subject']);
        $mail_body = $conn->real_escape_string($_POST['mail_body']);

        $sql = "INSERT INTO campaign_master (description, mail_subject, mail_body) 
                VALUES ('$description', '$mail_subject', '$mail_body')";

        if ($conn->query($sql)) {
            $message = 'Campaign added successfully!';
            $message_type = 'success';
            header("Location: campaigns.php?message=" . urlencode($message) . "&message_type=$message_type");
            exit();
        } else {
            $message = 'Error adding campaign: ' . $conn->error;
            $message_type = 'error';
        }
    } elseif (isset($_POST['update_campaign'])) {
        // Update existing campaign
        $campaign_id = intval($_POST['campaign_id']);
        $description = $conn->real_escape_string($_POST['description']);
        $mail_subject = $conn->real_escape_string($_POST['mail_subject']);
        $mail_body = $conn->real_escape_string($_POST['mail_body']);

        $sql = "UPDATE campaign_master SET 
                description = '$description',
                mail_subject = '$mail_subject',
                mail_body = '$mail_body'
                WHERE campaign_id = $campaign_id";

        if ($conn->query($sql)) {
            $message = 'Campaign updated successfully!';
            $message_type = 'success';
            header("Location: campaigns.php?message=" . urlencode($message) . "&message_type=$message_type");
            exit();
        } else {
            $message = 'Error updating campaign: ' . $conn->error;
            $message_type = 'error';
        }
    }
} elseif (isset($_GET['delete'])) {
    // Delete campaign
    $campaign_id = intval($_GET['delete']);
    $sql = "DELETE FROM campaign_master WHERE campaign_id = $campaign_id";

    if ($conn->query($sql)) {
        $message = 'Campaign deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error deleting campaign: ' . $conn->error;
        $message_type = 'error';
    }
    header("Location: campaigns.php?message=" . urlencode($message) . "&message_type=$message_type");
    exit();
}

// Check for messages in URL parameters
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}

// Get all campaigns
$result = $conn->query("SELECT * FROM campaign_master ORDER BY campaign_id DESC");
$campaigns = [];
while ($row = $result->fetch_assoc()) {
    $campaigns[] = $row;
}

// Get campaign for editing if edit parameter is set
$editCampaign = null;
if (isset($_GET['edit'])) {
    $campaign_id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM campaign_master WHERE campaign_id = $campaign_id");
    $editCampaign = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Campaigns</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: #3b82f6;
            text-decoration: none;
        }

        .navbar-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
        }

        .nav-link.active {
            background-color: #3b82f6;
            color: white;
        }

        .nav-link.active:hover {
            background-color: #2563eb;
        }
    </style>
</head>

<body class="bg-gray-100">


    <div class="container mx-auto px-4 py-8">
    <?php include 'navbar.php'; ?>

 


        <!-- Status Message -->
        <?php if ($message): ?>
            <div class="alert-<?= $message_type ?> p-4 mb-6 rounded-md shadow-sm flex items-start">
                <div class="ml-3">
                    <p class="text-sm font-medium">
                        <?= htmlspecialchars($message) ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-bullhorn mr-2 text-blue-600"></i>
                Email Campaigns
            </h1>
            <button onclick="document.getElementById('addCampaignModal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-plus mr-2"></i> Add Campaign
            </button>
        </div>

        <!-- Campaigns Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $campaign['campaign_id'] ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($campaign['description']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($campaign['mail_subject']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?edit=<?= $campaign['campaign_id'] ?>#editCampaignModal"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <a href="#" onclick="confirmDelete(<?= $campaign['campaign_id'] ?>)"
                                    class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No campaigns found. Add one to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Campaign Modal -->
        <div id="addCampaignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-1/2 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-plus-circle mr-2 text-blue-600"></i>
                        Add New Campaign
                    </h3>
                    <button onclick="document.getElementById('addCampaignModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <input type="text" name="description" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Campaign description">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject</label>
                        <input type="text" name="mail_subject" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Your email subject">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Body</label>
                        <textarea name="mail_body" rows="8" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Compose your email content here..."></textarea>
                    </div>

                    <div class="flex justify-end pt-4 space-x-3">
                        <button type="button"
                            onclick="document.getElementById('addCampaignModal').classList.add('hidden')"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" name="add_campaign"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i> Save Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Campaign Modal -->
        <?php if ($editCampaign): ?>
            <div id="editCampaignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-1/2 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-edit mr-2 text-blue-600"></i>
                            Edit Campaign
                        </h3>
                        <a href="campaigns.php" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="campaign_id" value="<?= $editCampaign['campaign_id'] ?>">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                value="<?= htmlspecialchars($editCampaign['description']) ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject</label>
                            <input type="text" name="mail_subject" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                value="<?= htmlspecialchars($editCampaign['mail_subject']) ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Body</label>
                            <textarea name="mail_body" rows="8" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= htmlspecialchars($editCampaign['mail_body']) ?></textarea>
                        </div>

                        <div class="flex justify-end pt-4 space-x-3">
                            <a href="campaigns.php"
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                            <button type="submit" name="update_campaign"
                                class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i> Update Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Confirm delete function
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this campaign?')) {
                window.location.href = 'campaigns.php?delete=' + id;
            }
        }

        // Scroll to edit modal if it exists
        <?php if ($editCampaign): ?>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('editCampaignModal').scrollIntoView({ behavior: 'smooth' });
            });
        <?php endif; ?>

        // Auto-hide success message after 5 seconds
        <?php if ($message_type === 'success'): ?>
            setTimeout(() => {
                const alert = document.querySelector('.alert-success');
                if (alert) alert.remove();
            }, 5000);
        <?php endif; ?>
    </script>
</body>

</html>