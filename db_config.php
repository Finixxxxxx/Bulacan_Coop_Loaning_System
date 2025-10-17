<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bulacan_coop');

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}
function generate_client_password($lastname, $phone) {
    $clean_lastname = str_replace(' ', '', $lastname);
    $clean_lastname = strtolower($clean_lastname);
    
    $clean_phone = preg_replace('/\D/', '', $phone);
    $last_four = substr($clean_phone, -4);
    
    return $clean_lastname . $last_four;
}
function calculate_monthly_payment($principal, $annualRate, $termMonths) {
    if ($termMonths <= 0) return $principal;
    
    $monthlyRate = ($annualRate / 100) / 12;
    
    if ($monthlyRate == 0) {
        return $principal / $termMonths;
    }
    
    $payment = $principal * ($monthlyRate / (1 - pow(1 + $monthlyRate, -$termMonths)));
    return round($payment, 2);
}

?>
