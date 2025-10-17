const API_URL = 'api.php';
let activeLoan = null;
function showMessageModal(title, message, type = 'success') {
    const modal = document.getElementById('messageModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalIcon = document.getElementById('modalIcon');

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
document.getElementById('closeMessageModal').addEventListener('click', function() {
    document.getElementById('messageModal').classList.add('hidden');
});
function formatCurrency(amount) {
    return `₱${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
window.setQuickAmount = function(amount) {
    const paymentAmountInput = document.getElementById('paymentAmount');
    let value = parseFloat(amount);
    if (isNaN(value) || value <= 0) {
        value = 0;
    }
    paymentAmountInput.value = value.toFixed(2);
}

async function fetchClientData() {
    try {
        const response = await fetch(API_URL + '?action=get_client_data');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        if (data.error) {
            console.error('API Error:', data.error);
            showMessageModal('Data Error', data.error, 'error');
            return;
        }

        updateLoanStatus(data.active_loan);
        updatePaymentHistory(data.payment_history);
        

        if (data.active_loan) {
            document.getElementById('paymentAmount').value = data.active_loan.monthly_payment;
            activeLoan = data.active_loan;
        } else {
            document.getElementById('paymentAmount').value = 0.00;
        }

    } catch (error) {
        console.error('Fetch error:', error);
        showMessageModal('Connection Error', 'Failed to load data. Please check your PHP server and database connection.', 'error');
    }
}

function updateLoanStatus(loan) {
    const loanDetailsContainer = document.getElementById('loanDetailsContainer');
    const noActiveLoanMessage = document.getElementById('noActiveLoanMessage');
    const monthlyPaymentInput = document.getElementById('monthlyPayment');
    
    if (loan && loan.loan_status === 'Active') {
        noActiveLoanMessage.classList.add('hidden');
        
        document.getElementById('currentBalance').textContent = formatCurrency(loan.current_balance);
        monthlyPaymentInput.textContent = formatCurrency(loan.monthly_payment);
        document.getElementById('nextPaymentDate').textContent = new Date(loan.next_payment_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        
        const badge = document.getElementById('loanStatusBadge');
        badge.textContent = loan.loan_status;
        badge.className = 'status-badge'
        
        if (loan.loan_status === 'Active') {
            badge.classList.add('bg-blue-100', 'text-blue-800');
        } else if (loan.loan_status === 'Paid') {
            badge.classList.add('bg-green-100', 'text-green-800');
        } else if (loan.loan_status === 'Overdue') {
            badge.classList.add('bg-red-100', 'text-red-800');
        }
        const now = new Date();
        const nextPayment = new Date(loan.next_payment_date);
        const daysUntilPayment = Math.ceil((nextPayment - now) / (1000 * 60 * 60 * 24));
        document.querySelector('[data-due-days]').textContent = daysUntilPayment > 0 
            ? `(${daysUntilPayment} days to go)` 
            : '(Due Date Passed)';
    } else {
        noActiveLoanMessage.classList.remove('hidden');

        document.getElementById('currentBalance').textContent = formatCurrency(0);
        monthlyPaymentInput.textContent = formatCurrency(0);
        document.getElementById('nextPaymentDate').textContent = '--';
        document.getElementById('loanStatusBadge').textContent = 'None';
        document.getElementById('loanStatusBadge').className = 'status-badge bg-gray-100 text-gray-600';
        document.querySelector('[data-due-days]').textContent = '';
    }
}

function updatePaymentHistory(payments) {
    const historyBody = document.getElementById('paymentHistoryBody');
    historyBody.innerHTML = ''

    if (!payments || payments.length === 0) {
        historyBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">No payment history found for active loans.</td></tr>';
        return;
    }

    payments.forEach(payment => {
        const row = historyBody.insertRow();
        row.className = 'fade-in';
        
        row.insertCell().textContent = new Date(payment.payment_date).toLocaleDateString();
        row.insertCell().textContent = formatCurrency(payment.payment_amount);
        row.insertCell().textContent = payment.payment_method;
    });
}

document.getElementById('generatePaymentQR').addEventListener('click', function() {
    const amountInput = document.getElementById('paymentAmount');
    const amount = parseFloat(amountInput.value);
    
    if (isNaN(amount) || amount <= 0) {
        showMessageModal('Invalid Amount', 'Please enter a valid payment amount.', 'error');
        return;
    }

    if (!activeLoan) {
        showMessageModal('No Active Loan', 'You must have an active loan to generate a payment QR code.', 'info');
        return;
    }
    
    if (amount > activeLoan.current_balance) {
        showMessageModal(
            'Overpayment Warning', 
            `The payment amount (${formatCurrency(amount)}) exceeds your current balance (${formatCurrency(activeLoan.current_balance)}). Continue with overpayment?`, 
            'info'
        );
    }
    
    const paymentData = {
        type: 'loan_payment',
        client_id: activeLoan.client_id, 
        loan_id: activeLoan.loan_id,
        amount: amount.toFixed(2),
        timestamp: Date.now()
    };

    const qrDataString = JSON.stringify(paymentData);
    const qrCanvas = document.getElementById('qrCanvas');
    const qrAmountDisplay = document.getElementById('qrAmountDisplay');
    
    qrAmountDisplay.textContent = formatCurrency(amount);

    QRCode.toCanvas(qrCanvas, qrDataString, {
        errorCorrectionLevel: 'H',
        margin: 1,
        width: 200,
        color: {
            dark: '#0369A1',
            light: '#FFFFFF'
        }
    }, function (error) {
        if (error) {
            console.error(error);
            showMessageModal('QR Error', 'Failed to generate QR code.', 'error');
        } else {
            document.getElementById('qrModal').classList.remove('hidden');
        }
    });
});
document.getElementById('closeQRModal').addEventListener('click', function() {
    document.getElementById('qrModal').classList.add('hidden');
});

document.getElementById('loanApplicationForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (activeLoan && activeLoan.loan_status === 'Active') {
        showMessageModal('Application Failed', 'You already have an active loan. Please settle it or wait for approval.', 'info');
        return;
    }
    const amount = document.getElementById('loanAmountInput').value;
    const term = document.getElementById('loanTermInput').value;
    const purpose = document.getElementById('loanPurposeInput').value;
    const submitBtn = document.getElementById('submitLoanBtn');
    const initialBtnText = submitBtn.innerHTML;

    if (amount < 1000 || purpose.length < 10) {
        showMessageModal('Validation Error', 'Please enter a loan amount of at least ₱1,000 and a detailed purpose.', 'error');
        return;
    }
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'submit_loan');
        formData.append('loan_amount', amount);
        formData.append('term_months', term);
        formData.append('loan_purpose', purpose);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            showMessageModal('Application Submitted!', result.message, 'success');
            this.reset()
        } else {
            showMessageModal('Submission Failed', result.message || 'An unexpected error occurred.', 'error');
        }

    } catch (error) {
        console.error('Submission Error:', error);
        showMessageModal('Network Error', 'Could not connect to the server. Please try again.', 'error');
    } finally {
        submitBtn.innerHTML = initialBtnText;
        submitBtn.disabled = false;

        fetchClientData(); 
    }
});

document.addEventListener('DOMContentLoaded', fetchClientData);
document.getElementById('logoutBtn').addEventListener('click', function() {
    showMessageModal(
        'Confirm Logout', 
        'Are you sure you want to log out of the client portal?', 
        'info'
    );
    document.getElementById('closeMessageModal').onclick = function() {
        window.location.href = "logout.php";
    };
});
window.addEventListener('click', function(e) {
    if (e.target.id === 'qrModal' || e.target.id === 'messageModal') {
        e.target.classList.add('hidden');

        if (e.target.id === 'messageModal') {
                document.getElementById('closeMessageModal').onclick = function() {
                document.getElementById('messageModal').classList.add('hidden');
            };
        }
    }
});
