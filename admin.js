import './jquery-3.7.1.js'

const API_URL = 'api.php'
let clientsData = []
let loansData = []
let pendingLoans = []
let collectorsData = []
let branchChartInstance = null
let riskAnalysisChartInstance = null
let loansAndPaymentsChartInstance = null
let collectorReportChartInstance = null
const branches = ['malolos', 'hagonoy', 'calumpit', 'balagtas', 'marilao', 'stamaria', 'plaridel']
let adminStats = {}

$(document).ready(function () {
    initializeAdminDashboard()
})

function initializeAdminDashboard() {
    initializeModals()
    initializeEventListeners()
    fetchAdminData($('#branchSelector').val() || 'all')
}

function initializeModals() {
    if (typeof window.showMessageModal === 'undefined') {
        window.showMessageModal = function (title, message, type = 'success') {
            const modal = $('#adminMessageModal')
            const modalTitle = $('#adminModalTitle')
            const modalMessage = $('#adminModalMessage')
            const modalIcon = $('#adminModalIcon')

            modalTitle.text(title)
            modalMessage.text(message)

            modalIcon.attr('class', 'mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4')
            if (type === 'success') {
                modalIcon.addClass('bg-green-100').html('<i class="fas fa-check text-green-600 text-xl"></i>')
            } else if (type === 'error') {
                modalIcon.addClass('bg-red-100').html('<i class="fas fa-times text-red-600 text-xl"></i>')
            } else {
                modalIcon.addClass('bg-blue-100').html('<i class="fas fa-info text-blue-600 text-xl"></i>')
            }

            modal.removeClass('hidden')
        }
    }
}

function initializeEventListeners() {

    $('#closeAdminMessageModal').on('click', function () {
        $('#adminMessageModal').addClass('hidden')
    })

    $('#closeAddClientModal').on('click', function () {
        $('#addClientModal').addClass('hidden')
    })

    $('#closeAddCollectorModal').on('click', function () {
        $('#addCollectorModal').addClass('hidden')
    })

    $('#closeEditCollectorModal').on('click', function () {
        $('#editCollectorModal').addClass('hidden')
    })

    $('#closeChangePasswordModal').on('click', function () {
        $('#changePasswordModal').addClass('hidden')
    })

    $('#closeNewLoanModal').on('click', function () {
        $('#newLoanModal').addClass('hidden')
    })

    $('#closeClientDetailsModal').on('click', function () {
        $('#clientDetailsModal').addClass('hidden')
    })

    $('#closeLoanDetailsModal').on('click', function () {
        $('#loanDetailsModal').addClass('hidden')
    })


    $('#addClientForm').on('submit', handleAddClient)
    $('#addCollectorForm').on('submit', handleAddCollector)
    $('#editCollectorForm').on('submit', handleEditCollector)
    $('#changePasswordForm').on('submit', handleChangePassword)


    $('#clientSearch').on('input', function (e) {
        populateClientsTable($(this).val())
    })

    $('#loanSearch').on('input', function (e) {
        populateLoansTable($(this).val())
    })

    $('#branchSelector').on('change', function () {
        const branch = $(this).val() || 'all'
        fetchAdminData(branch)
        fetchPaymentHistory(branch)
    })


    $('#showAddClientModal').on('click', function () {
        $('#addClientModal').removeClass('hidden')
    })

    $('#showAddCollectorModal').on('click', function () {
        $('#addCollectorModal').removeClass('hidden')
    })

    $('#changePasswordBtn').on('click', function () {
        $('#changePasswordModal').removeClass('hidden')
    })

    $('#exportClientsBtn').on('click', function () {
        const branch = $('#branchSelector').val() || 'all'
        window.location = API_URL + '?action=export_clients_csv&branch=' + encodeURIComponent(branch)
    })

    $('#viewAllLoansBtn').on('click', function () {
        window.location.hash = 'loans'
        switchTab('loans')
    })


    $('#logoutBtn').on('click', function (e) {
        e.preventDefault()
        $('#logoutConfirmationModal').removeClass('hidden')
    })

    $('#cancelLogoutBtn').on('click', function () {
        $('#logoutConfirmationModal').addClass('hidden')
    })

    $('#confirmLogoutBtn').on('click', function () {
        window.location.href = "logout.php"
    })


    $('.sidebar-nav-item').on('click', function (e) {
        e.preventDefault()
        const targetId = $(this).data('tab')
        window.location.hash = targetId
        switchTab(targetId)
    })


    $(document).on('click', function (e) {
        if ($(e.target).hasClass('modal-overlay')) {
            $(e.target).addClass('hidden')
        }
    })
}

const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const mobileBtn = document.getElementById('mobileSidebarBtn');

mobileBtn.addEventListener('click', () => {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
});

document.getElementById('sidebarOverlay').addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
});


function switchTab(targetId) {
    $('.tab-content').addClass('hidden')
    $(`#${targetId}`).removeClass('hidden')

    $('.sidebar-nav-item').removeClass('active')
    $(`[data-tab="${targetId}"]`).addClass('active')

    let title = $(`[data-tab="${targetId}"]`).text().trim()
    if (title === 'Overview') {
        title = 'Dashboard Overview'
    } else {
        title = title + ' Management'
    }
    $('#page-title').text(title)

}



function formatCurrency(amount) {
    return `₱${parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}
window.fetchAdminData = fetchAdminData();

export async function fetchAdminData(branch = 'all') {
    try {
        const url = API_URL + '?action=get_admin_data&branch=' + encodeURIComponent(branch)
        const response = await fetch(url)

        if (!response.ok) throw new Error('Network response was not ok')

        const data = await response.json()

        if (data.error) {
            console.error('API Error:', data.error)
            showMessageModal('Data Error', data.error, 'error')
            return
        }

        clientsData = data.clients || []
        loansData = data.loans || []
        collectorsData = data.collectors || []
        adminStats = data || {}

        updateDashboardStats()
        populateClientsTable()
        populateLoansTable()
        populateCollectorsTable()
        updateBranchChart()
        updateUpcomingPayments()
        updateLoansAndPaymentsChart()
        fetchPaymentHistory(branch)
        generateCollectorReport()
        checkNotifications()

    } catch (error) {
        console.error('Fetch error:', error)
        showMessageModal('Connection Error', 'Failed to load data. Please check your PHP server and database connection.', 'error')
    }
}

function checkNotifications() {
    const notifOverdue = $('#notifOverdueLoan').is(':checked')
    const notifPayment = $('#notifNewPayment').is(':checked')

    const overdueLoans = loansData.filter(l => l.loan_status === 'Overdue')
    if (notifOverdue && overdueLoans.length > 0) {
        showNotification(`You have ${overdueLoans.length} overdue loans`, 'error')
    }

    const todayPayments = adminStats.payments_today || 0
    if (notifPayment && todayPayments > 0) {
        showNotification(`₱${todayPayments.toLocaleString()} collected today`, 'success')
    }
}

function updateDashboardStats() {
    const totalClients = clientsData.length
    const totalOutstanding = adminStats.total_outstanding ?? loansData.reduce((sum, l) => sum + parseFloat(l.current_balance || 0), 0)
    const activeLoansCount = adminStats.active_loans_count ?? loansData.filter(l => l.loan_status === 'Active' || l.loan_status === 'Overdue').length

    $('#totalClientsCount').text(totalClients)
    $('#activeLoansCount').text(activeLoansCount)
    $('#totalOutstandingAmount').text(formatCurrency(totalOutstanding))

    const paymentsToday = adminStats.payments_today ?? 0
    const paymentsRate = adminStats.payments_rate_from_yesterday ?? 0
    $('#totalPaymentsToday').text(formatCurrency(paymentsToday))
    $('#totalCollectionsToday').text(formatCurrency(paymentsToday))
    $('#payRateFromYesterday').text(`${paymentsRate}%`)

    const icon = $('#payRateFromYesterdayIcon')
    icon.removeClass('fa-arrow-up fa-arrow-down text-red-600 text-emerald-600')
    if (paymentsRate < 0) {
        icon.addClass('fa-arrow-down text-red-600')
    } else {
        icon.addClass('fa-arrow-up text-emerald-600')
    }

    const overdueSum = loansData
        .filter(l => l.loan_status === 'Overdue')
        .reduce((s, l) => s + parseFloat(l.current_balance || 0), 0)
    $('#totalOverduePayments').text(formatCurrency(overdueSum))
}

function updateBranchChart() {
    const branchSet = new Set(clientsData.map(c => c.c_branch).filter(Boolean))
    const branches = Array.from(branchSet.length ? branchSet : ['malolos', 'hagonoy', 'calumpit', 'balagtas', 'marilao', 'stamaria', 'plaridel'])
    const loanCounts = branches.map(branch =>
        loansData.filter(loan => {
            const client = clientsData.find(c => c.client_id == loan.client_id)
            if (!client) return false
            return client.c_branch === branch && loan.loan_status !== 'Pending' && loan.loan_status !== 'Declined'
        }).length
    )

    const ctx = $('#branchChart')[0].getContext('2d')

    if (branchChartInstance) {
        branchChartInstance.destroy()
    }

    branchChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: branches.map(b => b.charAt(0).toUpperCase() + b.slice(1)),
            datasets: [{
                label: 'Total Active/Paid Loans',
                data: loanCounts,
                backgroundColor: branches.map((b, i) => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'][i % 4]),
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
    })
}

let collectorAmountChartInstance = null
let collectorClientsChartInstance = null
$('#collectorReportDate').on('change', function () {
    generateCollectorReport()
})
$('#branchSelector').on('change', function () {
    generateCollectorReport()
})
async function generateCollectorReport() {
    try {
        const reportDate = $('#collectorReportDate').val()
        const branch = $('#branchSelector').val() || 'all'

        const url = `${API_URL}?action=get_collector_reports&report_type=all&date=${reportDate}&branch=${branch}`
        const response = await fetch(url)
        const data = await response.json()

        if (data.error) {
            showMessageModal('Report Error', data.error, 'error')
            return
        }
        const amountCtx = $('#collectorAmountCollectedChart')[0].getContext('2d')

        if (collectorAmountChartInstance) {
            collectorAmountChartInstance.destroy()
        }

        collectorAmountChartInstance = new Chart(amountCtx, {
            type: 'bar',
            data: {
                labels: data.amount_labels || [],
                datasets: [{
                    label: 'Amount Collected (₱)',
                    data: data.amount_values || [],
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        })

        const clientsCtx = $('#collectorClientsCollectedChart')[0].getContext('2d')

        if (collectorClientsChartInstance) {
            collectorClientsChartInstance.destroy()
        }

        collectorClientsChartInstance = new Chart(clientsCtx, {
            type: 'bar',
            data: {
                labels: data.client_labels || [],
                datasets: [{
                    label: 'Clients Collected',
                    data: data.client_values || [],
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        })

    } catch (error) {
        console.error('Error generating collector report:', error)
        showMessageModal('Report Error', 'Failed to generate collector report.', 'error')
    }
}


async function updateUpcomingPayments() {
    try {
        const response = await fetch(API_URL + '?action=get_upcoming_payments')
        const data = await response.json()

        if (data.error) {
            console.error('Upcoming payments error:', data.error)
            return
        }

        const container = $('#upcomingPaymentsContainer')
        container.empty()

        if (!data.payments || data.payments.length === 0) {
            container.html(`
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calendar-check text-3xl mb-2"></i>
                    <p>No upcoming payments</p>
                </div>
            `)
            return
        }

        data.payments.forEach(payment => {
            const paymentCard = `
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">${payment.client_name}</h4>
                            <p class="text-xs text-gray-600">Loan L${payment.loan_id}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">₱${payment.daily_payment.toLocaleString()}</p>
                        <p class="text-xs ${payment.days_until_due === 0 ? 'text-red-600' : payment.days_until_due === 1 ? 'text-orange-600' : 'text-gray-600'}">
                            ${payment.days_until_due === 0 ? 'Today' : payment.days_until_due === 1 ? 'Tomorrow' : `In ${payment.days_until_due} days`}
                        </p>
                    </div>
                </div>
            `

            container.append(paymentCard)
        })

    } catch (error) {
        console.error('Error fetching upcoming payments:', error)
        $('#upcomingPaymentsContainer').html(`
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p>Failed to load upcoming payments</p>
            </div>
        `)
    }
}

async function updateLoansAndPaymentsChart() {
    try {
        const branch = $('#branchSelector').val() || 'all'
        const url = `${API_URL}?action=get_loans_payments_data&branch=${encodeURIComponent(branch)}`
        const response = await fetch(url)
        const data = await response.json()

        if (data.error) {
            console.error('Monthly trends error:', data.error)
            return
        }

        const ctx = $('#monthlyTrendsChart')[0].getContext('2d')

        if (loansAndPaymentsChartInstance) {
            loansAndPaymentsChartInstance.destroy()
        }

        loansAndPaymentsChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Loans Issued',
                        data: data.loans_issued || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Payments',
                        data: data.payments || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    } catch (error) {
        console.error('Error fetching monthly trends:', error)
    }
}

function populateClientsTable(searchTerm = '') {
    const clientsBody = $('#clientsTableBody')
    clientsBody.empty()

    const filteredClients = clientsData.filter(c =>
        (c.c_firstname || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
        (c.c_lastname || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
        (c.member_id || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
        (c.c_email || '').toLowerCase().includes(searchTerm.toLowerCase())
    )

    if (filteredClients.length === 0) {
        clientsBody.html('<tr><td colspan="6" class="text-center py-4 text-gray-500">No clients found.</td></tr>')
        return
    }

    filteredClients.forEach(client => {
        const outstanding = loansData
            .filter(l => l.client_id == client.client_id && l.loan_status === 'Active')
            .reduce((sum, l) => sum + parseFloat(l.current_balance || 0), 0)

        let statusBadge = ''
        if (client.c_status === 'Deactivated' || client.member_id.endsWith('-D')) {
            statusBadge = `<span class="status-badge bg-red-100 text-red-800">Deactivated</span>`
        } else if (outstanding > 0) {
            statusBadge = `<span class="status-badge bg-blue-100 text-blue-800">Active Loan</span>`
        } else {
            statusBadge = `<span class="status-badge bg-green-100 text-green-800">No Loan</span>`
        }

        const row = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${client.member_id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${client.c_firstname} ${client.c_lastname}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${client.c_email || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">${formatCurrency(outstanding)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${client.c_branch || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <button onclick="showClientDetailsModal(${client.client_id}, '${client.member_id}', '${client.c_firstname} ${client.c_lastname}', '${(client.c_email || 'N/A')}', '${(client.c_phone || 'N/A')}', '${(client.c_address || '')}', '${(client.c_branch || 'N/A')}', '${(client.c_date_joined || 'N/A')}')" 
                            class="text-blue-600 hover:text-blue-900 mx-1 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${(client.c_status !== 'Deactivated' && !client.member_id.endsWith('-D')) ? `
                    <button onclick="showNewLoanModal(${client.client_id}, '${client.member_id}', '${client.c_firstname} ${client.c_lastname}')" 
                            class="text-green-600 hover:text-green-900 mx-1 p-2 rounded-lg hover:bg-green-50 transition-colors">
                        <i class="fas fa-money-bill"></i>
                    </button>
                    ` : `
                    <button onclick="reactivateClientAccount(${client.client_id})" 
                            class="text-green-600 hover:text-green-900 mx-1 p-2 rounded-lg hover:bg-green-50 transition-colors">
                        <i class="fas fa-user-check"></i>
                    </button>
                    `}
                </td>
            </tr>
        `

        clientsBody.append(row)
    })
}

function populateCollectorsTable() {
    const collectorsBody = $('#collectorsTableBody')
    collectorsBody.empty()

    if (collectorsData.length === 0) {
        collectorsBody.html('<tr><td colspan="5" class="text-center py-4 text-gray-500">No collectors found.</td></tr>')
        return
    }

    collectorsData.forEach(collector => {
        const row = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${collector.col_fullname}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${collector.col_username}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${collector.col_branch}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="status-badge ${collector.col_status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${collector.col_status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <button onclick="showEditCollectorModal(${collector.collector_id}, '${collector.col_fullname}', '${collector.col_username}', '${collector.col_branch}', '${collector.col_status}')" class="text-blue-600 hover:text-blue-900 mx-1 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="showDeleteCollectorModal(${collector.collector_id}, '${collector.col_fullname}')" class="text-red-600 hover:text-red-900 mx-1 p-2 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `

        collectorsBody.append(row)
    })
}


function populateLoansTable(searchTerm = '') {
    const loansBody = $('#loansTableBody')
    loansBody.empty()

    const activeAndPaidLoans = loansData.filter(l => l.loan_status !== 'Pending' && l.loan_status !== 'Declined')

    const filteredLoans = activeAndPaidLoans.filter(loan => {
        const client = clientsData.find(c => c.client_id == loan.client_id)
        const name = client ? `${client.c_firstname} ${client.c_lastname}` : ''
        return name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            loan.loan_id.toString().includes(searchTerm)
    })

    if (filteredLoans.length === 0) {
        loansBody.html('<tr><td colspan="6" class="text-center py-4 text-gray-500">No active or paid loans found.</td></tr>')
        return
    }

    filteredLoans.forEach(loan => {
        const client = clientsData.find(c => c.client_id == loan.client_id)
        const clientName = client ? `${client.c_firstname} ${client.c_lastname}` : 'N/A'

        let statusBadge = ''
        if (loan.loan_status === 'Active') {
            statusBadge = `<span class="status-badge bg-blue-100 text-blue-800">${loan.loan_status}</span>`
        } else if (loan.loan_status === 'Paid') {
            statusBadge = `<span class="status-badge bg-green-100 text-green-800">${loan.loan_status}</span>`
        } else if (loan.loan_status === 'Overdue') {
            statusBadge = `<span class="status-badge bg-red-100 text-red-800">${loan.loan_status}</span>`
        } else {
            statusBadge = `<span class="status-badge bg-gray-100 text-gray-800">${loan.loan_status}</span>`
        }

        const row = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">L${loan.loan_id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${clientName}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatCurrency(loan.loan_amount)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">${formatCurrency(loan.current_balance)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <button onclick="showLoanDetailsModal('L${loan.loan_id}', '${loan.loan_status}', '${formatCurrency(loan.loan_amount)}', '${formatCurrency(loan.current_balance)}', '${formatCurrency(loan.daily_payment)}', '${clientName}', ${loan.days_paid || 0}, ${loan.term_days || 100})"
                            class="text-blue-600 hover:text-blue-900 mx-1 p-2 rounded-lg hover:bg-blue-50 transition-colors"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `

        loansBody.append(row)
    })
}

async function fetchPaymentHistory(branch = 'all', limit = 20) {
    try {
        const url = API_URL + '?action=get_payment_history&branch=' + encodeURIComponent(branch) + '&limit=' + encodeURIComponent(limit)
        const response = await fetch(url)
        if (!response.ok) throw new Error('Network error')
        const data = await response.json()
        const payments = data.payments || []
        const container = $('#recentPayments')
        container.empty()

        if (payments.length === 0) {
            container.html('<div class="text-center py-4 text-gray-500">No recent payments found.</div>')
            return
        }

        payments.forEach(p => {
            const d = new Date(p.payment_date)
            const timeStr = isNaN(d.getTime()) ? p.payment_date : d.toLocaleString()
            const el = `
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-medium text-gray-900">₱${parseFloat(p.payment_amount).toLocaleString()}</div>
                            <div class="text-xs text-gray-500">${p.client_name || ''} (Loan L${p.loan_id}) • ${p.payment_method || 'Cash'}</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500">${timeStr}</div>
                </div>
            `
            container.append(el)
        })
    } catch (e) {
        console.error(e)
    }
}

async function deactivateClientAccount(clientId) {
    const client = clientsData.find(c => c.client_id == clientId)
    if (!client) {
        showMessageModal('Error', 'Client not found.', 'error')
        return
    }

    $('#deactivateClientConfirmationModal').removeClass('hidden')
    $('#deactivateClientMessage').text(`Are you sure you want to deactivate ${client.c_firstname} ${client.c_lastname} (${client.member_id})? This action cannot be undone if the client has active loans.`)

    $('#confirmDeactivateClientBtn').off('click').on('click', async function () {
        $('#deactivateClientConfirmationModal').addClass('hidden')

        try {
            const formData = new FormData()
            formData.append('action', 'deactivate_client')
            formData.append('client_id', clientId)

            const response = await fetch(API_URL, { method: 'POST', body: formData })
            const result = await response.json()

            if (result.success) {
                showMessageModal('Account Deactivated!', result.message, 'success')
                fetchAdminData()
            } else {
                showMessageModal('Deactivation Failed', result.message || 'An unexpected error occurred.', 'error')
            }
        } catch (error) {
            showMessageModal('Network Error', 'Could not connect to the server.', 'error')
        }
    })
}

async function reactivateClientAccount(clientId) {
    const client = clientsData.find(c => c.client_id == clientId)
    if (!client) {
        showMessageModal('Error', 'Client not found.', 'error')
        return
    }

    showMessageModal('Confirm Reactivation', `Are you sure you want to reactivate ${client.c_firstname} ${client.c_lastname}?`, 'info')

    $('#closeAdminMessageModal').off('click').on('click', async function () {
        $('#adminMessageModal').addClass('hidden')

        try {
            const formData = new FormData()
            formData.append('action', 'reactivate_client')
            formData.append('client_id', clientId)

            const response = await fetch(API_URL, { method: 'POST', body: formData })
            const result = await response.json()

            if (result.success) {
                showMessageModal('Account Reactivated!', result.message, 'success')
                fetchAdminData()
            } else {
                showMessageModal('Reactivation Failed', result.message || 'An unexpected error occurred.', 'error')
            }
        } catch (error) {
            showMessageModal('Network Error', 'Could not connect to the server.', 'error')
        } finally {
            $('#closeAdminMessageModal').off('click').on('click', function () {
                $('#adminMessageModal').addClass('hidden')
            })
        }
    })
}
window.reactivateClientAccount = reactivateClientAccount

async function handleAddClient(e) {
    e.preventDefault()
    const submitBtn = $('#addClientSubmitBtn')
    const initialBtnText = submitBtn.html()
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...').prop('disabled', true)

    const formData = new FormData(this)
    formData.append('action', 'add_client')

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData })
        const result = await response.json()

        if (result.success) {
            showMessageModal('Client Added!', result.message, 'success')
            this.reset()
            $('#addClientModal').addClass('hidden')
            fetchAdminData($('#branchSelector').val())
        } else {
            showMessageModal('Failed to Add Client', result.message || 'An unexpected error occurred.', 'error')
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error')
    } finally {
        submitBtn.html(initialBtnText).prop('disabled', false)
    }
}

async function handleAddCollector(e) {
    e.preventDefault()
    const submitBtn = $('#addCollectorSubmitBtn')
    const initialBtnText = submitBtn.html()
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...').prop('disabled', true)

    const formData = new FormData(this)
    formData.append('action', 'add_collector')

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData })
        const result = await response.json()

        if (result.success) {
            showMessageModal('Collector Added!', result.message, 'success')
            this.reset()
            $('#addCollectorModal').addClass('hidden')
            fetchAdminData($('#branchSelector').val())
        } else {
            showMessageModal('Failed to Add Collector', result.message || 'An unexpected error occurred.', 'error')
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error')
    } finally {
        submitBtn.html(initialBtnText).prop('disabled', false)
    }
}

async function handleEditCollector(e) {
    e.preventDefault()
    const submitBtn = $('#editCollectorSubmitBtn')
    const initialBtnText = submitBtn.html()
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...').prop('disabled', true)

    const formData = new FormData(this)
    formData.append('action', 'edit_collector')

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData })
        const result = await response.json()

        if (result.success) {
            showMessageModal('Collector Updated!', result.message, 'success')
            this.reset()
            $('#editCollectorModal').addClass('hidden')
            fetchAdminData($('#branchSelector').val())
        } else {
            showMessageModal('Failed to Update Collector', result.message || 'An unexpected error occurred.', 'error')
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error')
    } finally {
        submitBtn.html(initialBtnText).prop('disabled', false)
    }
}

async function handleChangePassword(e) {
    e.preventDefault()
    const submitBtn = $('#changePasswordSubmitBtn')
    const initialBtnText = submitBtn.html()
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Changing...').prop('disabled', true)

    const formData = new FormData(this)
    formData.append('action', 'change_admin_password')

    try {
        const response = await fetch(API_URL, { method: 'POST', body: formData })
        const result = await response.json()

        if (result.success) {
            showMessageModal('Password Changed!', result.message, 'success')
            this.reset()
            $('#changePasswordModal').addClass('hidden')
        } else {
            showMessageModal('Password Change Failed', result.message || 'An unexpected error occurred.', 'error')
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error')
    } finally {
        submitBtn.html(initialBtnText).prop('disabled', false)
    }
}

const initialTab = window.location.hash.substring(1) || 'overview'
switchTab(initialTab)


const pageTitle = document.getElementById('page-title');
const allTabs = document.querySelectorAll('.tab-content');
const allLinks = document.querySelectorAll('.sidebar-nav-item');
const logoutBtn = document.getElementById('logoutBtn');
const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
allLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.dataset.tab;
        window.location.hash = targetId;
        switchTab(targetId);
        window.scrollTo({ top: 0 });
    });
});
logoutBtn.addEventListener('click', () => {
    document.getElementById('logoutConfirmationModal').classList.remove('hidden');
});

cancelLogoutBtn.addEventListener('click', () => {
    document.getElementById('logoutConfirmationModal').classList.add('hidden');
});

confirmLogoutBtn.addEventListener('click', () => {
    window.location.href = "logout.php";
});

document.getElementById("totalClients").addEventListener('click', function (e) {
    e.preventDefault();
    const targetId = this.getAttribute('go-to-tab');
    window.location.hash = targetId;
    switchTab(targetId);
    window.scrollTo({ top: 0 });
})

function showMessageModal(title, message, type = 'success') {
    const modal = document.getElementById('adminMessageModal');
    const modalTitle = document.getElementById('adminModalTitle');
    const modalMessage = document.getElementById('adminModalMessage');
    const modalIcon = document.getElementById('adminModalIcon');
    const closeBtn = document.getElementById('closeAdminMessageModal');

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    modalIcon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4';
    if (type === 'success') {
        modalIcon.classList.add('bg-emerald-100');
        modalIcon.innerHTML = '<i class="fas fa-check text-emerald-600 text-2xl"></i>';
    } else if (type === 'error') {
        modalIcon.classList.add('bg-red-100');
        modalIcon.innerHTML = '<i class="fas fa-times text-red-600 text-2xl"></i>';
    } else {
        modalIcon.classList.add('bg-blue-100');
        modalIcon.innerHTML = '<i class="fas fa-info text-primary text-2xl"></i>';
    }

    closeBtn.onclick = () => modal.classList.add('hidden');
    modal.classList.remove('hidden');
}
window.showMessageModal = showMessageModal;


function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification border-l-${type === 'success' ? 'green' : type === 'warning' ? 'yellow' : type === 'error' ? 'red' : 'blue'}-500`;
    notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'times-circle' : 'info-circle'} text-${type === 'success' ? 'green' : type === 'warning' ? 'yellow' : type === 'error' ? 'red' : 'blue'}-500 mr-3"></i>
                        <span class="text-sm font-medium">${message}</span>
                    </div>
                `;
    container.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}
window.showNotification = showNotification;

function showNewLoanModal(clientId, memberId, clientName) {
    const modal = document.getElementById('newLoanModal');
    const modalMemberId = document.getElementById('newLoanMemberId');
    const modalClientName = document.getElementById('newLoanClientName');
    const submitBtn = document.getElementById('submitNewLoanBtn');
    const closeBtn = document.getElementById('cancelNewLoanBtn');

    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden')
    })

    modalMemberId.textContent = memberId;
    modalClientName.textContent = clientName;

    modal.dataset.clientId = clientId;

    const amountInput = document.getElementById('newLoanAmount');
    amountInput.addEventListener('input', updateLoanCalculation);
    updateLoanCalculation();

    submitBtn.onclick = () => handleNewLoanSubmission(clientId, memberId);

    modal.classList.remove('hidden');
}
window.showNewLoanModal = showNewLoanModal;

function updateLoanCalculation() {
    const amountInput = document.getElementById('newLoanAmount');
    const amount = parseFloat(amountInput.value) || 0;

    const processingFee = 200;
    const amountReceived = amount - processingFee;
    const interest = amount * 0.15;
    const totalBalance = amount + interest;
    const dailyPayment = totalBalance / 100;

    document.getElementById('calcProcessingFee').textContent = `₱${processingFee.toFixed(2)}`;
    document.getElementById('calcAmountReceived').textContent = `₱${amountReceived.toFixed(2)}`;
    document.getElementById('calcInterest').textContent = `₱${interest.toFixed(2)}`;
    document.getElementById('calcTotalBalance').textContent = `₱${totalBalance.toFixed(2)}`;
    document.getElementById('calcDailyPayment').textContent = `₱${dailyPayment.toFixed(2)}`;
}

function showClientDetailsModal(clientId, memberId, clientName, clientEmail, clientPhone, clientAddress, clientBranch, clientDateJoined) {
    const modal = document.getElementById('clientDetailsModal');
    const modalMemberId = document.getElementById('clientMemberID');
    const modalClientName = document.getElementById('clientName');
    const modalClientEmail = document.getElementById('clientEmail');
    const modalClientPhone = document.getElementById('clientPhone');
    const modalClientAddress = document.getElementById('clientAddress');
    const modalClientBranch = document.getElementById('clientBranch');
    const modalClientDateJoined = document.getElementById('clientDateJoined');
    const closeBtn = document.getElementById('closeClientDetailsModal');
    const deactivateBtn = document.getElementById('deactivateClientBtn');

    modalMemberId.textContent = memberId;
    modalClientName.textContent = clientName;
    modalClientEmail.textContent = clientEmail;
    modalClientPhone.textContent = clientPhone;
    modalClientAddress.textContent = clientAddress;
    modalClientBranch.textContent = clientBranch.charAt(0).toUpperCase() + clientBranch.slice(1);
    modalClientDateJoined.textContent = clientDateJoined;
    modal.dataset.clientId = clientId;
    modal.dataset.clientName = clientName;
    modal.dataset.memberId = memberId;

    closeBtn.onclick = () => modal.classList.add('hidden');

    if (memberId.endsWith('-D')) {
        deactivateBtn.style.display = 'none';
    } else {
        deactivateBtn.style.display = 'block';
        deactivateBtn.onclick = () => showDeactivateClientConfirmation(clientId, clientName, memberId);
    }

    modal.classList.remove('hidden');
}
window.showClientDetailsModal = showClientDetailsModal;

function showLoanDetailsModal(loanId, loanStatus, loanAmount, loanBalance, loanDaily, loanClientName, daysPaid, termDays) {
    const modal = document.getElementById('loanDetailsModal');
    const modalLoanId = document.getElementById('loanId');
    const modalLoanStatus = document.getElementById('loanStatus');
    const modalLoanAmount = document.getElementById('loanAmount');
    const modalLoanBalance = document.getElementById('loanBalance');
    const modalDaily = document.getElementById('loanDaily');
    const modalClientName = document.getElementById('loanClientName');
    const modalDaysPaid = document.getElementById('loanDaysPaid');
    const closeBtn = document.getElementById('closeLoanDetailsModal');

    modalLoanId.textContent = loanId;
    modalLoanStatus.textContent = loanStatus;
    modalLoanAmount.textContent = loanAmount;
    modalLoanBalance.textContent = loanBalance;
    modalDaily.textContent = loanDaily;
    modalClientName.textContent = loanClientName;
    modalDaysPaid.textContent = `${daysPaid}/${termDays}`;

    closeBtn.onclick = () => modal.classList.add('hidden');

    modal.classList.remove('hidden');
}
window.showLoanDetailsModal = showLoanDetailsModal;

function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}
window.toggleModal = toggleModal;
window.showEditCollectorModal = function (collectorId, fullname, username, branch, status) {
    const modal = document.getElementById('editCollectorModal');
    document.getElementById('editCollectorId').value = collectorId;
    document.getElementById('editCollectorFullname').value = fullname;
    document.getElementById('editCollectorUsername').value = username;
    document.getElementById('editCollectorBranch').value = branch;
    document.getElementById('editCollectorStatus').value = status;

    modal.classList.remove('hidden');
}
window.showDeleteCollectorModal = function (collectorId, fullname) {
    const modal = document.getElementById('deleteCollectorConfirmationModal');
    document.getElementById('deleteCollectorMessage').textContent = `Are you sure you want to delete collector ${fullname}? This action cannot be undone.`;

    document.getElementById('confirmDeleteCollectorBtn').onclick = function () {
        deleteCollector(collectorId);
    };

    modal.classList.remove('hidden');
}

async function deleteCollector(collectorId) {
    try {
        const formData = new FormData();
        formData.append('action', 'delete_collector');
        formData.append('collector_id', collectorId);

        const response = await fetch('api.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            showMessageModal('Collector Deleted!', result.message, 'success');
            document.getElementById('deleteCollectorConfirmationModal').classList.add('hidden');
            if (typeof fetchAdminData === 'function') {
                fetchAdminData();
            }
        } else {
            showMessageModal('Deletion Failed', result.message || 'An unexpected error occurred.', 'error');
        }
    } catch (error) {
        showMessageModal('Network Error', 'Could not connect to the server.', 'error');
    }
}

function showDeactivateClientConfirmation(clientId, clientName, memberId) {
    const modal = document.getElementById('deactivateClientConfirmationModal');
    const message = document.getElementById('deactivateClientMessage');
    const cancelBtn = document.getElementById('cancelDeactivateClientBtn');
    const confirmBtn = document.getElementById('confirmDeactivateClientBtn');

    message.textContent = `Are you sure you want to deactivate ${clientName} (${memberId})? This action cannot be undone if the client has active loans.`;

    cancelBtn.onclick = () => modal.classList.add('hidden');

    confirmBtn.onclick = () => {
        modal.classList.add('hidden');
        deactivateClientAccount(clientId);
    };

    modal.classList.remove('hidden');
}

async function handleNewLoanSubmission(clientId, memberId) {
    const amount = document.getElementById('newLoanAmount').value;
    const messageDiv = document.getElementById('newLoanMessage');
    const submitBtn = document.getElementById('submitNewLoanBtn');

    if (!amount || amount < 10000) {
        messageDiv.textContent = 'Please enter a valid amount (min ₱10,000';
        messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
        messageDiv.classList.remove('hidden');
        return;
    }

    const initialBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'submit_loan_admin');
        formData.append('client_id', clientId);
        formData.append('loan_amount', amount);

        const response = await fetch('api.php', { method: 'POST', body: formData });
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            messageDiv.textContent = 'Server returned invalid response.';
            messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
            return;
        }
        if (result.success) {
            messageDiv.textContent = result.message;
            messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-green-100 text-green-700';
            messageDiv.classList.remove('hidden');
            if (typeof fetchAdminData === 'function') {
                fetchAdminData();
            }
        } else {
            messageDiv.textContent = result.message || 'An unexpected error occurred.';
            messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
            messageDiv.classList.remove('hidden');
        }
    } catch (error) {
        console.log(error);
        messageDiv.textContent = 'Could not connect to the server. Please try again.';
        messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
        messageDiv.classList.remove('hidden');
    } finally {
        submitBtn.innerHTML = initialBtnText;
        submitBtn.disabled = false;
    }
}

document.getElementById('changePasswordBtn').addEventListener('click', () => {
    document.getElementById('changePasswordModal').classList.remove('hidden');
});

document.getElementById('closeChangePasswordModal').addEventListener('click', () => {
    document.getElementById('changePasswordModal').classList.add('hidden');
});

document.getElementById('backupDataBtn').addEventListener('click', () => {
    window.location.href = 'api.php?action=backup_database';
});
document.getElementById('showAddCollectorModal').addEventListener('click', () => {
    document.getElementById('addCollectorModal').classList.remove('hidden');
});

document.getElementById('closeAddCollectorModal').addEventListener('click', () => {
    document.getElementById('addCollectorModal').classList.add('hidden');
});
document.getElementById('closeEditCollectorModal').addEventListener('click', () => {
    document.getElementById('editCollectorModal').classList.add('hidden');
});
document.getElementById('cancelDeleteCollectorBtn').addEventListener('click', () => {
    document.getElementById('deleteCollectorConfirmationModal').classList.add('hidden');
});

document.getElementById('newLoanBtn').addEventListener('click', () => {
    showMessageModal('Select Client', 'Please select a client from the Clients tab to create a new loan.', 'info');
});

$("document").on('click', '.modal-overlay', function (e) {
    if (e.target === this) {
        $(this).addClass('hidden');
    }
});

function downloadReport(type, format) {
    const branch = document.getElementById('branchSelector').value || 'all';
    window.location.href = `api.php?action=download_report&type=${type}&format=${format}&branch=${branch}`;
}
window.downloadReport = downloadReport;