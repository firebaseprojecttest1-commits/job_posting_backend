<?php
class Database {
    protected $conn;

    public function __construct() {
        // Suriin kung may lokal na .env file (para sa local development)
        $envFile = __DIR__ . "/../.env";
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
            $host = $env['HOST'] ?? $env['DB_HOST'] ?? 'localhost';
            $port = $env['PORT'] ?? $env['DB_PORT'] ?? 24385;
            $db   = $env['DB'] ?? $env['DB_NAME'] ?? '';
            $user = $env['USER'] ?? $env['DB_USER'] ?? '';
            $pass = $env['PASS'] ?? $env['DB_PASS'] ?? '';
        } else {
            // Kung walang .env (Render / Cloud environment), kunin sa Environment Variables
            $host = getenv('DB_HOST') ?: getenv('HOST');
            $port = getenv('DB_PORT') ?: 24385;
            $db   = getenv('DB_NAME') ?: getenv('DB');
            $user = getenv('DB_USER') ?: getenv('USER');
            $pass = getenv('DB_PASS') ?: getenv('PASS');
        }

        $caCertPath = __DIR__ . '/ca.pem';

        $this->conn = mysqli_init();
        
        // Siguraduhing epektibo ang SSL kung nandon ang ca.pem file
        if (file_exists($caCertPath)) {
            $this->conn->ssl_set(NULL, NULL, $caCertPath, NULL, NULL);
        }

        $this->conn->real_connect($host, $user, $pass, $db, (int)$port, NULL, MYSQLI_CLIENT_SSL);
        
        if ($this->conn->connect_error) {
            die(json_encode([
                'success' => false, 
                'message' => 'Database connection failed: ' . $this->conn->connect_error
            ]));
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>