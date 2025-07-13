<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Try multiple connection options
        $this->db_name = $_ENV['DB_NAME'] ?? 'nx_domain_db';
        $this->username = $_ENV['DB_USER'] ?? 'nx_user';
        $this->password = $_ENV['DB_PASS'] ?? 'nx_password';
        
        // Try different host options
        $host_options = [
            $_ENV['DB_HOST'] ?? null,
            'nx-domain-db',
            'nx-domain-mysql',
            'localhost',
            '127.0.0.1',
            'host.docker.internal'
        ];
        
        foreach ($host_options as $host) {
            if ($host && $this->tryConnection($host)) {
                $this->host = $host;
                break;
            }
        }
    }
    
    private function tryConnection($host) {
        try {
            $test_conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                ]
            );
            $test_conn = null;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getConnection() {
        if (!$this->host) {
            echo "Database connection failed: Could not find working database host.<br>";
            echo "Tried: nx-domain-db, nx-domain-mysql, localhost, 127.0.0.1, host.docker.internal<br>";
            echo "Please check Docker configuration.";
            return null;
        }
        
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
            echo "<!-- Connected to database via: " . $this->host . " -->\n";
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            $this->conn = null;
        }
        
        return $this->conn;
    }
}
?>