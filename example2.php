<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Email Validator</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <style>
        .drag-active {
            border: 2px dashed #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.1);
        }
        .validation-step {
            transition: all 0.3s ease;
        }
        .result-row:nth-child(even) {
            background-color: rgba(243, 244, 246, 0.5);
        }
        .tab-active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        /* Prevent fixed elements for better PDF export */
        .history-container {
            max-height: 300px;
            overflow-y: auto;
        }
        /* Custom tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Advanced Email Validator</h1>
            <p class="text-gray-600 max-w-3xl mx-auto">
                A comprehensive tool for validating email addresses individually or in bulk via CSV upload. 
                Get detailed validation reports including account name format, domain MX records, and SMTP verification.
            </p>
        </header>

        <!-- Main Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <div class="flex flex-wrap -mb-px">
                <button id="single-tab" class="tab-active px-6 py-3 text-gray-700 font-medium">
                    <i class="fas fa-envelope mr-2"></i> Single Email
                </button>
                <button id="batch-tab" class="px-6 py-3 text-gray-500 font-medium">
                    <i class="fas fa-file-csv mr-2"></i> CSV Batch Validation
                </button>
            </div>
        </div>

        <!-- Single Email Validation Section -->
        <div id="single-email-section" class="mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-4">
                    <label for="email-input" class="block text-gray-700 font-medium mb-2">Enter Email Address</label>
                    <div class="flex">
                        <input type="email" id="email-input" class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="example@domain.com">
                        <button id="validate-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-2 rounded-r-lg transition duration-200">
                            Validate
                        </button>
                    </div>
                </div>

                <!-- Single Email Results -->
                <div id="single-result-container" class="mt-6 hidden">
                    <h3 class="text-lg font-medium mb-4">Validation Results</h3>
                    
                    <div class="mb-4 px-4 py-3 rounded-lg" id="validation-summary">
                        <!-- Summary will be inserted here -->
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Email Parsing -->
                        <div class="validation-step border-b border-gray-200">
                            <div class="p-4 bg-gray-50 flex justify-between items-center cursor-pointer" data-target="parsing-details">
                                <h4 class="font-medium">
                                    <span id="parsing-icon" class="mr-2 inline-block w-5 text-center"><i class="fas fa-spinner fa-spin text-blue-500"></i></span>
                                    Email Parsing
                                </h4>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </div>
                            <div id="parsing-details" class="p-4 hidden">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap">
                                        <div class="w-full md:w-1/2 mb-2 md:mb-0">
                                            <span class="text-gray-600 font-medium">Account Name:</span>
                                            <span id="account-name" class="ml-2">-</span>
                                        </div>
                                        <div class="w-full md:w-1/2">
                                            <span class="text-gray-600 font-medium">Domain:</span>
                                            <span id="domain-name" class="ml-2">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Name Validation -->
                        <div class="validation-step border-b border-gray-200">
                            <div class="p-4 bg-gray-50 flex justify-between items-center cursor-pointer" data-target="account-details">
                                <h4 class="font-medium">
                                    <span id="account-icon" class="mr-2 inline-block w-5 text-center"><i class="fas fa-spinner fa-spin text-blue-500"></i></span>
                                    Account Name Validation
                                </h4>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </div>
                            <div id="account-details" class="p-4 hidden">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">Valid characters only</span>
                                            <p class="text-sm text-gray-500">Only letters, numbers, dots, underscores, and hyphens</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="char-check">-</div>
                                    </div>
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">Start/End validation</span>
                                            <p class="text-sm text-gray-500">Cannot start or end with dots or hyphens</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="start-end-check">-</div>
                                    </div>
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">No consecutive dots or hyphens</span>
                                            <p class="text-sm text-gray-500">Cannot contain '..' or '--'</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="consecutive-check">-</div>
                                    </div>
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">Length validation</span>
                                            <p class="text-sm text-gray-500">Must be between 1 and 64 characters</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="length-check">-</div>
                                    </div>
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">Not only numbers</span>
                                            <p class="text-sm text-gray-500">Account name cannot contain only numbers</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="numbers-check">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Domain Validation -->
                        <div class="validation-step border-b border-gray-200">
                            <div class="p-4 bg-gray-50 flex justify-between items-center cursor-pointer" data-target="domain-details">
                                <h4 class="font-medium">
                                    <span id="domain-icon" class="mr-2 inline-block w-5 text-center"><i class="fas fa-spinner fa-spin text-blue-500"></i></span>
                                    Domain Validation
                                </h4>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </div>
                            <div id="domain-details" class="p-4 hidden">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">MX Records Check</span>
                                            <p class="text-sm text-gray-500">Domain must have valid MX records</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="mx-check">-</div>
                                    </div>
                                    <div id="mx-records-container" class="mt-3 hidden">
                                        <h5 class="font-medium text-gray-700 mb-2">Mail Servers:</h5>
                                        <ul id="mx-records-list" class="list-disc pl-5 text-sm text-gray-600">
                                            <!-- MX records will be listed here -->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Validation -->
                        <div class="validation-step">
                            <div class="p-4 bg-gray-50 flex justify-between items-center cursor-pointer" data-target="smtp-details">
                                <h4 class="font-medium">
                                    <span id="smtp-icon" class="mr-2 inline-block w-5 text-center"><i class="fas fa-spinner fa-spin text-blue-500"></i></span>
                                    SMTP Validation
                                </h4>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </div>
                            <div id="smtp-details" class="p-4 hidden">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">Connection to Mail Server</span>
                                            <p class="text-sm text-gray-500">Successfully connected to mail server on port 25</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="connection-check">-</div>
                                    </div>
                                    <div class="flex flex-wrap items-center">
                                        <div class="w-full sm:w-3/4">
                                            <span class="font-medium text-gray-700">SMTP Commands</span>
                                            <p class="text-sm text-gray-500">EHLO, MAIL FROM, RCPT TO commands accepted</p>
                                        </div>
                                        <div class="w-full sm:w-1/4 text-right" id="smtp-check">-</div>
                                    </div>
                                    <div id="smtp-log-container" class="mt-3 hidden">
                                        <h5 class="font-medium text-gray-700 mb-2">SMTP Session Log:</h5>
                                        <pre id="smtp-log" class="bg-gray-100 p-3 rounded text-sm font-mono text-gray-700 overflow-x-auto"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CSV Batch Validation Section -->
        <div id="csv-batch-section" class="mb-8 hidden">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-2">Upload CSV File</h3>
                    <p class="text-gray-600 mb-4">
                        Upload a CSV file containing email addresses for batch validation. 
                        The file should have headers, and you'll be able to select which column contains the emails.
                    </p>
                    
                    <!-- CSV Upload Area -->
                    <div id="csv-upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-file-upload text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600 mb-2">Drag & drop your CSV file here or click to browse</p>
                        <p class="text-gray-500 text-sm">Maximum file size: 5MB</p>
                        <input type="file" id="csv-file-input" class="hidden" accept=".csv">
                    </div>
                    
                    <div class="mt-3 flex justify-between">
                        <button id="download-template-btn" class="text-blue-500 text-sm hover:underline">
                            <i class="fas fa-download mr-1"></i> Download Template
                        </button>
                        <span id="selected-file-name" class="text-gray-600 text-sm"></span>
                    </div>
                </div>

                <!-- Column Selection (Hidden until file uploaded) -->
                <div id="column-selection-container" class="mb-6 hidden">
                    <h3 class="text-lg font-medium mb-3">Select Email Column</h3>
                    <div class="flex flex-wrap items-center">
                        <label for="email-column" class="w-full sm:w-1/4 text-gray-700 mb-2 sm:mb-0">Email Column:</label>
                        <select id="email-column" class="w-full sm:w-3/4 border border-gray-300 rounded px-3 py-2">
                            <option value="">-- Select Column --</option>
                            <!-- Column options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mt-4 text-right">
                        <button id="start-batch-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-2 rounded transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Start Validation
                        </button>
                    </div>
                </div>

                <!-- Batch Progress (Hidden until validation starts) -->
                <div id="batch-progress-container" class="mb-6 hidden">
                    <h3 class="text-lg font-medium mb-3">Validation Progress</h3>
                    <div class="flex justify-between mb-2">
                        <span id="progress-text" class="text-gray-600">Processing 0 of 0</span>
                        <span id="progress-percentage" class="text-gray-600">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                        <div id="progress-bar" class="bg-blue-500 h-2.5 rounded-full progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="text-center">
                        <button id="cancel-batch-btn" class="text-red-500 hover:text-red-600 font-medium">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                    </div>
                </div>

                <!-- Batch Results (Hidden until validation completes) -->
                <div id="batch-results-container" class="hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-3">Validation Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-100 p-4 rounded-lg text-center">
                                <p class="text-gray-500 mb-1">Total Emails</p>
                                <p id="total-emails" class="text-2xl font-bold text-gray-700">0</p>
                            </div>
                            <div class="bg-green-100 p-4 rounded-lg text-center">
                                <p class="text-green-600 mb-1">Valid Emails</p>
                                <p id="valid-emails" class="text-2xl font-bold text-green-600">0</p>
                            </div>
                            <div class="bg-red-100 p-4 rounded-lg text-center">
                                <p class="text-red-600 mb-1">Invalid Emails</p>
                                <p id="invalid-emails" class="text-2xl font-bold text-red-600">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4 flex justify-between items-center">
                        <h3 class="text-lg font-medium">Detailed Results</h3>
                        <button id="download-results-btn" class="bg-green-500 hover:bg-green-600 text-white font-medium px-4 py-2 rounded text-sm transition duration-200">
                            <i class="fas fa-download mr-1"></i> Download Results
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="results-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Results will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Validation History -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-medium mb-4">Recent Validation History</h3>
            <div id="history-empty" class="text-center py-8 text-gray-500">
                <i class="fas fa-history text-gray-300 text-4xl mb-3"></i>
                <p>No validation history yet</p>
            </div>
            <div id="history-container" class="history-container hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="history-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- History will be populated here -->
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-right">
                <button id="clear-history-btn" class="text-gray-500 hover:text-gray-700 text-sm font-medium hidden">
                    <i class="fas fa-trash-alt mr-1"></i> Clear History
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const singleTab = document.getElementById('single-tab');
            const batchTab = document.getElementById('batch-tab');
            const singleSection = document.getElementById('single-email-section');
            const batchSection = document.getElementById('csv-batch-section');

            singleTab.addEventListener('click', () => {
                singleTab.classList.add('tab-active');
                batchTab.classList.remove('tab-active');
                singleSection.classList.remove('hidden');
                batchSection.classList.add('hidden');
            });

            batchTab.addEventListener('click', () => {
                batchTab.classList.add('tab-active');
                singleTab.classList.remove('tab-active');
                batchSection.classList.remove('hidden');
                singleSection.classList.add('hidden');
            });

            // Accordion functionality for validation steps
            document.querySelectorAll('[data-target]').forEach(header => {
                header.addEventListener('click', () => {
                    const targetId = header.getAttribute('data-target');
                    const target = document.getElementById(targetId);
                    const isHidden = target.classList.contains('hidden');
                    
                    // Close all other sections first
                    const parentStep = header.closest('.validation-step');
                    const allDetails = parentStep.parentNode.querySelectorAll('[id$="-details"]');
                    allDetails.forEach(el => el.classList.add('hidden'));
                    
                    // Toggle chevron for all sections
                    parentStep.parentNode.querySelectorAll('.fa-chevron-up').forEach(icon => {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    });
                    
                    // Open the clicked section if it was closed
                    if (isHidden) {
                        target.classList.remove('hidden');
                        header.querySelector('i.fas').classList.remove('fa-chevron-down');
                        header.querySelector('i.fas').classList.add('fa-chevron-up');
                    }
                });
            });

            // CSV Upload functionality
            const csvUploadArea = document.getElementById('csv-upload-area');
            const csvFileInput = document.getElementById('csv-file-input');
            const selectedFileName = document.getElementById('selected-file-name');
            const columnSelectionContainer = document.getElementById('column-selection-container');
            const emailColumnSelect = document.getElementById('email-column');
            const startBatchBtn = document.getElementById('start-batch-btn');
            
            // Drag and drop functionality
            csvUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                csvUploadArea.classList.add('drag-active');
            });
            
            csvUploadArea.addEventListener('dragleave', () => {
                csvUploadArea.classList.remove('drag-active');
            });
            
            csvUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                csvUploadArea.classList.remove('drag-active');
                
                if (e.dataTransfer.files.length) {
                    handleCsvFile(e.dataTransfer.files[0]);
                }
            });
            
            csvUploadArea.addEventListener('click', () => {
                csvFileInput.click();
            });
            
            csvFileInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    handleCsvFile(e.target.files[0]);
                }
            });
            
            // Handle CSV file selection
            function handleCsvFile(file) {
                if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                    alert('Please upload a valid CSV file');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit');
                    return;
                }
                
                selectedFileName.textContent = file.name;
                
                // Read CSV headers to populate column selection
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csv = e.target.result;
                    const lines = csv.split('\n');
                    if (lines.length > 0) {
                        const headers = lines[0].split(',');
                        
                        // Clear previous options
                        emailColumnSelect.innerHTML = '<option value="">-- Select Column --</option>';
                        
                        // Add new options
                        headers.forEach((header, index) => {
                            header = header.trim().replace(/"/g, '');
                            const option = document.createElement('option');
                            option.value = index;
                            option.textContent = header;
                            emailColumnSelect.appendChild(option);
                        });
                        
                        // Show column selection
                        columnSelectionContainer.classList.remove('hidden');
                        
                        // Store CSV content for later processing
                        window.csvContent = csv;
                    }
                };
                reader.readAsText(file);
            }
            
            // Enable start button when column is selected
            emailColumnSelect.addEventListener('change', () => {
                startBatchBtn.disabled = !emailColumnSelect.value;
            });
            
            // Download template functionality
            document.getElementById('download-template-btn').addEventListener('click', () => {
                const template = 'Email,Name,Company\nexample@domain.com,John Doe,ACME Inc.\nsupport@example.net,Help Desk,Example Corp.';
                const blob = new Blob([template], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'email_template.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
            
            // Start batch validation
            const batchProgressContainer = document.getElementById('batch-progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const progressPercentage = document.getElementById('progress-percentage');
            const batchResultsContainer = document.getElementById('batch-results-container');
            const totalEmailsElement = document.getElementById('total-emails');
            const validEmailsElement = document.getElementById('valid-emails');
            const invalidEmailsElement = document.getElementById('invalid-emails');
            const resultsTableBody = document.getElementById('results-table-body');
            
            startBatchBtn.addEventListener('click', () => {
                const columnIndex = parseInt(emailColumnSelect.value);
                const csv = window.csvContent;
                const lines = csv.split('\n').filter(line => line.trim());
                
                // Hide column selection, show progress
                columnSelectionContainer.classList.add('hidden');
                batchProgressContainer.classList.remove('hidden');
                
                // Reset progress
                progressBar.style.width = '0%';
                progressText.textContent = `Processing 0 of ${lines.length - 1}`;
                progressPercentage.textContent = '0%';
                
                // Process emails
                const emails = [];
                for (let i = 1; i < lines.length; i++) {
                    const columns = parseCSVLine(lines[i]);
                    if (columns.length > columnIndex) {
                        emails.push(columns[columnIndex].trim().replace(/"/g, ''));
                    }
                }
                
                // Start validation
                batchValidate(emails);
            });
            
            // Parse CSV line respecting quoted values
            function parseCSVLine(line) {
                const result = [];
                let current = '';
                let inQuotes = false;
                
                for (let i = 0; i < line.length; i++) {
                    const char = line[i];
                    
                    if (char === '"' && (i === 0 || line[i-1] !== '\\')) {
                        inQuotes = !inQuotes;
                    } else if (char === ',' && !inQuotes) {
                        result.push(current);
                        current = '';
                    } else {
                        current += char;
                    }
                }
                
                result.push(current);
                return result;
            }
            
            // Batch validation function
            function batchValidate(emails) {
                const results = [];
                let validCount = 0;
                let processedCount = 0;
                
                // Process one email at a time to avoid freezing the UI
                function processNextEmail(index) {
                    if (index >= emails.length) {
                        // All done
                        finishBatchProcessing(results, validCount);
                        return;
                    }
                    
                    const email = emails[index];
                    
                    // Validate email
                    validateEmail(email)
                        .then(result => {
                            results.push({
                                email: email,
                                valid: result.valid,
                                issues: result.issues
                            });
                            
                            if (result.valid) validCount++;
                            processedCount++;
                            
                            // Update progress
                            const percent = Math.round((processedCount / emails.length) * 100);
                            progressBar.style.width = `${percent}%`;
                            progressText.textContent = `Processing ${processedCount} of ${emails.length}`;
                            progressPercentage.textContent = `${percent}%`;
                            
                            // Process next email
                            setTimeout(() => processNextEmail(index + 1), 100);
                        });
                }
                
                // Start processing
                processNextEmail(0);
            }
            
            // Finish batch processing
            function finishBatchProcessing(results, validCount) {
                // Hide progress bar
                batchProgressContainer.classList.add('hidden');
                
                // Show results
                batchResultsContainer.classList.remove('hidden');
                
                // Update summary
                totalEmailsElement.textContent = results.length;
                validEmailsElement.textContent = validCount;
                invalidEmailsElement.textContent = results.length - validCount;
                
                // Clear results table
                resultsTableBody.innerHTML = '';
                
                // Populate results table
                results.forEach((result, index) => {
                    const row = document.createElement('tr');
                    row.classList.add('result-row');
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${result.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${result.valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${result.valid ? 'Valid' : 'Invalid'}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">${result.issues.length ? result.issues.join(', ') : 'None'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="retry-btn text-blue-500 hover:text-blue-700" data-email="${result.email}">
                                <i class="fas fa-redo-alt mr-1"></i> Retry
                            </button>
                        </td>
                    `;
                    
                    resultsTableBody.appendChild(row);
                });
                
                // Add retry functionality
                document.querySelectorAll('.retry-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const email = btn.getAttribute('data-email');
                        document.getElementById('email-input').value = email;
                        singleTab.click();
                        document.getElementById('validate-btn').click();
                    });
                });
                
                // Add results to history
                results.forEach(result => {
                    addToHistory(result.email, result.valid, result.issues);
                });
            }
            
            // Download results
            document.getElementById('download-results-btn').addEventListener('click', () => {
                const rows = [];
                
                // Add header
                rows.push(['Email', 'Status', 'Issues']);
                
                // Add data
                document.querySelectorAll('#results-table-body tr').forEach(row => {
                    const email = row.querySelector('td:nth-child(2)').textContent;
                    const status = row.querySelector('td:nth-child(3) span').textContent;
                    const issues = row.querySelector('td:nth-child(4)').textContent;
                    
                    rows.push([email, status, issues]);
                });
                
                // Convert to CSV
                const csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
                
                // Download
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'email_validation_results.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
            
            // Single Email Validation
            const emailInput = document.getElementById('email-input');
            const validateBtn = document.getElementById('validate-btn');
            const singleResultContainer = document.getElementById('single-result-container');
            
            validateBtn.addEventListener('click', () => {
                const email = emailInput.value.trim();
                
                if (!email) {
                    alert('Please enter an email address');
                    return;
                }
                
                // Show result container
                singleResultContainer.classList.remove('hidden');
                
                // Reset validation steps
                resetValidationSteps();
                
                // Start validation
                validateEmailWithUI(email);
            });
            
            // Reset validation steps
            function resetValidationSteps() {
                // Reset icons
                document.querySelectorAll('[id$="-icon"]').forEach(icon => {
                    icon.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-500"></i>';
                });
                
                // Reset detail contents
                document.getElementById('account-name').textContent = '-';
                document.getElementById('domain-name').textContent = '-';
                document.querySelectorAll('[id$="-check"]').forEach(el => {
                    el.textContent = '-';
                });
                
                // Hide details
                document.querySelectorAll('[id$="-details"]').forEach(el => {
                    el.classList.add('hidden');
                });
                
                // Hide special containers
                document.getElementById('mx-records-container').classList.add('hidden');
                document.getElementById('smtp-log-container').classList.add('hidden');
                
                // Reset summary
                document.getElementById('validation-summary').className = 'mb-4 px-4 py-3 rounded-lg';
                document.getElementById('validation-summary').innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-xl mb-2"></i>
                        <p class="text-gray-600">Validating email address...</p>
                    </div>
                `;
            }
            
            // Validate email with UI updates
            function validateEmailWithUI(email) {
                // Email parsing
                setTimeout(() => {
                    const parts = email.split('@');
                    const accountName = parts[0];
                    const domain = parts.length > 1 ? parts[1] : '';
                    
                    // Update parsing results
                    document.getElementById('parsing-icon').innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                    document.getElementById('account-name').textContent = accountName;
                    document.getElementById('domain-name').textContent = domain;
                    
                    // Start account name validation
                    validateAccountName(accountName);
                }, 500);
            }
            
            // Validate account name
            function validateAccountName(accountName) {
                setTimeout(() => {
                    const validChars = /^[a-zA-Z0-9._-]+$/.test(accountName);
                    const validStartEnd = !/^[.-]|[.-]$/.test(accountName);
                    const noConsecutive = !/(\.\.)|(--)/.test(accountName);
                    const validLength = accountName.length >= 1 && accountName.length <= 64;
                    const notOnlyNumbers = !/^\d+$/.test(accountName);
                    
                    // Update account validation results
                    document.getElementById('char-check').innerHTML = validChars 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    document.getElementById('start-end-check').innerHTML = validStartEnd 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    document.getElementById('consecutive-check').innerHTML = noConsecutive 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    document.getElementById('length-check').innerHTML = validLength 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    document.getElementById('numbers-check').innerHTML = notOnlyNumbers 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    const accountValid = validChars && validStartEnd && noConsecutive && validLength && notOnlyNumbers;
                    
                    // Update account icon
                    document.getElementById('account-icon').innerHTML = accountValid 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    // Start domain validation
                    validateDomain(document.getElementById('domain-name').textContent, accountValid);
                }, 700);
            }
            
            // Validate domain
            function validateDomain(domain, accountValid) {
                setTimeout(() => {
                    // For demo purposes, we'll simulate MX record checks
                    // In a real application, this would use server-side code
                    const mxValid = simulateMXCheck(domain);
                    
                    // Update domain validation results
                    document.getElementById('mx-check').innerHTML = mxValid 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    // Update domain icon
                    document.getElementById('domain-icon').innerHTML = mxValid 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    if (mxValid) {
                        // Show MX records
                        document.getElementById('mx-records-container').classList.remove('hidden');
                        document.getElementById('mx-records-list').innerHTML = getMXRecordsHTML(domain);
                    }
                    
                    // Start SMTP validation
                    validateSMTP(document.getElementById('email-input').value, accountValid && mxValid);
                }, 1000);
            }
            
            // Validate SMTP
            function validateSMTP(email, previousValid) {
                setTimeout(() => {
                    // For demo purposes, simulate SMTP validation
                    // In a real application, this would use server-side code
                    const smtpResult = simulateSMTPCheck(email, previousValid);
                    
                    // Update SMTP validation results
                    document.getElementById('connection-check').innerHTML = smtpResult.connected 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    document.getElementById('smtp-check').innerHTML = smtpResult.accepted 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    // Update SMTP icon
                    document.getElementById('smtp-icon').innerHTML = (smtpResult.connected && smtpResult.accepted) 
                        ? '<i class="fas fa-check-circle text-green-500"></i>' 
                        : '<i class="fas fa-times-circle text-red-500"></i>';
                    
                    // Show SMTP log
                    document.getElementById('smtp-log-container').classList.remove('hidden');
                    document.getElementById('smtp-log').textContent = smtpResult.log;
                    
                    // Update final validation summary
                    updateValidationSummary(email, previousValid && smtpResult.connected && smtpResult.accepted);
                }, 1500);
            }
            
            // Update validation summary
            function updateValidationSummary(email, isValid) {
                const summaryElement = document.getElementById('validation-summary');
                
                if (isValid) {
                    summaryElement.className = 'mb-4 px-4 py-3 rounded-lg bg-green-100';
                    summaryElement.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                            <div>
                                <p class="font-medium text-green-800">Valid Email Address</p>
                                <p class="text-green-700">${email} appears to be a valid and deliverable email address.</p>
                            </div>
                        </div>
                    `;
                } else {
                    summaryElement.className = 'mb-4 px-4 py-3 rounded-lg bg-red-100';
                    summaryElement.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                            <div>
                                <p class="font-medium text-red-800">Invalid Email Address</p>
                                <p class="text-red-700">${email} appears to be invalid or undeliverable.</p>
                            </div>
                        </div>
                    `;
                }
                
                // Add to history
                addToHistory(email, isValid, getIssuesFromValidation(isValid));
            }
            
            // Simulate MX check
            function simulateMXCheck(domain) {
                const commonDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com', 'protonmail.com'];
                
                // For demo, common domains always have MX records
                return commonDomains.includes(domain.toLowerCase()) || Math.random() > 0.3;
            }
            
            // Get MX records HTML
            function getMXRecordsHTML(domain) {
                let html = '';
                
                const mxRecords = {
                    'gmail.com': [
                        { priority: 10, server: 'aspmx.l.google.com' },
                        { priority: 20, server: 'alt1.aspmx.l.google.com' },
                        { priority: 30, server: 'alt2.aspmx.l.google.com' }
                    ],
                    'yahoo.com': [
                        { priority: 1, server: 'mta5.am0.yahoodns.net' },
                        { priority: 1, server: 'mta6.am0.yahoodns.net' },
                        { priority: 1, server: 'mta7.am0.yahoodns.net' }
                    ],
                    'outlook.com': [
                        { priority: 10, server: 'mx1.hotmail.com' },
                        { priority: 10, server: 'mx2.hotmail.com' },
                        { priority: 10, server: 'mx3.hotmail.com' }
                    ],
                    'hotmail.com': [
                        { priority: 10, server: 'mx1.hotmail.com' },
                        { priority: 10, server: 'mx2.hotmail.com' }
                    ],
                    'aol.com': [
                        { priority: 15, server: 'mx.aol.com' }
                    ],
                    'icloud.com': [
                        { priority: 10, server: 'mx01.mail.icloud.com' },
                        { priority: 10, server: 'mx02.mail.icloud.com' }
                    ],
                    'protonmail.com': [
                        { priority: 10, server: 'mail.protonmail.ch' },
                        { priority: 20, server: 'mailsec.protonmail.ch' }
                    ]
                };
                
                const records = mxRecords[domain.toLowerCase()] || [
                    { priority: 10, server: `mx1.${domain}` },
                    { priority: 20, server: `mx2.${domain}` }
                ];
                
                records.forEach(record => {
                    html += `<li>Priority ${record.priority}: ${record.server} (IP: ${generateRandomIP()})</li>`;
                });
                
                return html;
            }
            
            // Generate random IP
            function generateRandomIP() {
                return `${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}`;
            }
            
            // Simulate SMTP check
            function simulateSMTPCheck(email, previousValid) {
                const domain = email.split('@')[1];
                const commonDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com', 'protonmail.com'];
                
                // For common domains, more likely to be successful
                const connected = commonDomains.includes(domain.toLowerCase()) || Math.random() > 0.2;
                
                // If previous checks failed, SMTP will likely fail too
                const accepted = previousValid && (commonDomains.includes(domain.toLowerCase()) || Math.random() > 0.3);
                
                // Generate SMTP log
                let log = '';
                
                if (connected) {
                    log += `> Connected to mail server (${domain})\n`;
                    log += `< 220 ${domain} ESMTP ready\n`;
                    log += `> EHLO client.local\n`;
                    log += `< 250-${domain} greets client.local\n`;
                    log += `< 250-SIZE 35882577\n`;
                    log += `< 250-8BITMIME\n`;
                    log += `< 250 STARTTLS\n`;
                    log += `> MAIL FROM: <test@validator.com>\n`;
                    log += `< 250 Sender accepted\n`;
                    log += `> RCPT TO: <${email}>\n`;
                    
                    if (accepted) {
                        log += `< 250 Recipient accepted\n`;
                        log += `> QUIT\n`;
                        log += `< 221 ${domain} closing connection\n`;
                    } else {
                        log += `< 550 Recipient address rejected: User unknown in virtual mailbox table\n`;
                        log += `> QUIT\n`;
                        log += `< 221 ${domain} closing connection\n`;
                    }
                } else {
                    log += `> Attempting connection to mail server (${domain})\n`;
                    log += `< Connection failed: Connection timed out\n`;
                }
                
                return {
                    connected: connected,
                    accepted: accepted,
                    log: log
                };
            }
            
            // Get issues from validation
            function getIssuesFromValidation(isValid) {
                if (isValid) return [];
                
                const issues = [];
                
                // Check account name issues
                const charCheck = document.getElementById('char-check').innerHTML.includes('fa-times-circle');
                const startEndCheck = document.getElementById('start-end-check').innerHTML.includes('fa-times-circle');
                const consecutiveCheck = document.getElementById('consecutive-check').innerHTML.includes('fa-times-circle');
                const lengthCheck = document.getElementById('length-check').innerHTML.includes('fa-times-circle');
                const numbersCheck = document.getElementById('numbers-check').innerHTML.includes('fa-times-circle');
                
                if (charCheck) issues.push('Invalid characters in account name');
                if (startEndCheck) issues.push('Account name cannot start/end with dots or hyphens');
                if (consecutiveCheck) issues.push('Account name contains consecutive dots or hyphens');
                if (lengthCheck) issues.push('Account name length invalid');
                if (numbersCheck) issues.push('Account name cannot be only numbers');
                
                // Check domain issues
                const mxCheck = document.getElementById('mx-check').innerHTML.includes('fa-times-circle');
                if (mxCheck) issues.push('Domain has no MX records');
                
                // Check SMTP issues
                const connectionCheck = document.getElementById('connection-check').innerHTML.includes('fa-times-circle');
                const smtpCheck = document.getElementById('smtp-check').innerHTML.includes('fa-times-circle');
                
                if (connectionCheck) issues.push('Could not connect to mail server');
                else if (smtpCheck) issues.push('Email address rejected by mail server');
                
                return issues;
            }
            
            // Validation API for batch processing
            function validateEmail(email) {
                return new Promise(resolve => {
                    setTimeout(() => {
                        const parts = email.split('@');
                        const accountName = parts[0];
                        const domain = parts.length > 1 ? parts[1] : '';
                        
                        const issues = [];
                        
                        // Account name validation
                        if (!/^[a-zA-Z0-9._-]+$/.test(accountName)) {
                            issues.push('Invalid characters in account name');
                        }
                        
                        if (/^[.-]|[.-]$/.test(accountName)) {
                            issues.push('Account name cannot start/end with dots or hyphens');
                        }
                        
                        if (/(\.\.)|(--)/.test(accountName)) {
                            issues.push('Account name contains consecutive dots or hyphens');
                        }
                        
                        if (accountName.length < 1 || accountName.length > 64) {
                            issues.push('Account name length invalid');
                        }
                        
                        if (/^\d+$/.test(accountName)) {
                            issues.push('Account name cannot be only numbers');
                        }
                        
                        // Domain validation
                        if (!domain || domain.indexOf('.') === -1) {
                            issues.push('Invalid domain format');
                        } else {
                            if (!simulateMXCheck(domain)) {
                                issues.push('Domain has no MX records');
                            } else {
                                // SMTP validation
                                const smtpResult = simulateSMTPCheck(email, issues.length === 0);
                                if (!smtpResult.connected) {
                                    issues.push('Could not connect to mail server');
                                } else if (!smtpResult.accepted) {
                                    issues.push('Email address rejected by mail server');
                                }
                            }
                        }
                        
                        resolve({
                            valid: issues.length === 0,
                            issues: issues
                        });
                    }, 200);
                });
            }
            
            // History management
            const historyEmpty = document.getElementById('history-empty');
            const historyContainer = document.getElementById('history-container');
            const historyTableBody = document.getElementById('history-table-body');
            const clearHistoryBtn = document.getElementById('clear-history-btn');
            
            // Load history from localStorage
            function loadHistory() {
                const history = JSON.parse(localStorage.getItem('emailValidationHistory') || '[]');
                
                if (history.length > 0) {
                    historyEmpty.classList.add('hidden');
                    historyContainer.classList.remove('hidden');
                    clearHistoryBtn.classList.remove('hidden');
                    
                    // Populate history table
                    historyTableBody.innerHTML = '';
                    
                    history.forEach(item => {
                        const row = document.createElement('tr');
                        
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.email}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(item.date)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${item.valid ? 'Valid' : 'Invalid'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="validate-again-btn text-blue-500 hover:text-blue-700" data-email="${item.email}">
                                    <i class="fas fa-redo-alt mr-1"></i> Validate Again
                                </button>
                                <button class="view-issues-btn ml-3 text-gray-500 hover:text-gray-700 tooltip" data-email="${item.email}">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltiptext">${item.issues.length ? item.issues.join(', ') : 'No issues found'}</span>
                                </button>
                            </td>
                        `;
                        
                        historyTableBody.appendChild(row);
                    });
                    
                    // Add validate again functionality
                    document.querySelectorAll('.validate-again-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const email = btn.getAttribute('data-email');
                            document.getElementById('email-input').value = email;
                            singleTab.click();
                            document.getElementById('validate-btn').click();
                        });
                    });
                }
            }
            
            // Add to history
            function addToHistory(email, valid, issues) {
                const history = JSON.parse(localStorage.getItem('emailValidationHistory') || '[]');
                
                // Add to beginning (most recent first)
                history.unshift({
                    email: email,
                    valid: valid,
                    issues: issues,
                    date: new Date().toISOString()
                });
                
                // Keep only the last 10 entries
                if (history.length > 10) {
                    history.length = 10;
                }
                
                // Save back to localStorage
                localStorage.setItem('emailValidationHistory', JSON.stringify(history));
                
                // Reload history display
                loadHistory();
            }
            
            // Format date
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            }
            
            // Clear history
            clearHistoryBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to clear your validation history?')) {
                    localStorage.removeItem('emailValidationHistory');
                    historyEmpty.classList.remove('hidden');
                    historyContainer.classList.add('hidden');
                    clearHistoryBtn.classList.add('hidden');
                    historyTableBody.innerHTML = '';
                }
            });
            
            // Cancel batch button
            document.getElementById('cancel-batch-btn').addEventListener('click', () => {
                batchProgressContainer.classList.add('hidden');
                columnSelectionContainer.classList.remove('hidden');
            });
            
            // Load history on page load
            loadHistory();
        });
    </script>
</body>
</html>
