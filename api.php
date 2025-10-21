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
        
        $response = [
            'clients' => [], 
            'loans' => [], 
            'pending_loans' => [],
            'payments_today' => 0.00, 
            'payments_yesterday' => 0.00, 
            'monthly_stats' => [] 
        ];

        $sql_clients = "SELECT client_id, member_id, c_firstname, c_lastname, c_email, c_phone, c_address, c_branch, c_status FROM clients";
        if ($result = $mysqli->query($sql_clients)) {
            while ($row = $result->fetch_assoc()) {
                $response['clients'][] = $row;
            }
        }
    
        $sql_loans = "SELECT l.*, c.c_firstname, c.c_lastname, c.c_branch FROM loans l JOIN clients c ON l.client_id = c.client_id ORDER BY l.application_date DESC";
        if ($result = $mysqli->query($sql_loans)) {
            while ($row = $result->fetch_assoc()) {
                $response['loans'][] = $row;
            }
        }
    
        $sql_pending = "SELECT l.*, c.c_firstname, c.c_lastname, c.c_branch FROM loans l JOIN clients c ON l.client_id = c.client_id WHERE l.loan_status = 'Pending' ORDER BY l.application_date ASC";
        if ($result = $mysqli->query($sql_pending)) {
            while ($row = $result->fetch_assoc()) {
                $row['client_name'] = $row['c_firstname'] . ' ' . $row['c_lastname'];
                $row['client_branch'] = $row['c_branch'];
                unset($row['c_firstname'], $row['c_lastname'], $row['c_branch']);
                $response['pending_loans'][] = $row;
            }
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
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        
        $loan_id = $_POST['loan_id'] ?? 0;
        $interest_rate = $_POST['interest_rate'] ?? 0.0;
        $term_months = $_POST['term_months'] ?? 0;

        if (!is_numeric($loan_id) || $loan_id <= 0 || !is_numeric($interest_rate) || $interest_rate <= 0 || !is_numeric($term_months) || $term_months <= 0) {
            json_error('Invalid loan details provided for approval.');
        }

    
        $sql_fetch = "SELECT loan_amount, client_id FROM loans WHERE loan_id = ? AND loan_status = 'Pending'";
        if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
            $stmt_fetch->bind_param("i", $loan_id);
            $stmt_fetch->execute();
            $result = $stmt_fetch->get_result();
            if (!$loan = $result->fetch_assoc()) {
                json_error('Pending loan not found or already processed.');
            }
            $stmt_fetch->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }

        $principal = $loan['loan_amount'];

    
        if (!function_exists('calculate_monthly_payment')) {
            json_error('Dependency error: calculate_monthly_payment function not found. Ensure db_config.php is fully loaded.');
        }
        $monthly_payment = calculate_monthly_payment($principal, $interest_rate, $term_months);
        $next_payment_date = date('Y-m-d', strtotime('+1 month'));

    
        $sql_update = "UPDATE loans SET 
                        interest_rate = ?, 
                        term_months = ?, 
                        monthly_payment = ?, 
                        current_balance = loan_amount,
                        approval_date = NOW(),
                        next_payment_date = ?,
                        loan_status = 'Active' 
                        WHERE loan_id = ?";
        
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("didssi", $interest_rate, $term_months, $monthly_payment, $next_payment_date, $loan_id);
            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Loan approved and set to Active. Monthly Payment: ' . formatCurrency($monthly_payment)]);
            } else {
                json_error('Failed to update loan status: ' . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }
        break;
        
    case 'decline_loan':
        if ($role !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Access denied.');
        $loan_id = $_POST['loan_id'] ?? 0;
        if (!is_numeric($loan_id) || $loan_id <= 0) json_error('Invalid loan ID.');

        $sql_update = "UPDATE loans SET loan_status = 'Declined' WHERE loan_id = ? AND loan_status = 'Pending'";
        if ($stmt = $mysqli->prepare($sql_update)) {
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Loan application declined.']);
            } else {
                json_error('Loan not found, already declined, or not pending.');
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

    default:
        json_error('Invalid action.');
        break;
}

$mysqli->close();
?>