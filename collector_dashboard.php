<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "collector"){
    header("location: index.php");
    exit;
}

$collector_name = $_SESSION["collector_name"] ?? "Collector";
$collector_branch = $_SESSION["collector_branch"] ?? "Unknown";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulacan Coop - Collector Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module" src="collector.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc;
        }
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
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-4 border-b">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-coins text-white text-sm"></i>
                    </div>
                    <span class="font-semibold text-gray-900">Bulacan Coop</span>
                </div>
            </div>
            <nav class="p-4">
                <div class="space-y-2">
                    <button class="sidebar-nav-item active w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-primary">
                        <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                        Dashboard
                    </button>
                </div>
            </nav>
            <div class="p-4 border-t mt-auto">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-gray-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($collector_name); ?></p>
                        <p class="text-xs text-gray-500">Collector • <?php echo htmlspecialchars($collector_branch); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Collector Dashboard</h1>
                <p class="text-gray-600">Manage daily payments and collections</p>
            </header>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-md card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Today's Collections</p>
                            <p id="todayCollections" class="text-2xl font-bold text-emerald-600">₱0</p>
                        </div>
                        <div class="p-3 bg-emerald-100 rounded-lg">
                            <i class="fas fa-money-bill-wave text-emerald-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Unpaid Clients</p>
                            <p id="unpaidClientsCount" class="text-2xl font-bold text-orange-600">0</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <i class="fas fa-users text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Paid Today</p>
                            <p id="paidClientsCount" class="text-2xl font-bold text-green-600">0</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Unpaid Clients -->
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center justify-between">
                        <span><i class="fas fa-clock text-orange-500 mr-2"></i>Unpaid Clients Today</span>
                        <span class="text-sm text-gray-500" id="unpaidCount">0 clients</span>
                    </h3>
                    <div id="unpaidClientsContainer" class="space-y-3 max-h-96 overflow-y-auto">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Loading unpaid clients...</p>
                        </div>
                    </div>
                </div>

                <!-- Paid Clients -->
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center justify-between">
                        <span><i class="fas fa-check-circle text-green-500 mr-2"></i>Paid Clients Today</span>
                        <span class="text-sm text-gray-500" id="paidCount">0 clients</span>
                    </h3>
                    <div id="paidClientsContainer" class="space-y-3 max-h-96 overflow-y-auto">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Loading paid clients...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Payments -->
            <div class="bg-white rounded-xl shadow-md p-6 mt-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-receipt text-blue-500 mr-2"></i>Today's Payments
                </h3>
                <div id="todayPaymentsContainer" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>Loading today's payments...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div id="qrScannerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform modal-content">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-qrcode text-primary mr-2"></i>
                    Scan QR Code
                </h2>
                <button id="closeQRScannerModal" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="text-center">
                <div id="qrReader" class="w-full h-64 bg-gray-100 rounded-lg mb-4 flex items-center justify-center">
                    <p class="text-gray-500">QR Scanner would appear here</p>
                </div>
                <p class="text-sm text-gray-600 mb-4">Scan client's payment QR code to record payment</p>
                <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200 mb-4">
                    <p class="text-sm text-yellow-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        For demo purposes, use manual payment entry below.
                    </p>
                </div>
                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Manual Payment Entry</h4>
                    <form id="manualPaymentForm" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loan ID</label>
                            <input type="number" id="manualLoanId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter Loan ID">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount (₱)</label>
                            <input type="number" id="manualPaymentAmount" required step="0.01" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter amount">
                        </div>
                        <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                            <i class="fas fa-check-circle mr-2"></i> Record Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Success Modal -->
    <div id="paymentSuccessModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-6 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Payment Recorded!</h3>
                <p id="paymentSuccessMessage" class="text-gray-600 mb-6">Payment has been successfully recorded.</p>
                <button id="closePaymentSuccessModal" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Simple modal controls
        document.getElementById('closeQRScannerModal').addEventListener('click', function() {
            document.getElementById('qrScannerModal').classList.add('hidden');
        });

        document.getElementById('closePaymentSuccessModal').addEventListener('click', function() {
            document.getElementById('paymentSuccessModal').classList.add('hidden');
            // Refresh data after successful payment
            if (typeof fetchCollectorData === 'function') {
                fetchCollectorData();
            }
        });

        // Manual payment form submission
        document.getElementById('manualPaymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loanId = document.getElementById('manualLoanId').value;
            const paymentAmount = document.getElementById('manualPaymentAmount').value;

            if (!loanId || !paymentAmount) {
                alert('Please fill in all fields');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'record_payment');
                formData.append('loan_id', loanId);
                formData.append('payment_amount', paymentAmount);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('paymentSuccessMessage').textContent = result.message;
                    document.getElementById('qrScannerModal').classList.add('hidden');
                    document.getElementById('paymentSuccessModal').classList.remove('hidden');
                    this.reset();
                } else {
                    alert('Payment failed: ' + result.message);
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('Payment failed. Please try again.');
            }
        });
    </script>
</body>
</html>