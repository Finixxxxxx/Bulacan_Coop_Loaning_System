<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bulacan_coop');

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

function generate_member_id($branch, $mysqli) {
    $branch_codes = [
        'malolos' => 'MAL',
        'hagonoy' => 'HAG', 
        'calumpit' => 'CAL',
        'balagtas' => 'BAL',
        'marilao' => 'MRO',
        'staMaria' => 'MRA',
        'plaridel' => 'PLA'
    ];
    
    $branch_code = $branch_codes[$branch] ?? 'GEN';
    
    // Find the highest number for this branch
    $sql = "SELECT member_id FROM clients WHERE member_id LIKE ? AND c_status = 'Active' ORDER BY client_id DESC LIMIT 1";
    $like_pattern = $branch_code . '%';
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $like_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $max_number = 0;
        while ($row = $result->fetch_assoc()) {
            $member_id = $row['member_id'];
            // Extract number from member_id (handle formats like MAL001, MAL001-1, etc.)
            if (preg_match('/^' . $branch_code . '(\d+)/', $member_id, $matches)) {
                $current_num = (int)$matches[1];
                if ($current_num > $max_number) {
                    $max_number = $current_num;
                }
            }
        }
        $stmt->close();
        
        $next_number = $max_number + 1;
        return $branch_code . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }
    
    return $branch_code . '001';
}

function generate_client_password($lastname, $member_id, $phone) {
    $clean_lastname = strtolower(str_replace(' ', '', $lastname));
    
    // Extract 3 numbers from member ID (after branch code)
    $member_numbers = preg_replace('/[^0-9]/', '', $member_id);
    $three_from_member = substr($member_numbers, 0, 3);
    
    // Last 4 digits of phone
    $clean_phone = preg_replace('/\D/', '', $phone);
    $last_four_phone = substr($clean_phone, -4);
    
    return $clean_lastname . $three_from_member . $last_four_phone;
}

function reactivate_client_member_id($client_id, $mysqli) {
    // Get client data
    $sql = "SELECT member_id, loan_count FROM clients WHERE client_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($client = $result->fetch_assoc()) {
            $current_member_id = $client['member_id'];
            $loan_count = $client['loan_count'] + 1;
            
            // Remove -D suffix if present and add loan count
            if (str_ends_with($current_member_id, '-D')) {
                $base_id = substr($current_member_id, 0, -2);
                $new_member_id = $base_id . '-' . $loan_count;
            } else {
                $new_member_id = $current_member_id . '-' . $loan_count;
            }
            
            // Update client
            $update_sql = "UPDATE clients SET member_id = ?, loan_count = ?, c_status = 'Active' WHERE client_id = ?";
            if ($update_stmt = $mysqli->prepare($update_sql)) {
                $update_stmt->bind_param("sii", $new_member_id, $loan_count, $client_id);
                $update_stmt->execute();
                $update_stmt->close();
                return $new_member_id;
            }
        }
        $stmt->close();
    }
    return false;
}

function deactivate_client_account($client_id, $mysqli) {
    // Check if client has active loans
    $sql_check = "SELECT loan_id FROM loans WHERE client_id = ? AND loan_status IN ('Active', 'Pending', 'Overdue') LIMIT 1";
    if ($stmt = $mysqli->prepare($sql_check)) {
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false; // Cannot deactivate - has active loans
        }
        $stmt->close();
    }
    
    // Add -D suffix to member_id
    $sql_update = "UPDATE clients SET member_id = CONCAT(member_id, '-D'), c_status = 'Deactivated' WHERE client_id = ?";
    if ($stmt = $mysqli->prepare($sql_update)) {
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
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