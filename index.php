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
        $username_err = "Please enter your Member ID or Phone Number.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        
        $sql = "SELECT client_id, member_id, c_firstname, c_lastname, c_phone, c_password_hash FROM clients WHERE member_id = ? OR c_phone = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("ss", $param_username, $param_username);
            
            $param_username = $username;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $stmt->bind_result($client_id, $member_id, $firstname, $lastname, $phone, $hashed_password);
                    if($stmt->fetch()){
                        
                        $expected_password_string = generate_client_password($lastname, $phone);
                        if(password_verify($password, $hashed_password)){
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["client_id"] = $client_id;
                            $_SESSION["member_id"] = $member_id;
                            $_SESSION["client_name"] = $firstname . " " . $lastname;
                            $_SESSION["role"] = "client";
                            header("location: client_portal.php");
                        } else{
                            echo "<script>console.log(\"Input: $password\", \"Database Stored: $hashed_password\")</script>";
                            $login_err = "Invalid client username or password.";
                        }
                    }
                } else{
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
                                        echo "<script>console.log(\"Input: $password\", \"Database Stored: $hashed_password\")</script>";
                                        $login_err = "Invalid admin username or password.";
                                    }
                                }
                            } else {
                                $login_err = "Invalid admin username or password.";
                            }
                        }
                        $stmt_admin->close();
                    }
                    if(!isset($_SESSION["loggedin"])){
                        $login_err = "Invalid client or admin username or password.";
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

    <style>        
    body {
            font-family: 'Inter', sans-serif;
        }
        @keyframes fadeInMove {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInMove {
            animation: fadeInMove 0.8s ease-out;
        }
    
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

    <div class="w-full max-w-md">
        <div class="bg-white shadow-xl rounded-2xl p-8 space-y-6 animate-fadeInMove">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Bulacan Coop</h1>
                <p class="text-lg text-gray-500">Loaning Management System</p>
            </div>
            
            <?php 
            if(!empty($login_err)){
                echo '<div id="errorMsg" class="p-3 bg-red-100 text-red-700 rounded-lg text-sm transition-all duration-300">
                        <i class="fas fa-exclamation-circle mr-2"></i>' . $login_err . '
                    </div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Member ID / Phone Number</label>
                    <input type="text" id="username" name="username" placeholder="Enter ID or Phone" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl input-focus-ring transition duration-150"
                        value="<?php echo $username ?? ''; ?>">
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $username_err; ?></span>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Enter Password" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl input-focus-ring transition duration-150">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Hint: Last name (no spaces) + last 4 digits of phone number.</p>
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $password_err; ?></span>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white font-semibold py-3 rounded-xl hover:bg-primary-dark transition-colors duration-200 shadow-lg shadow-blue-500/50">
                    <i class="fas fa-sign-in-alt mr-2"></i> Log In
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">Forgot Password?</a>
            </div>
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
