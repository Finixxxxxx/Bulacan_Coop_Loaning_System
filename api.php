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
$user_id = $_SESSION[$role . '_id'] ?? null;

switch ($action) {
    // ==================
    // Get Client Data - Client Portal
    // ==================
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

    // ==================
    // Get Admin Data - Admin Dashboard
    // ==================
    case 'get_admin_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $branch_filter = $_REQUEST['branch'] ?? 'all';
        $loan_status_filter = $_REQUEST['loan_status'] ?? 'all';
        
        $response = [
            'clients' => [], 
            'loans' => [], 
            'pending_loans' => [],
            'collectors' => [],
            'payments_today' => 0.00, 
            'payments_yesterday' => 0.00, 
            'payments_rate_from_yesterday' => 0.00,
            'total_outstanding' => 0.00,
            'active_loans_count' => 0,
            'monthly_stats' => [] 
        ];
        $sql_collectors = "SELECT * FROM collectors WHERE col_status = 'Active'";
        if ($branch_filter !== 'all') {
            $sql_collectors .= " AND col_branch = ?";
        }
        
        if ($stmt = $mysqli->prepare($sql_collectors)) {
            if ($branch_filter !== 'all') {
                $stmt->bind_param("s", $branch_filter);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['collectors'][] = $row;
            }
            $stmt->close();
        }

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
        }
        
        echo json_encode($response);
        break;

    // ==================
    // Add Client - Admin Dashboard | Client Management
    // ==================
    case 'add_client':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $firstname = $_POST['c_firstname'] ?? '';
        $lastname = $_POST['c_lastname'] ?? '';
        $email = $_POST['c_email'] ?? '';
        $phone = $_POST['c_phone'] ?? '';
        $address = $_POST['c_address'] ?? '';
        $branch = $_POST['c_branch'] ?? '';

        if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($branch)) {
            json_error('All required fields must be filled.');
        }

        $sql_check = "SELECT client_id FROM clients WHERE c_email = ?";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Email address already exists.');
            }
            $stmt->close();
        }

        $member_id = generate_member_id($branch, $mysqli);
        
        $plain_password = generate_client_password($lastname, $member_id, $phone);
        $password_hash = password_hash($plain_password, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO clients (member_id, c_firstname, c_lastname, c_email, c_phone, c_address, c_branch, c_password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            $stmt->bind_param("ssssssss", $member_id, $firstname, $lastname, $email, $phone, $address, $branch, $password_hash);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Client added successfully! Member ID: $member_id, Temporary Password: $plain_password"
                ]);
            } else {
                json_error('Failed to add client: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            json_error('Database prepare error: ' . $mysqli->error);
        }
        break;

    // ==================
    // Add Collector - Admin Dashboard
    // ==================
    case 'add_collector':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $fullname = $_POST['col_fullname'] ?? '';
        $username = $_POST['col_username'] ?? '';
        $password = $_POST['col_password'] ?? '';
        $branch = $_POST['col_branch'] ?? '';

        if (empty($fullname) || empty($username) || empty($password) || empty($branch)) {
            json_error('All required fields must be filled.');
        }

        $sql_check = "SELECT collector_id FROM collectors WHERE col_username = ?";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Username already exists.');
            }
            $stmt->close();
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO collectors (col_username, col_password_hash, col_fullname, col_branch) VALUES (?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            $stmt->bind_param("ssss", $username, $password_hash, $fullname, $branch);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Collector added successfully!"
                ]);
            } else {
                json_error('Failed to add collector: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            json_error('Database prepare error: ' . $mysqli->error);
        }
        break;

    case 'delete_collector':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        $collector_id = $_POST['collector_id'] ?? '';

        if (empty($collector_id)) {
            json_error('Collector ID is required.');
        }

        $sql_check = "SELECT payment_id FROM payments WHERE collector_id = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("i", $collector_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Cannot delete collector. They have recorded payments.');
            }
            $stmt->close();
        }

        $sql_delete = "DELETE FROM collectors WHERE collector_id = ?";
        if ($stmt = $mysqli->prepare($sql_delete)) {
            $stmt->bind_param("i", $collector_id);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Collector deleted successfully!"
                ]);
            } else {
                json_error('Failed to delete collector: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            json_error('Database prepare error: ' . $mysqli->error);
        }
        break;

    case 'edit_collector':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        $collector_id = $_POST['collector_id'] ?? '';
        $fullname = $_POST['col_fullname'] ?? '';
        $username = $_POST['col_username'] ?? '';
        $password = $_POST['col_password'] ?? '';
        $branch = $_POST['col_branch'] ?? '';
        $status = $_POST['col_status'] ?? 'Active';
        if (empty($collector_id) || empty($fullname) || empty($username) || empty($branch)) {
            json_error('All required fields must be filled.');
        }

        $sql_check = "SELECT collector_id FROM collectors WHERE col_username = ? AND collector_id != ?";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("si", $username, $collector_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Username already exists.');
            }
            $stmt->close();
        }

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE collectors SET col_username = ?, col_password_hash = ?, col_fullname = ?, col_branch = ?, col_status = ? WHERE collector_id = ?";
            if ($stmt = $mysqli->prepare($sql_update)) {
                $stmt->bind_param("sssssi", $username, $password_hash, $fullname, $branch, $status, $collector_id);
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true, 
                        'message' => "Collector updated successfully!"
                    ]);
                } else {
                    json_error('Failed to update collector: ' . $stmt->error);
                }
                $stmt->close();
            }
        } else {
            $sql_update = "UPDATE collectors SET col_username = ?, col_fullname = ?, col_branch = ?, col_status = ? WHERE collector_id = ?";
            if ($stmt = $mysqli->prepare($sql_update)) {
                $stmt->bind_param("ssssi", $username, $fullname, $branch, $status, $collector_id);
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true, 
                        'message' => "Collector updated successfully!"
                    ]);
                } else {
                    json_error('Failed to update collector: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
        break;

    // ==================
    // Submit Loan - Admin Dashboard
    // ==================
    case 'submit_loan_admin':
        if ($role !== 'admin') json_error('Access denied for this action.');
        $client_id = $_POST['client_id'] ?? '';
        $amount = $_POST['loan_amount'] ?? '';

        if (empty($client_id) || empty($amount) || !is_numeric($amount) || $amount < 1000) {
            json_error('Invalid loan details provided.');
        }

        $sql_check = "SELECT loan_id FROM loans WHERE client_id = ? AND loan_status IN ('Active') LIMIT 1";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Client already has an active loan.');
            }
            $stmt->close();
        }

        $loan_totals = calculate_loan_totals($amount);
        $term_days = 100;
        $daily_payment = $loan_totals['daily_payment'];
        $total_balance = $loan_totals['total_balance'];

        $next_payment_date = date('Y-m-d', strtotime('+1 day'));

        $sql_insert = "INSERT INTO loans (client_id, loan_amount, processing_fee, net_amount, interest_rate, term_days, daily_payment, total_balance, current_balance, loan_status, next_payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            $stmt->bind_param("iddddddddds", 
                $client_id, 
                $amount, 
                $loan_totals['processing_fee'], 
                $loan_totals['net_amount'], 
                15.00,
                $term_days, 
                $daily_payment, 
                $total_balance, 
                $total_balance, 
                $next_payment_date
            );
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Loan successfully created.']);
            } else {
                json_error('Failed to submit loan: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            json_error('Database prepare error for loan submission: ' . $mysqli->error);
        }
        break;

    // ==================
    // Deactivate Client - Admin Dashboard | Client Management
    // ==================
    case 'deactivate_client':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $client_id = $_POST['client_id'] ?? 0;
        if (!is_numeric($client_id) || $client_id <= 0) json_error('Invalid client ID.');

        if (deactivate_client_account($client_id, $mysqli)) {
            echo json_encode(['success' => true, 'message' => 'Client account deactivated successfully.']);
        } else {
            json_error('Cannot deactivate client. They may have active or pending loans.');
        }
        break;

    // ==================
    // Re-Activate Client - Admin Dashboard | Client Management
    // ==================
    case 'reactivate_client':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $client_id = $_POST['client_id'] ?? 0;
        if (!is_numeric($client_id) || $client_id <= 0) json_error('Invalid client ID.');

        $new_member_id = reactivate_client_member_id($client_id, $mysqli);
        if ($new_member_id) {
            echo json_encode(['success' => true, 'message' => "Client reactivated successfully! New Member ID: $new_member_id"]);
        } else {
            json_error('Failed to reactivate client.');
        }
        break;

    // ==================
    // Delete Client Data - Admin Dashboard | Client Management
    // ==================
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

    // ==================
    // Record Payment - Collector Dashboard
    // ==================
    case 'record_payment':
        if ($role !== 'collector' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $loan_id = $_POST['loan_id'] ?? 0;
        $payment_amount = $_POST['payment_amount'] ?? 0.0;
        $collector_id = $_SESSION['collector_id'];
        
        if (!is_numeric($loan_id) || $loan_id <= 0 || !is_numeric($payment_amount) || $payment_amount <= 0) {
            json_error('Invalid loan ID or payment amount.');
        }

        $mysqli->begin_transaction();
        $success = false;

        try {
            $sql_fetch = "SELECT client_id, current_balance, daily_payment, days_paid, term_days FROM loans WHERE loan_id = ? AND loan_status IN ('Active', 'Overdue')";
            if ($stmt = $mysqli->prepare($sql_fetch)) {
                $stmt->bind_param("i", $loan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($loan = $result->fetch_assoc()) {
                    $client_id = $loan['client_id'];
                    $current_balance = (float)$loan['current_balance'];
                    $daily_payment = (float)$loan['daily_payment'];
                    $days_paid = (int)$loan['days_paid'];
                    $term_days = (int)$loan['term_days'];

                    $payment_type = 'daily';
                    if ($payment_amount >= $current_balance) {
                        $payment_type = 'full';
                        $payment_amount = $current_balance;
                    } elseif ($payment_amount > $daily_payment) {
                        $payment_type = 'partial';
                    }

                    $final_balance = $current_balance - $payment_amount;
                    $new_days_paid = $days_paid;
                    
                    if ($payment_type === 'daily') {
                        $new_days_paid = $days_paid + 1;
                    }

                    $new_status = ($final_balance <= 0.01) ? 'Paid' : 'Active';
                    $final_balance = max(0.00, $final_balance);

                    $new_next_payment_date = $new_status == 'Active' ? date('Y-m-d', strtotime('+1 day')) : null;

                    $sql_update = "UPDATE loans SET current_balance = ?, loan_status = ?, next_payment_date = ?, days_paid = ? WHERE loan_id = ?";
                    if ($stmt_update = $mysqli->prepare($sql_update)) {
                        $stmt_update->bind_param("dssii", $final_balance, $new_status, $new_next_payment_date, $new_days_paid, $loan_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        throw new Exception("Update prep failed: " . $mysqli->error);
                    }

                    $sql_insert = "INSERT INTO payments (loan_id, client_id, collector_id, payment_amount, payment_method, payment_type) VALUES (?, ?, ?, ?, 'Cash', ?)";
                    if ($stmt_insert = $mysqli->prepare($sql_insert)) {
                        $stmt_insert->bind_param("iiids", $loan_id, $client_id, $collector_id, $payment_amount, $payment_type);
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

    // ==================
    // Get Collector Data - Collector Dashboard
    // ==================
    case 'get_collector_data':
        if ($role !== 'collector') json_error('Access denied.');
        
        $collector_id = $_SESSION['collector_id'];
        $branch = $_SESSION['collector_branch'];
        $today = date('Y-m-d');

        $response = [
            'today_payments' => [],
            'unpaid_clients' => [],
            'paid_clients' => []
        ];

        $sql_today_payments = "SELECT p.payment_id, p.payment_amount, p.payment_date, c.member_id, c.c_firstname, c.c_lastname, l.loan_id 
                            FROM payments p 
                            JOIN clients c ON p.client_id = c.client_id 
                            JOIN loans l ON p.loan_id = l.loan_id 
                            WHERE p.collector_id = ? AND DATE(p.payment_date) = ? 
                            ORDER BY p.payment_date DESC";
        
        if ($stmt = $mysqli->prepare($sql_today_payments)) {
            $stmt->bind_param("is", $collector_id, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['today_payments'][] = $row;
            }
            $stmt->close();
        }

        $sql_unpaid = "SELECT l.loan_id, c.member_id, c.c_firstname, c.c_lastname, l.daily_payment, l.current_balance 
                    FROM loans l 
                    JOIN clients c ON l.client_id = c.client_id 
                    WHERE l.loan_status = 'Active' 
                    AND c.c_branch = ? 
                    AND l.loan_id NOT IN (
                        SELECT loan_id FROM payments 
                        WHERE DATE(payment_date) = ? AND collector_id = ?
                    )";
        
        if ($stmt = $mysqli->prepare($sql_unpaid)) {
            $stmt->bind_param("ssi", $branch, $today, $collector_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['unpaid_clients'][] = $row;
            }
            $stmt->close();
        }

        $sql_paid = "SELECT DISTINCT l.loan_id, c.member_id, c.c_firstname, c.c_lastname, l.daily_payment, 
                    (SELECT SUM(payment_amount) FROM payments WHERE loan_id = l.loan_id AND DATE(payment_date) = ?) as paid_today
                    FROM loans l 
                    JOIN clients c ON l.client_id = c.client_id 
                    JOIN payments p ON l.loan_id = p.loan_id 
                    WHERE l.loan_status = 'Active' 
                    AND c.c_branch = ? 
                    AND DATE(p.payment_date) = ? 
                    AND p.collector_id = ?";
        
        if ($stmt = $mysqli->prepare($sql_paid)) {
            $stmt->bind_param("sssi", $today, $branch, $today, $collector_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['paid_clients'][] = $row;
            }
            $stmt->close();
        }

        echo json_encode($response);
        break;

    // ==================
    // Get Collector Reports - Admin Dashboard
    // ==================
    case 'get_collector_reports':
        if ($role !== 'admin') json_error('Access denied.');
        
        $branch_filter = $_REQUEST['branch'] ?? 'all';
        $date_filter = $_REQUEST['date'] ?? date('Y-m-d');
        $report_type = $_REQUEST['report_type'] ?? 'clients';

        $response = ['labels' => [], 'data' => []];

        $sql = "SELECT c.col_fullname, 
                COUNT(DISTINCT p.client_id) as client_count,
                SUM(p.payment_amount) as total_collected
                FROM payments p 
                JOIN collectors c ON p.collector_id = c.collector_id 
                WHERE DATE(p.payment_date) = ?";
        
        $params = [$date_filter];
        $types = "s";

        if ($branch_filter !== 'all') {
            $sql .= " AND c.col_branch = ?";
            $types .= "s";
            $params[] = $branch_filter;
        }

        $sql .= " GROUP BY c.collector_id ORDER BY total_collected DESC";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $response['labels'][] = $row['col_fullname'];
                if ($report_type === 'clients') {
                    $response['data'][] = (int)$row['client_count'];
                } else {
                    $response['data'][] = (float)$row['total_collected'];
                }
            }
            $stmt->close();
        }

        echo json_encode($response);
        break;

    // ==================
    // Export Clients as CSV - Admin Dashboard | Client Management
    // ==================
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

    // ==================
    // Get Payment History - Admin Dashboard | Loans & Payments
    // ==================
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

    // ==================
    // Download Reports - Admin Dashboard | Reports
    // ==================
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
                    DATE_FORMAT(l.application_date, '%Y-%m') as month,
                    COUNT(*) as loans_issued,
                    SUM(l.loan_amount) as total_issued,
                    SUM(p.payment_amount) as total_collected,
                    AVG(p.payment_amount) as avg_collection
                FROM loans l
                LEFT JOIN payments p ON l.loan_id = p.loan_id
                WHERE l.loan_status = 'Active'";
                
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
        break;

    // ==================
    // Change the Admin Password - Admin Dashboard | Settings
    // ==================
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

    // ==================
    // Backup Database - Admin Dashboard | Settings
    // ==================
    case 'backup_database':
        if ($role !== 'admin') json_error('Access denied.');
        
        $backup_file = 'bulacan_coop_backup_' . date('Ymd_His') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup_file . '"');
        
        $tables = ['admins', 'clients', 'collectors', 'loans', 'payments'];
        
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

    // ==================
    // Upcoming Payments - Admin Dashboard | Overview
    // ==================
    case 'get_upcoming_payments':
        if ($role !== 'admin') json_error('Access denied.');

        header('Content-Type: application/json; charset=utf-8');
        $response = ['payments' => []];

        $sql = "
            SELECT 
                l.loan_id,
                l.daily_payment,
                l.next_payment_date,
                CONCAT(c.c_firstname, ' ', c.c_lastname) AS client_name,
                DATEDIFF(l.next_payment_date, CURDATE()) AS days_until_due
            FROM loans l
            JOIN clients c ON l.client_id = c.client_id
            WHERE l.loan_status IN ('Active', 'Overdue')
            AND l.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY l.next_payment_date ASC
            LIMIT 10
        ";

        $result = $mysqli->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $response['payments'][] = [
                    'loan_id'        => (int)$row['loan_id'],
                    'daily_payment'  => (float)$row['daily_payment'],
                    'next_payment_date' => $row['next_payment_date'],
                    'client_name'    => $row['client_name'],
                    'days_until_due' => (int)$row['days_until_due']
                ];
            }
        }

        echo json_encode($response);
        break;

    // ==================
    // Monthly Trends Data - Admin Dashboard | Reports
    // ==================
    case 'get_monthly_trends_data':
        if ($role !== 'admin') json_error('Access denied.');
        
        $response = ['labels' => [], 'loans_issued' => [], 'payments' => []];
        
        $sql_loans = "SELECT 
            DATE_FORMAT(application_date, '%Y-%m') as month,
            COUNT(*) as loans_count,
            SUM(loan_amount) as total_issued
            FROM loans 
            WHERE loan_status = 'Active' 
            AND application_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(application_date), MONTH(application_date) 
            ORDER BY YEAR(application_date), MONTH(application_date)";
            
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

    // ==================
    // Update Collector Profile - Collector Dashboard
    // ==================
    case 'update_collector_profile':
        if ($role !== 'collector' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $collector_id = $_SESSION['collector_id'];
        $fullname = $_POST['col_fullname'] ?? '';
        $username = $_POST['col_username'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($fullname) || empty($username)) {
            json_error('Full name and username are required.');
        }

        $sql_check = "SELECT collector_id FROM collectors WHERE col_username = ? AND collector_id != ?";
        if ($stmt = $mysqli->prepare($sql_check)) {
            $stmt->bind_param("si", $username, $collector_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                json_error('Username already exists.');
            }
            $stmt->close();
        }

        if (!empty($new_password)) {
            if (empty($current_password)) {
                json_error('Current password is required to set a new password.');
            }
            
            if ($new_password !== $confirm_password) {
                json_error('New passwords do not match.');
            }
            
            if (strlen($new_password) < 6) {
                json_error('New password must be at least 6 characters long.');
            }

            $sql_verify = "SELECT col_password_hash FROM collectors WHERE collector_id = ?";
            if ($stmt = $mysqli->prepare($sql_verify)) {
                $stmt->bind_param("i", $collector_id);
                $stmt->execute();
                $stmt->bind_result($hashed_password);
                $stmt->fetch();
                $stmt->close();
                
                if (!password_verify($current_password, $hashed_password)) {
                    json_error('Current password is incorrect.');
                }
            }

            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE collectors SET col_username = ?, col_password_hash = ?, col_fullname = ? WHERE collector_id = ?";
            if ($stmt = $mysqli->prepare($sql_update)) {
                $stmt->bind_param("sssi", $username, $password_hash, $fullname, $collector_id);
                if ($stmt->execute()) {
                    $_SESSION['collector_username'] = $username;
                    $_SESSION['collector_name'] = $fullname;
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "Profile updated successfully!"
                    ]);
                } else {
                    json_error('Failed to update profile: ' . $stmt->error);
                }
                $stmt->close();
            }
        } else {
            $sql_update = "UPDATE collectors SET col_username = ?, col_fullname = ? WHERE collector_id = ?";
            if ($stmt = $mysqli->prepare($sql_update)) {
                $stmt->bind_param("ssi", $username, $fullname, $collector_id);
                if ($stmt->execute()) {
                    $_SESSION['collector_username'] = $username;
                    $_SESSION['collector_name'] = $fullname;
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "Profile updated successfully!"
                    ]);
                } else {
                    json_error('Failed to update profile: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
        break;

    default:
        json_error('Invalid action.');
        break;
}

$mysqli->close();
?>