<?php
// PHP file for database configuration (db_config.php)

// ** IMPORTANT: Update these credentials with your actual database details **
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bulacan_coop');

/* Attempt to connect to MySQL database */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Function to generate the client's expected password string
function generate_client_password($lastname, $phone) {
    // 1. Clean the lastname (remove spaces and convert to lowercase)
    $clean_lastname = str_replace(' ', '', $lastname);
    $clean_lastname = strtolower($clean_lastname);
    
    // 2. Get the last 4 digits of the phone number
    // Clean phone number from non-digit characters for reliable substring
    $clean_phone = preg_replace('/\D/', '', $phone);
    $last_four = substr($clean_phone, -4);
    
    // 3. Combine
    return $clean_lastname . $last_four;
}

// Function to calculate monthly payment (simple interest amortization)
function calculate_monthly_payment($principal, $annualRate, $termMonths) {
    if ($termMonths <= 0) return $principal;
    
    $monthlyRate = ($annualRate / 100) / 12;
    
    if ($monthlyRate == 0) {
        // If interest is 0, just divide principal by term
        return $principal / $termMonths;
    }
    
    // Annuity formula for amortization
    $payment = $principal * ($monthlyRate / (1 - pow(1 + $monthlyRate, -$termMonths)));
    return round($payment, 2);
}

?>
