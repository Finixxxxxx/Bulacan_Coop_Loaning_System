<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

function json_error($message) {
    global $mysqli;
    error_log("API Error: " . $message);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);

    if (isset($mysqli) && $mysqli) $mysqli->close();
    exit;
}

function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2, '.', ',');
}

$action = $_REQUEST['action'] ?? '';
if (empty($action)) {
    json_error('No action specified.');
}

$role = $_SESSION['role'] ?? '';
$user_id = $role === 'client' ? ($_SESSION['client_id'] ?? null) : ($_SESSION['admin_id'] ?? null);

switch ($action) {
    case 'get_client_data':
        if ($role !== 'client') json_error('Access denied for this action.');
    
        $client_id = $_SESSION['client_id'];
        $response = ['active_loan' => null, 'payment_history' => []];

        $sql_loan = "SELECT * FROM loans WHERE client_id = ? AND loan_status IN ('Active', 'Overdue') ORDER BY application_date DESC LIMIT 1";
        if ($stmt = $mysqli->prepare($sql_loan)) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($loan = $result->fetch_assoc()) {
                $response['active_loan'] = $loan;
            }
            $stmt->close();
        }

        if ($response['active_loan']) {
            $loan_id = $response['active_loan']['loan_id'];
            $sql_payments = "SELECT payment_amount, payment_date, payment_method FROM payments WHERE loan_id = ? ORDER BY payment_date DESC";
            if ($stmt = $mysqli->prepare($sql_payments)) {
                $stmt->bind_param("i", $loan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $payments = [];
                while ($row = $result->fetch_assoc()) {
                    $payments[] = $row;
                }
                $response['payment_history'] = $payments;
                $stmt->close();
            }
        }
        echo json_encode($response);
        break;

    case 'submit_loan':
        if ($role !== 'client') json_error('Access denied for this action.');
        
        $client_id = $_SESSION['client_id'];
        $amount = $_POST['loan_amount'] ?? '';
        $term = $_POST['term_months'] ?? '';
        $purpose = $_POST['loan_purpose'] ?? '';

        if (empty($amount) || empty($term) || empty($purpose) || !is_numeric($amount) || $amount < 1000) {
            json_error('Invalid loan details provided.');
        }

        $sql_check = "SELECT loan_id FROM loans WHERE client_id = ? AND loan_status IN ('Active', 'Pending') LIMIT 1";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('You already have an active or pending loan application.');
            }
            $stmt->close();
        }

        $sql_insert = "INSERT INTO loans (client_id, loan_amount, term_months, current_balance, loan_purpose, loan_status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            $stmt->bind_param("idids", $client_id, $amount, $term, $amount, $purpose);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Loan application submitted for review.']);
            } else {
                json_error('Failed to submit loan application: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            json_error('Database prepare error for loan submission: ' . $mysqli->error);
        }
        break;

    case 'get_admin_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $branch_filter = $_REQUEST['branch'] ?? 'all';
        $loan_status_filter = $_REQUEST['loan_status'] ?? 'all';
        
        $response = [
            'clients' => [], 
            'loans' => [], 
            'pending_loans' => [],
            'payments_today' => 0.00, 
            'payments_yesterday' => 0.00, 
            'payments_rate_from_yesterday' => 0.00,
            'total_outstanding' => 0.00,
            'active_loans_count' => 0,
            'monthly_stats' => [] 
        ];

        $sql_outstanding = "SELECT SUM(current_balance) AS total_outstanding, COUNT(*) AS active_loans_count FROM loans WHERE loan_status IN ('Active', 'Overdue')";
        if ($result = $mysqli->query($sql_outstanding)) {
            $data = $result->fetch_assoc();
            $response['total_outstanding'] = (float) ($data['total_outstanding'] ?? 0);
            $response['active_loans_count'] = (int) ($data['active_loans_count'] ?? 0);
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $sql_payments_today = "SELECT SUM(payment_amount) AS total_today FROM payments WHERE DATE(payment_date) = '{$today}'";
        if ($result = $mysqli->query($sql_payments_today)) {
            $response['payments_today'] = (float) ($result->fetch_assoc()['total_today'] ?? 0);
        }

        $sql_payments_yesterday = "SELECT SUM(payment_amount) AS total_yesterday FROM payments WHERE DATE(payment_date) = '{$yesterday}'";
        if ($result = $mysqli->query($sql_payments_yesterday)) {
            $response['payments_yesterday'] = (float) ($result->fetch_assoc()['total_yesterday'] ?? 0);
        }
        
        $payments_today = $response['payments_today'];
        $payments_yesterday = $response['payments_yesterday'];
        $rate = 0;
        if ($payments_yesterday > 0) {
            $rate = (($payments_today - $payments_yesterday) / $payments_yesterday) * 100;
        } elseif ($payments_today > 0) {
            $rate = 100; 
        }
        $response['payments_rate_from_yesterday'] = round($rate, 2);

        $sql_loan_issued_monthly = "SELECT DATE_FORMAT(approval_date, '%Y-%m') AS month, SUM(loan_amount) AS total_issued FROM loans WHERE approval_date IS NOT NULL AND approval_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC";
        if ($result = $mysqli->query($sql_loan_issued_monthly)) {
            while ($row = $result->fetch_assoc()) {
                $response['monthly_stats'][$row['month']]['issued'] = (float) $row['total_issued'];
            }
        }
        
        $sql_payment_received_monthly = "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(payment_amount) AS total_received FROM payments WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC";
        if ($result = $mysqli->query($sql_payment_received_monthly)) {
            while ($row = $result->fetch_assoc()) {
                $response['monthly_stats'][$row['month']]['received'] = (float) $row['total_received'];
            }
        }

        $sql_clients = "SELECT client_id, member_id, c_firstname, c_lastname, c_email, c_phone, c_address, c_branch, c_status FROM clients WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($branch_filter !== 'all') {
            $sql_clients .= " AND c_branch = ?";
            $types .= "s";
            $params[] = $branch_filter;
        }

        if ($stmt = $mysqli->prepare($sql_clients)) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['clients'][] = $row;
            }
            $stmt->close();
        } else {
            error_log("Client statement prep failed: " . $mysqli->error);
        }

        $sql_loans = "SELECT l.*, c.c_firstname, c.c_lastname, c.c_branch FROM loans l JOIN clients c ON l.client_id = c.client_id WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($branch_filter !== 'all') {
            $sql_loans .= " AND c.c_branch = ?";
            $types .= "s";
            $params[] = $branch_filter;
        }

        if ($loan_status_filter !== 'all') {
            $sql_loans .= " AND l.loan_status = ?";
            $types .= "s";
            $params[] = $loan_status_filter;
        }
        
        $sql_loans .= " ORDER BY l.application_date DESC";

        if ($stmt = $mysqli->prepare($sql_loans)) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['loans'][] = $row;
            }
            $stmt->close();
        } else {
            error_log("Loan statement prep failed: " . $mysqli->error);
        }

        $sql_pending = "SELECT l.*, c.c_firstname, c.c_lastname, c.c_branch FROM loans l JOIN clients c ON l.client_id = c.client_id WHERE l.loan_status = 'Pending'";
        $params = [];
        $types = "";

        if ($branch_filter !== 'all') {
            $sql_pending .= " AND c.c_branch = ?";
            $types .= "s";
            $params[] = $branch_filter;
        }
        
        $sql_pending .= " ORDER BY l.application_date ASC";
        
        if ($stmt = $mysqli->prepare($sql_pending)) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['client_name'] = $row['c_firstname'] . ' ' . $row['c_lastname'];
                $row['client_branch'] = $row['c_branch'];
                unset($row['c_firstname'], $row['c_lastname'], $row['c_branch']);
                $response['pending_loans'][] = $row;
            }
            $stmt->close();
        } else {
            error_log("Pending statement prep failed: " . $mysqli->error);
        }
        
        echo json_encode($response);
        break;

    case 'delete_client':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $client_id = $_POST['client_id'] ?? 0;
        if (!is_numeric($client_id) || $client_id <= 0) json_error('Invalid client ID.');

        $sql_check = "SELECT loan_id FROM loans WHERE client_id = ? AND loan_status IN ('Active', 'Pending', 'Overdue') LIMIT 1";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Cannot delete client. They have an active or pending loan.');
            }
            $stmt->close();
        }
        
        $mysqli->begin_transaction();
        try {
            $sql_payments = "DELETE FROM payments WHERE client_id = ?";
            if ($stmt = $mysqli->prepare($sql_payments)) {
                $stmt->bind_param("i", $client_id);
                $stmt->execute();
                $stmt->close();
            }

            $sql_loans = "DELETE FROM loans WHERE client_id = ?";
            if ($stmt = $mysqli->prepare($sql_loans)) {
                $stmt->bind_param("i", $client_id);
                $stmt->execute();
                $stmt->close();
            }

            $sql_client = "DELETE FROM clients WHERE client_id = ?";
            if ($stmt = $mysqli->prepare($sql_client)) {
                $stmt->bind_param("i", $client_id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows === 0) throw new Exception("Client not found.");
                    $mysqli->commit();
                    echo json_encode(['success' => true, 'message' => 'Client and all associated data deleted successfully.']);
                } else {
                    throw new Exception("Client deletion failed: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("Client statement prep failed: " . $mysqli->error);
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            json_error("Deletion failed: " . $e->getMessage());
        }
        break;
        
    case 'approve_loan':
        if ($role !== 'admin') json_error('Access denied for this action.');

        $loan_id = $_POST['loan_id'] ?? '';
        $rate = $_POST['interest_rate'] ?? '';
        $monthly_payment = $_POST['monthly_payment'] ?? '';

        if (empty($loan_id) || empty($rate) || empty($monthly_payment) || !is_numeric($rate) || !is_numeric($monthly_payment)) {
            json_error('Invalid approval details.');
        }
        $sql_fetch = "SELECT client_id, loan_amount, term_months FROM loans WHERE loan_id = ? AND loan_status = 'Pending'";
        if ($stmt = $mysqli->prepare($sql_fetch)) {
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($loan = $result->fetch_assoc()) {
                $client_id = $loan['client_id'];
                $current_balance = $loan['loan_amount'];
                
                $next_payment_date = date('Y-m-d', strtotime('+30 days'));

                $sql_update = "UPDATE loans SET interest_rate = ?, monthly_payment = ?, loan_status = 'Active', approval_date = NOW(), next_payment_date = ?, current_balance = ? WHERE loan_id = ?";
                if ($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("dssdi", $rate, $monthly_payment, $next_payment_date, $current_balance, $loan_id);
                    if ($stmt_update->execute()) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Loan approved successfully. Monthly payment: ' . $monthly_payment
                        ]);
                    } else {
                        json_error("Database update error: " . $mysqli->error);
                    }
                    $stmt_update->close();
                } else {
                    json_error("Database prepare error: " . $mysqli->error);
                }

            } else {
                json_error('Pending loan not found or already processed.');
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }
        break;
        
    case 'decline_loan':
        if ($role !== 'admin') json_error('Access denied for this action.');
        
        $loan_id = $_POST['loan_id'] ?? '';
        
        if (empty($loan_id)) json_error('Loan ID is required.');

        $sql_update = "UPDATE loans SET loan_status = 'Declined', approval_date = NOW() WHERE loan_id = ? AND loan_status = 'Pending'";
        if ($stmt = $mysqli->prepare($sql_update)) {
            $stmt->bind_param("i", $loan_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Loan application has been declined.']);
            } else {
                json_error("Database update error: " . $mysqli->error);
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }
        break;

    case 'record_payment':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $loan_id = $_POST['loan_id'] ?? 0;
        $payment_amount = $_POST['payment_amount'] ?? 0.0;
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        
        if (!is_numeric($loan_id) || $loan_id <= 0 || !is_numeric($payment_amount) || $payment_amount <= 0) {
            json_error('Invalid loan ID or payment amount.');
        }

        $mysqli->begin_transaction();
        $success = false;

        try {
        
            $sql_fetch = "SELECT client_id, current_balance, monthly_payment, next_payment_date FROM loans WHERE loan_id = ? AND loan_status IN ('Active', 'Overdue')";
            if ($stmt = $mysqli->prepare($sql_fetch)) {
                $stmt->bind_param("i", $loan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($loan = $result->fetch_assoc()) {
                    $client_id = $loan['client_id'];
                    $current_balance = (float)$loan['current_balance'];
                    $next_payment_date = $loan['next_payment_date'];

                    if ($payment_amount > $current_balance) {
                        $payment_amount = $current_balance; 
                    }

                    $final_balance = $current_balance - $payment_amount;
                    $new_status = ($final_balance <= 0.01) ? 'Paid' : 'Active';
                    $final_balance = max(0.00, $final_balance);

                    $new_next_payment_date = $new_status == 'Active' ? date('Y-m-d', strtotime($next_payment_date . ' +1 month')) : null;

                    $sql_update = "UPDATE loans SET current_balance = ?, loan_status = ?, next_payment_date = ? WHERE loan_id = ?";
                    if ($stmt_update = $mysqli->prepare($sql_update)) {
                        $stmt_update->bind_param("dssi", $final_balance, $new_status, $new_next_payment_date, $loan_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        throw new Exception("Update prep failed: " . $mysqli->error);
                    }

                    $sql_insert = "INSERT INTO payments (loan_id, client_id, payment_amount, payment_method) VALUES (?, ?, ?, ?)";
                    if ($stmt_insert = $mysqli->prepare($sql_insert)) {
                        $stmt_insert->bind_param("iids", $loan_id, $client_id, $payment_amount, $payment_method);
                        $stmt_insert->execute();
                        $stmt_insert->close();
                    } else {
                        throw new Exception("Insert prep failed: " . $mysqli->error);
                    }
                    
                    $mysqli->commit();
                    $success = true;

                } else {
                    json_error('Active loan not found for the provided ID.');
                }
                $stmt->close();
            } else {
                json_error("Database prepare error: " . $mysqli->error);
            }

            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Payment of " . formatCurrency($payment_amount) . " recorded. New Balance: " . formatCurrency($final_balance) . ($new_status == 'Paid' ? ' (Loan Fully Paid)' : ''),
                    'new_balance' => $final_balance,
                    'new_status' => $new_status,
                ]);
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            json_error("Payment processing failed: " . $e->getMessage());
        }
        break;

    case 'export_clients_csv':
        if ($role !== 'admin') json_error('Access denied.');
        
        $sql = "SELECT member_id, c_firstname, c_lastname, c_email, c_phone, c_address, c_branch, c_status FROM clients ORDER BY client_id ASC";
        
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="clients_export_' . date('Ymd_His') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            fputcsv($output, ['Member ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address', 'Branch', 'Status']);
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            $mysqli->close();
            exit;
        } else {
            json_error('No client data to export.');
        }
        break;

    case 'get_payment_history':
        if ($role !== 'admin') json_error('Access denied.');

        $branch_filter = $_REQUEST['branch'] ?? 'all';
        $limit = $_REQUEST['limit'] ?? 1000;

        $sql_payments = "SELECT 
                            p.payment_id, 
                            p.payment_amount, 
                            p.payment_date, 
                            p.payment_method, 
                            l.loan_id, 
                            c.c_firstname, 
                            c.c_lastname,
                            c.c_branch
                         FROM payments p
                         JOIN loans l ON p.loan_id = l.loan_id
                         JOIN clients c ON p.client_id = c.client_id
                         WHERE 1=1";
        
        $params = [];
        $types = "";

        if ($branch_filter !== 'all') {
            $sql_payments .= " AND c.c_branch = ?";
            $types .= "s";
            $params[] = $branch_filter;
        }

        $sql_payments .= " ORDER BY p.payment_date DESC LIMIT ?";
        $types .= "i";
        $params[] = (int)$limit;

        $response = ['payments' => []];

        if ($stmt = $mysqli->prepare($sql_payments)) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $row['client_name'] = $row['c_firstname'] . ' ' . $row['c_lastname'];
                unset($row['c_firstname'], $row['c_lastname']);
                $response['payments'][] = $row;
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }

        echo json_encode($response);
        break;

    case 'download_report':
        if ($role !== 'admin') json_error('Access denied.');
        
        $type = $_REQUEST['type'] ?? '';
        $format = $_REQUEST['format'] ?? 'pdf';
        $branch = $_REQUEST['branch'] ?? 'all';
        
        if (!in_array($type, ['performance', 'client', 'financial'])) {
            json_error('Invalid report type.');
        }
        
        if (!in_array($format, ['pdf', 'excel', 'csv'])) {
            json_error('Invalid format.');
        }
        
        $filename = "{$type}_report_" . date('Ymd_His') . ".{$format}";
        
        switch ($format) {
            case 'pdf':
                header('Content-Type: application/pdf');
                break;
            case 'excel':
                header('Content-Type: application/vnd.ms-excel');
                break;
            case 'csv':
                header('Content-Type: text/csv');
                break;
        }
        
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        switch ($type) {
            case 'performance':
                $sql = "SELECT 
                    DATE_FORMAT(l.approval_date, '%Y-%m') as month,
                    COUNT(*) as loans_issued,
                    SUM(l.loan_amount) as total_issued,
                    SUM(p.payment_amount) as total_collected,
                    AVG(p.payment_amount) as avg_collection
                FROM loans l
                LEFT JOIN payments p ON l.loan_id = p.loan_id
                WHERE l.approval_date IS NOT NULL";
                
                if ($branch !== 'all') {
                    $sql .= " AND l.client_id IN (SELECT client_id FROM clients WHERE c_branch = '$branch')";
                }
                
                $sql .= " GROUP BY month ORDER BY month DESC";
                break;
                
            case 'client':
                $sql = "SELECT 
                    c.member_id,
                    c.c_firstname,
                    c.c_lastname,
                    c.c_phone,
                    c.c_branch,
                    COUNT(l.loan_id) as total_loans,
                    SUM(l.loan_amount) as total_borrowed,
                    MAX(l.application_date) as last_loan_date
                FROM clients c
                LEFT JOIN loans l ON c.client_id = l.client_id";
                
                if ($branch !== 'all') {
                    $sql .= " WHERE c.c_branch = '$branch'";
                }
                
                $sql .= " GROUP BY c.client_id ORDER BY c.c_lastname, c.c_firstname";
                break;
                
            case 'financial':
                $sql = "SELECT 
                    'Revenue' as category,
                    SUM(p.payment_amount) as amount
                FROM payments p";
                
                if ($branch !== 'all') {
                    $sql .= " WHERE p.client_id IN (SELECT client_id FROM clients WHERE c_branch = '$branch')";
                }
                
                $sql .= " UNION ALL
                    SELECT 
                    'Outstanding Loans' as category,
                    SUM(l.current_balance) as amount
                    FROM loans l
                    WHERE l.loan_status IN ('Active', 'Overdue')";
                    
                if ($branch !== 'all') {
                    $sql .= " AND l.client_id IN (SELECT client_id FROM clients WHERE c_branch = '$branch')";
                }
                break;
        }
        
        $result = $mysqli->query($sql);
        
        if ($format === 'csv') {
            $output = fopen('php://output', 'w');
            
            if ($result && $result->num_rows > 0) {
                $first = true;
                while ($row = $result->fetch_assoc()) {
                    if ($first) {
                        fputcsv($output, array_keys($row));
                        $first = false;
                    }
                    fputcsv($output, $row);
                }
            }
            fclose($output);
        } else {
            echo "Report data for {$type} report in {$format} format\n\n";
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $value) {
                        echo "{$key}: {$value}\n";
                    }
                    echo "---\n";
                }
            }
        }
        
        $mysqli->close();
        exit;
        break;

    case 'change_admin_password':
        if ($role !== 'admin') json_error('Access denied.');
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            json_error('All password fields are required.');
        }
        
        if ($new_password !== $confirm_password) {
            json_error('New passwords do not match.');
        }
        
        if (strlen($new_password) < 6) {
            json_error('New password must be at least 6 characters long.');
        }
        
        $admin_id = $_SESSION['admin_id'];
        $sql = "SELECT a_password_hash FROM admins WHERE admin_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($hashed_password);
                $stmt->fetch();
                
                if (password_verify($current_password, $hashed_password)) {
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_sql = "UPDATE admins SET a_password_hash = ? WHERE admin_id = ?";
                    if ($update_stmt = $mysqli->prepare($update_sql)) {
                        $update_stmt->bind_param("si", $new_hashed_password, $admin_id);
                        if ($update_stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
                        } else {
                            json_error('Failed to update password.');
                        }
                        $update_stmt->close();
                    } else {
                        json_error('Database error.');
                    }
                } else {
                    json_error('Current password is incorrect.');
                }
            } else {
                json_error('Admin not found.');
            }
            $stmt->close();
        } else {
            json_error('Database error.');
        }
        break;

    case 'backup_database':
        if ($role !== 'admin') json_error('Access denied.');
        
        $backup_file = 'bulacan_coop_backup_' . date('Ymd_His') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup_file . '"');
        
        $tables = ['admins', 'clients', 'loans', 'payments'];
        
        foreach ($tables as $table) {
            echo "--\n";
            echo "-- Table structure for table `$table`\n";
            echo "--\n\n";
            
            $result = $mysqli->query("SHOW CREATE TABLE $table");
            if ($result) {
                $row = $result->fetch_assoc();
                echo $row['Create Table'] . ";\n\n";
            }
            
            echo "--\n";
            echo "-- Dumping data for table `$table`\n";
            echo "--\n\n";
            
            $result = $mysqli->query("SELECT * FROM $table");
            if ($result && $result->num_rows > 0) {
                echo "INSERT INTO `$table` VALUES ";
                $first = true;
                while ($row = $result->fetch_assoc()) {
                    if (!$first) {
                        echo ",\n";
                    }
                    $values = array_map(function($value) use ($mysqli) {
                        if ($value === null) return 'NULL';
                        return "'" . $mysqli->real_escape_string($value) . "'";
                    }, array_values($row));
                    echo "(" . implode(', ', $values) . ")";
                    $first = false;
                }
                echo ";\n\n";
            }
        }
        
        break;
        
        case 'get_outstanding_chart_data':
    if ($role !== 'admin') json_error('Access denied.');
    
    $filter = $_REQUEST['filter'] ?? 'thisWeek';
    $response = ['labels' => [], 'data' => []];
    
    switch ($filter) {
        case 'today':
            // For today, we'll show hourly data of current outstanding balance
            // Since we don't have hourly snapshots, we'll use the current outstanding
            $sql = "SELECT 
                HOUR(NOW()) as current_hour,
                SUM(current_balance) as total_outstanding
                FROM loans 
                WHERE loan_status IN ('Active', 'Overdue')";
            break;
            
        case 'thisWeek':
            $sql = "SELECT 
                DAYNAME(l.application_date) as day,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE l.application_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND l.loan_status IN ('Active', 'Overdue')
                GROUP BY DAYOFWEEK(l.application_date) 
                ORDER BY DAYOFWEEK(l.application_date)";
            break;
            
        case 'last30Days':
            $sql = "SELECT 
                DATE(l.application_date) as date,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE l.application_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND l.loan_status IN ('Active', 'Overdue')
                GROUP BY DATE(l.application_date) 
                ORDER BY DATE(l.application_date)";
            break;
            
        case 'last3Months':
            $sql = "SELECT 
                DATE_FORMAT(l.application_date, '%Y-%m') as month,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE l.application_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                AND l.loan_status IN ('Active', 'Overdue')
                GROUP BY YEAR(l.application_date), MONTH(l.application_date) 
                ORDER BY YEAR(l.application_date), MONTH(l.application_date)";
            break;
            
        case 'last6Months':
            $sql = "SELECT 
                DATE_FORMAT(l.application_date, '%Y-%m') as month,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE l.application_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND l.loan_status IN ('Active', 'Overdue')
                GROUP BY YEAR(l.application_date), MONTH(l.application_date) 
                ORDER BY YEAR(l.application_date), MONTH(l.application_date)";
            break;
            
        case 'thisYear':
            $sql = "SELECT 
                DATE_FORMAT(l.application_date, '%Y-%m') as month,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE YEAR(l.application_date) = YEAR(CURDATE())
                AND l.loan_status IN ('Active', 'Overdue')
                GROUP BY YEAR(l.application_date), MONTH(l.application_date) 
                ORDER BY YEAR(l.application_date), MONTH(l.application_date)";
            break;
            
        default:
            // All time - monthly breakdown
            $sql = "SELECT 
                DATE_FORMAT(l.application_date, '%Y-%m') as month,
                SUM(l.current_balance) as total_outstanding
                FROM loans l
                WHERE l.loan_status IN ('Active', 'Overdue')
                GROUP BY YEAR(l.application_date), MONTH(l.application_date) 
                ORDER BY YEAR(l.application_date), MONTH(l.application_date)";
            break;
    }
    
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Handle different column names based on query
            $labelKey = array_keys($row)[0];
            $dataKey = array_keys($row)[1];
            
            $response['labels'][] = $row[$labelKey];
            $response['data'][] = (float)($row[$dataKey] ?? 0);
        }
        
        // If no data found, provide some sample data for demonstration
        if (empty($response['data'])) {
            $response['labels'] = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $response['data'] = [500000, 750000, 600000, 900000, 800000, 950000];
        }
    } else {
        // Fallback data if query fails
        $response['labels'] = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $response['data'] = [500000, 750000, 600000, 900000, 800000, 950000];
    }
    
    echo json_encode($response);
    break;

    case 'get_monthly_trends_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $response = ['labels' => [], 'loans_issued' => [], 'payments' => []];
        
        $sql_loans = "SELECT 
            DATE_FORMAT(approval_date, '%Y-%m') as month,
            COUNT(*) as loans_count,
            SUM(loan_amount) as total_issued
            FROM loans 
            WHERE approval_date IS NOT NULL 
            AND approval_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(approval_date), MONTH(approval_date) 
            ORDER BY YEAR(approval_date), MONTH(approval_date)";
            
        $sql_payments = "SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            SUM(payment_amount) as total_payments
            FROM payments 
            WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(payment_date), MONTH(payment_date) 
            ORDER BY YEAR(payment_date), MONTH(payment_date)";
        
        $result_loans = $mysqli->query($sql_loans);
        $result_payments = $mysqli->query($sql_payments);
        
        $loans_data = [];
        $payments_data = [];
        
        if ($result_loans) {
            while ($row = $result_loans->fetch_assoc()) {
                $month = $row['month'];
                $loans_data[$month] = (float)$row['total_issued'];
                if (!in_array($month, $response['labels'])) {
                    $response['labels'][] = $month;
                }
            }
        }
        
        if ($result_payments) {
            while ($row = $result_payments->fetch_assoc()) {
                $month = $row['month'];
                $payments_data[$month] = (float)$row['total_payments'];
                if (!in_array($month, $response['labels'])) {
                    $response['labels'][] = $month;
                }
            }
        }
        
        sort($response['labels']);
        
        foreach ($response['labels'] as $month) {
            $response['loans_issued'][] = $loans_data[$month] ?? 0;
            $response['payments'][] = $payments_data[$month] ?? 0;
        }
        
        echo json_encode($response);
        break;

    case 'get_risk_analysis_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $response = ['labels' => ['Low Risk', 'Medium Risk', 'High Risk'], 'data' => [0, 0, 0]];
        
        $sql = "SELECT 
            CASE 
                WHEN DATEDIFF(CURDATE(), next_payment_date) <= 7 THEN 'Low Risk'
                WHEN DATEDIFF(CURDATE(), next_payment_date) BETWEEN 8 AND 30 THEN 'Medium Risk'
                ELSE 'High Risk'
            END as risk_level,
            COUNT(*) as loan_count
            FROM loans 
            WHERE loan_status = 'Active' 
            AND next_payment_date IS NOT NULL
            GROUP BY risk_level";
            
        $result = $mysqli->query($sql);
        if ($result) {
            $risk_data = [];
            while ($row = $result->fetch_assoc()) {
                $risk_data[$row['risk_level']] = (int)$row['loan_count'];
            }
            
            $response['data'] = [
                $risk_data['Low Risk'] ?? 0,
                $risk_data['Medium Risk'] ?? 0,
                $risk_data['High Risk'] ?? 0
            ];
        }
        
        echo json_encode($response);
        break;

    case 'get_loans_payments_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $response = ['labels' => ['Active', 'Paid', 'Overdue', 'Pending'], 'data' => []];
        
        $sql = "SELECT 
            loan_status,
            COUNT(*) as status_count
            FROM loans 
            GROUP BY loan_status";
            
        $result = $mysqli->query($sql);
        if ($result) {
            $status_data = [];
            while ($row = $result->fetch_assoc()) {
                $status_data[$row['loan_status']] = (int)$row['status_count'];
            }
            
            $response['data'] = [
                $status_data['Active'] ?? 0,
                $status_data['Paid'] ?? 0,
                $status_data['Overdue'] ?? 0,
                $status_data['Pending'] ?? 0
            ];
        }
        
        echo json_encode($response);
        $mysqli->close();
        exit;
        break;

    default:
        json_error('Invalid action.');
        break;
}

$mysqli->close();
?>