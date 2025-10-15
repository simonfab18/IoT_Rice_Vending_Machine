<?php
// Database configuration for XAMPP Local Development
define('DB_HOST', 'localhost');
define('DB_NAME', 'rice_dispenser');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Get the database instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get the database connection
    public function getConnection() {
        return $this->conn;
    }
}
?>
