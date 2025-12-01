<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "collector"){
    header("location: index.php");
    exit;
}

$collector_name = $_SESSION["collector_name"] ?? "Collector";
$collector_branch = $_SESSION["collector_branch"] ?? "Unknown";
$collector_username = $_SESSION["collector_username"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulacan Coop - Collector Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
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
        #qrReader {
            width: 100%;
            height: 300px;
        }
        #qrReader__dashboard_section{
            display: none;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        
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
                    <button class="sidebar-nav-item active w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-primary" data-tab="dashboard">
                        <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                        Dashboard
                    </button>
                    <button class="sidebar-nav-item w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50" data-tab="profile">
                        <i class="fas fa-user mr-3 w-5"></i>
                        Profile
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

        
        <div class="flex-1 p-6">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900" id="pageTitle">Collector Dashboard</h1>
                <p class="text-gray-600" id="pageSubtitle">Manage daily payments and collections</p>
            </header>

            
            <div id="dashboard" class="tab-content">
                
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

                
                <div class="mb-8 flex space-x-4">
                    <button id="scanQRBtn" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors flex items-center">
                        <i class="fas fa-qrcode mr-2"></i> Scan QR Code
                    </button>
                    <button id="manualEntryBtn" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors flex items-center">
                        <i class="fas fa-keyboard mr-2"></i> Manual Entry
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
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

            
            <div id="profile" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover max-w-2xl">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Collector Profile</h2>
                    
                    <form id="profileForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="profileFullname" name="col_fullname" value="<?php echo htmlspecialchars($collector_name); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" id="profileUsername" name="col_username" value="<?php echo htmlspecialchars($collector_username); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                            <input type="text" value="<?php echo htmlspecialchars($collector_branch); ?>" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Branch cannot be changed</p>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="Enter current password">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" name="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="Enter new password">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition" placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>

                        <div id="profileMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>

                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button type="button" id="logoutBtn" class="bg-red-600 text-white py-2.5 px-6 rounded-lg hover:bg-red-700 transition-colors font-semibold">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                            <button type="submit" id="profileSubmitBtn" class="bg-primary text-white py-2.5 px-6 rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                                <i class="fas fa-save mr-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
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
                <div id="qrReader" class="w-full h-64 bg-gray-100 rounded-lg mb-4 items-center justify-center">
                    <p class="text-gray-500">Initializing camera...</p>
                </div>
                <p class="text-sm text-gray-600 mb-4">Scan client's payment QR code to record payment</p>
                <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200 mb-4">
                    <p class="text-sm text-yellow-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Make sure the QR code contains valid payment information.
                    </p>
                </div>
            </div>
        </div>
    </div>

    
    <div id="manualPaymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white transform modal-content">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-keyboard text-primary mr-2"></i>
                    Manual Payment Entry
                </h2>
                <button id="closeManualPaymentModal" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="manualPaymentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loan ID</label>
                    <input type="number" id="manualLoanId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter Loan ID">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount (₱)</label>
                    <input type="number" id="manualPaymentAmount" required step="0.01" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter amount">
                </div>
                <div id="manualPaymentMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>
                <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                    <i class="fas fa-check-circle mr-2"></i> Record Payment
                </button>
            </form>
        </div>
    </div>

    
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
        let html5QrcodeScanner = null;
        document.querySelectorAll('.sidebar-nav-item').forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                switchTab(targetTab);
            });
        });

        function switchTab(tabName) {
            document.querySelectorAll('.sidebar-nav-item').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-50', 'text-primary');
                btn.classList.add('text-gray-600', 'hover:bg-gray-50');
            });
            
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.add('active', 'bg-blue-50', 'text-primary');
            activeButton.classList.remove('text-gray-600', 'hover:bg-gray-50');
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tabName).classList.remove('hidden');
            const pageTitle = document.getElementById('pageTitle');
            const pageSubtitle = document.getElementById('pageSubtitle');
            
            if (tabName === 'dashboard') {
                pageTitle.textContent = 'Collector Dashboard';
                pageSubtitle.textContent = 'Manage daily payments and collections';
            } else if (tabName === 'profile') {
                pageTitle.textContent = 'Collector Profile';
                pageSubtitle.textContent = 'Manage your account information';
            }
        }
        document.getElementById('scanQRBtn').addEventListener('click', function() {
            document.getElementById('qrScannerModal').classList.remove('hidden');
            initializeQRScanner();
        });

        document.getElementById('manualEntryBtn').addEventListener('click', function() {
            document.getElementById('manualPaymentModal').classList.remove('hidden');
        });

        document.getElementById('closeQRScannerModal').addEventListener('click', function() {
            document.getElementById('qrScannerModal').classList.add('hidden');
            stopQRScanner();
        });

        document.getElementById('closeManualPaymentModal').addEventListener('click', function() {
            document.getElementById('manualPaymentModal').classList.add('hidden');
        });

        document.getElementById('closePaymentSuccessModal').addEventListener('click', function() {
            document.getElementById('paymentSuccessModal').classList.add('hidden');
            if (typeof fetchCollectorData === 'function') {
                fetchCollectorData();
            }
        });

        document.getElementById('logoutBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

        function initializeQRScanner() {
            if (html5QrcodeScanner) {
                return;
            }

            html5QrcodeScanner = new Html5QrcodeScanner(
                "qrReader",
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                },
                false
            );

            html5QrcodeScanner.render(onQRScanSuccess, onQRScanFailure);
        }

        function stopQRScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => {
                    html5QrcodeScanner = null;
                }).catch(err => {
                    console.error("Failed to clear QR scanner", err);
                });
            }
        }

        function onQRScanSuccess(decodedText, decodedResult) {
            try {
                const qrData = JSON.parse(decodedText);
                if (!qrData.clientId || !qrData.loanId || !qrData.paymentAmount) {
                    throw new Error('Invalid QR code format');
                }
                processQRPayment(qrData.clientId, qrData.loanId, qrData.paymentAmount);
                stopQRScanner();
                document.getElementById('qrScannerModal').classList.add('hidden');
                
            } catch (error) {
                console.error('QR scan error:', error);
                alert('Invalid QR code. Please make sure you are scanning a valid payment QR code.');
            }
        }

        function onQRScanFailure(error) {
            console.log('QR scan failed:', error);
        }

        async function processQRPayment(clientId, loanId, paymentAmount) {
            try {
                const formData = new FormData();
                formData.append('action', 'process_qr_payment');
                formData.append('client_id', clientId);
                formData.append('loan_id', loanId);
                formData.append('payment_amount', paymentAmount);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('paymentSuccessMessage').textContent = result.message;
                    document.getElementById('paymentSuccessModal').classList.remove('hidden');
                } else {
                    alert('Payment failed: ' + result.message);
                }
            } catch (error) {
                console.error('Payment processing error:', error);
                alert('Payment failed. Please try again.');
            }
        }
        document.getElementById('manualPaymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loanId = document.getElementById('manualLoanId').value;
            const paymentAmount = document.getElementById('manualPaymentAmount').value;
            const messageDiv = document.getElementById('manualPaymentMessage');

            if (!loanId || !paymentAmount) {
                messageDiv.textContent = 'Please fill in all fields.';
                messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
                messageDiv.classList.remove('hidden');
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
                    messageDiv.textContent = result.message;
                    messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-green-100 text-green-700';
                    messageDiv.classList.remove('hidden');
                    setTimeout(() => {
                        document.getElementById('manualPaymentModal').classList.add('hidden');
                        this.reset();
                        messageDiv.classList.add('hidden');
                        document.getElementById('paymentSuccessMessage').textContent = result.message;
                        document.getElementById('paymentSuccessModal').classList.remove('hidden');
                    }, 2000);
                } else {
                    messageDiv.textContent = 'Payment failed: ' + result.message;
                    messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
                    messageDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Payment error:', error);
                messageDiv.textContent = 'Payment failed. Please try again.';
                messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
                messageDiv.classList.remove('hidden');
            }
        });

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('profileSubmitBtn');
            const messageDiv = document.getElementById('profileMessage');
            const initialBtnText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'update_collector_profile');

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageDiv.textContent = result.message;
                    messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-green-100 text-green-700';
                    messageDiv.classList.remove('hidden');
                } else {
                    messageDiv.textContent = result.message;
                    messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
                    messageDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Profile update error:', error);
                messageDiv.textContent = 'Profile update failed. Please try again.';
                messageDiv.className = 'p-3 text-center rounded-lg text-sm font-medium bg-red-100 text-red-700';
                messageDiv.classList.remove('hidden');
            } finally {
                submitBtn.innerHTML = initialBtnText;
                submitBtn.disabled = false;
            }
        });
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.add('hidden');
                stopQRScanner();
            }
        });
    </script>
</body>
</html>