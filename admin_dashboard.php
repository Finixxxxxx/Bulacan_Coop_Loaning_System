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
    <script type="module" src="admin.js"></script>
    <link rel="stylesheet" href="admin.css">
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


            <div id="mainContent" class="space-y-10">
                <!-- Overview Section -->
                <section id="overview" class="tab-content">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Clients -->
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
                        <!-- Total Issued Loan -->
                        <div class="stat-card bg-gradient-to-r from-rose-500 to-rose-600 p-6 rounded-xl shadow-lg text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-rose-100 text-sm font-medium">Total Issued Loan</p>
                                    <p id="totalOutstandingAmount" class="text-3xl font-bold">₱0</p>
                                    <p class="text-rose-100 text-xs mt-1">Active Loans <strong class="text-md font-base bg-rose-200/50 rounded-full py-1 px-2 ml-1" id="activeLoansCount">0</strong></p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Total Payments Today (TODO: Add a rate comparing today with yesterday rate in percentage) -->
                        <div class="stat-card bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-lg text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-emerald-100 text-sm font-medium">Total Payments Today</p>
                                    <p id="totalPaymentsToday" class="text-3xl font-bold">₱0</p>
                                    <p class="text-emerald-100 text-xs mt-1">
                                        <i id="payRateFromYesterdayIcon" class="fas fa-arrow-up"></i>
                                        Rate from yesterday <strong id="payRateFromYesterday" class="text-md font-base bg-emerald-200/50 rounded-full py-1 px-2 ml-1">0%</strong></p>
                                </div>
                                <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-2xl"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Pending Loan Applications -->
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

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-3 card-flat">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3">Branch Statistics (Total Loans)</h3>
                            <div style="height: 350px;">
                                <canvas id="branchChart" style="max-height: 350px;"></canvas>
                            </div>
                        </div>
                        <!-- TODO: Add Statistic Charts: Bar Chart - Loan Issued vs. Payments, Total Outstanding Balance Over Time, Line Chart - Loan Interest Overtime -->
                    </div>
                </section>
                <!-- Client Section -->
                <section id="clients" class="tab-content hidden">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 hidden">Client Management</h2>
                            <p class="text-gray-600 text-sm mt-1 hidden">Manage your loan clients and their information</p>
                        </div>
                        <div class="flex gap-3 w-full sm:w-auto">
                            <button id="exportClientsBtn" class="w-1/2 sm:w-auto bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 transition-colors hidden">
                                <i class="fas fa-download mr-2"></i>Export
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

                <!-- Loans Section -->
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

                <!-- Payments Section (TODO: Record payments with payment id and their corresponding loan id and the amount, date and name of the client who paid) -->
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
                                    <p class="text-2xl font-bold text-emerald-600">₱8,450</p>
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
                                    <p class="text-2xl font-bold text-orange-600">₱15,230</p>
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
                                    <p class="text-2xl font-bold text-red-600">₱3,120</p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-receipt mr-2 text-primary"></i>Recent Payments
                        </h3>
                        <div id="recentPayments" class="space-y-4">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">₱4,500.00 from John Doe (Loan #1)</span>
                                </div>
                                <span class="text-xs text-gray-500">2 min ago</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">₱1,200.00 from Maria Cruz (Loan #5)</span>
                                </div>
                                <span class="text-xs text-gray-500">1 hour ago</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">₱800.00 from David Lee (Loan #8)</span>
                                </div>
                                <span class="text-xs text-gray-500">3 hours ago</span>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Reports Section (TODO: Generate choose from PDF, Excel, or CSV files for reports, Also have Advanced Analytics Such as rates of payment, total yield of interest and such) -->
                <section id="reports" class="tab-content hidden">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
                        <p class="text-gray-600 text-sm mt-1">Generate comprehensive reports and analytics</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-sky-100 rounded-lg">
                                    <i class="fas fa-chart-line text-primary text-2xl"></i>
                                </div>
                                <button class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance Report</h3>
                            <p class="text-gray-600 text-sm">Monthly loan performance and collection rates</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-emerald-100 rounded-lg">
                                    <i class="fas fa-users text-emerald-600 text-2xl"></i>
                                </div>
                                <button class="text-emerald-600 hover:text-emerald-800">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Client Report</h3>
                            <p class="text-gray-600 text-sm">Detailed client information and loan history</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer card-flat">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-purple-600 text-2xl"></i>
                                </div>
                                <button class="text-purple-600 hover:text-purple-800">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Financial Report</h3>
                            <p class="text-gray-600 text-sm">Revenue, expenses, and profit analysis</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-area mr-2 text-indigo-600"></i>Advanced Analytics
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <canvas id="monthlyTrendsChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="riskAnalysisChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Settings Section (TODO: Remove the General Settings and replace with Company Info, Make the Notifications functional with pop-up notifications) -->
                <section id="settings" class="tab-content hidden">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
                        <p class="text-gray-600 text-sm mt-1">Configure your loan management system</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-cog mr-2 text-primary"></i>General Settings
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                                    <input type="text" value="Bulacan Cooperative Loaning Services" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Interest Rate (%)</label>
                                    <input type="number" value="15" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="PHP" selected>PHP (₱)</option>
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-bell mr-2 text-yellow-600"></i>Notifications
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Payment Reminders</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Overdue Alerts</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-light rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">New Client Notifications</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
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
                                <button class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                                <button class="w-full bg-emerald-600 text-white py-2 px-4 rounded-lg hover:bg-emerald-700 transition-colors">
                                    <i class="fas fa-mobile-alt mr-2"></i>Enable 2FA
                                </button>
                                <button class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-download mr-2"></i>Backup Data
                                </button>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-flat">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-info-circle mr-2 text-indigo-600"></i>System Information
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Version:</span>
                                    <span class="font-medium">v2.1.0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Backup:</span>
                                    <span class="font-medium">Today, 3:00 AM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Database Size:</span>
                                    <span class="font-medium">245 MB</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Active Users:</span>
                                    <span class="font-medium">3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <div id="addClientModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white p-6 sm:p-8 space-y-6 transform transition-all duration-300 ease-out scale-95 modal-content">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-3 mb-4">Add New Client / Member</h3>
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
                        <i class="fas fa-thumbs-up mr-2"></i> Final Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Collector QR Scanner Modal (TODO: Open Camera and scan the generated QR from the client, in that qr, it will contain the loan id and the payment amount which will automatically be deducted when scaned with this modal) -->
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
    
    <!-- Currently, this is in use for viewing Loan and Client Details too (TODO: Make a separate Modal for client details and loan details) -->
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
        });
    </script>
</body>
</html>