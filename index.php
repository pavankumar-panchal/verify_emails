<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Split & Verification</title>

    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/main.css">

    <link rel="stylesheet" href="assets/style_tailwind.css">
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="assets/ajax.js"></script>

    <style>
        .custom-file-input {
            display: block;
            width: 100%;
            color: #6B7280;
            /* Tailwind's gray-500 */
            font-size: 0.875rem;
            /* text-sm */
            border: none;
            padding: 0;
            background-color: transparent;
        }

        .custom-file-input::file-selector-button {
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            background-color: #EFF6FF;
            /* Tailwind's blue-50 */
            color: #1D4ED8;
            /* Tailwind's blue-700 */
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .custom-file-input::file-selector-button:hover {
            background-color: #DBEAFE;
            /* Tailwind's blue-100 */
        }

        /* Navbar styles */

        /* Progress overlay styles */
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

        .main-content {
            margin-top: 80px;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
    <?php
    include 'navbar.php';
    ?>


    <div id="progressOverlay" class="progress-overlay hidden">
        <div class="circle-loader">
            <svg width="180" height="180">
                <circle class="circle-bg" cx="90" cy="90" r="75"></circle>
                <circle class="circle-progress" cx="90" cy="90" r="75" stroke-dasharray="471" stroke-dashoffset="471">
                </circle>
            </svg>
            <div class="loader-text" id="progressText">0%</div>
        </div>
        <div class="progress-label" id="progressLabel">Processing Emails</div>
    </div>


    <div class="mt-4 w-full max-w-7xl">
        <!-- <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">
            <i class="fas fa-envelope-open-text mr-2"></i>Split Emails & Verify Domains
        </h2> -->
        <!-- File Upload Section -->
        <div class="bg-white p-8 rounded-xl shadow-lg mb-6 border border-gray-100">
            <form id="csvForm" class="w-full" enctype="multipart/form-data">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            Upload Email List
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Upload your CSV file to verify email addresses</p>
                    </div>

                    <!-- Form Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- List Name Field -->
                        <div class="space-y-1">
                            <label for="list_name" class="block text-sm font-medium text-gray-700 flex items-center">
                                <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                List Name
                            </label>
                            <input type="text" id="list_name" name="list_name" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                placeholder="e.g. csv_list_2025">
                        </div>

                        <!-- File Name Field -->
                        <div class="space-y-1">
                            <label for="file_name" class="block text-sm font-medium text-gray-700 flex items-center">
                                <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                File Name
                            </label>
                            <input type="text" id="file_name" name="file_name" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                placeholder="e.g. csv_file_2025">
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="space-y-1">
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 flex items-center">
                            <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            CSV File
                        </label>
                        <div
                            class="mt-1 flex justify-center px-4 pt-4 pb-4 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition">
                            <div class="text-center text-sm">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <label for="csv_file"
                                    class="cursor-pointer text-blue-600 hover:text-blue-500 font-medium">
                                    Upload a file
                                    <input id="csv_file" name="csv_file" type="file" class="sr-only" required
                                        accept=".csv">
                                </label>
                                <p class="text-xs text-gray-500">or drag and drop.</p>
                                <p id="fileNameDisplay" class="mt-2 text-sm text-gray-600"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4">
                        <button type="submit" id="uploadBtn"
                            class="inline-flex items-center justify-center px-5 py-2.5 text-sm bg-gradient-to-r from-blue-500 to-blue-600 text-white font-medium rounded-md shadow-md hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            <span id="uploadText">Upload & Process</span>
                            <div id="uploadSpinner" class="hidden ml-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Status Message -->
            <div id="statusMessage" class="hidden mt-6 p-4 rounded-lg text-center"></div>
        </div>


        <div class="bg-white p-4 rounded-lg shadow-md mb-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-list mr-2"></i>Verified Emails
                    </h3>
                    <p id="resultCount" class="text-sm text-gray-500">No data loaded</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchInput" placeholder="Search emails, domains..."
                            class="pl-10 border border-gray-300 p-2 rounded-md w-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-2">

                        <button id="exportValidBtn"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i>Export Valid
                        </button>
                        <button id="exportInvalidBtn"
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i>Export Invalid
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            sp_account</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            sp_domain</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Verified</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Validation Response</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="email-table" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
            <div id="emptyState" class="text-center py-8">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                <h4 class="text-lg font-medium text-gray-500">No emails found</h4>
                <p class="text-gray-400">Upload a CSV file to get started</p>
            </div>

            <div id="loadingState" class="hidden text-center py-8">
                <div class="loader mx-auto"></div>
                <p class="mt-2 text-gray-500">Loading email data...</p>
            </div>

        </div>

        <!-- Pagination Controls -->
        <div
            class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-lg shadow-md">
            <div class="text-sm text-gray-500">
                Showing <span id="startItem">0</span> to <span id="endItem">0</span> of <span id="totalItems">0</span>
                entries
            </div>
            <div class="flex items-center gap-2">
                <button id="firstPage"
                    class="pagination-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 disabled:opacity-50">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button id="prevPage"
                    class="pagination-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 disabled:opacity-50">
                    <i class="fas fa-angle-left"></i>
                </button>
                <div class="flex items-center gap-1">
                    <span class="text-sm">Page</span>
                    <input type="number" id="jumpToPage" class="border p-1 w-12 text-center rounded-md text-sm" min="1">
                    <span id="totalPages" class="text-sm text-gray-500">of 0</span>
                </div>
                <button id="nextPage"
                    class="pagination-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 disabled:opacity-50">
                    <i class="fas fa-angle-right"></i>
                </button>
                <button id="lastPage"
                    class="pagination-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-300 disabled:opacity-50">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Rows per page:</span>
                <select id="rowsPerPage" class="border p-1 rounded-md text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <script>




        function triggerDomainVerification() {
            fetch('includes/pro.php')  // You can create this PHP script to handle the background task
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showStatusMessage("Verification process started!", "success");
                        setTimeout(() => {
                            fetchProgress();  // Start checking progress after 10 seconds
                        }, 10000);
                    } else {
                        showStatusMessage("Error starting verification process.", "error");
                    }
                })
                .catch(error => {
                    console.error("Error triggering domain verification:", error);
                    showStatusMessage("Error triggering domain verification.", "error");
                });
        }

        // Progress Tracking

        const progressOverlay = document.getElementById('progressOverlay');
        const progressText = document.getElementById('progressText');
        const progressLabel = document.getElementById('progressLabel');
        const progressCircle = document.querySelector('.circle-progress');
        const csvForm = document.getElementById('csvForm');
        const statusMessage = document.getElementById('statusMessage');

        let progressInterval;

        function showProgressOverlay() {
            progressOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideProgressOverlay() {
            progressOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function updateProgress(processed, total, percent = null) {
            const p = percent ?? (total > 0 ? Math.round((processed / total) * 100) : 0);
            const circumference = 471;
            const offset = circumference - (circumference * p / 100);

            progressCircle.style.strokeDashoffset = offset;
            progressText.textContent = `${p}%`;
        }



        function showStatusMessage(message, type = 'info') {
            statusMessage.textContent = message;
            statusMessage.className = `mt-4 text-center p-3 rounded-md ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
                }`;
            statusMessage.classList.remove('hidden');

            setTimeout(() => {
                statusMessage.classList.add('hidden');
            }, 5000);
        }

        function fetchProgress() {
            fetch('./includes/progress.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.details || 'Progress error');

                    // Update progress bar
                    updateProgress(data.processed, data.total, data.percent);

                    // Update label based on stage
                    if (data.stage === "domain") {
                        progressLabel.textContent = "Verifying Domains...";
                    } else if (data.stage === "smtp") {
                        progressLabel.textContent = "Verifying SMTP...";
                    }

                    // When complete
                    if (data.total > 0 && data.processed >= data.total) {
                        clearInterval(progressInterval);
                        setTimeout(() => {
                            hideProgressOverlay();
                            showStatusMessage("Verification completed successfully!", "success");
                            fetchEmails(); // Optional: Refresh the email list
                            setTimeout(() => {
                                location.reload(); // 🔄 Reload after short delay
                            }, 1000);
                        }, 1000);
                    }
                })
                .catch(err => {
                    console.error('Progress check error:', err);
                    clearInterval(progressInterval);
                    hideProgressOverlay();
                    showStatusMessage("Error checking verification progress", "error");
                });
        }

        // Check for existing progress on page load
        document.addEventListener('DOMContentLoaded', () => {
            hideProgressOverlay();

            // Check if verification is already running
            fetch('./includes/progress.php')
                .then(response => {
                    if (!response.ok) throw new Error('Progress check failed');
                    return response.json();
                })
                .then(data => {
                    if (data.total > 0 && data.processed < data.total) {
                        showProgressOverlay();
                        updateProgress(data.processed, data.total, data.percent);

                        progressLabel.textContent = data.stage === "smtp" ?
                            "Verifying SMTP..." : "Verifying Domains...";
                        progressInterval = setInterval(fetchProgress, 2000);
                    }
                })
                .catch(err => {
                    console.error('Initial progress check error:', err);
                });
        });

        // Make sure to clear interval when leaving page
        window.addEventListener('beforeunload', () => {
            if (progressInterval) {
                clearInterval(progressInterval);
            }
        });

        document.getElementById('csv_file').addEventListener('change', function () {
            const fileInput = this;
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = `Selected file: ${fileInput.files[0].name}`;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    </script>



    <script src="./assets/script.js"></script>




</body>

</html>