<?php
// Database connection configuration
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Get database details from environment variables
        $this->host = getenv('PGHOST') ?: 'pgsql03-farm1.kinghost.net';
        $this->port = getenv('PGPORT') ?: '5432';
        $this->db_name = getenv('PGDATABASE') ?: 'inspectia';
        $this->username = getenv('PGUSER') ?: 'inspectia';
        $this->password = getenv('PGPASSWORD') ?: 'Excelencia2025@PowerLuis@';

        //$this->host = getenv('PGHOST') ?: 'localhost';
        //$this->port = getenv('PGPORT') ?: '5432';
        //$this->db_name = getenv('PGDATABASE') ?: 'inspectia';
        //$this->username = getenv('PGUSER') ?: 'postgres';
        //$this->password = getenv('PGPASSWORD') ?: '110822';
    }

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
