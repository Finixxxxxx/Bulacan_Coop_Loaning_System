const API_URL = 'api.php';

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function formatCurrency(amount) {
    return `₱${parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

async function fetchCollectorData() {
    try {
        const response = await fetch(API_URL + '?action=get_collector_data');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();

        if (data.error) {
            console.error('API Error:', data.error);
            showNotification(data.error, 'error');
            return;
        }

        updateDashboardStats(data);
        updateUnpaidClients(data.unpaid_clients);
        updatePaidClients(data.paid_clients);
        updateTodayPayments(data.today_payments);

    } catch (error) {
        console.error('Fetch error:', error);
        showNotification('Failed to load data. Please check your connection.', 'error');
    }
}

function updateDashboardStats(data) {
    const todayCollections = data.today_payments.reduce((sum, payment) => sum + parseFloat(payment.payment_amount || 0), 0);
    const unpaidCount = data.unpaid_clients.length;
    const paidCount = data.paid_clients.length;

    document.getElementById('todayCollections').textContent = formatCurrency(todayCollections);
    document.getElementById('unpaidClientsCount').textContent = unpaidCount;
    document.getElementById('paidClientsCount').textContent = paidCount;
}

function updateUnpaidClients(unpaidClients) {
    const container = document.getElementById('unpaidClientsContainer');
    const countElement = document.getElementById('unpaidCount');
    
    container.innerHTML = '';
    countElement.textContent = `${unpaidClients.length} clients`;

    if (unpaidClients.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-check-circle text-2xl mb-2"></i>
                <p>All clients paid today!</p>
            </div>
        `;
        return;
    }

    unpaidClients.forEach(client => {
        const clientCard = `
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-orange-600"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">${client.c_firstname} ${client.c_lastname}</h4>
                        <p class="text-xs text-gray-600">${client.member_id} • Loan L${client.loan_id}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-900">${formatCurrency(client.daily_payment)}</p>
                    <p class="text-xs text-gray-500">Due Today</p>
                </div>
            </div>
        `;
        container.innerHTML += clientCard;
    });
}

function updatePaidClients(paidClients) {
    const container = document.getElementById('paidClientsContainer');
    const countElement = document.getElementById('paidCount');
    
    container.innerHTML = '';
    countElement.textContent = `${paidClients.length} clients`;

    if (paidClients.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-clock text-2xl mb-2"></i>
                <p>No payments collected today</p>
            </div>
        `;
        return;
    }

    paidClients.forEach(client => {
        const clientCard = `
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-green-600"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">${client.c_firstname} ${client.c_lastname}</h4>
                        <p class="text-xs text-gray-600">${client.member_id} • Loan L${client.loan_id}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-green-600">${formatCurrency(client.paid_today)}</p>
                    <p class="text-xs text-gray-500">Paid Today</p>
                </div>
            </div>
        `;
        container.innerHTML += clientCard;
    });
}

function updateTodayPayments(todayPayments) {
    const container = document.getElementById('todayPaymentsContainer');
    container.innerHTML = '';

    if (todayPayments.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-receipt text-2xl mb-2"></i>
                <p>No payments recorded today</p>
            </div>
        `;
        return;
    }

    todayPayments.forEach(payment => {
        const paymentDate = new Date(payment.payment_date);
        const timeString = paymentDate.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });

        const paymentCard = `
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">${payment.c_firstname} ${payment.c_lastname}</h4>
                        <p class="text-xs text-gray-600">${payment.member_id} • Loan L${payment.loan_id}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-900">${formatCurrency(payment.payment_amount)}</p>
                    <p class="text-xs text-gray-500">${timeString}</p>
                </div>
            </div>
        `;
        container.innerHTML += paymentCard;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    fetchCollectorData();
    setInterval(fetchCollectorData, 30000);
});