<?php

namespace Core;

use PDO;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        try {
            $host = $_ENV['DB_HOST'] ?? 'db';
            $db = $_ENV['DB_NAME'] ?? 'bank_db';
            $user = $_ENV['DB_USER'] ?? 'bank_user';
            $pass = $_ENV['DB_PASSWORD'] ?? 'bank_password';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
            ]);
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    private function __clone() {}
    
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
