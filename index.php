<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Split & Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
  
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

   
    <script src="./assets/script.js"></script>
</body>

</html>