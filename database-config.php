<?php
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

    // Method to list tables
    public static function listTables() {
        try {
            $conn = self::getConnection();
            $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Table Listing Error: " . $e->getMessage());
            return [];
        }
    }
}

// Diagnostic function
function testDatabaseConnection() {
    try {
        $conn = DatabaseConfig::getConnection();
        echo "Database Connection Successful!\n";

        $tables = DatabaseConfig::listTables();
        echo "Tables in the database:\n";
        foreach ($tables as $table) {
            echo "- " . $table . "\n";
        }
    } catch (Exception $e) {
        echo "Connection Failed: " . $e->getMessage() . "\n";
    }
}

// Uncomment to test
// testDatabaseConnection();