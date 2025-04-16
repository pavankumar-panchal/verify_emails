<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Email Validator</title>
    <!-- Tailwind CSS from jsdelivr -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .validation-step {
            transition: all 0.3s ease;
        }
        .result-icon {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .success {
            background-color: #d1fae5;
            color: #047857;
        }
        .fail {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .pending {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .spinner {
            animation: spin 1.5s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-10 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Email Validator Pro</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                A comprehensive tool to validate email addresses through multiple checks including syntax, domain, and SMTP verification.
            </p>
        </header>

        <!-- Email Input Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 max-w-3xl mx-auto">
            <form id="emailForm" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Enter Email Address</label>
                    <input type="text" id="email" name="email" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="example@domain.com">
                    <p id="emailError" class="mt-1 text-sm text-red-600 hidden">Please enter a valid email format</p>
                </div>
                <div class="self-end">
                    <button type="submit" id="validateBtn" 
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-6 rounded-md transition duration-200 flex items-center">
                        <span>Validate</span>
                        <i id="validateSpinner" class="fas fa-spinner spinner ml-2 hidden"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto mb-8 hidden">
            <!-- Summary Box -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <div class="flex items-center">
                    <div id="overallResult" class="result-icon pending mr-4">
                        <i class="fas fa-question"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold" id="summaryTitle">Validation in progress...</h3>
                        <p class="text-gray-600" id="summaryText">Checking email validity through multiple verification steps.</p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <div class="flex flex-wrap -mb-px">
                    <button class="tab-button active px-6 py-2 font-medium text-sm border-b-2 mr-2 focus:outline-none" data-tab="format">
                        Format Check
                    </button>
                    <button class="tab-button px-6 py-2 font-medium text-sm border-b-2 mr-2 focus:outline-none" data-tab="account">
                        Account Name
                    </button>
                    <button class="tab-button px-6 py-2 font-medium text-sm border-b-2 mr-2 focus:outline-none" data-tab="domain">
                        Domain Check
                    </button>
                    <button class="tab-button px-6 py-2 font-medium text-sm border-b-2 focus:outline-none" data-tab="smtp">
                        SMTP Validation
                    </button>
                </div>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content active" id="format-tab">
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="formatResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Email Format Validation</h4>
                            <p class="text-gray-600 text-sm mb-2">Checking if the email follows the correct format with an @ symbol and valid structure.</p>
                            <div id="formatDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg bg-gray-50">
                    <div class="flex items-start">
                        <div id="splitResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Email Split Analysis</h4>
                            <p class="text-gray-600 text-sm mb-2">Breaking down the email into account name and domain components.</p>
                            <div id="splitDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="account-tab">
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="charsResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Valid Characters</h4>
                            <p class="text-gray-600 text-sm mb-2">Checking if the account name contains only allowed characters (letters, numbers, dots, underscores, hyphens).</p>
                            <div id="charsDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="startEndResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Start/End Characters</h4>
                            <p class="text-gray-600 text-sm mb-2">Verifying the account name doesn't start or end with periods or hyphens.</p>
                            <div id="startEndDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="doubleCharsResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Double Character Check</h4>
                            <p class="text-gray-600 text-sm mb-2">Ensuring there are no double dots or double hyphens in the account name.</p>
                            <div id="doubleCharsDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="lengthResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Length Check</h4>
                            <p class="text-gray-600 text-sm mb-2">Verifying the account name is between 1 and 64 characters long.</p>
                            <div id="lengthDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg bg-gray-50">
                    <div class="flex items-start">
                        <div id="onlyNumsResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Not Only Numbers</h4>
                            <p class="text-gray-600 text-sm mb-2">Checking that the account name isn't composed of only numbers.</p>
                            <div id="onlyNumsDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="domain-tab">
                <div class="validation-step p-4 rounded-lg mb-4 bg-gray-50">
                    <div class="flex items-start">
                        <div id="domainFormatResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">Domain Format</h4>
                            <p class="text-gray-600 text-sm mb-2">Checking if the domain has a valid format with appropriate TLD.</p>
                            <div id="domainFormatDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="validation-step p-4 rounded-lg bg-gray-50">
                    <div class="flex items-start">
                        <div id="mxRecordResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">MX Records Check</h4>
                            <p class="text-gray-600 text-sm mb-2">Verifying the domain has valid MX records for receiving emails.</p>
                            <p class="text-yellow-600 text-sm mb-2">Note: In a browser-only environment, this is simulated based on common domains.</p>
                            <div id="mxRecordDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="smtp-tab">
                <div class="validation-step p-4 rounded-lg bg-gray-50">
                    <div class="flex items-start">
                        <div id="smtpResult" class="result-icon pending mt-1 mr-4">
                            <i class="fas fa-spinner spinner"></i>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-medium mb-1">SMTP Validation</h4>
                            <p class="text-gray-600 text-sm mb-2">Testing email deliverability through SMTP protocol checks.</p>
                            <p class="text-yellow-600 text-sm mb-2">Note: In a browser-only environment, this is simulated based on domain reputation.</p>
                            <div id="smtpDetails" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Results Button -->
            <div class="mt-6 text-right">
                <button id="copyResultsBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-md transition duration-200">
                    <i class="far fa-copy mr-1"></i> Copy Results
                </button>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-gray-500 text-sm mt-8">
            <p>Email Validator Pro - A comprehensive email validation tool</p>
            <p class="mt-1">This is a client-side simulation for demonstration purposes.</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Deactivate all buttons and tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Activate selected button and tab
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });

            // Form submission
            const emailForm = document.getElementById('emailForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const validateBtn = document.getElementById('validateBtn');
            const validateSpinner = document.getElementById('validateSpinner');
            const resultsSection = document.getElementById('resultsSection');
            const copyResultsBtn = document.getElementById('copyResultsBtn');

            emailForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Reset previous errors
                emailError.classList.add('hidden');
                
                // Basic format validation
                const email = emailInput.value.trim();
                if (!email || !email.includes('@')) {
                    emailError.textContent = 'Please enter a valid email format';
                    emailError.classList.remove('hidden');
                    return;
                }

                // Show loading state
                validateBtn.disabled = true;
                validateSpinner.classList.remove('hidden');
                resultsSection.classList.remove('hidden');
                
                // Reset all result indicators to pending
                const resultIcons = document.querySelectorAll('.result-icon');
                resultIcons.forEach(icon => {
                    icon.className = 'result-icon pending mt-1 mr-4';
                    icon.innerHTML = '<i class="fas fa-spinner spinner"></i>';
                });
                
                // Clear previous details
                document.querySelectorAll('[id$="Details"]').forEach(el => {
                    el.innerHTML = '';
                });
                
                // Update summary
                document.getElementById('summaryTitle').textContent = 'Validation in progress...';
                document.getElementById('summaryText').textContent = 'Checking email validity through multiple verification steps.';
                document.getElementById('overallResult').className = 'result-icon pending mr-4';
                document.getElementById('overallResult').innerHTML = '<i class="fas fa-spinner spinner"></i>';
                
                // Start validation process
                validateEmail(email);
            });

            // Copy results functionality
            copyResultsBtn.addEventListener('click', function() {
                const resultSummary = document.getElementById('summaryTitle').textContent + '\n' + 
                                     document.getElementById('summaryText').textContent + '\n\n';
                
                let fullResults = 'Email Validation Results for ' + emailInput.value + '\n\n';
                fullResults += resultSummary;
                
                document.querySelectorAll('.validation-step').forEach(step => {
                    const title = step.querySelector('h4').textContent;
                    const details = step.querySelector('[id$="Details"]').textContent;
                    fullResults += title + ': ' + details + '\n';
                });
                
                // Create a temporary textarea to copy the text
                const textarea = document.createElement('textarea');
                textarea.value = fullResults;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                // Provide feedback
                const originalText = copyResultsBtn.innerHTML;
                copyResultsBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
                setTimeout(() => {
                    copyResultsBtn.innerHTML = originalText;
                }, 2000);
            });

            // Email validation functions
            function validateEmail(email) {
                // Parse the email
                const atIndex = email.lastIndexOf('@');
                const accountName = email.substring(0, atIndex);
                const domainName = email.substring(atIndex + 1);
                
                // Start validation steps with timing to simulate processing
                setTimeout(() => validateFormat(email, accountName, domainName), 300);
            }

            function validateFormat(email, accountName, domainName) {
                // Basic format check
                const formatResult = document.getElementById('formatResult');
                const formatDetails = document.getElementById('formatDetails');
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const isValidFormat = emailRegex.test(email);
                
                if (isValidFormat) {
                    formatResult.className = 'result-icon success mt-1 mr-4';
                    formatResult.innerHTML = '<i class="fas fa-check"></i>';
                    formatDetails.innerHTML = `
                        <div class="text-green-700">
                            Email format is valid with proper @ symbol and domain structure.
                        </div>
                    `;
                } else {
                    formatResult.className = 'result-icon fail mt-1 mr-4';
                    formatResult.innerHTML = '<i class="fas fa-times"></i>';
                    formatDetails.innerHTML = `
                        <div class="text-red-700">
                            Email format is invalid. Must contain a single @ symbol with valid parts before and after.
                        </div>
                    `;
                }

                // Split analysis
                setTimeout(() => {
                    const splitResult = document.getElementById('splitResult');
                    const splitDetails = document.getElementById('splitDetails');
                    
                    splitResult.className = 'result-icon success mt-1 mr-4';
                    splitResult.innerHTML = '<i class="fas fa-check"></i>';
                    splitDetails.innerHTML = `
                        <div class="bg-blue-50 p-3 rounded-md">
                            <div class="mb-2">
                                <span class="font-medium">Account Name:</span> ${accountName}
                            </div>
                            <div>
                                <span class="font-medium">Domain Name:</span> ${domainName}
                            </div>
                        </div>
                    `;
                    
                    // Continue to account name validation
                    setTimeout(() => validateAccountName(accountName, domainName), 400);
                }, 400);
            }

            function validateAccountName(accountName, domainName) {
                // 1. Valid characters check
                const charsResult = document.getElementById('charsResult');
                const charsDetails = document.getElementById('charsDetails');
                
                const validCharsRegex = /^[a-zA-Z0-9._-]+$/;
                const hasValidChars = validCharsRegex.test(accountName);
                
                if (hasValidChars) {
                    charsResult.className = 'result-icon success mt-1 mr-4';
                    charsResult.innerHTML = '<i class="fas fa-check"></i>';
                    charsDetails.innerHTML = `
                        <div class="text-green-700">
                            Account name contains only valid characters (letters, numbers, dots, underscores, hyphens).
                        </div>
                    `;
                } else {
                    charsResult.className = 'result-icon fail mt-1 mr-4';
                    charsResult.innerHTML = '<i class="fas fa-times"></i>';
                    charsDetails.innerHTML = `
                        <div class="text-red-700">
                            Account name contains invalid characters. Only letters, numbers, dots, underscores, and hyphens are allowed.
                        </div>
                    `;
                }

                // 2. Start/End check
                setTimeout(() => {
                    const startEndResult = document.getElementById('startEndResult');
                    const startEndDetails = document.getElementById('startEndDetails');
                    
                    const startsWithInvalid = accountName.startsWith('.') || accountName.startsWith('-');
                    const endsWithInvalid = accountName.endsWith('.') || accountName.endsWith('-');
                    
                    if (!startsWithInvalid && !endsWithInvalid) {
                        startEndResult.className = 'result-icon success mt-1 mr-4';
                        startEndResult.innerHTML = '<i class="fas fa-check"></i>';
                        startEndDetails.innerHTML = `
                            <div class="text-green-700">
                                Account name doesn't start or end with a period or hyphen.
                            </div>
                        `;
                    } else {
                        startEndResult.className = 'result-icon fail mt-1 mr-4';
                        startEndResult.innerHTML = '<i class="fas fa-times"></i>';
                        startEndDetails.innerHTML = `
                            <div class="text-red-700">
                                ${startsWithInvalid ? 'Account name cannot start with a period or hyphen.' : ''}
                                ${endsWithInvalid ? 'Account name cannot end with a period or hyphen.' : ''}
                            </div>
                        `;
                    }
                
                    // 3. Double characters check
                    setTimeout(() => {
                        const doubleCharsResult = document.getElementById('doubleCharsResult');
                        const doubleCharsDetails = document.getElementById('doubleCharsDetails');
                        
                        const hasDoubleDots = accountName.includes('..');
                        const hasDoubleHyphens = accountName.includes('--');
                        
                        if (!hasDoubleDots && !hasDoubleHyphens) {
                            doubleCharsResult.className = 'result-icon success mt-1 mr-4';
                            doubleCharsResult.innerHTML = '<i class="fas fa-check"></i>';
                            doubleCharsDetails.innerHTML = `
                                <div class="text-green-700">
                                    Account name doesn't contain consecutive dots or hyphens.
                                </div>
                            `;
                        } else {
                            doubleCharsResult.className = 'result-icon fail mt-1 mr-4';
                            doubleCharsResult.innerHTML = '<i class="fas fa-times"></i>';
                            doubleCharsDetails.innerHTML = `
                                <div class="text-red-700">
                                    ${hasDoubleDots ? 'Account name cannot contain consecutive dots (..).' : ''}
                                    ${hasDoubleHyphens ? 'Account name cannot contain consecutive hyphens (--).' : ''}
                                </div>
                            `;
                        }
                        
                        // 4. Length check
                        setTimeout(() => {
                            const lengthResult = document.getElementById('lengthResult');
                            const lengthDetails = document.getElementById('lengthDetails');
                            
                            const isValidLength = accountName.length >= 1 && accountName.length <= 64;
                            
                            if (isValidLength) {
                                lengthResult.className = 'result-icon success mt-1 mr-4';
                                lengthResult.innerHTML = '<i class="fas fa-check"></i>';
                                lengthDetails.innerHTML = `
                                    <div class="text-green-700">
                                        Account name length (${accountName.length} characters) is within the valid range (1-64).
                                    </div>
                                `;
                            } else {
                                lengthResult.className = 'result-icon fail mt-1 mr-4';
                                lengthResult.innerHTML = '<i class="fas fa-times"></i>';
                                lengthDetails.innerHTML = `
                                    <div class="text-red-700">
                                        Account name length (${accountName.length} characters) must be between 1 and 64 characters.
                                    </div>
                                `;
                            }
                            
                            // 5. Not only numbers check
                            setTimeout(() => {
                                const onlyNumsResult = document.getElementById('onlyNumsResult');
                                const onlyNumsDetails = document.getElementById('onlyNumsDetails');
                                
                                const isOnlyNumbers = /^\d+$/.test(accountName);
                                
                                if (!isOnlyNumbers) {
                                    onlyNumsResult.className = 'result-icon success mt-1 mr-4';
                                    onlyNumsResult.innerHTML = '<i class="fas fa-check"></i>';
                                    onlyNumsDetails.innerHTML = `
                                        <div class="text-green-700">
                                            Account name is not composed of only numbers.
                                        </div>
                                    `;
                                } else {
                                    onlyNumsResult.className = 'result-icon fail mt-1 mr-4';
                                    onlyNumsResult.innerHTML = '<i class="fas fa-times"></i>';
                                    onlyNumsDetails.innerHTML = `
                                        <div class="text-red-700">
                                            Account name cannot consist of only numbers.
                                        </div>
                                    `;
                                }
                                
                                // Continue to domain validation
                                setTimeout(() => validateDomain(accountName, domainName), 400);
                            }, 300);
                        }, 300);
                    }, 300);
                }, 300);
            }

            function validateDomain(accountName, domainName) {
                // Domain format check
                const domainFormatResult = document.getElementById('domainFormatResult');
                const domainFormatDetails = document.getElementById('domainFormatDetails');
                
                // Check for basic domain format with a TLD
                const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z]{2,})+$/;
                const isValidDomain = domainRegex.test(domainName);
                
                if (isValidDomain) {
                    domainFormatResult.className = 'result-icon success mt-1 mr-4';
                    domainFormatResult.innerHTML = '<i class="fas fa-check"></i>';
                    domainFormatDetails.innerHTML = `
                        <div class="text-green-700">
                            Domain format is valid with proper structure and TLD.
                        </div>
                    `;
                } else {
                    domainFormatResult.className = 'result-icon fail mt-1 mr-4';
                    domainFormatResult.innerHTML = '<i class="fas fa-times"></i>';
                    domainFormatDetails.innerHTML = `
                        <div class="text-red-700">
                            Domain format is invalid. Must have a valid name and TLD (e.g., .com, .org).
                        </div>
                    `;
                }
                
                // MX record check (simulated)
                setTimeout(() => {
                    const mxRecordResult = document.getElementById('mxRecordResult');
                    const mxRecordDetails = document.getElementById('mxRecordDetails');
                    
                    // List of common domains with MX records
                    const knownDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com', 
                                         'protonmail.com', 'mail.com', 'zoho.com', 'yandex.com', 'gmx.com', 'live.com'];
                    
                    // Simulate MX record check based on known domains or domain components
                    let hasMxRecords = false;
                    let confidence = 'low';
                    
                    if (knownDomains.includes(domainName.toLowerCase())) {
                        hasMxRecords = true;
                        confidence = 'high';
                    } else if (domainName.toLowerCase().endsWith('.edu') || 
                              domainName.toLowerCase().endsWith('.gov') || 
                              domainName.toLowerCase().endsWith('.org') || 
                              domainName.toLowerCase().endsWith('.com')) {
                        hasMxRecords = Math.random() > 0.3; // 70% chance for common TLDs
                        confidence = 'medium';
                    }
                    
                    if (hasMxRecords) {
                        mxRecordResult.className = 'result-icon success mt-1 mr-4';
                        mxRecordResult.innerHTML = '<i class="fas fa-check"></i>';
                        mxRecordDetails.innerHTML = `
                            <div class="text-green-700">
                                Domain likely has MX records (${confidence} confidence in simulation).
                            </div>
                            ${confidence === 'high' ? 
                                `<div class="mt-2 text-blue-700">
                                    This is a well-known email provider with reliable mail servers.
                                </div>` : ''}
                        `;
                    } else {
                        mxRecordResult.className = 'result-icon fail mt-1 mr-4';
                        mxRecordResult.innerHTML = '<i class="fas fa-times"></i>';
                        mxRecordDetails.innerHTML = `
                            <div class="text-red-700">
                                Domain likely doesn't have MX records (simulation result).
                            </div>
                            <div class="mt-2 text-gray-700">
                                This domain may not be configured to receive emails.
                            </div>
                        `;
                    }
                    
                    // Continue to SMTP validation
                    setTimeout(() => simulateSmtpValidation(accountName, domainName, hasMxRecords), 600);
                }, 500);
            }

            function simulateSmtpValidation(accountName, domainName, hasMxRecords) {
                const smtpResult = document.getElementById('smtpResult');
                const smtpDetails = document.getElementById('smtpDetails');
                
                // Simulate SMTP validation with a connection process
                smtpDetails.innerHTML = `
                    <div class="text-blue-700 mb-2">
                        <i class="fas fa-spinner spinner mr-1"></i> Connecting to mail server...
                    </div>
                `;
                
                setTimeout(() => {
                    smtpDetails.innerHTML += `
                        <div class="text-blue-700 mb-2">
                            <i class="fas fa-check mr-1"></i> Connected to simulated mail server
                        </div>
                    `;
                    
                    setTimeout(() => {
                        smtpDetails.innerHTML += `
                            <div class="text-blue-700 mb-2">
                                <i class="fas fa-spinner spinner mr-1"></i> Sending EHLO command...
                            </div>
                        `;
                        
                        setTimeout(() => {
                            smtpDetails.innerHTML += `
                                <div class="text-blue-700 mb-2">
                                    <i class="fas fa-check mr-1"></i> Server acknowledged EHLO command
                                </div>
                            `;
                            
                            setTimeout(() => {
                                smtpDetails.innerHTML += `
                                    <div class="text-blue-700 mb-2">
                                        <i class="fas fa-spinner spinner mr-1"></i> Sending MAIL FROM command...
                                    </div>
                                `;
                                
                                setTimeout(() => {
                                    smtpDetails.innerHTML += `
                                        <div class="text-blue-700 mb-2">
                                            <i class="fas fa-check mr-1"></i> Server acknowledged MAIL FROM command
                                        </div>
                                    `;
                                    
                                    setTimeout(() => {
                                        smtpDetails.innerHTML += `
                                            <div class="text-blue-700 mb-2">
                                                <i class="fas fa-spinner spinner mr-1"></i> Sending RCPT TO command for ${accountName}@${domainName}...
                                            </div>
                                        `;
                                        
                                        setTimeout(() => {
                                            // Determine if the email likely exists based on domain reputation and randomness
                                            const knownDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com'];
                                            const isKnownDomain = knownDomains.includes(domainName.toLowerCase());
                                            
                                            // For demonstration, use different logic for known vs unknown domains
                                            let emailExists;
                                            
                                            if (isKnownDomain) {
                                                // For known domains, simulate that popular services don't reveal if emails exist
                                                emailExists = null; // null means "unknown"
                                                smtpDetails.innerHTML += `
                                                    <div class="text-yellow-700 mb-2">
                                                        <i class="fas fa-info-circle mr-1"></i> Server accepted recipient but doesn't confirm existence
                                                    </div>
                                                `;
                                            } else if (!hasMxRecords) {
                                                // If no MX records, email can't exist
                                                emailExists = false;
                                                smtpDetails.innerHTML += `
                                                    <div class="text-red-700 mb-2">
                                                        <i class="fas fa-times mr-1"></i> Server rejected recipient - domain not configured for email
                                                    </div>
                                                `;
                                            } else {
                                                // For other domains, simulate a random result
                                                emailExists = Math.random() > 0.5;
                                                if (emailExists) {
                                                    smtpDetails.innerHTML += `
                                                        <div class="text-green-700 mb-2">
                                                            <i class="fas fa-check mr-1"></i> Server accepted recipient
                                                        </div>
                                                    `;
                                                } else {
                                                    smtpDetails.innerHTML += `
                                                        <div class="text-red-700 mb-2">
                                                            <i class="fas fa-times mr-1"></i> Server rejected recipient - user not found
                                                        </div>
                                                    `;
                                                }
                                            }
                                            
                                            // Final SMTP result
                                            setTimeout(() => {
                                                if (emailExists === true) {
                                                    smtpResult.className = 'result-icon success mt-1 mr-4';
                                                    smtpResult.innerHTML = '<i class="fas fa-check"></i>';
                                                    smtpDetails.innerHTML += `
                                                        <div class="bg-green-50 p-3 rounded-md mt-2">
                                                            <span class="font-medium text-green-800">Final SMTP validation result:</span>
                                                            <div class="text-green-700 mt-1">
                                                                Email address appears to be deliverable.
                                                            </div>
                                                        </div>
                                                    `;
                                                } else if (emailExists === false) {
                                                    smtpResult.className = 'result-icon fail mt-1 mr-4';
                                                    smtpResult.innerHTML = '<i class="fas fa-times"></i>';
                                                    smtpDetails.innerHTML += `
                                                        <div class="bg-red-50 p-3 rounded-md mt-2">
                                                            <span class="font-medium text-red-800">Final SMTP validation result:</span>
                                                            <div class="text-red-700 mt-1">
                                                                Email address is not deliverable.
                                                            </div>
                                                        </div>
                                                    `;
                                                } else {
                                                    smtpResult.className = 'result-icon success mt-1 mr-4';
                                                    smtpResult.innerHTML = '<i class="fas fa-check"></i>';
                                                    smtpDetails.innerHTML += `
                                                        <div class="bg-yellow-50 p-3 rounded-md mt-2">
                                                            <span class="font-medium text-yellow-800">Final SMTP validation result:</span>
                                                            <div class="text-yellow-700 mt-1">
                                                                Email deliverability could not be definitively confirmed, but the domain is valid.
                                                            </div>
                                                            <div class="text-gray-700 mt-1">
                                                                (Many major email providers don't reveal if specific addresses exist for security reasons)
                                                            </div>
                                                        </div>
                                                    `;
                                                }
                                                
                                                // Complete the validation process and update summary
                                                setTimeout(() => finalizeValidation(accountName, domainName, hasMxRecords, emailExists), 300);
                                            }, 400);
                                        }, 600);
                                    }, 400);
                                }, 400);
                            }, 400);
                        }, 400);
                    }, 400);
                }, 500);
            }

            function finalizeValidation(accountName, domainName, hasMxRecords, emailExists) {
                // Collect all validation results
                const formatResult = document.getElementById('formatResult').className.includes('success');
                const charsResult = document.getElementById('charsResult').className.includes('success');
                const startEndResult = document.getElementById('startEndResult').className.includes('success');
                const doubleCharsResult = document.getElementById('doubleCharsResult').className.includes('success');
                const lengthResult = document.getElementById('lengthResult').className.includes('success');
                const onlyNumsResult = document.getElementById('onlyNumsResult').className.includes('success');
                const domainFormatResult = document.getElementById('domainFormatResult').className.includes('success');
                const mxRecordResult = document.getElementById('mxRecordResult').className.includes('success');
                const smtpResult = document.getElementById('smtpResult').className.includes('success');
                
                // Count passed and total checks
                const accountChecks = [charsResult, startEndResult, doubleCharsResult, lengthResult, onlyNumsResult];
                const passedAccountChecks = accountChecks.filter(check => check).length;
                
                const allChecks = [formatResult, ...accountChecks, domainFormatResult, mxRecordResult, smtpResult];
                const passedChecks = allChecks.filter(check => check).length;
                
                // Update overall result
                const overallResult = document.getElementById('overallResult');
                const summaryTitle = document.getElementById('summaryTitle');
                const summaryText = document.getElementById('summaryText');
                
                // Enable the validation button again
                document.getElementById('validateBtn').disabled = false;
                document.getElementById('validateSpinner').classList.add('hidden');
                
                // Determine overall validity level
                let validityLevel = 'invalid';
                if (formatResult && passedAccountChecks >= 4 && domainFormatResult) {
                    if (mxRecordResult && smtpResult) {
                        validityLevel = 'high';
                    } else if (mxRecordResult || smtpResult) {
                        validityLevel = 'medium';
                    } else {
                        validityLevel = 'low';
                    }
                }
                
                // Update UI based on validity level
                if (validityLevel === 'high') {
                    overallResult.className = 'result-icon success mr-4';
                    overallResult.innerHTML = '<i class="fas fa-check"></i>';
                    summaryTitle.textContent = 'Email is valid with high confidence';
                    summaryText.textContent = `${accountName}@${domainName} passed ${passedChecks} out of ${allChecks.length} checks, including format, domain, and deliverability tests.`;
                } else if (validityLevel === 'medium') {
                    overallResult.className = 'result-icon success mr-4';
                    overallResult.innerHTML = '<i class="fas fa-check"></i>';
                    summaryTitle.textContent = 'Email is likely valid';
                    summaryText.textContent = `${accountName}@${domainName} passed basic validation but has some potential issues with deliverability.`;
                } else if (validityLevel === 'low') {
                    overallResult.className = 'result-icon pending mr-4';
                    overallResult.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    summaryTitle.textContent = 'Email has format issues';
                    summaryText.textContent = `${accountName}@${domainName} has valid format but domain deliverability could not be confirmed.`;
                } else {
                    overallResult.className = 'result-icon fail mr-4';
                    overallResult.innerHTML = '<i class="fas fa-times"></i>';
                    summaryTitle.textContent = 'Email is invalid';
                    summaryText.textContent = `${accountName}@${domainName} failed critical validation checks and is not deliverable.`;
                }
            }
        });
    </script>
</body>
</html>
