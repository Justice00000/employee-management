<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'database-config.php';

// Start output buffering
ob_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection
        $conn = DatabaseConfig::getConnection();

        // Sanitize input
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone']);
        $department = sanitizeInput($_POST['department']);
        $position = sanitizeInput($_POST['position']);
        $hire_date = $_POST['hire_date'];

        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($position)) {
            throw new Exception("All required fields must be filled");
        }

        // Generate Employee ID
        $employee_id = generateEmployeeID();
        if (!$employee_id) {
            throw new Exception("Failed to generate employee ID");
        }

        // Prepare SQL to insert new employee
        $stmt = $conn->prepare("
            INSERT INTO employees (
                employee_id, 
                first_name, 
                last_name, 
                email, 
                phone, 
                department, 
                position, 
                hire_date
            ) VALUES (
                :employee_id, 
                :first_name, 
                :last_name, 
                :email, 
                :phone, 
                :department, 
                :position, 
                :hire_date
            )
        ");

        // Bind parameters
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':hire_date', $hire_date);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect with success message
            header("Location: index.php?success=1&message=" . urlencode("Employee $employee_id added successfully"));
            ob_end_clean();
            exit();
        } else {
            throw new Exception("Failed to add employee");
        }

    } catch (PDOException $e) {
        // Handle database-specific errors
        $error_message = "Database Error: ";
        if ($e->getCode() == '23505') {
            // Unique constraint violation (likely duplicate email)
            $error_message .= "An employee with this email already exists.";} else {
                $error_message .= $e->getMessage();
            }
            
            // Log the full error
            error_log($e->getMessage());
    
            // Redirect with error message
            header("Location: index.php?error=1&message=" . urlencode($error_message));
            ob_end_clean();
            exit();
    
        } catch (Exception $e) {
            // Handle other exceptions
            error_log($e->getMessage());
            
            // Redirect with error message
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