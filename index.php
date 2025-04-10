<?php
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

    // Connection test method
    public static function testConnection() {
        try {
            $conn = self::getConnection();
            // Try a simple query
            $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            error_log("Connection Test Failed: " . $e->getMessage());
            return false;
        }
    }
}

// Start session for potential messages
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <?php
        // Display database connection status
        try {
            DatabaseConfig::getConnection();
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Database Connection Successful
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Database Connection Failed: ' . htmlspecialchars($e->getMessage()) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }

        // Display success or error messages
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                 htmlspecialchars($_GET['message']) . 
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
                 htmlspecialchars($_GET['message']) . 
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        ?>

        <div class="text-center mb-5">
            <h1 class="display-4">Leave Management System</h1>
            <p class="lead text-muted">Manage Employees and Leave Applications</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-hover shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h4 class="card-title">Add Employee</h4>
                        <p class="card-text text-muted">Register a new employee in the system</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover shadow-sm" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                        <h4 class="card-title">Apply for Leave</h4>
                        <p class="card-text text-muted">Submit a new leave application</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover shadow-sm" onclick="window.location.href='view-leaves.php'">
                    <div class="card-body text-center">
                        <i class="fas fa-list-alt fa-3x text-warning mb-3"></i>
                        <h4 class="card-title">View Leaves</h4>
                        <p class="card-text text-muted">Check leave history and status</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Employee Modal -->
        <div class="modal fade" id="addEmployeeModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addEmployeeForm" method="POST" action="add-employee.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="HR">Human Resources</option>
                                        <option value="IT">Information Technology</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Sales">Sales</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hire Date</label>
                                <input type="date" class="form-control" name="hire_date" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Add Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apply Leave Modal -->
        <div class="modal fade" id="applyLeaveModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply for Leave</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="applyLeaveForm" method="POST" action="apply-leave.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" name="employee_id" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Leave Type</label>
                                    <select class="form-select" name="leave_type" required>
                                        <option value="">Select Leave Type</option>
                                        <option value="1">Annual Leave</option>
                                        <option value="2">Sick Leave</option>
                                        <option value="3">Compassionate Leave</option>
                                        <option value="4">Maternity Leave</option>
                                        <option value="5">Paternity Leave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Leave</label>
                                <textarea class="form-control" name="reason" rows="3" required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success">Submit Leave Application</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>