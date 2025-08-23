<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'greenleaf_db';
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prepared statement helper methods
    public function prepare($query) {
        return $this->connection->prepare($query);
    }
    
    public function query($query) {
        return $this->connection->query($query);
    }
    
    public function escape_string($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function insert_id() {
        return $this->connection->insert_id;
    }
    
    public function affected_rows() {
        return $this->connection->affected_rows;
    }
}
?>
