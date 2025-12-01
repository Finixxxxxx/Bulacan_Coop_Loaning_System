const API_URL = 'api.php';

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white fade-in`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showMessageModal(title, message, type = 'info') {
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

function formatCurrency(amount) {
    return `₱${parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function calculateDaysUntilDue(nextPaymentDate) {
    if (!nextPaymentDate) return null;
    
    const today = new Date();
    const dueDate = new Date(nextPaymentDate);
    const timeDiff = dueDate.getTime() - today.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    return daysDiff;
}

window.clientData = {};
async function fetchClientData() {
    try {
        const response = await fetch(API_URL + '?action=get_client_data');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        if (data.error) {
            console.error('API Error:', data.error);
            showNotification(data.error, 'error');
            return;
        }

        updateLoanInformation(data);
        fetchPaymentHistory()

    } catch (error) {
        console.error('Fetch error:', error);
        showNotification('Failed to load data. Please check your connection.', 'error');
    }
}

async function fetchPaymentHistory() {
    try {
        const response = await fetch(API_URL + '?action=get_payment_history_client');
        if (!response.ok) throw new Error('Network error');

        const data = await response.json();

        if (data.error) {
            showNotification(data.error, 'error');
            return;
        }

        updatePaymentHistory(data.payments);

    } catch (error) {
        console.error('Payment history fetch error:', error);
        showNotification('Unable to load payment history.', 'error');
    }
}


function updateLoanInformation(data) {
    const loan = data.active_loan;
    const loanContainer = document.getElementById('loanDetailsContainer');
    const loanInfo = document.getElementById('loanInfo');
    const noLoanMessage = document.getElementById('noActiveLoanMessage');

    if (!loan) {
        loanContainer.classList.add('hidden');
        loanInfo.classList.add('hidden');
        noLoanMessage.classList.remove('hidden');
        window.CURRENT_LOAN_ID = null;
        return;
    }

    loanContainer.classList.remove('hidden');
    loanInfo.classList.remove('hidden');
    noLoanMessage.classList.add('hidden');

    document.getElementById('currentBalance').textContent = formatCurrency(loan.current_balance);
    document.getElementById('dailyPayment').textContent = formatCurrency(loan.daily_payment);
    document.getElementById('totalAmount').textContent = formatCurrency(loan.total_balance);
    document.getElementById('processingFee').textContent = formatCurrency(200.00);
    document.getElementById('netAmount').textContent = formatCurrency(loan.net_amount || (loan.loan_amount - 200));
    document.getElementById('daysPaid').textContent = `${loan.days_paid || 0}/100`;

    const nextPaymentDate = loan.next_payment_date;
    const nextPaymentElement = document.getElementById('nextPaymentDate');
    const dueDaysElement = document.querySelector('[data-due-days]');
    
    if (nextPaymentDate) {
        nextPaymentElement.textContent = formatDate(nextPaymentDate);
        const daysUntilDue = calculateDaysUntilDue(nextPaymentDate);
        
        if (daysUntilDue !== null) {
            if (daysUntilDue === 0) {
                dueDaysElement.textContent = 'Due today';
                dueDaysElement.className = 'text-xs text-red-600 font-semibold';
            } else if (daysUntilDue === 1) {
                dueDaysElement.textContent = 'Due tomorrow';
                dueDaysElement.className = 'text-xs text-orange-600';
            } else if (daysUntilDue > 1) {
                dueDaysElement.textContent = `Due in ${daysUntilDue} days`;
                dueDaysElement.className = 'text-xs text-gray-500';
            } else {
                dueDaysElement.textContent = 'Overdue';
                dueDaysElement.className = 'text-xs text-red-600 font-semibold';
            }
        }
    } else {
        nextPaymentElement.textContent = 'N/A';
        dueDaysElement.textContent = '';
    }

    const statusBadge = document.getElementById('loanStatusBadge');
    statusBadge.textContent = loan.loan_status;
    statusBadge.className = 'status-badge ';
    
    switch (loan.loan_status) {
        case 'Active':
            statusBadge.classList.add('bg-green-100', 'text-green-800');
            break;
        case 'Overdue':
            statusBadge.classList.add('bg-red-100', 'text-red-800');
            break;
        case 'Paid':
            statusBadge.classList.add('bg-blue-100', 'text-blue-800');
            break;
        case 'Pending':
            statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
            break;
        default:
            statusBadge.classList.add('bg-gray-100', 'text-gray-800');
    }

    window.CURRENT_LOAN_ID = loan.loan_id;
}

function updatePaymentHistory(payments) {
    const tbody = document.getElementById('paymentHistoryBody');
    
    if (!payments || payments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4 text-gray-500">
                    <i class="fas fa-receipt text-2xl mb-2 block"></i>
                    No payment history found.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = payments.map(payment => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${formatDate(payment.payment_date)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                ${formatCurrency(payment.payment_amount)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${payment.payment_method || 'Cash'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="status-badge ${
                    payment.payment_type === 'full' ? 'bg-green-100 text-green-800' :
                    payment.payment_type === 'partial' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                }">
                    ${payment.payment_type || 'daily'}
                </span>
            </td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    fetchClientData();
    setInterval(fetchClientData, 30000);
});

window.setQuickAmount = setQuickAmount;
window.showMessageModal = showMessageModal;