const API_URL = 'api.php';
        let clientsData = [];
        let loansData = [];
        let pendingLoans = [];
        let branchChartInstance = null;
        let totalOutstandingBalanceChartInstance = null;
        let monthlyTrendsChartInstance = null;
        let riskAnalysisChartInstance = null;
        let loansAndPaymentsChartInstance = null;
        const branches = ['malolos','hagonoy','calumpit','balagtas', 'marilao', 'stamaria', 'plaridel'];
        let adminStats = {};

        if (typeof window.showMessageModal === 'undefined') {
            window.showMessageModal = function(title, message, type = 'success') {
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
            };
        }

        document.getElementById('closeAdminMessageModal').addEventListener('click', function() {
            document.getElementById('adminMessageModal').classList.add('hidden');
        });

        function formatCurrency(amount) {
            return `₱${parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        async function fetchAdminData(branch = 'all') {
            try {
                const url = API_URL + '?action=get_admin_data&branch=' + encodeURIComponent(branch);
                const response = await fetch(url);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();

                if (data.error) {
                    console.error('API Error:', data.error);
                    showMessageModal('Data Error', data.error, 'error');
                    return;
                }
            
                clientsData = data.clients || [];
                loansData = data.loans || [];
                pendingLoans = data.pending_loans || [];
                adminStats = data || {};

                updateDashboardStats();
                populatePendingLoans();
                populateClientsTable();
                populateLoansTable();
                updateBranchChart();
                updateTotalOustandingChart();
                updateMonthlyTrendsChart()
                updateRiskAnalysisChart()
                updateLoansAndPaymentsChart()
                fetchPaymentHistory(branch);

                checkNotifications();

            } catch (error) {
                console.error('Fetch error:', error);
                showMessageModal('Connection Error', 'Failed to load data. Please check your PHP server and database connection.', 'error');
            }
        }

        function checkNotifications() {
            const notifPending = document.getElementById('notifPendingLoan').checked;
            const notifOverdue = document.getElementById('notifOverdueLoan').checked;
            const notifPayment = document.getElementById('notifNewPayment').checked;

            if (notifPending && pendingLoans.length > 0) {
                showNotification(`You have ${pendingLoans.length} pending loan applications`, 'warning');
            }

            const overdueLoans = loansData.filter(l => l.loan_status === 'Overdue');
            if (notifOverdue && overdueLoans.length > 0) {
                showNotification(`You have ${overdueLoans.length} overdue loans`, 'error');
            }

            const todayPayments = adminStats.payments_today || 0;
            if (notifPayment && todayPayments > 0) {
                showNotification(`₱${todayPayments.toLocaleString()} collected today`, 'success');
            }
        }

        function updateDashboardStats() {
            const totalClients = clientsData.length;
            const totalOutstanding = adminStats.total_outstanding ?? loansData.reduce((sum, l) => sum + parseFloat(l.current_balance || 0), 0);
            const activeLoansCount = adminStats.active_loans_count ?? loansData.filter(l => l.loan_status === 'Active' || l.loan_status === 'Overdue').length;
            const pendingCount = pendingLoans.length;

            document.getElementById('totalClientsCount').textContent = totalClients;
            document.getElementById('activeLoansCount').textContent = activeLoansCount;
            document.getElementById('pendingLoansCount').textContent = pendingCount;
            document.getElementById('totalOutstandingAmount').textContent = formatCurrency(totalOutstanding);

            const paymentsToday = adminStats.payments_today ?? 0;
            const paymentsRate = adminStats.payments_rate_from_yesterday ?? 0;
            document.getElementById('totalPaymentsToday').textContent = formatCurrency(paymentsToday);
            document.getElementById('totalCollectionsToday').textContent = formatCurrency(paymentsToday);
            document.getElementById('payRateFromYesterday').textContent = `${paymentsRate}%`;

            const icon = document.getElementById('payRateFromYesterdayIcon');
            icon.classList.remove('fa-arrow-up', 'fa-arrow-down', 'text-red-600', 'text-emerald-600');
            if (paymentsRate < 0) {
                icon.classList.add('fa-arrow-down', 'text-red-600');
            } else {
                icon.classList.add('fa-arrow-up', 'text-emerald-600');
            }

            const today = new Date().toISOString().slice(0,10);
            const pendingSum = loansData
                .filter(l => l.loan_status === 'Active' && l.next_payment_date && l.next_payment_date.slice(0,10) === today)
                .reduce((s, l) => s + parseFloat(l.monthly_payment || 0), 0);
            document.getElementById('totalPendingPaymentsTodal').textContent = formatCurrency(pendingSum);

            const overdueSum = loansData
                .filter(l => l.loan_status === 'Overdue')
                .reduce((s, l) => s + parseFloat(l.current_balance || 0), 0);
            document.getElementById('totalOverduePayments').textContent = formatCurrency(overdueSum);

            document.getElementById('totalOutstandingAmount').textContent = formatCurrency(totalOutstanding);
            document.getElementById('activeLoansCount').textContent = activeLoansCount;
        }

        function updateBranchChart() {
            const branchSet = new Set(clientsData.map(c => c.c_branch).filter(Boolean));
            const branches = Array.from(branchSet.length ? branchSet : ['malolos','hagonoy','calumpit','balagtas', 'marilao', 'stamaria', 'plaridel']);
            const loanCounts = branches.map(branch => 
                loansData.filter(loan => {
                    const client = clientsData.find(c => c.client_id == loan.client_id);
                    if (!client) return false
                    return client.c_branch === branch && loan.loan_status !== 'Pending' && loan.loan_status !== 'Declined';
                }).length
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
                        backgroundColor: branches.map((b, i) => ['#3b82f6','#10b981','#f59e0b','#ef4444'][i % 4]),
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

        async function updateTotalOustandingChart(){
            const chartFilter = document.getElementById("outstandingBalanceFilter").value;
            
            try {
                const response = await fetch(API_URL + '?action=get_outstanding_chart_data&filter=' + encodeURIComponent(chartFilter));
                const data = await response.json();
                console.log(data.labels)
                
                if (data.error) {
                    console.error('Chart data error:', data.error);
                    return;
                }

                const ctx = document.getElementById('totalOutstandingBalanceChart').getContext('2d');
                
                if (totalOutstandingBalanceChartInstance) {
                    totalOutstandingBalanceChartInstance.destroy();
                }

                totalOutstandingBalanceChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels || [],
                        datasets: [{
                            label: 'Total Outstanding Balance',
                            data: data.data || [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return '₱' + (value / 1000000).toFixed(1) + 'M';
                                        } else if (value >= 1000) {
                                            return '₱' + (value / 1000).toFixed(0) + 'K';
                                        }
                                        return '₱' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Outstanding: ₱${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching chart data:', error);
                const ctx = document.getElementById('totalOutstandingBalanceChart').getContext('2d');
                
                if (totalOutstandingBalanceChartInstance) {
                    totalOutstandingBalanceChartInstance.destroy();
                }

                totalOutstandingBalanceChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Total Outstanding Balance',
                            data: [500000, 750000, 600000, 900000, 800000, 950000],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return '₱' + (value / 1000000).toFixed(1) + 'M';
                                        } else if (value >= 1000) {
                                            return '₱' + (value / 1000).toFixed(0) + 'K';
                                        }
                                        return '₱' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
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
        }

        async function updateMonthlyTrendsChart(){
            try {
                const response = await fetch(API_URL + '?action=get_monthly_trends_data');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Monthly trends error:', data.error);
                    return;
                }

                const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
                
                if (monthlyTrendsChartInstance) {
                    monthlyTrendsChartInstance.destroy();
                }

                monthlyTrendsChartInstance = new Chart(ctx, {
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
                });
            } catch (error) {
                console.error('Error fetching monthly trends:', error);
            }
        }

        async function updateRiskAnalysisChart(){
            try {
                const response = await fetch(API_URL + '?action=get_risk_analysis_data');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Risk analysis error:', data.error);
                    return;
                }

                const ctx = document.getElementById('riskAnalysisChart').getContext('2d');
                
                if (riskAnalysisChartInstance) {
                    riskAnalysisChartInstance.destroy();
                }

                riskAnalysisChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels || ['Low Risk', 'Medium Risk', 'High Risk'],
                        datasets: [{
                            data: data.data || [65, 25, 10],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching risk analysis:', error);
            }
        }

        async function updateLoansAndPaymentsChart(){
            try {
                const response = await fetch(API_URL + '?action=get_loans_payments_data');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Loans payments error:', data.error);
                    return;
                }

                const ctx = document.getElementById('loanAndPaymentsChart').getContext('2d');
                
                if (loansAndPaymentsChartInstance) {
                    loansAndPaymentsChartInstance.destroy();
                }

                loansAndPaymentsChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels || [],
                        datasets: [{
                            label: 'Loan Status',
                            data: data.data || [],
                            backgroundColor: ['#3b82f6', '#10b981', '#ef4444', '#f59e0b']
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching loans payments:', error);
            }
        }

        function populateClientsTable(searchTerm = '') {
            const clientsBody = document.getElementById('clientsTableBody');
            clientsBody.innerHTML = '';
            
            const filteredClients = clientsData.filter(c => 
                (c.c_firstname || '').toLowerCase().includes(searchTerm.toLowerCase()) || 
                (c.c_lastname || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (c.member_id || '').toLowerCase().includes(searchTerm.toLowerCase())
            );

            if (filteredClients.length === 0) {
                clientsBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No clients found.</td></tr>';
                return;
            }

            filteredClients.forEach(client => {
                const row = clientsBody.insertRow();
                const outstanding = loansData
                    .filter(l => l.client_id == client.client_id && l.loan_status === 'Active')
                    .reduce((sum, l) => sum + parseFloat(l.current_balance || 0), 0);
                
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${client.c_branch || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <button 
                        onclick="showClientDetailsModal('${client.member_id}', '${client.c_firstname} ${client.c_lastname}', '${(client.c_email||'N/A')}', '${(client.c_phone||'N/A')}', '${(client.c_address||'')}', '${(client.c_branch||'N/A')}', '${(client.date_joined||'N/A')}')" 
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
                        <button onclick="showLoanDetailsModal('${loan.loan_id}', '${loan.loan_status}', '${formatCurrency(loan.loan_amount)}', '${formatCurrency(loan.current_balance)}', '${formatCurrency(loan.monthly_payment)}', '${clientName}', '${loan.loan_purpose}')"
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${(loan.loan_purpose || '').substring(0, 50)}...</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <button onclick="showApproveLoanModal(${loan.loan_id})" class="text-green-600 hover:text-green-900 mx-1 p-1 rounded hover:bg-green-100"><i class="fas fa-check"></i> Approve</button>
                        <button onclick="declineLoan(${loan.loan_id})" class="text-red-600 hover:text-red-900 mx-1 p-1 rounded hover:bg-red-100"><i class="fas fa-times"></i> Decline</button>
                    </td>
                `;
            });
        }

        async function fetchPaymentHistory(branch = 'all', limit = 20) {
            try {
                const url = API_URL + '?action=get_payment_history&branch=' + encodeURIComponent(branch) + '&limit=' + encodeURIComponent(limit);
                const response = await fetch(url);
                if (!response.ok) throw new Error('Network error');
                const data = await response.json();
                const payments = data.payments || [];
                const container = document.getElementById('recentPayments');
                container.innerHTML = '';

                if (payments.length === 0) {
                    container.innerHTML = '<div class="text-center py-4 text-gray-500">No recent payments found.</div>';
                    return;
                }

                payments.forEach(p => {
                    const d = new Date(p.payment_date);
                    const timeStr = isNaN(d.getTime()) ? p.payment_date : d.toLocaleString();
                    const el = document.createElement('div');
                    el.className = 'flex items-center justify-between py-2 border-b border-gray-100';
                    el.innerHTML = `
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">₱${parseFloat(p.payment_amount).toLocaleString()}</div>
                                <div class="text-xs text-gray-500">${p.client_name || ''} (Loan L${p.loan_id}) • ${p.payment_method || 'Cash'}</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">${timeStr}</div>
                    `;
                    container.appendChild(el);
                });
            } catch (e) {
                console.error(e);
            }
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
                    fetchAdminData(document.getElementById('branchSelector').value);
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
                : (monthlyPayment.toFixed(2));

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
                : (monthlyPayment.toFixed(2));
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
                    fetchAdminData(document.getElementById('branchSelector').value);
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

        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('changePasswordSubmitBtn');
            const initialBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Changing...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'change_admin_password');

            try {
                const response = await fetch(API_URL, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showMessageModal('Password Changed!', result.message, 'success');
                    this.reset();
                    document.getElementById('changePasswordModal').classList.add('hidden');
                } else {
                    showMessageModal('Password Change Failed', result.message || 'An unexpected error occurred.', 'error');
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

        document.getElementById('branchSelector').addEventListener('change', function() {
            const branch = this.value || 'all';
            fetchAdminData(branch);
            fetchPaymentHistory(branch);
        });

        document.getElementById('exportClientsBtn').addEventListener('click', function() {
            const branch = document.getElementById('branchSelector').value || 'all';
            window.location = API_URL + '?action=export_clients_csv&branch=' + encodeURIComponent(branch);
        });

        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        document.querySelectorAll('.sidebar-nav-item').forEach(tabLink => {
            tabLink.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.add('-translate-x-full');
                document.querySelectorAll('.sidebar-nav-item').forEach(link => link.classList.remove('active'));
                this.classList.add('active');
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

        document.addEventListener('DOMContentLoaded', function() {
            fetchAdminData(document.getElementById('branchSelector').value || 'all');
        });

        window.addEventListener('click', function(e) {
            if (e.target.classList && e.target.classList.contains('fixed')) {
                e.target.classList.add('hidden');
            }
        });