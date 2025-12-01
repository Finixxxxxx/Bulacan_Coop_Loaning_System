<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "client"){
    header("location: index.php");
    exit;
}

$client_name = $_SESSION["client_name"];
$member_id = $_SESSION["member_id"];
$client_id = $_SESSION["client_id"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulacan Coop - Client Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
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

    <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">

            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-primary card-hover fade-in" id="loanStatusSection">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-money-check-alt text-primary mr-3"></i> Current Loan Status
                </h2>
                
                <div id="loanDetailsContainer" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-gray-600">
                        <div>
                            <p class="text-sm font-medium">Current Balance</p>
                            <p id="currentBalance" class="text-3xl font-bold text-gray-900">₱0.00</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Daily Due</p>
                            <p id="dailyPayment" class="text-2xl font-bold text-red-600">₱0.00</p>
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
                    <div id="loanInfo" class="bg-blue-50 p-4 rounded-lg border border-blue-200 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Loan Information</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm text-blue-700">
                            <div>
                                <span>Term:</span>
                                <span id="loanTerm" class="font-semibold">100 Days</span>
                            </div>
                            <div>
                                <span>Interest Rate:</span>
                                <span id="interestRate" class="font-semibold">15%</span>
                            </div>
                            <div>
                                <span>Days Paid:</span>
                                <span id="daysPaid" class="font-semibold">0/100</span>
                            </div>
                            <div>
                                <span>Total Loan Balance:</span>
                                <span id="totalAmount" class="font-semibold">₱0.00</span>
                            </div>
                            <div>
                                <span>Processing Fee:</span>
                                <span id="processingFee" class="font-semibold">₱0.00</span>
                            </div>
                            <div>
                                <span>Total Received:</span>
                                <span id="netAmount" class="font-semibold">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    <div id="noActiveLoanMessage" class="hidden text-center p-8 bg-blue-50 rounded-lg">
                        <i class="fas fa-info-circle text-blue-500 text-2xl mb-2"></i>
                        <p class="text-lg font-medium text-blue-800">No Active Loan Found.</p>
                        <p class="text-sm text-blue-700">Contact administrator for loan applications.</p>
                    </div>
                </div>
            </section>

            <section class="bg-white p-6 rounded-2xl shadow-lg card-hover fade-in">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history text-primary mr-3"></i> Payment History
                </h2>
                <div id="paymentHistoryContainer">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                </tr>
                            </thead>
                            <tbody id="paymentHistoryBody" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No payment history found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="lg:col-span-1 space-y-6">
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
                    <button onclick="setQuickAmount(document.getElementById('dailyPayment').textContent.replace(/[^0-9.]/g, ''))" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition">Daily Due</button>
                    <button onclick="setQuickAmount(document.getElementById('currentBalance').textContent.replace(/[^0-9.]/g, ''))" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition">Full Balance</button>
                </div>
                
                <button id="generatePaymentQR" 
                        class="w-full bg-green-600 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition-colors duration-200 shadow-lg shadow-green-500/50">
                    <i class="fas fa-qrcode mr-2"></i> Generate QR Code
                </button>
            </section>

            <section class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-purple-500 card-hover fade-in">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle text-purple-500 mr-3"></i> Account Information
                </h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Member ID:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($member_id); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($client_name); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span id="accountStatus" class="status-badge bg-green-100 text-green-800">Active</span>
                    </div>
                </div>
            </section>
        </div>
    </main>

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

    <div id="messageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center fade-in">
        <div class="relative p-6 border w-96 shadow-2xl rounded-xl bg-white transform scale-100 transition-transform">
            <div class="text-center">
                <div id="modalIcon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4">
                </div>
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                <p id="modalMessage" class="text-gray-600 mb-6"></p>
                <button id="closeMessageModal" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center fade-in">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to log out of the client portal?</p>
                <div class="flex space-x-3">
                    <button id="cancelLogoutBtn" class="w-1/2 bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Cancel
                    </button>
                    <button id="confirmLogoutBtn" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg hover:bg-red-700 transition-colors font-semibold">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CLIENT_ID = <?php echo $client_id; ?>;

        function setQuickAmount(amount) {
            document.getElementById('paymentAmount').value = parseFloat(amount).toFixed(2);
        }

        function generateQRCode() {
            const paymentAmount = document.getElementById('paymentAmount').value;
            
            if (!paymentAmount || paymentAmount < 100) {
                showMessage('Invalid Amount', 'Please enter a valid payment amount (minimum ₱100).', 'error');
                return;
            }

            if (!window.CURRENT_LOAN_ID) {
                showMessage('No Active Loan', 'You do not have an active loan to make payments for.', 'error');
                return;
            }
            const qrData = {
                clientId: CLIENT_ID,
                loanId: window.CURRENT_LOAN_ID,
                paymentAmount: parseFloat(paymentAmount),
                timestamp: new Date().toISOString()
            };
            const qrString = JSON.stringify(qrData);
            const canvas = document.getElementById('qrCanvas');
            QRCode.toCanvas(canvas, qrString, {
                width: 200,
                margin: 1,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function(error) {
                if (error) {
                    console.error('QR Code generation error:', error);
                    showMessage('QR Error', 'Failed to generate QR code. Please try again.', 'error');
                    return;
                }
                document.getElementById('qrAmountDisplay').textContent = `₱${parseFloat(paymentAmount).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                document.getElementById('qrModal').classList.remove('hidden');
            });
        }

        function downloadQRCode() {
            const canvas = document.getElementById('qrCanvas');
            const link = document.createElement('a');
            link.download = `payment_qr_${new Date().getTime()}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        function shareQRCode() {
            if (navigator.share) {
                const canvas = document.getElementById('qrCanvas');
                canvas.toBlob(function(blob) {
                    const file = new File([blob], 'payment_qr.png', { type: 'image/png' });
                    navigator.share({
                        files: [file],
                        title: 'Payment QR Code',
                        text: `Payment QR Code for ₱${document.getElementById('paymentAmount').value}`
                    });
                });
            } else {
                showMessage('Sharing Not Supported', 'Your browser does not support sharing files.', 'info');
            }
        }

        function showMessage(title, message, type = 'info') {
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

        document.getElementById('generatePaymentQR').addEventListener('click', generateQRCode);
        document.getElementById('downloadQR').addEventListener('click', downloadQRCode);
        document.getElementById('shareQR').addEventListener('click', shareQRCode);
        document.getElementById('closeQRModal').addEventListener('click', function() {
            document.getElementById('qrModal').classList.add('hidden');
        });
        document.getElementById('closeMessageModal').addEventListener('click', function() {
            document.getElementById('messageModal').classList.add('hidden');
        });

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
            document.getElementById('logoutConfirmationModal').classList.remove('hidden');
        });

        document.getElementById('cancelLogoutBtn').addEventListener('click', function() {
            document.getElementById('logoutConfirmationModal').classList.add('hidden');
        });

        document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
    </script>
</body>
</html>