<?php
session_start();
require_once 'db_config.php';
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === "client"){
    header("location: client_portal.php");
    exit;
}
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === "admin"){
    header("location: admin_dashboard.php");
    exit;
}
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter your Email Address.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        
        // Check clients first (using email)
        $sql = "SELECT client_id, member_id, c_firstname, c_lastname, c_email, c_phone, c_password_hash, c_status FROM clients WHERE c_email = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_username);
            
            $param_username = $username;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $stmt->bind_result($client_id, $member_id, $firstname, $lastname, $email, $phone, $hashed_password, $status);
                    if($stmt->fetch()){
                        // Check if account is deactivated
                        if (str_ends_with($member_id, '-D') || $status === 'Deactivated') {
                            $login_err = "Account is deactivated. Please contact administrator.";
                        } else if(password_verify($password, $hashed_password)){
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["client_id"] = $client_id;
                            $_SESSION["member_id"] = $member_id;
                            $_SESSION["client_name"] = $firstname . " " . $lastname;
                            $_SESSION["role"] = "client";
                            header("location: client_portal.php");
                        } else{
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    // Check admins
                    $sql_admin = "SELECT admin_id, a_username, a_fullname, a_password_hash FROM admins WHERE a_username = ?";
                    if($stmt_admin = $mysqli->prepare($sql_admin)){
                        $stmt_admin->bind_param("s", $param_admin_username);
                        $param_admin_username = $username;
                        if($stmt_admin->execute()){
                            $stmt_admin->store_result();
                            if($stmt_admin->num_rows == 1){
                                $stmt_admin->bind_result($admin_id, $admin_username, $admin_fullname, $admin_hashed_password);
                                if($stmt_admin->fetch()){
                                    if(password_verify($password, $admin_hashed_password)){
                                        session_regenerate_id();
                                        $_SESSION["loggedin"] = true;
                                        $_SESSION["admin_id"] = $admin_id;
                                        $_SESSION["admin_name"] = $admin_fullname;
                                        $_SESSION["role"] = "admin";
                                        header("location: admin_dashboard.php");
                                        exit;
                                    } else {
                                        $login_err = "Invalid username or password.";
                                    }
                                }
                            } else {
                                $login_err = "Invalid email or password.";
                            }
                        }
                        $stmt_admin->close();
                    }
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulacan Coop - Login</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>        
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('./assets/bulacan_capitol.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(3px) brightness(1);
            transform: scale(1.1);
            z-index: -1;
        }
        @keyframes fadeInMove {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInMove { animation: fadeInMove 0.8s ease-out; }
        .input-focus-ring:focus {
            --tw-ring-color: #3b82f6;
            box-shadow: 0 0 0 2px var(--tw-ring-color);
            border-color: #3b82f6;
        }
        .bg-primary { background-color: #0369A1; }
        .hover\:bg-primary-dark:hover { background-color: #075985; }
        </style>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm bg-white shadow-2xl rounded-xl p-6 md:p-8 space-y-6 transform transition duration-500 hover:shadow-3xl border-t-4 border-primary">
            
            <div class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 mb-1 tracking-tight">Bulacan Coop</h1>
                <p class="text-lg text-gray-500 font-medium">Loaning Management System</p>
            </div>
            
            <?php 
            if(!empty($login_err)){
                echo '<div id="errorMsg" class="p-3 bg-red-50 text-red-700 border border-red-300 rounded-lg text-sm transition-all duration-300 flex items-center" role="alert">
                        <i class="fas fa-exclamation-circle mr-2 text-lg"></i>
                        <span class="font-medium">' . $login_err . '</span>
                    </div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm" class="space-y-6">
                
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="username" name="username" placeholder="Enter your email" required 
                            class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition duration-200 shadow-inner text-gray-800"
                            value="<?php echo $username ?? ''; ?>">
                    </div>
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $username_err; ?></span>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Enter Password" required 
                            class="w-full pl-10 pr-10 py-3 border-2 border-gray-200 rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition duration-200 shadow-inner text-gray-800">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary transition duration-150" aria-label="Toggle password visibility">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>Hint: Last name (no spaces) + 3 numbers from Member ID + last 4 digits of phone number.
                    </p>
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $password_err; ?></span>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white font-bold tracking-wider py-3 rounded-lg hover:bg-primary-dark transition-colors duration-300 shadow-xl shadow-primary/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transform hover:scale-[1.01]">
                    <i class="fas fa-sign-in-alt mr-2"></i> LOG IN
                </button>
            </form>

            <div class="text-center pt-2 border-t border-gray-100 mt-4">
                <a href="#" class="text-sm text-primary hover:text-primary-dark font-medium transition-colors">
                    <i class="fas fa-question-circle mr-1"></i> Forgot Password?
                </a>
            </div>
        </div>

        <script>
            const passwordInput = document.getElementById("password");
            const toggleBtn = document.getElementById("togglePassword");
            const toggleIcon = document.getElementById("toggleIcon");

            toggleBtn.addEventListener("click", () => {
                const type = passwordInput.type === "password" ? "text" : "password";
                passwordInput.type = type;

                if (type === "password") {
                    toggleIcon.classList.remove("fa-eye-slash");
                    toggleIcon.classList.add("fa-eye");
                } else {
                    toggleIcon.classList.remove("fa-eye");
                    toggleIcon.classList.add("fa-eye-slash");
                }
            });
        </script>
    </body>
</html>