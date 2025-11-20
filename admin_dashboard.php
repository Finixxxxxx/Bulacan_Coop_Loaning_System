<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulacan Coop - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin.css">
    <script type="module" src="admin.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-color: #2563EB;
            --primary-light: #BFDBFE;
            --primary-dark: #1D4ED8;
            --background-soft: #f4f6f9;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--background-soft); 
        }
        .bg-primary { background-color: var(--primary-color); } 
        .hover\:bg-primary-dark:hover { background-color: var(--primary-dark); }
        .text-primary { color: var(--primary-color); }
        #sidebar {
            width: 16rem;
            z-index: 50;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        .sidebar-nav-item {
            transition: all 0.2s;
            color: #4B5563;
            background-color: transparent;
        }
        .sidebar-nav-item:hover {
            color: #1F2937;
            background-color: #F9FAFB;
        }
        .sidebar-nav-item.active {
            color: var(--primary-color) !important; 
            background-color: #EFF6FF !important;
            font-weight: 600;
        }
        .card-flat { 
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        .card-flat:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
        }
        
        .status-badge {
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .notification-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
        .notification {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid;
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': 'var(--primary-color)',
                        'primary-light': 'var(--primary-light)',
                        'primary-dark': 'var(--primary-dark)',
                        'background-soft': 'var(--background-soft)',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background-soft min-h-screen">
    <div id="dashboard" class="flex min-h-screen">
        
        <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-coins text-white text-sm"></i>
                        </div>
                        <span class="font-semibold text-gray-900">Bulacan Coop</span>
                    </div>
                    <button id="closeSidebar" class="lg:hidden p-1 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4 border-b border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter mr-2 text-primary"></i>Filter by Branch
                    </label>
                    <select id="branchSelector" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="all">All Branches</option>
                        <option value="malolos">Malolos Branch</option>
                        <option value="hagonoy">Hagonoy Branch</option>
                        <option value="calumpit">Calumpit Branch</option>
                        <option value="balagtas">Balagtas Branch</option>
                        <option value="marilao">Marilao Branch</option>
                        <option value="stamaria">Sta. Maria Branch</option>
                        <option value="plaridel">Plaridel Branch</option>
                    </select>
                </div>

                <nav class="flex-1 p-4">
                    <div class="space-y-2">
                        <button class="sidebar-nav-item active w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="overview">
                            <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                            Overview
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="clients">
                            <i class="fas fa-users mr-3 w-5"></i>
                            Clients
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="loans">
                            <i class="fas fa-list-alt mr-3 w-5"></i>
                            Loans & Pending
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="payments">
                            <i class="fas fa-credit-card mr-3 w-5"></i>
                            Payments
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="reports">
                            <i class="fas fa-chart-bar mr-3 w-5"></i>
                            Reports
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg" data-tab="settings">
                            <i class="fas fa-cog mr-3 w-5"></i>
                            Settings
                        </button>
                    </div>
                </nav>

                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                        <div class="flex items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin_name); ?></p>
                                <p class="text-xs text-gray-500">System Administrator</p>
                            </div>
                            <div class="ml-8">
                                <i id="logoutBtn" class="fas fa-right-from-bracket rounded-lg text-rose-500 hover:text-rose-700 text-xl cursor-pointer"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="sidebarOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 lg:hidden hidden"></div>
        
        <main class="flex-1 lg:ml-64 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 overflow-y-auto">
            
            <header class="bg-white shadow-sm border-b border-gray-200 rounded-xl p-4 mb-8">
                <div class="flex justify-between items-center h-full">
                    <div class="flex items-center">
                        <button id="sidebarToggle" class="lg:hidden mr-4 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 id="page-title" class="text-2xl font-extrabold text-gray-900 transition-all duration-300">Dashboard Overview</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 hidden sm:inline">
                            <i class="fas fa-user-shield mr-2 text-primary"></i> Admin: <?php echo htmlspecialchars($admin_name); ?>
                        </span>
                        
                    </div>
                </div>
            </header>

            <div id="notificationContainer" class="notification-container"></div>

            <div id="mainContent" class="space-y-10">
                <!-- Overview Section -->
                <section id="overview" class="tab-content">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div id="totalClientsCard" class="stat-card bg-gradient-to-r from-sky-500 to-sky-600 p-6 rounded-xl shadow-lg text-white cursor-pointer" data-tab="clients">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sky-100 text-sm font-medium">Total Clients</p>
                                    <p class="text-3xl font-bold" id="totalClientsCount">0</p>
                                    <p class="text-sky-100 text-xs mt-1">Click to view clients</p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-users text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-gradient-to-r from-rose-500 to-rose-600 p-6 rounded-xl shadow-lg text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-rose-100 text-sm font-medium">Total Active Loan</p>
                                    <p id="totalOutstandingAmount" class="text-3xl font-bold">₱0</p>
                                    <p class="text-rose-100 text-xs mt-1">Active Loans <strong class="text-md font-base bg-rose-200/50 rounded-full py-1 px-2 ml-1" id="activeLoansCount">0</strong></p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-lg text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-emerald-100 text-sm font-medium">Total Payments Today</p>
                                    <p id="totalPaymentsToday" class="text-3xl font-bold">₱0</p>
                                    <p class="text-emerald-100 text-xs mt-1">
                                        <i id="payRateFromYesterdayIcon" class="fas fa-arrow-up text-white"></i>
                                        Rate from yesterday <strong id="payRateFromYesterday" class="text-sm font-base bg-emerald-200/50 rounded-full py-1 px-2 ml-1">0%</strong></p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        <div id="pendingLoanCard" class="stat-card bg-gradient-to-r from-yellow-500 to-yellow-600 p-6 rounded-xl shadow-lg text-white cursor-pointer" data-tab="loans">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-yellow-100 text-sm font-medium">Pending Loan Applications</p>
                                    <p id="pendingLoansCount" class="text-3xl font-bold">0</p>
                                    <p class="text-yellow-100 text-xs mt-1">Click to view applications</p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-clock text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistic Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> 
                        <!-- Total Loans per Branch -->
                        <div class="bg-white p-6 rounded-xl shadow-md card-flat">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3">Total Loans per Branch</h3>
                            <div style="height: 350px;">
                                <canvas id="branchChart" style="max-height: 350px;"></canvas>
                            </div>
                        </div>

                        <!-- Loan Repayment Progress -->
                        <div class="bg-white p-6 rounded-xl shadow-md card-flat">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-gray-800 border-b pb-3">Loan Repayment Progress</h3>
                                <button id="viewAllLoansBtn" class="text-primary hover:text-primary-dark text-sm font-medium">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                            <div id="repaymentProgressContainer" class="space-y-4">
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Loading repayment progress...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Payments -->
                    <div class="bg-white p-6 rounded-xl shadow-md card-flat mt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-3">Upcoming Payments</h3>
                            <span class="text-sm text-gray-500">Next 7 days</span>
                        </div>
                        <div id="upcomingPaymentsContainer" class="space-y-3">
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading upcoming payments...</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="clients" class="tab-content hidden">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 hidden">Client Management</h2>
                            <p class="text-gray-600 text-sm mt-1 hidden">Manage your loan clients and their information</p>
                        </div>
                        <div class="flex gap-3 w-full sm:w-auto">
                            <button id="exportClientsBtn" class="w-1/2 sm:w-auto bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 transition-colors">
                                <i class="fas fa-download text-xl mr-2"></i>Export
                            </button>
                            <button id="showAddClientModal" class="w-full sm:w-auto bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg">
                                <i class="fas fa-plus mr-2"></i> Add New Client
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 card-flat">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="relative md:col-span-2">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                                <input type="text" id="clientSearch" placeholder="Search by name or ID..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm">
                            </div>
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent hidden">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Paid">Paid</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                            <select id="sortBy" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent hidden">
                                <option value="name">Sort by Name</option>
                                <option value="outstanding">Sort by Outstanding</option>
                                <option value="date">Sort by Date</option>
                            </select>
                            <button id="clearFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors hidden">
                                <i class="fas fa-times mr-2"></i>Clear
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 table-container card-flat">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-id-card mr-2"></i>Member ID</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Name</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-dollar-sign mr-2"></i>Outstanding</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-building mr-2"></i>Branch</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">Loading client data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="loans" class="tab-content hidden">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Pending Loan Applications</h2>
                            <p class="text-gray-600 text-sm mt-1">Review and approve new loan applications.</p>
                        </div>
                        <button id="newLoanBtn" class="bg-primary text-white px-4 py-2.5 rounded-lg font-medium hover:bg-primary-dark transition-colors hidden">
                            <i class="fas fa-plus mr-2"></i>New Loan
                        </button>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-10 card-flat">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Client</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-dollar-sign mr-2"></i>Amount</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-calendar mr-2"></i>Term</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-tag mr-2"></i>Purpose</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingLoansTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No pending applications.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Active & Paid Loan History</h2>
                            <p class="text-gray-600 text-sm mt-1">Track all active and completed loan transactions.</p>
                        </div>
                        <div class="flex flex-wrap justify-between items-center space-y-3 sm:space-y-0 sm:space-x-4">
                            <input type="text" id="loanSearch" placeholder="Search by Loan ID or Client..." class="w-full sm:max-w-sm px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition shadow-sm">
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-flat">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-hashtag mr-2"></i>Loan ID</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Client Name</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-dollar-sign mr-2"></i>Amount</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-balance-scale mr-2"></i>Balance</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-info-circle mr-2"></i>Status</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="loansTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">Loading loan data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="payments" class="tab-content hidden">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Payment Management</h2>
                            <p class="text-gray-600 text-sm mt-1">Track and manage all loan payments</p>
                        </div>
                        <div class="flex gap-3">
                            <button id="showPaymentQRModal" class="bg-emerald-600 text-white px-4 py-2.5 rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-lg">
                                <i class="fas fa-qrcode mr-2"></i>Record Payment / QR
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium">Today's Collections</p>
                                    <p id="totalCollectionsToday" class="text-2xl font-bold text-emerald-600">₱0</p>
                                </div>
                                <div class="p-3 bg-emerald-100 rounded-lg">
                                    <i class="fas fa-money-check-alt text-emerald-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium">Pending Payments</p>
                                    <p id="totalPendingPaymentsTodal" class="text-2xl font-bold text-orange-600">₱0</p>
                                </div>
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium">Late Payments</p>
                                    <p id="totalOverduePayments" class="text-2xl font-bold text-red-600">₱0</p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 justify-between flex items-center">
                            <div class="flex items-center">
                                <i class="fas fa-receipt mr-2 text-primary"></i>Recent Payments
                            </div>
                            <select name="payment_date_filter" id="paymentDateFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="thisWeek">This Week</option>
                                <option value="thisMonth">This Month</option>
                                <option value="last30Days">Last 30 Days</option>
                                <option value="last3Months">Last 3 Months</option>
                                <option value="last6Months">Last 6 Months</option>
                                <option value="thisYear">This Year</option>
                            </select>
                        </h3>
                        <div id="recentPayments" class="space-y-4">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-900 border-r-2 border-black pr-4">[₱{Payment} from {Client Name} ({Loan ID})]</span>
                                        <span class="text-sm font-medium text-gray-900 ml-2">Remaining Balance: ₱{Remaining Balance of the Loan}</span>
                                    </div>
                                    
                                </div>
                                <span class="text-xs text-gray-500">{Time paid compared to the time now}</span>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section id="reports" class="tab-content hidden">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
                        <p class="text-gray-600 text-sm mt-1">Generate comprehensive reports and analytics</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-sky-100 rounded-lg">
                                    <i class="fas fa-chart-line text-primary text-2xl"></i>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="downloadReport('performance', 'pdf')" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('performance', 'excel')" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('performance', 'csv')" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance Report</h3>
                            <p class="text-gray-600 text-sm">Monthly loan performance and collection rates</p>
                        </div>  

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-emerald-100 rounded-lg">
                                    <i class="fas fa-users text-emerald-600 text-2xl"></i>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="downloadReport('client', 'pdf')" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('client', 'excel')" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('client', 'csv')" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Client Report</h3>
                            <p class="text-gray-600 text-sm">Detailed client information and loan history</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-purple-600 text-2xl"></i>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="downloadReport('financial', 'pdf')" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('financial', 'excel')" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('financial', 'csv')" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Financial Report</h3>
                            <p class="text-gray-600 text-sm">Revenue, expenses, and profit analysis</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-area mr-2 text-indigo-600"></i>Advanced Analytics
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div>
                                <canvas id="monthlyTrendsChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="riskAnalysisChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="loanAndPaymentsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="settings" class="tab-content hidden">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
                        <p class="text-gray-600 text-sm mt-1">Configure your loan management system</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-bell mr-2 text-yellow-600"></i>Notifications
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">New Pending Loan</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer" id="notifPendingLoan">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Overdue Loan Alerts</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer" id="notifOverdueLoan">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">New Payments</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="notifNewPayment">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-shield-alt mr-2 text-emerald-600"></i>Security
                            </h3>
                            <div class="space-y-4">
                                <button id="changePasswordBtn" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                                <button id="backupDataBtn" class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-download mr-2"></i>Backup Data
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <div id="addClientModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform transition-all duration-300 ease-out scale-95 modal-content">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4"><i class="fas fa-user-plus"></i> Add New Client</h3>
            <form id="addClientForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="c_firstname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="c_lastname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Member ID</label>
                    <input type="text" name="member_id" required placeholder="e.g. M004" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="c_phone" required placeholder="e.g. +639171234567" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                    <p class="text-xs text-gray-500 mt-1">Used to generate temporary password (Lastname + last 4 digits).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                    <select name="c_branch" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                        <option value="malolos">Malolos</option>
                        <option value="hagonoy">Hagonoy</option>
                        <option value="calumpit">Calumpit</option>
                        <option value="balagtas">Balagtas</option>
                        <option value="marilao">Marilao</option>
                        <option value="staMaria">Sta. Maria</option>
                        <option value="plaridel">Plaridel</option>
                    </select>
                </div>
                <div id="addClientMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeAddClientModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="addClientSubmitBtn" class="bg-emerald-600 text-white py-2.5 px-5 rounded-lg hover:bg-emerald-700 transition-colors font-semibold shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-save mr-2"></i> Save Client
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="approveLoanModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform transition-all duration-300 ease-out scale-95 modal-content">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4">Approve Loan Application</h3>
            <div id="loanSummary" class="bg-primary/5 p-4 rounded-xl text-sm font-medium space-y-1 border border-primary/20">
                <p><strong>Client:</strong> <span id="approveClientName" class="text-gray-800"></span></p>
                <p><strong>Amount:</strong> <span id="approveLoanAmount" class="text-gray-800"></span></p>
                <p><strong>Term:</strong> <span id="approveLoanTerm" class="text-gray-800"></span></p>
            </div>
            <form id="approveLoanForm" class="space-y-4">
                <input type="hidden" id="loanIdToApprove">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Interest Rate (%)</label>
                    <input type="number" id="approveInterestRate" name="interest_rate" value="15.00" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Calculated Monthly Payment (₱)</label>
                    <input type="text" id="calculatedMonthlyPayment" readonly class="w-full px-4 py-2 border border-emerald-400 rounded-lg bg-emerald-50 font-extrabold text-emerald-700 text-lg">
                </div>
                <div id="approvalMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeApproveModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="approveLoanSubmitBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-circle-check mr-2"></i> Final Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="qrScannerModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-sm shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform transition-all duration-300 ease-out scale-95 modal-content">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4">Record Payment (Manual/QR)</h3>
            <p class="text-sm text-gray-600 mb-4 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                <i class="fas fa-info-circle mr-2 text-yellow-600"></i> Manual entry for demo purposes.
            </p>
            <form id="recordPaymentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loan ID to Pay</label>
                    <input type="number" id="paymentLoanId" name="loan_id" required placeholder="e.g. 1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount (₱)</label>
                    <input type="number" id="paymentAmountManual" name="payment_amount" required step="0.01" min="100" placeholder="e.g. 4513.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div id="paymentMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeQRScannerModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="recordPaymentSubmitBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-check-circle mr-2"></i> Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="adminMessageModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform scale-100 transition-transform">
            <div class="text-center">
                <div id="adminModalIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4"></div>
                <h3 id="adminModalTitle" class="text-xl font-bold text-gray-900 mb-2"></h3>
                <p id="adminModalMessage" class="text-gray-600 mb-6"></p>
                <button id="closeAdminMessageModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="newLoanModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform scale-100 transition-transform">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
                <i class="fa-solid fa-hand-holding-dollar text-green-600 mr-1"></i>
                New Loan
            </h2>
            <div class="space-y-4 text-gray-800">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-id-card"></i> Client ID:
                    </span>
                    <span id="newLoanClientId">M001</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-user"></i> Name:
                    </span>
                    <span id="newLoanClientName">Juan Dela Cruz</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loan Amount</label>
                    <input type="number" id="newLoanAmount" name="loan_amount" value="1000.00" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
            </div>

            <div class="mt-8 text-center">
                <button id="closeNewLoanModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold flex items-center justify-center gap-2">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="clientDetailsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform scale-100 transition-transform">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
                <i class="fa-solid fa-user-circle text-primary"></i>
                Client Details
            </h2>
            <div class="space-y-4 text-gray-800">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-id-card"></i> Member ID:
                    </span>
                    <span id="clientMemberID">M001</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-user"></i> Name:
                    </span>
                    <span id="clientName">Juan Dela Cruz</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-envelope"></i> Email:
                    </span>
                    <span id="clientEmail">email@example.com</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-phone"></i> Phone:
                    </span>
                    <span id="clientPhone">+63 912 345 6789</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot"></i> Address:
                    </span>
                    <span id="clientAddress">123 Purok 1 Malolos</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-building"></i> Branch:
                    </span>
                    <span id="clientBranch">Bulacan</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-day"></i> Date Joined:
                    </span>
                    <span id="clientDateJoined">October 21, 2025</span>
                </div>
            </div>

            <div class="mt-8 text-center">
                <button id="closeClientDetailsModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold flex items-center justify-center gap-2">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <div id="loanDetailsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform scale-100 transition-transform">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
                <i class="fa-solid fa-money-bill-wave text-orange-600"></i>
                Loan Details
            </h2>
            <div class="space-y-4 text-gray-800">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-id-card"></i> Loan ID:
                    </span>
                    <span id="loanId">L0</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i> Status:
                    </span>
                    <span id="loanStatus">Loan Status</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Loan Amount:
                    </span>
                    <span id="loanAmount">₱0</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-money-bill"></i> Loan Balance:
                    </span>
                    <span id="loanBalance">₱0</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-calendar"></i> Monthly Payment:
                    </span>
                    <span id="loanMonthly">₱0</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-user"></i> Loan Client Name:
                    </span>
                    <span id="loanClientName">Juan DelaCruz</span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard"></i> Loan Purpose:
                    </span>
                    <span id="loanPurpose">Purpose</span>
                </div>

            </div>

            <div class="mt-8 text-center">
                <button id="closeLoanDetailsModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold flex items-center justify-center gap-2">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="changePasswordModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform transition-all duration-300 ease-out scale-95 modal-content">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4">Change Password</h3>
            <form id="changePasswordForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary transition">
                </div>
                <div id="changePasswordMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeChangePasswordModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="changePasswordSubmitBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const pageTitle = document.getElementById('page-title');
            const allTabs = document.querySelectorAll('.tab-content');
            const allLinks = document.querySelectorAll('.sidebar-nav-item');
            
            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
            }
            function closeSidebarFn() {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            }

            sidebarToggle.addEventListener('click', openSidebar);
            closeSidebar.addEventListener('click', closeSidebarFn);
            sidebarOverlay.addEventListener('click', closeSidebarFn);

            function switchTab(targetId) {
                allTabs.forEach(tab => tab.classList.add('hidden'));
                const targetTab = document.getElementById(targetId);
                if (targetTab) {
                    targetTab.classList.remove('hidden');
                }
                
                allLinks.forEach(link => link.classList.remove('active'));
                const activeLink = document.querySelector(`[data-tab="${targetId}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                
                    let title = activeLink.textContent.trim();
                    if (title.includes('Loans & Pending')) {
                        title = 'Loans & Pending Management';
                    } else if (title === 'Overview') {
                        title = 'Dashboard Overview';
                    } else {
                        title = title + ' Management';
                    }
                    pageTitle.textContent = title;
                }
                
                if (window.innerWidth < 1024) {
                    closeSidebarFn();
                }
            }
        
            const initialTab = window.location.hash.substring(1) || 'overview';
            switchTab(initialTab);
        
            allLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.dataset.tab;
                    window.location.hash = targetId;
                    switchTab(targetId);
                });
            });
            document.getElementById('viewAllLoansBtn').addEventListener('click', (e) =>{
                e.preventDefault();
                window.location.hash = 'loans';
                switchTab('loans');
            });
            document.getElementById('totalClientsCard').addEventListener('click', (e) => {
                e.preventDefault();
                window.location.hash = 'clients';
                switchTab('clients');
            });

            document.getElementById('pendingLoanCard').addEventListener('click', (e) => {
                e.preventDefault();
                window.location.hash = 'loans';
                switchTab('loans');
            });

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
                    modalIcon.classList.add('bg-primary/20');
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

            function showNewLoanModal(clientId, clientName) {
                const modal = document.getElementById('newLoanModal');
                const modalClientId = document.getElementById('newLoanClientId');
                const modalClientName = document.getElementById('newLoanClientName');
                const closeBtn = document.getElementById('closeNewLoanModal');

                modalClientId.textContent = clientId;
                modalClientName.textContent = clientName;
                closeBtn.onclick = () => modal.classList.add('hidden');
                modal.classList.remove('hidden');
            }
            window.showNewLoanModal = showNewLoanModal;
            
            function showClientDetailsModal(clientId, clientName, clientEmail, clientPhone, clientAddress, clientBranch, clientDateJoined) {
                const modal = document.getElementById('clientDetailsModal');
                const modalClientId = document.getElementById('clientMemberID');
                const modalClientName = document.getElementById('clientName');
                const modalClientEmail = document.getElementById('clientEmail');
                const modalClientPhone = document.getElementById('clientPhone');
                const modalClientAddress = document.getElementById('clientAddress');
                const modalClientBranch = document.getElementById('clientBranch');
                const modalClientDateJoined = document.getElementById('clientDateJoined');
                const closeBtn = document.getElementById('closeClientDetailsModal');

                modalClientId.textContent = clientId;
                modalClientName.textContent = clientName;
                modalClientEmail.textContent = clientEmail;
                modalClientPhone.textContent = clientPhone;
                modalClientAddress.textContent = clientAddress;
                modalClientBranch.textContent = clientBranch.charAt(0).toUpperCase() + clientBranch.slice(1);;
                modalClientDateJoined.textContent = clientDateJoined;
                
                closeBtn.onclick = () => modal.classList.add('hidden');
                modal.classList.remove('hidden');
            }
            window.showClientDetailsModal = showClientDetailsModal;

            function showLoanDetailsModal(loanId, loanStatus, loanAmount, loanBalance, loanMonthly, loanClientName, loanPurpose) {
                const modal = document.getElementById('loanDetailsModal');
                const modalLoanId = document.getElementById('loanId');
                const modalLoanStatus = document.getElementById('loanStatus');
                const modalLoanAmount = document.getElementById('loanAmount');
                const modalLoanBalance = document.getElementById('loanBalance');
                const modalMonthly = document.getElementById('loanMonthly');
                const modalClientName = document.getElementById('loanClientName');
                const modalPurpose = document.getElementById('loanPurpose');
                const closeBtn = document.getElementById('closeLoanDetailsModal');

                modalLoanId.textContent = loanId;
                modalLoanStatus.textContent = loanStatus;
                modalLoanAmount.textContent = loanAmount;
                modalLoanBalance.textContent = loanBalance;
                modalMonthly.textContent = loanMonthly;
                modalClientName.textContent = loanClientName;
                modalPurpose.textContent = loanPurpose;

                closeBtn.onclick = () => modal.classList.add('hidden');
                modal.classList.remove('hidden');
            }
            window.showLoanDetailsModal = showLoanDetailsModal;

            function toggleModal(modalId, show) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                if (show) {
                    modal.classList.remove('hidden');
                
                    setTimeout(() => {
                        modalContent.classList.remove('scale-95');
                        modalContent.classList.add('scale-100', 'opacity-100');
                    }, 50);
                } else {
                    modalContent.classList.remove('scale-100', 'opacity-100');
                    modalContent.classList.add('scale-95');
                
                    setTimeout(() => {
                        modal.classList.add('hidden');
                    }, 300);
                }
            }
            window.toggleModal = toggleModal;

            document.getElementById('changePasswordBtn').addEventListener('click', () => {
                document.getElementById('changePasswordModal').classList.remove('hidden');
            });

            document.getElementById('closeChangePasswordModal').addEventListener('click', () => {
                document.getElementById('changePasswordModal').classList.add('hidden');
            });

            document.getElementById('backupDataBtn').addEventListener('click', () => {
                window.location.href = 'api.php?action=backup_database';
            });
            
        });

        function downloadReport(type, format) {
            const branch = document.getElementById('branchSelector').value || 'all';
            window.location.href = `api.php?action=download_report&type=${type}&format=${format}&branch=${branch}`;
        }
        window.downloadReport = downloadReport;
    </script>
</body>
</html>