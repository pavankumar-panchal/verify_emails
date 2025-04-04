<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Split & Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .loader {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 30px;
            height: 30px;
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

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .verified {
            background-color: #dcfce7;
            color: #166534;
        }

        .not-verified {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .correct {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .wrong {
            background-color: #ffedd5;
            color: #9a3412;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen px-4">
    <div class="mt-10 w-full max-w-6xl">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">
            <i class="fas fa-envelope-open-text mr-2"></i>Split Emails & Verify Domains
        </h2>

        <!-- File Upload Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <form id="csvForm" class="flex flex-col items-center w-full" enctype="multipart/form-data">
                <div class="w-full max-w-md mb-4">
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">Select CSV File</label>
                    <input type="file" id="csv_file" name="csv_file" required accept=".csv"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-500"></p>
                </div>
                <button type="submit" id="uploadBtn"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition flex items-center justify-center min-w-[150px]">
                    <span id="uploadText">Upload & Process</span>
                    <div id="uploadSpinner" class="loader ml-2 hidden"></div>
                </button>
            </form>
            <div id="statusMessage" class="hidden mt-4 text-center p-3 rounded-md"></div>
        </div>

        <!-- Search and Controls Section -->
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
        <div class="bg-white p-4 rounded-lg shadow-md overflow-x-auto">
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
        // Global variables
        let emailsData = [];
        let currentPage = 1;
        let rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
        let totalPages = 0;
        let isLoading = false;

        // DOM elements
        const elements = {
            tableBody: document.getElementById('email-table'),
            emptyState: document.getElementById('emptyState'),
            loadingState: document.getElementById('loadingState'),
            statusMessage: document.getElementById('statusMessage'),
            resultCount: document.getElementById('resultCount'),
            uploadBtn: document.getElementById('uploadBtn'),
            uploadText: document.getElementById('uploadText'),
            uploadSpinner: document.getElementById('uploadSpinner'),
            searchInput: document.getElementById('searchInput'),
            exportValidBtn: document.getElementById('exportValidBtn'),
            exportInvalidBtn: document.getElementById('exportInvalidBtn'),
            pagination: {
                firstPage: document.getElementById('firstPage'),
                prevPage: document.getElementById('prevPage'),
                nextPage: document.getElementById('nextPage'),
                lastPage: document.getElementById('lastPage'),
                jumpToPage: document.getElementById('jumpToPage'),
                totalPages: document.getElementById('totalPages'),
                rowsPerPage: document.getElementById('rowsPerPage'),
                startItem: document.getElementById('startItem'),
                endItem: document.getElementById('endItem'),
                totalItems: document.getElementById('totalItems')
            }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            elements.emptyState.classList.remove('hidden');
            elements.loadingState.classList.add('hidden');
            fetchEmails();
            setupEventListeners();
        });

        function setupEventListeners() {
            document.getElementById("csvForm").addEventListener("submit", handleFileUpload);
            elements.searchInput.addEventListener("input", debounce(() => {
                currentPage = 1;
                displayEmails();
            }, 300));

            // Export buttons
            elements.exportValidBtn.addEventListener("click", exportValidEmails);
            elements.exportInvalidBtn.addEventListener("click", exportInvalidEmails);

            // Pagination
            elements.pagination.firstPage.addEventListener("click", () => navigateToPage(1));
            elements.pagination.prevPage.addEventListener("click", () => navigateToPage(currentPage - 1));
            elements.pagination.nextPage.addEventListener("click", () => navigateToPage(currentPage + 1));
            elements.pagination.lastPage.addEventListener("click", () => navigateToPage(totalPages));
            elements.pagination.jumpToPage.addEventListener("change", (e) => {
                const page = parseInt(e.target.value);
                if (page >= 1 && page <= totalPages) {
                    navigateToPage(page);
                } else {
                    e.target.value = currentPage;
                }
            });

            elements.pagination.rowsPerPage.addEventListener("change", (e) => {
                rowsPerPage = parseInt(e.target.value);
                currentPage = 1;
                displayEmails();
            });
        }

        function debounce(func, wait) {
            let timeout;
            return function () {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    func.apply(context, args);
                }, wait);
            };
        }

        function navigateToPage(page) {
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;
            elements.pagination.jumpToPage.value = page;
            displayEmails();
        }

        async function fetchEmails() {
            if (isLoading) return;
            isLoading = true;
            showLoadingState();

            try {
                const response = await fetch('email_processor.php');
                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();

                if (!Array.isArray(data)) {
                    throw new Error("Invalid data format received from server");
                }

                emailsData = data;
                displayEmails();
            } catch (error) {
                console.error("Error fetching emails:", error);
                showStatusMessage(`Error: ${error.message}`, 'error');
                showEmptyState();
            } finally {
                isLoading = false;
            }
        }

        function displayEmails() {
            if (isLoading) return;

            const searchQuery = elements.searchInput.value.toLowerCase();
            let filteredEmails = emailsData;

            if (searchQuery) {
                filteredEmails = emailsData.filter(email =>
                    (email.raw_emailid && email.raw_emailid.toLowerCase().includes(searchQuery)) ||
                    (email.sp_account && email.sp_account.toLowerCase().includes(searchQuery)) ||
                    (email.sp_domain && email.sp_domain.toLowerCase().includes(searchQuery))
                );
            }

            totalPages = Math.ceil(filteredEmails.length / rowsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * rowsPerPage;
            const paginatedEmails = filteredEmails.slice(startIndex, startIndex + rowsPerPage);

            updatePaginationInfo(filteredEmails.length, startIndex, paginatedEmails.length);

            if (filteredEmails.length === 0) {
                showEmptyState();
            } else {
                renderTable(paginatedEmails);
            }
        }

        function updatePaginationInfo(totalItems, startIndex, currentPageItems) {
            elements.pagination.totalPages.textContent = `of ${totalPages}`;
            elements.pagination.jumpToPage.value = currentPage;
            elements.pagination.jumpToPage.max = totalPages;

            elements.pagination.startItem.textContent = startIndex + 1;
            elements.pagination.endItem.textContent = startIndex + currentPageItems;
            elements.pagination.totalItems.textContent = totalItems;

            elements.resultCount.textContent = `${totalItems} ${totalItems === 1 ? 'result' : 'results'} found`;

            elements.pagination.firstPage.disabled = currentPage === 1;
            elements.pagination.prevPage.disabled = currentPage === 1;
            elements.pagination.nextPage.disabled = currentPage >= totalPages;
            elements.pagination.lastPage.disabled = currentPage >= totalPages;
        }

        function renderTable(emails) {
            elements.tableBody.innerHTML = '';
            elements.emptyState.classList.add('hidden');
            elements.loadingState.classList.add('hidden');

            emails.forEach(row => {
                const rowElement = document.createElement('tr');
                rowElement.className = 'hover:bg-gray-50 transition';
                rowElement.dataset.id = row.id;

                rowElement.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.id || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${row.raw_emailid || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.sp_account || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.sp_domain || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="status-badge ${row.domain_verified == 1 ? 'verified' : 'not-verified'}">
                            ${row.domain_verified == 1 ? 'Verified' : 'Not Verified'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="status-badge ${row.domain_status == 1 ? 'correct' : 'wrong'}">
                            ${row.domain_status == 1 ? 'Correct' : 'Wrong'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="tooltip">
                            ${truncateText(row.validation_response || 'N/A', 20)}
                            ${row.validation_response ? `<span class="tooltiptext">${row.validation_response}</span>` : ''}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button onclick="deleteEmail(${row.id}, this)"
                            class="text-red-600 hover:text-red-900 mr-3 tooltip">
                            <i class="fas fa-trash-alt"></i>
                            <span class="tooltiptext">Delete this email</span>
                        </button>

                     
                    </td>
                `;
                elements.tableBody.appendChild(rowElement);
            });
        }

        function truncateText(text, maxLength) {
            if (!text) return 'N/A';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }

        function showLoadingState() {
            elements.tableBody.innerHTML = '';
            elements.emptyState.classList.add('hidden');
            elements.loadingState.classList.remove('hidden');
        }

        function showEmptyState() {
            elements.tableBody.innerHTML = '';
            elements.loadingState.classList.add('hidden');
            elements.emptyState.classList.remove('hidden');
        }

        function showStatusMessage(message, type = 'info') {
            elements.statusMessage.classList.remove('hidden');
            elements.statusMessage.textContent = message;
            elements.statusMessage.className = `mt-4 text-center p-3 rounded-md ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;

            setTimeout(() => {
                elements.statusMessage.classList.add('hidden');
            }, 5000);
        }

        async function handleFileUpload(event) {
            event.preventDefault();

            const fileInput = document.getElementById('csv_file');
            if (fileInput.files.length === 0) {
                showStatusMessage("Please select a CSV file first.", 'error');
                return;
            }

            if (fileInput.files[0].size > 5 * 1024 * 1024) {
                showStatusMessage("File size exceeds 5MB limit.", 'error');
                return;
            }

            elements.uploadText.textContent = "Processing...";
            elements.uploadSpinner.classList.remove('hidden');
            elements.uploadBtn.disabled = true;

            const formData = new FormData(document.getElementById("csvForm"));

            try {
                const response = await fetch('email_processor.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showStatusMessage(data.message || "File processed successfully!");
                    await fetchEmails();
                    currentPage = 1;
                    displayEmails();
                } else {
                    throw new Error(data.message || "Error during file processing");
                }
            } catch (error) {
                showStatusMessage(error.message, 'error');
            } finally {
                elements.uploadText.textContent = "Upload & Process";
                elements.uploadSpinner.classList.add('hidden');
                elements.uploadBtn.disabled = false;
                document.getElementById("csvForm").reset();
            }
        }

        async function deleteEmail(id, button) {
            if (!confirm("Are you sure you want to delete this email record?")) return;

            const row = button.closest('tr');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            if (row) {
                row.style.opacity = '0.5';
            }

            try {
                const response = await fetch(`email_processor.php?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                if (data.status === 'success') {
                    emailsData = emailsData.filter(email => String(email.id) !== String(id));

                    if (row) row.remove();

                    const startIndex = (currentPage - 1) * rowsPerPage;
                    if (emailsData.length <= startIndex && currentPage > 1) {
                        currentPage--;
                    }

                    displayEmails();
                    showStatusMessage("Email deleted successfully!");
                } else {
                    throw new Error(data.message || "Error deleting email");
                }
            } catch (error) {
                console.error("Delete error:", error);
                showStatusMessage(error.message || "Failed to delete email", 'error');
                if (row) row.style.opacity = '1';
            } finally {
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        }


        function exportToCSV(emails, filename) {
            if (!emails || emails.length === 0) {
                showStatusMessage("No emails to export", 'error');
                return;
            }

            // Create CSV content with just the email addresses
            let csvContent = "Email\n"; // Single column header

            emails.forEach(email => {
                // Escape quotes and wrap in quotes if contains comma
                const emailValue = email.raw_emailid || '';
                csvContent += `"${emailValue.replace(/"/g, '""')}"\n`;
            });

            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        function exportValidEmails() {
            const validEmails = emailsData
                .filter(email => email.domain_verified == 1 && email.domain_status == 1)
                .map(email => ({ raw_emailid: email.raw_emailid })); // Only keep email field

            if (validEmails.length === 0) {
                showStatusMessage("No valid emails to export", 'error');
                return;
            }
            exportToCSV(validEmails, 'valid_emails.csv');
            showStatusMessage(`Exported ${validEmails.length} valid emails`, 'info');
        }


        function exportInvalidEmails() {
            const invalidEmails = emailsData
                .filter(email => email.domain_verified != 1 || email.domain_status != 1)
                .map(email => ({ raw_emailid: email.raw_emailid })); // Only keep email field

            if (invalidEmails.length === 0) {
                showStatusMessage("No invalid emails to export", 'error');
                return;
            }
            exportToCSV(invalidEmails, 'invalid_emails.csv');
            showStatusMessage(`Exported ${invalidEmails.length} invalid emails`, 'info');
        }

        // Make functions available globally
        window.deleteEmail = deleteEmail;
        // window.verifySingleEmail = verifySingleEmail;
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                document.getElementById("nextPage").click();
            }, 100); // Ensures the button is fully loaded before clicking
        });
    </script>
</body>

</html>