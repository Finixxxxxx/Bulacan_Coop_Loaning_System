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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="./css/admin.css">
    <link rel="stylesheet" href="./css/global.css">
    <script type="module" src="admin.js"></script>
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
<body class="bg-background-soft min-h-screen antialiased">
    <div id="dashboard" class="flex min-h-screen">
        <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden"></div>
        <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
            <div class="flex flex-col h-full">
                <div class="flex items-center m-3">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-primary/30">
                        <i class="fas fa-handshake text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Bulacan Coop</p>
                        <p class="text-gray-500 text-xs">Loaning Management System</p>
                    </div>
                </div>

                <div class="p-4 border-b border-gray-100">
                    <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase">
                        <i class="fas fa-filter mr-2 text-primary"></i>Filter by Branch
                    </label>
                    <select id="branchSelector" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent input-focus-style">
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

                <nav class="flex-1 p-3">
                    <div class="space-y-1">
                        <button class="sidebar-nav-item active w-full flex items-center font-medium rounded-lg" data-tab="overview">
                            <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                            Overview
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center font-medium rounded-lg" data-tab="clients">
                            <i class="fas fa-users mr-3 w-5"></i>
                            Clients
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center font-medium rounded-lg" data-tab="loans">
                            <i class="fas fa-list-alt mr-3 w-5"></i>
                            Loans
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center font-medium rounded-lg" data-tab="payments">
                            <i class="fas fa-credit-card mr-3 w-5"></i>
                            Payments
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center font-medium rounded-lg" data-tab="collectors">
                            <i class="fas fa-user-friends mr-3 w-5"></i>
                            Collectors
                        </button>
                        <button class="sidebar-nav-item w-full flex items-center font-medium rounded-lg" data-tab="settings">
                            <i class="fas fa-cog mr-3 w-5"></i>
                            Reports & Settings
                        </button>
                    </div>
                </nav>

                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-9 h-9 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user-shield text-primary text-md"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($admin_name); ?></p>
                                <p class="text-xs text-gray-500">System Administrator</p>
                            </div>
                        </div>
                        <button id="logoutBtn" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <main class="flex-1 lg:ml-64 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 overflow-y-auto">
            
            <header class="bg-white rounded-xl p-4 mb-8 card-flat">
                <div class="flex justify-between items-center h-full">
                    <div class="flex items-center">
                        <button id="mobileSidebarBtn" class="text-gray-700 focus:outline-none md:hidden mr-3">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <h1 id="page-title" class="text-2xl font-bold text-gray-900 transition-all duration-300">Dashboard Overview</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 hidden sm:inline">
                            <i class="fas fa-calendar-alt mr-2 text-primary"></i> Today: <?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                </div>
            </header>

            <div id="notificationContainer" class="notification-container"></div>

            <div id="mainContent" class="space-y-10">
                
                <section id="overview" class="tab-content fade-in">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div id="totalClients" class="stat-card bg-gradient-to-r from-sky-500 to-sky-600 p-6 rounded-xl shadow-xl shadow-sky-500/30 text-white cursor-pointer hover:shadow-sky-500/50 transition-all duration-300" go-to-tab="clients">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sky-100 text-sm font-medium uppercase tracking-wider">Total Clients</p>
                                    <p class="text-4xl font-extrabold mt-1" id="totalClientsCount">0</p>
                                    <p class="text-sky-100 text-xs mt-2 font-light">Click to manage clients</p>
                                </div>
                                <div class="p-4 bg-black bg-opacity-10 rounded-lg">
                                    <i class="fas fa-users text-3xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-gradient-to-r from-rose-500 to-rose-600 p-6 rounded-xl shadow-xl shadow-rose-500/30 text-white hover:shadow-rose-500/50 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-rose-100 text-sm font-medium uppercase tracking-wider">Total Outstanding</p>
                                    <p id="totalOutstandingAmount" class="text-4xl font-extrabold mt-1">₱0</p>
                                    <p class="text-rose-100 text-xs mt-2 font-light">Active Loans: <strong class="ml-1 font-semibold bg-white/30 rounded-full px-2 py-1" id="activeLoansCount">0</strong></p>
                                </div>
                                <div class="p-4 bg-black bg-opacity-10 rounded-lg">
                                    <i class="fas fa-hand-holding-usd text-3xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-xl shadow-emerald-500/30 text-white hover:shadow-emerald-500/50 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-emerald-100 text-sm font-medium uppercase tracking-wider">Payments Today</p>
                                    <p id="totalPaymentsToday" class="text-4xl font-extrabold mt-1">₱0</p>
                                    <p class="text-emerald-100 text-xs mt-2 font-light">
                                        <i id="payRateFromYesterdayIcon" class="fas fa-arrow-up text-white mr-1"></i>
                                        Rate from yesterday <strong id="payRateFromYesterday" class="ml-1 font-semibold bg-white/30 rounded-full px-2 py-1">0%</strong></p>
                                </div>
                                <div class="p-4 bg-black bg-opacity-10 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-3xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> 
                        <div class="bg-white p-6 rounded-xl card-flat">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3">
                                <i class="fas fa-chart-pie mr-2 text-primary"></i>Total Loans per Branch
                            </h3>
                            <div style="height: 350px;">
                                <canvas id="branchChart" style="max-height: 350px;"></canvas>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl card-flat">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3">
                                <i class="fas fa-chart-bar mr-2 text-primary"></i>Loan & Payment Trends
                            </h3>
                            <div style="height: 350px;">
                                <canvas id="monthlyTrendsChart" style="max-height: 350px;"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="clients" class="tab-content hidden fade-in">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Client Management</h2>
                            <p class="text-gray-600 text-sm mt-1">Manage your loan clients and their information.</p>
                        </div>
                        <div class="flex gap-3 w-full sm:w-auto">
                            <button id="exportClientsBtn" class="w-1/2 sm:w-auto bg-emerald-600 text-white px-4 py-2.5 rounded-lg font-semibold hover:bg-emerald-700 transition-colors shadow-md shadow-emerald-600/20">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                            <button id="showAddClientModal" class="w-full sm:w-auto bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-md shadow-primary/20">
                                <i class="fas fa-user-plus mr-2"></i> Add New Client
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-xl mb-6 card-flat">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="relative md:col-span-2">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                                <input type="text" id="clientSearch" placeholder="Search by name, ID, or email..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style transition">
                            </div>
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style hidden">
                                <option value="">All Status</option>
                            </select>
                            <select id="sortBy" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style hidden">
                                <option value="name">Sort by Name</option>
                            </select>
                            <button id="clearFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors hidden">
                                <i class="fas fa-times mr-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden card-flat">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-id-card mr-2"></i>Member ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-envelope mr-2"></i>Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-dollar-sign mr-2"></i>Outstanding</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-building mr-2"></i>Branch</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Loading client data...</p>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="loans" class="tab-content hidden fade-in">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Loan Management</h2>
                            <p class="text-gray-600 text-sm mt-1">View all loan applications and transactions.</p>
                        </div>
                        <div class="flex space-x-3 items-center w-full sm:w-auto">
                            <input type="text" id="loanSearch" placeholder="Search by Loan ID or Client..." 
                                class="w-full sm:max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style transition shadow-sm">
                            <button id="newLoanBtn" class="bg-primary text-white px-4 py-2.5 rounded-lg font-semibold hover:bg-primary-dark transition-colors shadow-md shadow-primary/20 whitespace-nowrap">
                                <i class="fas fa-plus mr-2"></i>New Loan
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden card-flat">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-hashtag mr-2"></i>Loan ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Client Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-sack-dollar mr-2"></i>Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-balance-scale-left mr-2"></i>Balance</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-info-circle mr-2"></i>Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="loansTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Loading loan data...</p>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="payments" class="tab-content hidden fade-in">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Payment Management</h2>
                        <p class="text-gray-600 text-sm mt-1">Track and manage all loan payments and collections.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wider">Today's Collections</p>
                                    <p id="totalCollectionsToday" class="text-3xl font-bold text-emerald-600 mt-1">₱0</p>
                                </div>
                                <div class="p-3 bg-emerald-100 rounded-lg">
                                    <i class="fas fa-money-check-alt text-emerald-600 text-2xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wider">Pending Payments</p>
                                    <p id="totalPendingPaymentsTodal" class="text-3xl font-bold text-orange-600 mt-1">₱0</p>
                                </div>
                                <div class="p-3 bg-orange-100 rounded-lg">
                                    <i class="fas fa-clock text-orange-600 text-2xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wider">Total Overdue</p>
                                    <p id="totalOverduePayments" class="text-3xl font-bold text-red-600 mt-1">₱0</p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 card-flat">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 justify-between flex items-center border-b pb-3">
                            <div class="flex items-center">
                                <i class="fas fa-receipt mr-2 text-primary"></i>Recent Payments History
                            </div>
                            <select name="payment_date_filter" id="paymentDateFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:border-transparent input-focus-style">
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
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading recent payments...</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="collectors" class="tab-content hidden fade-in">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Collector Management</h2>
                            <p class="text-gray-600 text-sm mt-1">Manage payment collectors and their assignments.</p>
                        </div>
                        <button id="showAddCollectorModal" class="w-full sm:w-auto bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-md shadow-primary/20">
                            <i class="fas fa-user-plus mr-2"></i> Add New Collector
                        </button>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden card-flat mb-8">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user-tag mr-2"></i>Username</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-building mr-2"></i>Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-info-circle mr-2"></i>Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="collectorsTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Loading collector data...</p>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 card-flat">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3">
                            <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Collector Performance Reports
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Date</label>
                                <input type="date" id="collectorReportDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="flex justify-between mt-6 gap-6 flex-col md:flex-row">
                            <div class="w-full" style="height: 300px;">
                                <canvas id="collectorAmountCollectedChart"></canvas>
                                <p class="text-center text-sm font-medium text-gray-600 mt-2">Amount Collected</p>
                            </div>
                            <div class="w-full" style="height: 300px;">
                                <canvas id="collectorClientsCollectedChart"></canvas>
                                <p class="text-center text-sm font-medium text-gray-600 mt-2">Clients Served</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="settings" class="tab-content hidden fade-in">
                    
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
                        <p class="text-gray-600 text-sm mt-1">Generate and download comprehensive reports.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-sky-100 rounded-lg">
                                    <i class="fas fa-chart-area text-primary text-2xl"></i>
                                </div>
                                <div class="flex space-x-2 text-sm font-semibold">
                                    <button onclick="downloadReport('performance', 'pdf')" class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('performance', 'excel')" class="text-green-600 hover:text-green-800 p-2 rounded-full hover:bg-green-50 transition-colors">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('performance', 'csv')" class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Performance Report</h3>
                            <p class="text-gray-500 text-sm">Monthly loan performance and collection rates.</p>
                        </div>  

                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-emerald-100 rounded-lg">
                                    <i class="fas fa-users text-emerald-600 text-2xl"></i>
                                </div>
                                <div class="flex space-x-2 text-sm font-semibold">
                                    <button onclick="downloadReport('client', 'pdf')" class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('client', 'excel')" class="text-green-600 hover:text-green-800 p-2 rounded-full hover:bg-green-50 transition-colors">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('client', 'csv')" class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Client Report</h3>
                            <p class="text-gray-500 text-sm">Detailed client information and loan history.</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <i class="fas fa-money-bill-transfer text-purple-600 text-2xl"></i>
                                </div>
                                <div class="flex space-x-2 text-sm font-semibold">
                                    <button onclick="downloadReport('financial', 'pdf')" class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('financial', 'excel')" class="text-green-600 hover:text-green-800 p-2 rounded-full hover:bg-green-50 transition-colors">
                                        <i class="fas fa-file-excel text-xl"></i>
                                    </button>
                                    <button onclick="downloadReport('financial', 'csv')" class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors">
                                        <i class="fas fa-file-csv text-xl"></i>
                                    </button>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Financial Report</h3>
                            <p class="text-gray-500 text-sm">Revenue, expenses, and profit analysis.</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
                        <p class="text-gray-600 text-sm mt-1">Configure your loan management system and security.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <div class="bg-white rounded-xl p-6 card-flat">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3">
                                <i class="fas fa-bell mr-2 text-yellow-600"></i>Notifications
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700">Overdue Loan Alerts</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer" id="notifOverdueLoan">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700">New Payments</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="notifNewPayment">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl p-6 card-flat">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3">
                                <i class="fas fa-shield-alt mr-2 text-emerald-600"></i>Security & Maintenance
                            </h3>
                            <div class="space-y-4">
                                <button id="changePasswordBtn" class="w-full bg-primary text-white py-2.5 px-4 rounded-lg font-semibold hover:bg-primary-dark transition-colors shadow-md shadow-primary/20">
                                    <i class="fas fa-key mr-2"></i>Change Admin Password
                                </button>
                                <button id="backupDataBtn" class="w-full bg-gray-600 text-white py-2.5 px-4 rounded-lg font-semibold hover:bg-gray-700 transition-colors shadow-md shadow-gray-600/20">
                                    <i class="fas fa-download mr-2"></i>Perform Data Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div id="addClientModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4">
                <h3 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-plus text-primary mr-2"></i> Add New Client</h3>
            </div>
            <form id="addClientForm" class="space-y-4">
                <div class="flex space-x-4">
                    <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                        <input type="text" name="c_firstname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter First Name">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="c_lastname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Last Name">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email (Optional)</label>
                    <input type="email" name="c_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Email">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                    <input type="text" name="c_phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Phone Number">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
                    <input type="text" name="c_address" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Full Address">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Branch</label>
                    <select name="c_branch" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                        <option value="" disabled selected>Select Branch</option>
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
                    <button type="button" id="closeAddClientModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button type="submit" id="addClientSubmitBtn" class="bg-emerald-600 text-white py-2.5 px-5 rounded-lg hover:bg-emerald-700 transition-colors font-semibold shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-save mr-2"></i> Save Client
                    </button>
                </div>
            </form>
        </div>
    </div> 

    <div id="addCollectorModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4">
                <h3 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-plus text-primary mr-2"></i> Add New Collector</h3>
            </div>
            <form id="addCollectorForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="col_fullname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Full Name">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <input type="text" name="col_username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Username (e.g., col_juan)">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Temporary Password</label>
                    <input type="password" name="col_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Initial Password">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Branch</label>
                    <select name="col_branch" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                        <option value="" disabled selected>Select Branch</option>
                        <option value="malolos">Malolos</option>
                        <option value="hagonoy">Hagonoy</option>
                        <option value="calumpit">Calumpit</option>
                        <option value="balagtas">Balagtas</option>
                        <option value="marilao">Marilao</option>
                        <option value="staMaria">Sta. Maria</option>
                        <option value="plaridel">Plaridel</option>
                    </select>
                </div>
                <div id="addCollectorMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeAddCollectorModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button type="submit" id="addCollectorSubmitBtn" class="bg-emerald-600 text-white py-2.5 px-5 rounded-lg hover:bg-emerald-700 transition-colors font-semibold shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-user-check mr-2"></i> Create Collector
                    </button>
                </div>
            </form>
        </div>
    </div> 

    <div id="editCollectorModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4">
                <h3 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-edit text-primary mr-2"></i> Edit Collector</h3>
            </div>
            <form id="editCollectorForm" class="space-y-4">
                <input type="hidden" id="editCollectorId" name="collector_id">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="editCollectorFullname" name="col_fullname" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Full Name">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <input type="text" id="editCollectorUsername" name="col_username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter Username">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">New Password (leave blank to keep current)</label>
                    <input type="password" name="col_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="Enter New Password">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Branch</label>
                    <select id="editCollectorBranch" name="col_branch" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                        <option value="" disabled selected>Select Branch</option>
                        <option value="malolos">Malolos</option>
                        <option value="hagonoy">Hagonoy</option>
                        <option value="calumpit">Calumpit</option>
                        <option value="balagtas">Balagtas</option>
                        <option value="marilao">Marilao</option>
                        <option value="staMaria">Sta. Maria</option>
                        <option value="plaridel">Plaridel</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select id="editCollectorStatus" name="col_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div id="editCollectorMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeEditCollectorModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button type="submit" id="editCollectorSubmitBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="newLoanModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800"> 
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-600 mr-2"></i> New Loan Application 
                </h2>
            </div>
            <form id="newLoanForm" class="space-y-4">
                <input type="hidden" name="client_id" id="newLoanClientId">
                <input type="hidden" name="member_id" id="newLoanMemberId">
                <p class="text-sm text-gray-600 mb-4">Client: <strong id="newLoanClientName" class="text-primary"></strong></p>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Loan Amount (Principal)</label>
                    <input type="number" step="100" min="10000" value="10000.00" id="newLoanAmount" name="loan_amount" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style" placeholder="e.g., 5000">
                </div>

                <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                    <p class="font-semibold text-gray-700 border-b pb-2 mb-2"><i class="fas fa-calculator mr-2 text-primary"></i> Estimated Loan Calculation (100 days)</p>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Processing Fee (- ₱200):</span>
                        <span id="calcProcessingFee" class="font-bold text-red-500">₱0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Amount Received:</span>
                        <span id="calcAmountReceived" class="font-bold text-green-500">₱0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Interest (15%):</span>
                        <span id="calcInterest" class="font-bold text-orange-500">₱0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-base border-t pt-2">
                        <span class="text-gray-800">Total Balance Due:</span>
                        <span id="calcTotalBalance" class="text-primary">₱0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-base">
                        <span class="text-gray-800">Estimated Daily Payment:</span>
                        <span id="calcDailyPayment" class="text-emerald-600">₱0.00</span>
                    </div>
                </div>

                <div id="newLoanMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>

                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="cancelNewLoanBtn" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button type="submit" id="submitNewLoanBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Loan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="clientDetailsModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800"> 
                    <i class="fa-solid fa-user-circle text-primary mr-2"></i> Client Details 
                </h2>
            </div>
            <div class="space-y-4 text-gray-800 mb-6">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-id-card"></i> Member ID: </span>
                    <span id="clientMemberID" class="font-bold text-primary">M001</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-user"></i> Name: </span>
                    <span id="clientName">Juan Dela Cruz</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-envelope"></i> Email: </span>
                    <span id="clientEmail">email@example.com</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-phone"></i> Phone: </span>
                    <span id="clientPhone">+63 912 345 6789</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-location-dot"></i> Address: </span>
                    <span id="clientAddress">123 Purok 1 Malolos</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-building"></i> Branch: </span>
                    <span id="clientBranch">Malolos</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-calendar-alt"></i> Date Joined: </span>
                    <span id="clientDateJoined">2023-01-01</span>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button id="closeClientDetailsModal" class="w-1/2 bg-gray-400 text-white py-2.5 px-3 rounded-lg hover:bg-gray-500 transition-colors font-semibold shadow-md shadow-gray-600/20">
                    <i class="fas fa-times mr-2"></i> Close
                </button>
                <button id="deactivateClientBtn" class="w-1/2 bg-red-600 text-white py-2.5 px-3 rounded-lg hover:bg-red-700 transition-colors font-semibold shadow-md shadow-red-600/20">
                    <i class="fas fa-user-slash mr-2"></i> Deactivate
                </button>
            </div>
        </div>
    </div>

    <div id="loanDetailsModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800"> 
                    <i class="fa-solid fa-magnifying-glass-chart text-primary mr-2"></i> Loan Details 
                </h2>
            </div>
            <div class="space-y-4 text-gray-800 mb-6">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-hashtag"></i> Loan ID: </span>
                    <span id="loanId" class="font-bold text-primary">L001</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-user"></i> Client Name: </span>
                    <span id="loanClientName">Juan DelaCruz</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-info-circle"></i> Status: </span>
                    <span id="loanStatus" class="status-badge bg-green-100 text-green-800">Active</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-money-bill-transfer"></i> Loan Amount: </span>
                    <span id="loanAmount" class="font-bold">₱10,000.00</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-sack-xmark"></i> Loan Balance: </span>
                    <span id="loanBalance" class="font-bold text-red-600">₱0</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-calendar-day"></i> Daily Payment: </span>
                    <span id="loanDaily" class="font-bold text-emerald-600">₱0</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-600 flex items-center gap-2"> <i class="fa-solid fa-chart-line"></i> Days Paid/Total: </span>
                    <span id="loanDaysPaid" class="font-bold">0/100</span>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button id="closeLoanDetailsModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Close </button>
            </div>
        </div>
    </div>

    <div id="adminMessageModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="text-center">
                <div id="adminModalIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4"></div>
                <h3 id="adminModalTitle" class="text-xl font-bold text-gray-900 mb-2"></h3>
                <p id="adminModalMessage" class="text-gray-600 mb-6"></p>
                <button id="closeAdminMessageModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold"> Close </button>
            </div>
        </div>
    </div> 

    <div id="logoutConfirmationModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to log out of the admin dashboard?</p>
                <div class="flex space-x-3">
                    <button id="cancelLogoutBtn" class="w-1/2 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button id="confirmLogoutBtn" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 transition-colors font-semibold"> 
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout 
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="changePasswordModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform modal-content  fade-in">
            <div class="flex justify-between items-center border-b pb-4">
                <h3 class="text-2xl font-bold text-gray-900"><i class="fas fa-key text-primary mr-2"></i> Change Admin Password</h3>
            </div>
            <form id="changePasswordForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent input-focus-style">
                </div>
                <div id="changePasswordMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" id="closeChangePasswordModal" class="bg-gray-200 text-gray-700 py-2.5 px-5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button type="submit" id="changePasswordSubmitBtn" class="bg-primary text-white py-2.5 px-5 rounded-lg hover:bg-primary-dark transition-colors font-semibold shadow-lg shadow-primary/30">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmDeclineModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-ban text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Loan Decline</h3>
                <p id="declineLoanMessage" class="text-gray-600 mb-6">Are you sure you want to decline this loan application? This action cannot be undone.</p>
                <div class="flex space-x-3">
                    <button id="closeConfirmDeclineModal" class="w-1/2 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button id="confirmDeclineBtn" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 transition-colors font-semibold"> 
                        <i class="fas fa-ban mr-2"></i> Decline 
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="deactivateClientConfirmationModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-full max-w-sm shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-user-slash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Deactivate Client Account</h3>
                <p id="deactivateClientMessage" class="text-gray-600 mb-6"></p>
                <div class="flex space-x-3">
                    <button id="cancelDeactivateClientBtn" class="w-1/2 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button id="confirmDeactivateClientBtn" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 transition-colors font-semibold"> 
                        <i class="fas fa-user-slash mr-2"></i> Deactivate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteCollectorConfirmationModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content  fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-user-times text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Collector</h3>
                <p id="deleteCollectorMessage" class="text-gray-600 mb-6"></p>
                <div class="flex space-x-3">
                    <button id="cancelDeleteCollectorBtn" class="w-1/2 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors font-semibold"> Cancel </button>
                    <button id="confirmDeleteCollectorBtn" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 transition-colors font-semibold"> 
                        <i class="fas fa-trash-alt mr-2"></i> Delete 
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            

        });

        

        
    </script>
</body>
</html>