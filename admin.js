
const API_URL = 'api.php';
let clientsData = [];
let loansData = [];
let pendingLoans = [];
let branchChartInstance = null;

function showMessageModal(title, message, type = 'success') {
    const modal = document.getElementById('adminMessageModal');
    const modalTitle = document.getElementById('adminModalTitle');
    const modalMessage = document.getElementById('adminModalMessage');
    const modalIcon = document.getElementById('adminModalIcon');

    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    modalIcon.className = 'mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4';
    if (type === 'success') {
        modalIcon.classList.add('bg-green-100');
        modalIcon.innerHTML = '<i class="fas fa-check text-green-600 text-xl"></i>';
    } else if (type === 'error') {
        modalIcon.classList.add('bg-red-100');
        modalIcon.innerHTML = '<i class="fas fa-times text-red-600 text-xl"></i>';
    } else {
        modalIcon.classList.add('bg-blue-100');
        modalIcon.innerHTML = '<i class="fas fa-info text-blue-600 text-xl"></i>';
    }

    modal.classList.remove('hidden');
}

document.getElementById('closeAdminMessageModal').addEventListener('click', function() {
    document.getElementById('adminMessageModal').classList.add('hidden');
});

function formatCurrency(amount) {
    return `₱${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}


async function fetchAdminData() {
    try {
        const response = await fetch(API_URL + '?action=get_admin_data');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        if (data.error) {
            console.error('API Error:', data.error);
            showMessageModal('Data Error', data.error, 'error');
            return;
        }
    
        clientsData = data.clients;
        loansData = data.loans;
        pendingLoans = data.pending_loans;

        updateDashboardStats();
        populatePendingLoans();
        populateClientsTable();
        populateLoansTable();
        updateBranchStatistics();

    } catch (error) {
        console.error('Fetch error:', error);
        showMessageModal('Connection Error', 'Failed to load data. Please check your PHP server and database connection.', 'error');
    }
}


function updateDashboardStats() {
    const totalClients = clientsData.length;
    const activeLoans = loansData.filter(l => l.loan_status === 'Active').length;
    const pendingCount = pendingLoans.length;
    const totalOutstanding = loansData.reduce((sum, loan) => sum + parseFloat(loan.current_balance), 0);

    document.getElementById('totalClientsCount').textContent = totalClients;
    document.getElementById('activeLoansCount').textContent = activeLoans;
    document.getElementById('pendingLoansCount').textContent = pendingCount;
    document.getElementById('totalOutstandingAmount').textContent = formatCurrency(totalOutstanding); //Change this into active loans only
}

function updateBranchStatistics() {
    const branches = ['malolos', 'hagonoy', 'calumpit', 'balagtas'];
    const loanCounts = branches.map(branch => 
        loansData.filter(loan => 
            clientsData.find(c => c.client_id == loan.client_id)?.c_branch === branch
        ).length
    );

    const ctx = document.getElementById('branchChart').getContext('2d');
    
    if (branchChartInstance) {
        branchChartInstance.destroy();
    }

    branchChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: branches.map(b => b.charAt(0).toUpperCase() + b.slice(1)),
            datasets: [{
                label: 'Total Active/Paid Loans',
                data: loanCounts,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function populateClientsTable(searchTerm = '') {
    const clientsBody = document.getElementById('clientsTableBody');
    clientsBody.innerHTML = '';
    
    const filteredClients = clientsData.filter(c => 
        c.c_firstname.toLowerCase().includes(searchTerm.toLowerCase()) || 
        c.c_lastname.toLowerCase().includes(searchTerm.toLowerCase()) ||
        c.member_id.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (filteredClients.length === 0) {
        clientsBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No clients found.</td></tr>';
        return;
    }

    // Use the Client Details Modal
    filteredClients.forEach(client => {
        const row = clientsBody.insertRow();
        const outstanding = loansData
            .filter(l => l.client_id == client.client_id && l.loan_status === 'Active')
            .reduce((sum, l) => sum + parseFloat(l.current_balance), 0);
        
        let statusBadge = '';
        if (outstanding > 0) {
            statusBadge = `<span class="status-badge bg-blue-100 text-blue-800">Active Loan</span>`;
        } else {
            statusBadge = `<span class="status-badge bg-green-100 text-green-800">No Loan</span>`;
        }

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${client.member_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${client.c_firstname} ${client.c_lastname}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">${formatCurrency(outstanding)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button 
                onclick="showClientDetailsModal('${client.member_id}', '${client.c_firstname} ${client.c_lastname}', '${client.c_email || 'N/A'}', '${client.c_phone || 'N/A'}', '${client.c_address}', '${client.c_branch || 'N/A'}','${client.date_joined || 'N/A'}')" 
                class="text-blue-600 hover:text-blue-900 mx-1">
                    <i class="fas fa-eye"></i>
                </button>
                <button 
                onclick="showNewLoanModal(${client.client_id}, '${client.c_firstname} ${client.c_lastname}')" 
                class="text-green-600 hover:text-green-900 mx-1">
                    <i class="fas fa-money-bill"></i>
                </button>
            </td>
        `;
    });
}

function populateLoansTable(searchTerm = '') {
    const loansBody = document.getElementById('loansTableBody');
    loansBody.innerHTML = '';
    
    const activeAndPaidLoans = loansData.filter(l => l.loan_status !== 'Pending' && l.loan_status !== 'Declined');
    
    const filteredLoans = activeAndPaidLoans.filter(loan => {
        const client = clientsData.find(c => c.client_id == loan.client_id);
        const name = client ? `${client.c_firstname} ${client.c_lastname}` : '';
        return name.toLowerCase().includes(searchTerm.toLowerCase()) || 
               loan.loan_id.toString().includes(searchTerm);
    });

    if (filteredLoans.length === 0) {
        loansBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">No active or paid loans found.</td></tr>';
        return;
    }

    filteredLoans.forEach(loan => {
        const client = clientsData.find(c => c.client_id == loan.client_id);
        const clientName = client ? `${client.c_firstname} ${client.c_lastname}` : 'N/A';
        
        let statusBadge = '';
        if (loan.loan_status === 'Active') {
            statusBadge = `<span class="status-badge bg-blue-100 text-blue-800">${loan.loan_status}</span>`;
        } else if (loan.loan_status === 'Paid') {
            statusBadge = `<span class="status-badge bg-green-100 text-green-800">${loan.loan_status}</span>`;
        } else if (loan.loan_status === 'Overdue') {
            statusBadge = `<span class="status-badge bg-red-100 text-red-800">${loan.loan_status}</span>`;
        } else {
             statusBadge = `<span class="status-badge bg-gray-100 text-gray-800">${loan.loan_status}</span>`;
        }
        const row = loansBody.insertRow();
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">L${loan.loan_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${clientName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatCurrency(loan.loan_amount)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">${formatCurrency(loan.current_balance)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button onclick="showMessageModal('Loan Details', 'Loan ID: L${loan.loan_id}\\nAmount: ${formatCurrency(loan.loan_amount)}\\nBalance: ${formatCurrency(loan.current_balance)}\\nMonthly Payment: ${formatCurrency(loan.monthly_payment)}\\nPurpose: ${loan.loan_purpose}', 'info')"
                        class="text-blue-600 hover:text-blue-900 mx-1"><i class="fas fa-eye"></i></button>
            </td>
        `;
    });
}

function populatePendingLoans() {
    const pendingBody = document.getElementById('pendingLoansTableBody');
    pendingBody.innerHTML = '';
    
    if (pendingLoans.length === 0) {
        pendingBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No pending applications.</td></tr>';
        return;
    }

    pendingLoans.forEach(loan => {
        const row = pendingBody.insertRow();
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${loan.client_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatCurrency(loan.loan_amount)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${loan.term_months} Months</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${loan.loan_purpose.substring(0, 50)}...</td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button onclick="showApproveLoanModal(${loan.loan_id})" class="text-green-600 hover:text-green-900 mx-1 p-1 rounded hover:bg-green-100"><i class="fas fa-check"></i> Approve</button>
                <button onclick="declineLoan(${loan.loan_id})" class="text-red-600 hover:text-red-900 mx-1 p-1 rounded hover:bg-red-100"><i class="fas fa-times"></i> Decline</button>
            </td>
        `;
    });
}


document.getElementById('showAddClientModal').addEventListener('click', () => document.getElementById('addClientModal').classList.remove('hidden'));
document.getElementById('closeAddClientModal').addEventListener('click', () => document.getElementById('addClientModal').classList.add('hidden'));

document.getElementById('addClientForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('addClientSubmitBtn');
    const initialBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
    submitBtn.disabled = true;

    const formData = new FormData(this);
    formData.append('action', 'add_client');

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showMessageModal('Client Added!', result.message, 'success');
            this.reset();
            document.getElementById('addClientModal').classList.add('hidden');
            fetchAdminData();
        } else {
            showMessageModal('Failed to Add Client', result.message || 'An unexpected error occurred.', 'error');
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error');
    } finally {
        submitBtn.innerHTML = initialBtnText;
        submitBtn.disabled = false;
    }
});

window.showApproveLoanModal = function(loanId) {
    const loan = pendingLoans.find(l => l.loan_id == loanId);
    if (!loan) {
        showMessageModal('Error', 'Loan not found.', 'error');
        return;
    }


    document.getElementById('loanIdToApprove').value = loanId;
    document.getElementById('approveClientName').textContent = loan.client_name;
    document.getElementById('approveLoanAmount').textContent = formatCurrency(loan.loan_amount);
    document.getElementById('approveLoanTerm').textContent = `${loan.term_months} Months`;
    

    const principal = parseFloat(loan.loan_amount);
    const termMonths = parseInt(loan.term_months);
    const annualRate = parseFloat(document.getElementById('approveInterestRate').value);
    
    const monthlyRate = (annualRate / 100) / 12;
    const monthlyPayment = principal * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -termMonths)));
    
    document.getElementById('calculatedMonthlyPayment').value = isNaN(monthlyPayment) 
        ? 'N/A' 
        : formatCurrency(monthlyPayment).replace('₱', '');

    document.getElementById('approveLoanModal').classList.remove('hidden');
}

document.getElementById('approveInterestRate').addEventListener('input', function() {
    const loanId = document.getElementById('loanIdToApprove').value;
    const loan = pendingLoans.find(l => l.loan_id == loanId);
    if (!loan) return;
    
    const principal = parseFloat(loan.loan_amount);
    const termMonths = parseInt(loan.term_months);
    const annualRate = parseFloat(this.value);

    const monthlyRate = (annualRate / 100) / 12;

    let monthlyPayment;
    if (termMonths <= 0 || monthlyRate <= 0) {
        monthlyPayment = principal / termMonths;
    } else {
        monthlyPayment = principal * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -termMonths)));
    }
    
    document.getElementById('calculatedMonthlyPayment').value = isNaN(monthlyPayment) 
        ? 'N/A' 
        : formatCurrency(monthlyPayment).replace('₱', '');
});


document.getElementById('closeApproveModal').addEventListener('click', () => document.getElementById('approveLoanModal').classList.add('hidden'));

document.getElementById('approveLoanForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('approveLoanSubmitBtn');
    const initialBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Approving...';
    submitBtn.disabled = true;

    const loanId = document.getElementById('loanIdToApprove').value;
    const interestRate = document.getElementById('approveInterestRate').value;
    const monthlyPayment = document.getElementById('calculatedMonthlyPayment').value.replace(/,/g, '');

    const formData = new FormData();
    formData.append('action', 'approve_loan');
    formData.append('loan_id', loanId);
    formData.append('interest_rate', interestRate);
    formData.append('monthly_payment', monthlyPayment);

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showMessageModal('Loan Approved!', result.message, 'success');
            document.getElementById('approveLoanModal').classList.add('hidden');
            fetchAdminData();
        } else {
            showMessageModal('Approval Failed', result.message || 'An unexpected error occurred.', 'error');
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error');
    } finally {
        submitBtn.innerHTML = initialBtnText;
        submitBtn.disabled = false;
    }
});

window.declineLoan = async function(loanId) {
    const loan = pendingLoans.find(l => l.loan_id == loanId);
    if (!loan) {
        showMessageModal('Error', 'Loan not found.', 'error');
        return;
    }
    
    showMessageModal(
        'Confirm Decline', 
        `Are you sure you want to decline the loan application for ${loan.client_name} (₱${parseFloat(loan.loan_amount).toLocaleString()})?`, 
        'info'
    );

    document.getElementById('closeAdminMessageModal').onclick = async function() {
        document.getElementById('adminMessageModal').classList.add('hidden');
        
        try {
            const formData = new FormData();
            formData.append('action', 'decline_loan');
            formData.append('loan_id', loanId);

            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showMessageModal('Loan Declined!', result.message, 'success');
                fetchAdminData();
            } else {
                showMessageModal('Decline Failed', result.message || 'An unexpected error occurred.', 'error');
            }
        } catch (error) {
            showMessageModal('Network Error', 'Could not connect to the server.', 'error');
        } finally {
            document.getElementById('closeAdminMessageModal').onclick = function() {
                document.getElementById('adminMessageModal').classList.add('hidden');
            };
        }
    };
}

document.getElementById('showPaymentQRModal').addEventListener('click', () => document.getElementById('qrScannerModal').classList.remove('hidden'));
document.getElementById('closeQRScannerModal').addEventListener('click', () => document.getElementById('qrScannerModal').classList.add('hidden'));

document.getElementById('recordPaymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('recordPaymentSubmitBtn');
    const initialBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Recording...';
    submitBtn.disabled = true;

    const loanId = document.getElementById('paymentLoanId').value;
    const paymentAmount = document.getElementById('paymentAmountManual').value;

    const formData = new FormData();
    formData.append('action', 'record_payment');
    formData.append('loan_id', loanId);
    formData.append('payment_amount', paymentAmount);

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showMessageModal('Payment Recorded!', result.message, 'success');
            this.reset();
            document.getElementById('qrScannerModal').classList.add('hidden');
            fetchAdminData();
        } else {
            showMessageModal('Payment Failed', result.message || 'An unexpected error occurred.', 'error');
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error');
    } finally {
        submitBtn.innerHTML = initialBtnText;
        submitBtn.disabled = false;
    }
});

document.getElementById('clientSearch').addEventListener('input', (e) => populateClientsTable(e.target.value));
document.getElementById('loanSearch').addEventListener('input', (e) => populateLoansTable(e.target.value));


const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
});

document.querySelectorAll('#sidebar a[data-tab]').forEach(tabLink => {
    tabLink.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.add('-translate-x-full');
        document.querySelectorAll('#sidebar a').forEach(link => link.classList.remove('active-link', 'bg-white', 'text-primary'));
        this.classList.add('active-link', 'bg-white', 'text-primary');
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        const targetId = this.getAttribute('data-tab');
        document.getElementById(targetId).classList.remove('hidden');
    });
});

document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    showMessageModal(
        'Confirm Logout', 
        'Are you sure you want to log out of the admin dashboard?', 
        'info'
    );

    document.getElementById('closeAdminMessageModal').onclick = function() {
        window.location.href = "logout.php";
    };
});

document.addEventListener('DOMContentLoaded', fetchAdminData);

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('fixed')) {
        e.target.classList.add('hidden');
    }
});
