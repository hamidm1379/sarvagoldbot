<?php

namespace GoldSalekBot;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * Supports MariaDB and MySQL (MariaDB compatible)
 * Uses PDO with mysql driver which works with both MySQL and MariaDB
 */
class Database
{
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'gold_salek_bot';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $this->connect();
    }

    private function connect()
    {
        // MariaDB/MySQL compatible DSN (mysql: driver works with both)
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false, // Don't use persistent connections to avoid stale connections
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed");
        }
    }

    private function isConnected()
    {
        try {
            if ($this->connection === null) {
                return false;
            }
            // Try a simple query to check if connection is alive
            // Use direct PDO query to avoid recursion
            $this->connection->query("SELECT 1")->closeCursor();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function ensureConnection()
    {
        if (!$this->isConnected()) {
            error_log("Database connection lost, attempting to reconnect...");
            $this->connect();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        $this->ensureConnection();
        return $this->connection;
    }

    public function query($sql, $params = [])
    {
        $maxRetries = 2;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $this->ensureConnection();
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                $errorCode = $e->getCode();
                
                // Check if it's a connection lost error (works for both MySQL and MariaDB)
                // Error codes: 2006 (MySQL server has gone away), 2013 (Lost connection)
                $isConnectionError = (
                    $errorCode == 2006 || 
                    $errorCode == 2013 ||
                    strpos($errorMessage, '2006') !== false || 
                    strpos($errorMessage, '2013') !== false ||
                    strpos($errorMessage, 'MySQL server has gone away') !== false ||
                    strpos($errorMessage, 'MariaDB server has gone away') !== false ||
                    strpos($errorMessage, 'Lost connection') !== false ||
                    strpos($errorMessage, 'Connection lost') !== false ||
                    strpos($errorMessage, 'server has gone away') !== false
                );
                
                if ($isConnectionError && $retryCount < $maxRetries) {
                    error_log("Database connection lost, retrying... (Attempt " . ($retryCount + 1) . "/{$maxRetries})");
                    $this->connection = null; // Force reconnection
                    $this->connect();
                    $retryCount++;
                    continue;
                }
                
                error_log("Database query error: " . $errorMessage);
                throw $e;
            }
        }
    }

    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId()
    {
        $this->ensureConnection();
        return $this->connection->lastInsertId();
    }
}

