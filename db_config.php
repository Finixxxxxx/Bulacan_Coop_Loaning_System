<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bulacan_coop');

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($mysqli->connect_error){
    die("ERROR: Could not connect to database. " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
function generate_member_id($branch, $mysqli) {
    $prefix = strtoupper(substr($branch, 0, 3));
    $sql = "SELECT member_id FROM clients WHERE member_id LIKE '{$prefix}%' ORDER BY client_id DESC LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $last_member = $result->fetch_assoc();
        $last_number = intval(substr($last_member['member_id'], 3));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
}

function generate_client_password($lastname, $member_id, $phone) {
    $phone_part = substr($phone, -4);
    $name_part = strtoupper(substr($lastname, 0, 3));
    $id_part = substr($member_id, -3);
    
    return $phone_part . $name_part . $id_part;
}

function calculate_loan_totals($amount) {
    $processing_fee = 200.00;
    $net_amount = $amount - $processing_fee;
    $interest_rate = 0.15;
    $term_days = 100;
    
    $interest = $amount * $interest_rate;
    $total_balance = $amount + $interest;
    $daily_payment = $total_balance / $term_days;
    
    return [
        'processing_fee' => $processing_fee,
        'net_amount' => $net_amount,
        'interest' => $interest,
        'total_balance' => $total_balance,
        'daily_payment' => $daily_payment
    ];
}

function deactivate_client_account($client_id, $mysqli) {
    $sql_check = "SELECT loan_id FROM loans WHERE client_id = ? AND loan_status IN ('Active', 'Pending', 'Overdue') LIMIT 1";
    if ($stmt = $mysqli->prepare($sql_check)) {
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false; 
        }
        $stmt->close();
    }
    $sql_update = "UPDATE clients SET member_id = CONCAT(member_id, '-D'), c_status = 'Deactivated' WHERE client_id = ?";
    if ($stmt = $mysqli->prepare($sql_update)) {
        $stmt->bind_param("i", $client_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function reactivate_client_member_id($client_id, $mysqli) {
    $sql_check = "SELECT member_id FROM clients WHERE client_id = ?";
    if ($stmt = $mysqli->prepare($sql_check)) {
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $stmt->bind_result($member_id);
        if ($stmt->fetch()) {
            $stmt->close();
            
            if (str_ends_with($member_id, '-D')) {
                $new_member_id = str_replace('-D', '', $member_id);
                
                $sql_update = "UPDATE clients SET member_id = ?, c_status = 'Active' WHERE client_id = ?";
                if ($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("si", $new_member_id, $client_id);
                    if ($stmt_update->execute()) {
                        $stmt_update->close();
                        return $new_member_id;
                    }
                    $stmt_update->close();
                }
            }
            return $member_id;
        }
        $stmt->close();
    }
    return false;
}
?>