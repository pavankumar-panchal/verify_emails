<?php
// Database connection to get SMTP servers
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Get active SMTP servers
$result = $conn->query("SELECT * FROM smtp_servers WHERE is_active = TRUE");
$smtpServers = [];
while ($row = $result->fetch_assoc()) {
  $smtpServers[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Bulk Emails</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-light: #6366f1;
      --primary-dark: #4338ca;
      --secondary: #f9fafb;
      --text: #111827;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #f3f4f6;
      color: var(--text);
    }

    .navbar {
      background-color: white;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 50;
    }

    .email-container {
      background: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
      transition: all 0.2s ease;
    }

    .email-container:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .progress-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .progress-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .progress-card {
      background: white;
      border-radius: 0.75rem;
      width: 100%;
      max-width: 28rem;
      padding: 1.5rem;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .progress-overlay.active .progress-card {
      transform: translateY(0);
    }

    .progress-bar {
      height: 0.5rem;
      background-color: #e5e7eb;
      border-radius: 0.25rem;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background-color: var(--primary);
      width: 0%;
      transition: width 0.3s ease;
    }

    .attachment-chip {
      background-color: #f0f9ff;
      color: #0369a1;
      border-radius: 9999px;
      padding: 0.25rem 0.75rem;
      font-size: 0.875rem;
      display: inline-flex;
      align-items: center;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .attachment-chip button {
      color: #0284c7;
      margin-left: 0.5rem;
      background: none;
      border: none;
      cursor: pointer;
    }

    @media (max-width: 640px) {
      .responsive-flex {
        flex-direction: column;
        align-items: flex-start;
      }

      .responsive-flex>* {
        width: 100%;
      }
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

<body class="antialiased">
  <!-- Navbar -->
  <nav class="navbar">
    <div class="navbar-container">
      <a href="#" class="navbar-brand">
        <i class="fas fa-envelope mr-2"></i>Email
      </a>
      <div class="navbar-links">
        <a href="index.php" class="nav-link">
          <i class="fas fa-check-circle mr-2"></i>Verification
        </a>
        <a href="send_email.php" class="nav-link active">
          <i class="fas fa-paper-plane mr-2"></i>Send Emails
        </a>
        <a href="smtp_records.php" class="nav-link ">
          <i class="fas fa-server mr-2"></i>SMTP Servers
        </a>
      </div>
    </div>
  </nav>

  <!-- Progress Overlay -->
  <div id="progressOverlay" class="progress-overlay">
    <div class="progress-card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900" id="progressMessage">Sending emails...</h3>
        <span class="text-sm font-medium text-indigo-600" id="progressPercent">0%</span>
      </div>
      <div class="progress-bar mb-2">
        <div id="progressBar" class="progress-fill"></div>
      </div>
      <div class="flex justify-between text-sm text-gray-500">
        <span id="progressCount">0 emails sent</span>
        <span id="progressTotal">0 total</span>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 mt-14">
    <div class="email-container p-6 sm:p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
          <i class="fas fa-paper-plane mr-3 text-indigo-600"></i>
          Send Emails
        </h1>
      </div>


      <form id="emailForm" enctype="multipart/form-data" class="space-y-6">
        <!-- SMTP Server Selection -->
        <div>
          <label for="smtpServerSelect" class="block text-sm font-medium text-gray-700 mb-2">SMTP Server</label>
          <div class="relative">
            <select name="selected_smtp_server" id="smtpServerSelect" required
              class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
              <option value="">-- Select SMTP Server --</option>
              <?php foreach ($smtpServers as $server): ?>
                <option value="<?= $server['id'] ?>">
                  <?= htmlspecialchars($server['name']) ?> (<?= htmlspecialchars($server['email']) ?>)
                  - Daily limit: <?= $server['daily_limit'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="mt-1 text-xs text-gray-500">Select the SMTP server</p>
        </div>

        <!-- Sender Info and Subject in one row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sender Name</label>
            <input type="text" name="sender_name" required
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Your Company Name" value="Relyonsoft">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
            <input type="text" name="subject" required
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Your email subject line">
          </div>
        </div>

        <!-- Email Body -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email Content</label>
          <textarea name="body" rows="12" required
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Compose your email here..."></textarea>
        </div>

        <!-- Attachments -->
        <div class="border-t border-gray-200 pt-4">
          <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Attachments</label>
            <span class="text-xs text-gray-500">Max 25MB total</span>
          </div>
          <div id="attachment-container" class="flex flex-wrap mb-3"></div>
          <div class="flex">
            <input type="file" id="file-input" name="attachments[]" multiple class="hidden">
            <button type="button" id="add-attachment"
              class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
              <i class="fas fa-paperclip mr-1"></i> Add Attachment
            </button>
            <span id="file-names" class="ml-3 text-sm text-gray-500 self-center"></span>
          </div>
        </div>

        <!-- Send Button -->
        <div class="flex justify-end pt-2">
          <button type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-paper-plane mr-2"></i> Send Emails
          </button>
        </div>
      </form>
    </div>
  </main>

  <script>
    // File attachments handling
    document.getElementById('add-attachment').addEventListener('click', () => {
      document.getElementById('file-input').click();
    });

    document.getElementById('file-input').addEventListener('change', function (e) {
      const container = document.getElementById('attachment-container');
      const fileNames = document.getElementById('file-names');
      container.innerHTML = '';

      if (this.files.length === 0) {
        fileNames.textContent = '';
        return;
      }

      fileNames.textContent = `${this.files.length} file(s) selected`;

      Array.from(this.files).forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'attachment-chip';
        chip.innerHTML = `
          <span>${file.name}</span>
          <button type="button" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
          </button>
        `;
        container.appendChild(chip);
      });
    });

    // Form submission
    document.getElementById('emailForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const selectedServer = document.getElementById('smtpServerSelect').value;

      if (!selectedServer) {
        alert('Please select an SMTP server');
        return;
      }

      // Show progress overlay
      const overlay = document.getElementById('progressOverlay');
      overlay.classList.add('active');

      try {
        const response = await fetch('send_email.php', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const data = await response.json();

        if (data.status === 'success') {
          updateProgress(data.sent, data.sent + data.failed);
          document.getElementById('progressMessage').textContent = 'Emails sent successfully!';
          document.getElementById('progressPercent').textContent = '100%';
          document.getElementById('progressCount').textContent = `${data.sent} emails sent`;

          setTimeout(() => {
            overlay.classList.remove('active');
            if (data.failed > 0) {
              alert(`Successfully sent ${data.sent} emails. ${data.failed} failed. Check the log for details.`);
            } else {
              alert(`All ${data.sent} emails sent successfully!`);
            }
          }, 2000);
        } else {
          throw new Error(data.message || 'Error sending emails');
        }
      } catch (error) {
        console.error('Error:', error);
        document.getElementById('progressMessage').textContent = 'Error sending emails';
        document.getElementById('progressMessage').className += ' text-red-600';
        document.getElementById('progressPercent').textContent = 'Failed';

        setTimeout(() => {
          overlay.classList.remove('active');
          alert('Error: ' + error.message);
        }, 2000);
      }
    });

    function updateProgress(processed, total) {
      const percent = Math.round((processed / total) * 100);
      document.getElementById('progressBar').style.width = `${percent}%`;
      document.getElementById('progressPercent').textContent = `${percent}%`;
      document.getElementById('progressCount').textContent = `${processed} emails sent`;
      document.getElementById('progressTotal').textContent = `${total} total`;
    }
  </script>
</body>

</html>