<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'nx-domain-db';
        $this->db_name = $_ENV['DB_NAME'] ?? 'nx_domain_db';
        $this->username = $_ENV['DB_USER'] ?? 'nx_user';
        $this->password = $_ENV['DB_PASS'] ?? 'nx_password';
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }
}
?>