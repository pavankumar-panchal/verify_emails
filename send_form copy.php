<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Bulk Emails</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Google Sans', Roboto, Arial, sans-serif;
      background: #f6f8fc;
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
    .email-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 1px 2px 0 rgba(60,64,67,0.302), 0 2px 6px 2px rgba(60,64,67,0.149);
      margin-top: 80px;
    }
    .input-field {
      border: none;
      border-bottom: 1px solid #e0e0e0;
      border-radius: 0;
    }
    .input-field:focus {
      outline: none;
      box-shadow: none;
      border-bottom: 2px solid #1a73e8;
    }
    .send-btn {
      background: #0b57d0;
      color: white;
      border-radius: 20px;
      font-weight: 500;
      letter-spacing: 0.25px;
    }
    .send-btn:hover {
      background: #1b61d1;
      box-shadow: 0 1px 2px 0 rgba(60,64,67,0.302), 0 1px 3px 1px rgba(60,64,67,0.149);
    }
    .editor-toolbar {
      border-bottom: 1px solid #e0e0e0;
    }
    .attachment-chip {
      background: #e8f0fe;
      color: #174ea6;
      border-radius: 16px;
      padding: 4px 8px;
      font-size: 12px;
      margin-right: 8px;
      display: inline-flex;
      align-items: center;
    }
    .attachment-chip button {
      margin-left: 4px;
      color: #174ea6;
      background: none;
      border: none;
      cursor: pointer;
    }
    .server-select {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 16px;
    }
    .server-option {
      display: flex;
      align-items: center;
      padding: 8px;
      border-radius: 4px;
      margin-bottom: 4px;
      cursor: pointer;
    }
    .server-option:hover {
      background-color: #f3f4f6;
    }
    .server-option input {
      margin-right: 8px;
    }
    .server-details {
      margin-left: 24px;
      font-size: 12px;
      color: #666;
    }
  </style>
</head>
<body>
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
        <a href="send_form.php" class="nav-link active">
          <i class="fas fa-paper-plane mr-2"></i>Send Emails
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mx-auto px-4 max-w-4xl">
    <!-- Email Form -->
    <div class="email-container p-6 mt-20">
      <h1 class="text-2xl font-normal text-gray-800 mb-6 flex items-center">
        <i class="fas fa-paper-plane mr-3 text-blue-500"></i>
        Send Emails
      </h1>
      
      <form action="send_email.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <!-- From Field -->

        <div class="server-select">
          <label for="mail_server">Mail Server:</label>
          <select id="mail_server" name="mail_server" required>
            <option value="">-- Select a Mail Server --</option>
            <option value="1" selected>SMTP1</option>
            <option value="2">SMTP2</option>
            <option value="3">SMTP3</option>
            <option value="4">SMTP4</option>
            <option value="5">SMTP5</option>
          </select>
        </div>

        
        <!-- <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">From Email:</span>
          <input type="email" name="sender_email" required 
                class="flex-1 input-field py-2 px-1 text-gray-800" 
                placeholder="your.email@example.com">
        </div> -->

        <!-- Sender Name -->
        <!-- <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">Sender Name:</span>
          <input type="text" name="sender_name" required 
                class="flex-1 input-field py-2 px-1 text-gray-800" 
                placeholder="Your Name">
        </div> -->

        <!-- Password -->
        <!-- <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">Password:</span>
          <input type="password" name="sender_password" required 
                class="flex-1 input-field py-2 px-1 text-gray-800" 
                placeholder="••••••••">
          <button type="button" class="text-sm text-blue-600 hover:text-blue-800 ml-2 toggle-password">
            Show
          </button>
        </div> -->

        <!-- Subject -->
        <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">Subject:</span>
          <input type="text" name="subject" required 
                class="flex-1 input-field py-2 px-1 text-gray-800" 
                placeholder="Subject">
        </div>

        <!-- Email Body -->
        <div class="mt-4">
          <!-- Simple Editor Toolbar -->
          <div class="editor-toolbar py-2 flex space-x-2">
            <button type="button" class="p-1 text-gray-600 hover:bg-gray-100 rounded">
              <i class="fas fa-bold"></i>
            </button>
            <button type="button" class="p-1 text-gray-600 hover:bg-gray-100 rounded">
              <i class="fas fa-italic"></i>
            </button>
            <button type="button" class="p-1 text-gray-600 hover:bg-gray-100 rounded">
              <i class="fas fa-underline"></i>
            </button>
            <button type="button" class="p-1 text-gray-600 hover:bg-gray-100 rounded">
              <i class="fas fa-link"></i>
            </button>
          </div>
          <textarea name="body" rows="12" required 
                    class="w-full p-3 text-gray-800 focus:outline-none resize-none"
                    placeholder="Compose your email here..."></textarea>
        </div>

        <!-- Attachments -->
        <div class="mt-4">
          <div id="attachment-container" class="flex flex-wrap gap-2 mb-2"></div>
          <input type="file" id="file-input" name="attachments[]" multiple style="display: none;">
          <button type="button" id="add-attachment" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
            <i class="fas fa-paperclip mr-1"></i> Add Attachment
          </button>
        </div>

        <!-- Send Button -->
        <div class="flex justify-between items-center pt-4">
          <div class="flex space-x-2">
            <button type="button" id="add-image" class="p-2 text-gray-600 hover:bg-gray-100 rounded-full">
              <i class="fas fa-image"></i>
            </button>
          </div>
          <button type="submit" class="send-btn px-6 py-2">
            Send Emails
          </button>
        </div>
      </form>
    </div>

    <!-- Status Bar -->
    <div class="mt-4 text-sm text-gray-500 flex items-center">
      <i class="fas fa-info-circle mr-2"></i>
      <span>All emails will be sent securely using SMTP protocol</span>
    </div>
  </div>

  <script>
    // Toggle password visibility
    document.querySelector('.toggle-password').addEventListener('click', function() {
      const passwordField = document.querySelector('input[name="sender_password"]');
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        this.textContent = 'Hide';
      } else {
        passwordField.type = 'password';
        this.textContent = 'Show';
      }
    });

    // Simple rich text formatting
    document.querySelectorAll('.editor-toolbar button').forEach(button => {
      button.addEventListener('click', function() {
        const textarea = document.querySelector('textarea[name="body"]');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        let newText = '';
        
        if (this.querySelector('.fa-bold')) {
          newText = `<strong>${selectedText}</strong>`;
        } else if (this.querySelector('.fa-italic')) {
          newText = `<em>${selectedText}</em>`;
        } else if (this.querySelector('.fa-underline')) {
          newText = `<u>${selectedText}</u>`;
        } else if (this.querySelector('.fa-link')) {
          const url = prompt('Enter URL:', 'https://');
          if (url) newText = `<a href="${url}" target="_blank">${selectedText}</a>`;
          else return;
        } else {
          return;
        }
        
        textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
      });
    });

    // File attachments handling
    document.getElementById('add-attachment').addEventListener('click', () => {
      document.getElementById('file-input').click();
    });

    document.getElementById('add-image').addEventListener('click', () => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.multiple = true;
      input.style.display = 'none';
      input.addEventListener('change', handleFileSelect);
      document.body.appendChild(input);
      input.click();
      document.body.removeChild(input);
    });

    document.getElementById('file-input').addEventListener('change', handleFileSelect);

    function handleFileSelect(event) {
      const files = event.target.files;
      const container = document.getElementById('attachment-container');
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const chip = document.createElement('div');
        chip.className = 'attachment-chip';
        chip.innerHTML = `
          <span>${file.name}</span>
          <button type="button" class="remove-attachment" data-index="${i}">
            <i class="fas fa-times"></i>
          </button>
        `;
        container.appendChild(chip);
      }
    }

    document.getElementById('attachment-container').addEventListener('click', (e) => {
      if (e.target.classList.contains('remove-attachment') || e.target.closest('.remove-attachment')) {
        const chip = e.target.closest('.attachment-chip');
        chip.remove();
      }
    });
  </script>
</body>
</html>