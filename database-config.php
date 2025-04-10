<?php
// Database Configuration
class DatabaseConfig {
    private static $host = 'dpg-cvn925a4d50c73fv6m70-a';
    private static $port = 5432;
    private static $dbname = 'admin_db_5jq5';
    private static $user = 'admin_db_5jq5_user';
    private static $password = 'zQ7Zey6xTtDtqT99fKgUepfsuEhCjIoZ';

    public static function getConnection() {
        try {
            // Detailed connection string
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
                self::$host, 
                self::$port, 
                self::$dbname,
                self::$user,
                self::$password
            );

            // Increase connection timeout
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10  // 10-second timeout
            ];

            // Attempt connection
            $pdo = new PDO($dsn, null, null, $options);

            return $pdo;
        } catch (PDOException $e) {
            // Detailed error logging
            error_log("Database Connection Error Details:");
            error_log("Error Code: " . $e->getCode());
            error_log("Error Message: " . $e->getMessage());
            
            // Throw a more informative exception
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    // Test connection method
    public static function testConnection() {
        try {
            $conn = self::getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Helper function to log detailed error information
function logConnectionError($e) {
    error_log("Database Connection Error:");
    error_log("Error Code: " . $e->getCode());
    error_log("Error Message: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
}

// Helper Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateEmployeeID() {
    try {
        $conn = DatabaseConfig::getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as count FROM employees");
        $count = $stmt->fetch()['count'] + 1;
        return 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        error_log("Error generating employee ID: " . $e->getMessage());
        return null;
    }
}

// Add a diagnostic function to check database connection
function checkDatabaseConnection() {
    try {
        $conn = DatabaseConfig::getConnection();
        
        // Try a simple query
        $stmt = $conn->query("SELECT 1");
        
        return [
            'status' => true,
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}