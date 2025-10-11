<?php
session_start();

// Check if the user is logged in and is a client, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "client"){
    header("location: index.php");
    exit;
}

$client_name = $_SESSION["client_name"];
$member_id = $_SESSION["member_id"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulacan Coop - Client Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module" src="client.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
            box-sizing: border-box;
            background-color: #f7f7f7;
        }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .bg-primary { background-color: #0369A1; } 
        .hover\:bg-primary-dark:hover { background-color: #075985; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="p-4 sm:p-6 lg:p-8 min-h-screen">

    <!-- Header / Nav -->
    <header class="mb-8 p-4 bg-white shadow-md rounded-xl flex justify-between items-center fade-in">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
            Welcome, <span id="clientNameDisplay" class="text-primary"><?php echo htmlspecialchars($client_name); ?></span>
        </h1>
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-600 hidden sm:inline">ID: <?php echo htmlspecialchars($member_id); ?></span>
            <button id="logoutBtn" class="bg-gray-100 text-gray-700 py-1 px-3 rounded-lg text-sm font-semibold hover:bg-red-500 hover:text-white transition-colors">
                <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Logout</span>
            </button>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Column 1: Loan Status and Quick Pay -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Active Loan Status Card -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-primary card-hover fade-in" id="loanStatusSection">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-money-check-alt text-primary mr-3"></i> Current Loan Status
                </h2>
                
                <div id="loanDetailsContainer" class="space-y-4">
                    <!-- Data will be populated by client.js -->
                    <div class="grid grid-cols-2 gap-4 text-gray-600">
                        <div>
                            <p class="text-sm font-medium">Current Balance</p>
                            <p id="currentBalance" class="text-3xl font-bold text-gray-900">₱0.00</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Monthly Due</p>
                            <p id="monthlyPayment" class="text-2xl font-bold text-red-600">₱0.00</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Next Payment Due</p>
                            <p id="nextPaymentDate" class="text-lg font-semibold">--</p>
                            <span data-due-days class="text-xs text-gray-500"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Loan Status</p>
                            <span id="loanStatusBadge" class="status-badge bg-gray-100 text-gray-600">Loading...</span>
                        </div>
                    </div>

                    <div id="noActiveLoanMessage" class="hidden text-center p-8 bg-blue-50 rounded-lg">
                        <i class="fas fa-info-circle text-blue-500 text-2xl mb-2"></i>
                        <p class="text-lg font-medium text-blue-800">No Active Loan Found.</p>
                        <p class="text-sm text-blue-700">Apply for a new loan below!</p>
                    </div>

                </div>
            </section>

            <!-- Loan History Table -->
            <section class="bg-white p-6 rounded-2xl shadow-lg card-hover fade-in">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history text-primary mr-3"></i> Payment History
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data populated by client.js -->
                            <tr><td colspan="3" class="text-center py-4 text-gray-500">No payment history found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Column 2: Payment and Application -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Quick Payment Card -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-green-500 card-hover fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-qrcode text-green-500 mr-3"></i> Quick Payment QR
                </h2>
                <p class="text-sm text-gray-600 mb-3">Generate a QR code for quick payment at any branch.</p>
                <div class="space-y-3">
                    <label for="paymentAmount" class="block text-sm font-medium text-gray-700">Payment Amount (₱)</label>
                    <input type="number" id="paymentAmount" value="0.00" min="100" placeholder="e.g. 2500" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-150">
                </div>
                
                <div class="flex space-x-2 mt-3 mb-4">
                    <button onclick="setQuickAmount(document.getElementById('monthlyPayment').textContent.replace(/[^0-9.]/g, ''))" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition">Monthly Due</button>
                    <button onclick="setQuickAmount(document.getElementById('currentBalance').textContent.replace(/[^0-9.]/g, ''))" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition">Full Balance</button>
                </div>
                
                <button id="generatePaymentQR" 
                        class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition-colors duration-200 shadow-lg shadow-green-500/50">
                    <i class="fas fa-qrcode mr-2"></i> Generate QR Code
                </button>
            </section>

            <!-- New Loan Application Card -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-yellow-500 card-hover fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-file-invoice-dollar text-yellow-500 mr-3"></i> Apply for New Loan
                </h2>
                <form id="loanApplicationForm" class="space-y-4">
                    <div>
                        <label for="loanAmountInput" class="block text-sm font-medium text-gray-700 mb-1">Amount (₱)</label>
                        <input type="number" id="loanAmountInput" required min="1000" step="500" placeholder="e.g. 50000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition duration-150">
                    </div>
                    <div>
                        <label for="loanTermInput" class="block text-sm font-medium text-gray-700 mb-1">Term (Months)</label>
                        <select id="loanTermInput" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition duration-150">
                            <option value="6">6 Months</option>
                            <option value="12">12 Months</option>
                            <option value="18">18 Months</option>
                            <option value="24">24 Months</option>
                        </select>
                    </div>
                    <div>
                        <label for="loanPurposeInput" class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                        <textarea id="loanPurposeInput" rows="3" required placeholder="e.g. Educational expenses for child, business expansion"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition duration-150"></textarea>
                    </div>
                    <div id="applicationMessage" class="hidden p-3 text-center rounded-lg text-sm"></div>
                    <button type="submit" id="submitLoanBtn"
                            class="w-full bg-yellow-600 text-white font-semibold py-3 rounded-xl hover:bg-yellow-700 transition-colors duration-200 shadow-lg shadow-yellow-500/50">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Application
                    </button>
                </form>
            </section>
        </div>
    </main>

    <!-- QR Modal -->
    <div id="qrModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center fade-in">
        <div class="relative p-8 border w-full max-w-sm shadow-2xl rounded-2xl bg-white space-y-4 transform scale-100 transition-transform">
            <button id="closeQRModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center">
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Payment QR Code</h3>
                <p class="text-sm text-gray-500 mb-4">Show this code at the branch teller.</p>
                <div id="qrCodeContainer" class="mb-4 flex justify-center p-2 bg-gray-50 rounded-lg">
                    <canvas id="qrCanvas" class="border border-gray-300 rounded-lg"></canvas>
                </div>
                <p id="qrAmountDisplay" class="text-2xl font-bold text-green-600 mb-4">₱0.00</p>
                
                <div class="flex space-x-3">
                    <button id="downloadQR" class="w-1/2 bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition-colors text-sm">
                        <i class="fas fa-download mr-1"></i> Download
                    </button>
                    <button id="shareQR" class="w-1/2 bg-gray-300 text-gray-700 py-2 rounded-xl hover:bg-gray-400 transition-colors text-sm">
                        <i class="fas fa-share-alt mr-1"></i> Share
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Message Modal -->
    <div id="messageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center fade-in">
        <div class="relative p-6 border w-96 shadow-2xl rounded-xl bg-white transform scale-100 transition-transform">
            <div class="text-center">
                <div id="modalIcon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4">
                    <!-- Icon will be injected -->
                </div>
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                <p id="modalMessage" class="text-gray-600 mb-6"></p>
                <button id="closeMessageModal" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

</body>
</html>
