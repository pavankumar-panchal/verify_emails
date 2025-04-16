

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
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        document.getElementById("nextPage").click();
    }, 100); // Ensures the button is fully loaded before clicking
});

