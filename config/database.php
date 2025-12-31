<?php
//C:\xampp\htdocs\SISEE\config\database.php
class Database {
    private $host = 'nice-dubinsky.192-99-212-154.plesk.page';
    private $db_name = 'sisegestion';
    private $username = 'siseg';
    private $password = 'NK!Igudh306ameu?';
    private $port = 3306;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        $this->logConnection("Intentando conectar a: {$this->host}:{$this->port}/{$this->db_name}");

        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name . 
                   ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10 // 10 segundos timeout
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->logConnection("Conexión exitosa");
            
        } catch(PDOException $exception) {
            $errorMsg = "Error de conexión: " . $exception->getMessage() . " (Code: " . $exception->getCode() . ")";
            $this->logConnection("ERROR: " . $errorMsg);
            error_log($errorMsg);
            throw new Exception("No se pudo conectar a la base de datos: " . $exception->getMessage());
        }

        return $this->conn;
    }
    
    private function logConnection($message) {
        $logDir = dirname(__DIR__) . '/api/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/database_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
?>