<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
class DatabaseConfig {
    private static $connectionString = 'postgresql://admin_db_5jq5_user:zQ7Zey6xTtDtqT99fKgUepfsuEhCjIoZ@dpg-cvn925a4d50c73fv6m70-a.oregon-postgres.render.com/admin_db_5jq5';

    public static function getConnection() {
        try {
            // Parse the connection string
            $parsedUrl = parse_url(self::$connectionString);
            
            // Extract connection details
            $host = $parsedUrl['host'];
            $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 5432;
            $dbname = ltrim($parsedUrl['path'], '/');
            $username = $parsedUrl['user'];
            $password = $parsedUrl['pass'];

            // Create DSN
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            // Create PDO connection
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            return $pdo;
        } catch (PDOException $e) {
            // Log detailed error
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Throw a generic error
            throw new Exception("Unable to connect to the database");
        }
    }
}

// Helper Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Start output buffering
ob_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection
        $conn = DatabaseConfig::getConnection();

        // Sanitize and validate inputs
        $employee_id = sanitizeInput($_POST['employee_id']);
        $leave_type_id = filter_var($_POST['leave_type'], FILTER_VALIDATE_INT);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = sanitizeInput($_POST['reason']);

        // Validate inputs
        if (empty($employee_id) || empty($leave_type_id) || empty($start_date) || empty($end_date)) {
            throw new Exception("All required fields must be filled");
        }

        // Calculate total leave days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $total_days = $interval->days + 1; // Include start and end dates

        // Verify employee exists and get employee details
        $emp_stmt = $conn->prepare("SELECT id, remaining_leave_days FROM employees WHERE employee_id = :employee_id");
        $emp_stmt->bindParam(':employee_id', $employee_id);
        $emp_stmt->execute();
        $employee = $emp_stmt->fetch();

        if (!$employee) {
            throw new Exception("Employee not found");
        }

        // Check if employee has enough leave days
        if ($total_days > $employee['remaining_leave_days']) {
            throw new Exception("Insufficient leave balance. Available days: " . $employee['remaining_leave_days']);
        }

        // Prepare SQL to insert leave application
        $stmt = $conn->prepare("
            INSERT INTO leave_applications (
                employee_id, 
                leave_type_id, 
                start_date, 
                end_date, 
                total_days, 
                reason
            ) VALUES (
                :employee_id, 
                :leave_type_id, 
                :start_date, 
                :end_date, 
                :total_days, 
                :reason
            )
        ");

        // Bind parameters
        $stmt->bindParam(':employee_id', $employee['id']);
        $stmt->bindParam(':leave_type_id', $leave_type_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':total_days', $total_days);
        $stmt->bindParam(':reason', $reason);

        // Begin transaction
        $conn->beginTransaction();

        // Execute the leave application
        if ($stmt->execute()) {
            // Update remaining leave days
            $update_stmt = $conn->prepare("
                UPDATE employees 
                SET remaining_leave_days = remaining_leave_days - :total_days 
                WHERE id = :employee_id
            ");
            $update_stmt->bindParam(':total_days', $total_days);
            $update_stmt->bindParam(':employee_id', $employee['id']);
            $update_stmt->execute();

            // Commit transaction
            $conn->commit();

            // Redirect with success message
            header("Location: index.php?success=1&message=" . urlencode("Leave application submitted successfully"));
            ob_end_clean();
            exit();
        } else {
            // Rollback transaction
            $conn->rollBack();
            throw new Exception("Failed to submit leave application");
        }

    } catch (PDOException $e) {
        // Rollback transaction in case of database error
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }

        // Log and handle database errors
        error_log("Database Error: " . $e->getMessage());
        header("Location: index.php?error=1&message=" . urlencode("Database error occurred"));
        ob_end_clean();
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }

        // Log and handle other exceptions
        error_log($e->getMessage());
        header("Location: index.php?error=1&message=" . urlencode($e->getMessage()));
        ob_end_clean();
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: index.php");
    ob_end_clean();
    exit();
}