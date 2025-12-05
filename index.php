<?php
session_start();
require_once 'db_config.php';

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    switch($_SESSION["role"]){
        case "client":
            header("location: client_portal.php");
            exit;
        case "admin":
            header("location: admin_dashboard.php");
            exit;
        case "collector":
            header("location: collector_dashboard.php");
            exit;
    }
}

$username_err = $password_err = $login_err = "";
$username = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter your username/email.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        $sql = "SELECT client_id, member_id, c_firstname, c_lastname, c_email, c_phone, c_password_hash, c_status FROM clients WHERE member_id = ? OR c_email = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("ss", $param_username1, $param_username2);
            $param_username1 = $username;
            $param_username2 = $username;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $stmt->bind_result($client_id, $member_id, $firstname, $lastname, $email, $phone, $hashed_password, $status);
                    if($stmt->fetch()){
                        if (str_ends_with($member_id, '-D') || $status === 'Deactivated') {
                            $login_err = "Your account is deactivated. Please contact administrator.";
                        } else if(password_verify($password, $hashed_password)){
                            session_regenerate_id(true);
                            $_SESSION["loggedin"] = true;
                            $_SESSION["client_id"] = $client_id;
                            $_SESSION["member_id"] = $member_id;
                            $_SESSION["client_name"] = $firstname . " " . $lastname;
                            $_SESSION["role"] = "client";
                            header("location: client_portal.php");
                            exit;
                        } else{
                            $login_err = "Invalid username or password.";
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
                                        session_regenerate_id(true);
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
                                $sql_collector = "SELECT collector_id, col_username, col_fullname, col_branch, col_password_hash FROM collectors WHERE col_username = ? AND col_status = 'Active'";
                                if($stmt_collector = $mysqli->prepare($sql_collector)){
                                    $stmt_collector->bind_param("s", $param_collector_username);
                                    $param_collector_username = $username;
                                    if($stmt_collector->execute()){
                                        $stmt_collector->store_result();
                                        if($stmt_collector->num_rows == 1){
                                            $stmt_collector->bind_result($collector_id, $col_username, $col_fullname, $col_branch, $col_hashed_password);
                                            if($stmt_collector->fetch()){
                                                if(password_verify($password, $col_hashed_password)){
                                                    session_regenerate_id(true);
                                                    $_SESSION["loggedin"] = true;
                                                    $_SESSION["collector_id"] = $collector_id;
                                                    $_SESSION["collector_name"] = $col_fullname;
                                                    $_SESSION["collector_branch"] = $col_branch;
                                                    $_SESSION["role"] = "collector";
                                                    header("location: collector_dashboard.php");
                                                    exit;
                                                } else {
                                                    $login_err = "Invalid username or password.";
                                                }
                                            }
                                        } else {
                                            $login_err = "Invalid username or password.";
                                        }
                                    }
                                    $stmt_collector->close();
                                }
                            }
                        }
                        $stmt_admin->close();
                    }
                }
            } else{
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulacan Coop - Secure Login</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>        
        :root {
            --primary-color: #0369A1; 
            --accent-color: #0F667F; 
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F4F6;
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
            filter: blur(4px) brightness(1.2) contrast(0.9);
            transform: scale(1.1);
            z-index: -1;
        }
        @keyframes fadeInMove {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInMove { animation: fadeInMove 0.8s ease-out; }
        .bg-card { background-color: white; }
        .border-primary { border-color: var(--primary-color); }
        .text-primary { color: var(--primary-color); }
        .bg-primary { background-color: var(--primary-color); }
        .hover\:bg-primary-dark:hover { background-color: var(--accent-color); }
        .input-style {
            background-color: #F9FAFB;
            color: #1F2937;
            border: 1px solid #E5E7EB;
        }
        .input-style:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        </style>
    </head>
    <body class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-card shadow-2xl rounded-xl p-8 space-y-7 border-t-4 border-primary animate-fadeInMove">
            
            <div class="text-center">
                <i class="fas fa-handshake text-5xl text-primary mb-3"></i>
                <h1 class="text-3xl font-extrabold text-gray-900 mb-1 tracking-wider">BULACAN COOPERATIVE</h1>
                <p class="text-md text-gray-500 font-medium">Loaning Management System Access</p>
            </div>
            
            <?php 
            if(!empty($login_err)){
                echo '<div class="p-4 bg-red-100 text-red-700 border border-red-300 rounded-lg text-sm flex items-center transition-all duration-300" role="alert">
                        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                        <span class="font-medium">' . $login_err . '</span>
                    </div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm" class="space-y-6">
                
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">USERNAME / EMAIL</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" placeholder="Enter your credentials" required 
                            class="w-full pl-12 pr-4 py-3.5 rounded-lg input-style focus:ring-1 transition duration-200"
                            value="<?php echo $username ?? ''; ?>">
                    </div>
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $username_err; ?></span>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">PASSWORD</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Enter secure password" required 
                            class="w-full pl-12 pr-12 py-3.5 rounded-lg input-style focus:ring-1 transition duration-200">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary transition duration-150" aria-label="Toggle password visibility">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="text-xs text-red-500 mt-1 block"><?php echo $password_err; ?></span>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white font-bold tracking-wider py-3.5 rounded-lg hover:bg-primary-dark transition-colors duration-300 shadow-xl shadow-primary/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transform hover:scale-[1.005]">
                    <i class="fas fa-sign-in-alt mr-2"></i> LOG IN
                </button>
            </form>
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