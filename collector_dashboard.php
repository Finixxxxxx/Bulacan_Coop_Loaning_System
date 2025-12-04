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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <link rel="stylesheet" href="./css/collector.css">
    <link rel="stylesheet" href="./css/global.css">
    <script type="module" src="./js/collector.js"></script>
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden"></div>
        <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
            <div class="p-4 border-b">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-primary/30">
                        <i class="fas fa-handshake text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Bulacan Coop</p>
                        <p class="text-gray-500 text-xs">Loaning Management System</p>
                    </div>
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
                <div class="flex items-center justify-between">
                    <div class="flex">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($collector_name); ?></p>
                            <p class="text-xs text-gray-500">Collector • <?php echo htmlspecialchars($collector_branch); ?></p>
                        </div>
                    </div>
                    <button type="button" id="logoutBtn" class="text-red-600 rounded-lg text-lg hover:text-red-700 transition-colors font-semibold">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        
        <div class="flex-1 p-6 md:ml-64">
            <header class="md:flex items-center justify-between mb-8 space-x-2">
                
                <div class="md:block flex space-x-4 mb-2 md:mb-0">
                    <button id="mobileSidebarBtn" class="text-gray-700 focus:outline-none md:hidden">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-900" id="pageTitle">Collector Dashboard</h1>
                    <p class="text-gray-600 md:flex hidden" id="pageSubtitle">Manage daily payments and collections</p>
                </div>
                <div class="flex space-x-4">
                    <button id="scanQRBtn" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors flex items-center">
                        <i class="fas fa-qrcode mr-2"></i> Scan QR Code
                    </button>
                    <button id="manualEntryBtn" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors flex items-center">
                        <i class="fas fa-keyboard mr-2"></i> Manual Entry
                    </button>
                </div>
            </header>

            <div id="dashboard" class="tab-content fade-in">
                
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

            
            <div id="profile" class="tab-content hidden fade-in">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    
                    <form id="profileForm" class="space-y-6">
                        <div class="flex justify-between space-x-3">
                            <div class="p-4 rounded-lg w-full">
                                <h2 class="text-2xl font-bold text-gray-900 pb-2 mb-4 border-b border-gray-200"><i class="fas fa-circle-user text-green-600 mr-2"></i>Account Information</h2>
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
                            </div>
                            
                            <div class="p-4 rounded-lg bg-white w-full">
                                <h3 class="text-2xl font-semibold text-gray-900 pb-2 mb-4 border-b border-gray-200"><i class="fas fa-key text-sky-600 mr-2"></i>Change Password</h3>
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
                        </div>
                        <div id="profileMessage" class="hidden p-3 text-center rounded-lg text-sm font-medium"></div>

                        <div class="flex justify-end space-x-3 pt-4 border-t">
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

    <div id="logoutConfirmationModal" class="hidden bg-black/50 fixed inset-0 modal-overlay overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-8 border w-96 shadow-2xl rounded-2xl bg-white transform modal-content fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to log out of the admin dashboard?</p>
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
        let qrScannerActive = false;
        let videoStream = null;
        let animationFrameId = null;

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
            const modal = document.getElementById("logoutConfirmationModal")
            modal.classList.remove('hidden')
        });
        
        document.getElementById('cancelLogoutBtn').addEventListener('click', function(){
            const modal = document.getElementById("logoutConfirmationModal")
            modal.classList.add('hidden')
        })
        
        document.getElementById('confirmLogoutBtn').addEventListener('click', function(){
            window.location.href = 'logout.php';
        })

        async function initializeQRScanner() {
            const qrReader = document.getElementById('qrReader');
            
            qrReader.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Initializing camera...</p>
                </div>
            `;
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                videoStream = stream;
                qrScannerActive = true;
                
                const video = document.createElement('video');
                video.setAttribute('autoplay', '');
                video.setAttribute('playsinline', '');
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
                
                const canvas = document.createElement('canvas');
                const canvasContext = canvas.getContext('2d');
                
                qrReader.innerHTML = '';
                qrReader.appendChild(video);
                
                video.srcObject = stream;
                
                video.onloadedmetadata = () => {
                    video.play();
                    
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    scanQRCode(video, canvas, canvasContext);
                };
                
                video.onerror = (error) => {
                    console.error('Video error:', error);
                    showCameraError('Failed to start video stream');
                };
                
            } catch (error) {
                console.error('Camera access error:', error);
                showCameraError(getCameraErrorMessage(error));
            }
        }

        function scanQRCode(video, canvas, canvasContext) {
            if (!qrScannerActive) return;
            
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvasContext.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
                
                try {
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert",
                    });
                    
                    if (code) {
                        drawQRCodeBounds(canvasContext, code.location);
                        
                        processScannedQRCode(code.data);
                        retur
                    }
                } catch (error) {
                    console.error('QR decoding error:', error);
                }
            }
            
            animationFrameId = requestAnimationFrame(() => scanQRCode(video, canvas, canvasContext));
        }

        function drawQRCodeBounds(context, location) {
            context.beginPath();
            context.lineWidth = 4;
            context.strokeStyle = "#10B981";
            context.moveTo(location.topLeftCorner.x, location.topLeftCorner.y);
            context.lineTo(location.topRightCorner.x, location.topRightCorner.y);
            context.lineTo(location.bottomRightCorner.x, location.bottomRightCorner.y);
            context.lineTo(location.bottomLeftCorner.x, location.bottomLeftCorner.y);
            context.closePath();
            context.stroke();
        }

        function processScannedQRCode(decodedText) {
            try {
                const qrData = JSON.parse(decodedText);
                
                if (!qrData.clientId || !qrData.loanId || !qrData.paymentAmount) {
                    throw new Error('Invalid QR code format');
                }
                
                stopQRScanner();
                
                document.getElementById('qrScannerModal').classList.add('hidden');
                
                processQRPayment(qrData.clientId, qrData.loanId, qrData.paymentAmount);
                
            } catch (error) {
                console.error('QR data error:', error);
                alert('Invalid QR code. Please make sure you are scanning a valid payment QR code.');
                qrScannerActive = true;
            }
        }

        function stopQRScanner() {
            qrScannerActive = false;
            
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
            
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
        }

        function getCameraErrorMessage(error) {
            switch (error.name) {
                case 'NotAllowedError':
                    return 'Camera access was denied. Please allow camera access in your browser settings.';
                case 'NotFoundError':
                    return 'No camera found. Please connect a camera and try again.';
                case 'NotSupportedError':
                    return 'Camera not supported. Please try a different browser or device.';
                case 'NotReadableError':
                    return 'Camera is already in use by another application.';
                default:
                    return 'Failed to access camera. Please check permissions.';
            }
        }

        function showCameraError(message) {
            const qrReader = document.getElementById('qrReader');
            qrReader.innerHTML = `
                <div class="text-center p-6">
                    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-video-slash text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Camera Error</h3>
                    <p class="text-gray-600 mb-4">${message}</p>
                    <div class="space-y-3">
                        <button id="retryCameraBtn" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                            <i class="fas fa-redo mr-2"></i> Try Again
                        </button>
                        <button id="manualEntryFallbackBtn" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-keyboard mr-2"></i> Use Manual Entry Instead
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('retryCameraBtn').addEventListener('click', function() {
                initializeQRScanner();
            });
            
            document.getElementById('manualEntryFallbackBtn').addEventListener('click', function() {
                document.getElementById('qrScannerModal').classList.add('hidden');
                document.getElementById('manualPaymentModal').classList.remove('hidden');
            });
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

                const response = await fetch('/api.php', {
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
                const response = await fetch('/api.php', {
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

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const mobileBtn = document.getElementById('mobileSidebarBtn');

        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
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