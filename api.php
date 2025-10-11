<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';

// Check if user is logged in for any action requiring authentication
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}


// Function to handle errors and return a JSON response
function json_error($message) {
    global $mysqli;
    error_log("API Error: " . $message);
    http_response_code(400); // Set bad request status code
    echo json_encode(['success' => false, 'message' => $message]);
    $mysqli->close();
    exit;
}

// Ensure an action is set
$action = $_REQUEST['action'] ?? '';
if (empty($action)) {
    json_error('No action specified.');
}

// Determine the role for authorization
$role = $_SESSION['role'];
$user_id = $role === 'client' ? $_SESSION['client_id'] : $_SESSION['admin_id'];


// --- API Logic based on Action ---

switch ($action) {
    
    // =================================================================
    // CLIENT ACTIONS
    // =================================================================
    case 'get_client_data':
        if ($role !== 'client') json_error('Access denied for this action.');

        $client_id = $_SESSION['client_id'];
        $response = ['active_loan' => null, 'payment_history' => []];

        // 1. Fetch Active Loan Details
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

        // 2. Fetch Payment History for the active loan (if one exists)
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

        // Check for existing active/pending loan
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

        // Insert new loan application (status 'Pending')
        $sql_insert = "INSERT INTO loans (client_id, loan_amount, term_months, current_balance, loan_purpose, monthly_payment, loan_status) VALUES (?, ?, ?, ?, ?, 0.00, 'Pending')";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            // current_balance is initialized to the full amount, monthly_payment is 0 until approved
            $stmt->bind_param("iddds", $client_id, $amount, $term, $amount, $purpose);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Loan application submitted successfully and is now pending review.']);
            } else {
                json_error("Database error on submission: " . $mysqli->error);
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }
        break;

    // =================================================================
    // ADMIN ACTIONS
    // =================================================================
    case 'get_admin_data':
        if ($role !== 'admin') json_error('Access denied for this action.');
        
        $response = ['clients' => [], 'loans' => [], 'pending_loans' => []];

        // 1. Fetch all clients
        $sql_clients = "SELECT client_id, member_id, c_firstname, c_lastname, c_phone, c_email, c_address, c_branch, c_status FROM clients";
        $result = $mysqli->query($sql_clients);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $response['clients'][] = $row;
            }
        }

        // 2. Fetch all loans
        $sql_loans = "SELECT * FROM loans";
        $result = $mysqli->query($sql_loans);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $response['loans'][] = $row;
            }
        }
        
        // 3. Fetch Pending Loans and attach client names
        $sql_pending = "
            SELECT l.*, c.c_firstname, c.c_lastname, c.c_branch 
            FROM loans l
            JOIN clients c ON l.client_id = c.client_id
            WHERE l.loan_status = 'Pending'
            ORDER BY l.application_date ASC
        ";
        $result = $mysqli->query($sql_pending);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['client_name'] = $row['c_firstname'] . ' ' . $row['c_lastname'];
                unset($row['c_firstname'], $row['c_lastname']);
                $response['pending_loans'][] = $row;
            }
        }
        
        echo json_encode($response);
        break;

    case 'add_client':
        if ($role !== 'admin') json_error('Access denied for this action.');

        $mid = $_POST['member_id'] ?? '';
        $fn = $_POST['c_firstname'] ?? '';
        $ln = $_POST['c_lastname'] ?? '';
        $phone = $_POST['c_phone'] ?? '';
        $branch = $_POST['c_branch'] ?? '';

        if (empty($mid) || empty($fn) || empty($ln) || empty($phone) || empty($branch)) {
            json_error('All fields are required.');
        }

        // 1. Derive the temporary password
        $temp_password_string = generate_client_password($ln, $phone);
        
        // 2. Hash the password
        $hashed_password = password_hash($temp_password_string, PASSWORD_DEFAULT);

        // 3. Insert client
        $sql_insert = "INSERT INTO clients (member_id, c_firstname, c_lastname, c_phone, c_branch, c_password_hash, c_address) VALUES (?, ?, ?, ?, ?, ?, 'Address Not Provided')";
        if ($stmt = $mysqli->prepare($sql_insert)) {
            $stmt->bind_param("ssssss", $mid, $fn, $ln, $phone, $branch, $hashed_password);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Client added successfully. Initial password is: ' . $temp_password_string
                ]);
            } else {
                json_error("Database error (Client ID or Phone may exist): " . $mysqli->error);
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
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

        // Fetch original loan details to get term and amount
        $sql_fetch = "SELECT client_id, loan_amount, term_months FROM loans WHERE loan_id = ? AND loan_status = 'Pending'";
        if ($stmt = $mysqli->prepare($sql_fetch)) {
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($loan = $result->fetch_assoc()) {
                $client_id = $loan['client_id'];
                $current_balance = $loan['loan_amount']; // Balance is initialized to full amount
                
                // Calculate next payment date (e.g., 30 days from now)
                $next_payment_date = date('Y-m-d', strtotime('+30 days'));

                // Update the loan to 'Active'
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
        if ($role !== 'admin') json_error('Access denied for this action.');
        
        $loan_id = $_POST['loan_id'] ?? '';
        $payment_amount = $_POST['payment_amount'] ?? '';
        
        if (empty($loan_id) || empty($payment_amount) || !is_numeric($payment_amount) || $payment_amount <= 0) {
            json_error('Invalid loan ID or payment amount.');
        }

        // 1. Fetch active loan details
        $sql_fetch = "SELECT client_id, current_balance, monthly_payment, term_months, loan_amount FROM loans WHERE loan_id = ? AND loan_status IN ('Active', 'Overdue')";
        if ($stmt = $mysqli->prepare($sql_fetch)) {
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($loan = $result->fetch_assoc()) {
                $client_id = $loan['client_id'];
                $old_balance = $loan['current_balance'];
                $new_balance = $old_balance - $payment_amount;
                
                // Determine next payment date and status
                $new_status = $new_balance <= 0.00 ? 'Paid' : 'Active';
                $next_payment_date = $new_status == 'Active' ? date('Y-m-d', strtotime('+30 days')) : NULL;
                $final_balance = $new_balance < 0 ? 0.00 : $new_balance; // Ensure balance doesn't go negative in DB if overpaid

                // 2. Start Transaction
                $mysqli->begin_transaction();
                $success = false;
                
                try {
                    // Update Loan Balance and Status
                    $sql_update = "UPDATE loans SET current_balance = ?, loan_status = ?, next_payment_date = ? WHERE loan_id = ?";
                    if ($stmt_update = $mysqli->prepare($sql_update)) {
                        $stmt_update->bind_param("dsii", $final_balance, $new_status, $next_payment_date, $loan_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        throw new Exception("Update prep failed: " . $mysqli->error);
                    }

                    // Insert Payment Record
                    $sql_insert = "INSERT INTO payments (loan_id, client_id, payment_amount) VALUES (?, ?, ?)";
                    if ($stmt_insert = $mysqli->prepare($sql_insert)) {
                        $stmt_insert->bind_param("iid", $loan_id, $client_id, $payment_amount);
                        $stmt_insert->execute();
                        $stmt_insert->close();
                    } else {
                        throw new Exception("Insert prep failed: " . $mysqli->error);
                    }
                    
                    // Commit transaction
                    $mysqli->commit();
                    $success = true;

                } catch (Exception $e) {
                    $mysqli->rollback();
                    json_error("Payment processing failed: " . $e->getMessage());
                }

                if ($success) {
                    echo json_encode([
                        'success' => true, 
                        'message' => "Payment of " . $payment_amount . " recorded. New Balance: " . $final_balance . ($new_status == 'Paid' ? ' (Loan Fully Paid)' : '')
                    ]);
                }

            } else {
                json_error('Active loan not found for the provided ID.');
            }
            $stmt->close();
        } else {
            json_error("Database prepare error: " . $mysqli->error);
        }
        break;

    default:
        json_error('Invalid action.');
        break;
}

$mysqli->close();
?>
