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

// Start output buffering
ob_start();

try {
    // Get database connection
    $conn = DatabaseConfig::getConnection();

    // Fetch leave applications with employee and leave type details
    $stmt = $conn->prepare("
        SELECT 
            la.id,
            e.employee_id,
            e.first_name || ' ' || e.last_name AS employee_name,
            lt.name AS leave_type,
            la.start_date,
            la.end_date,
            la.total_days,
            la.reason,
            la.status,
            la.created_at
        FROM 
            leave_applications la
        JOIN 
            employees e ON la.employee_id = e.id
        JOIN 
            leave_types lt ON la.leave_type_id = lt.id
        ORDER BY 
            la.created_at DESC
    ");
    $stmt->execute();
    $leave_applications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "Unable to fetch leave applications";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Applications</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-approved {
            background-color: #28a745;
            color: #fff;
        }
        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Leave Applications</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="leaveTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Employee Name</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Total Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_applications as $application): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($application['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($application['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($application['leave_type']); ?></td>
                                        <td><?php echo htmlspecialchars($application['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($application['end_date']); ?></td>
                                        <td><?php echo htmlspecialchars($application['total_days']); ?></td>
                                        <td><?php echo htmlspecialchars($application['reason']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                echo match($application['status']) {
                                                    'Pending' => 'status-pending',
                                                    'Approved' => 'status-approved',
                                                    'Rejected' => 'status-rejected',
                                                    default => ''
                                                };
                                                ?>
                                            ">
                                                <?php echo htmlspecialchars($application['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($application['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#leaveTable').DataTable({
                "pageLength": 10,
                "order": [[8, "desc"]]
            });
        });
    </script>
</body>
</html>
<?php 
// Flush output buffer
ob_end_clean(); 
?>