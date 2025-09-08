<?php
/**
 * Database Service Class
 * 
 * Menangani operasi database untuk sistem SMS notification
 */

namespace App\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DatabaseService
{
    private $connection;
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('database');
        $this->logger->pushHandler(new StreamHandler($_ENV['LOG_FILE'] ?? 'logs/database.log'));
        
        $this->connect();
    }

    /**
     * Koneksi ke database
     */
    private function connect()
    {
        try {
            $connectionParams = [
                'dbname' => $_ENV['DB_NAME'],
                'user' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASS'],
                'host' => $_ENV['DB_HOST'],
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'driver' => 'pdo_mysql',
                'charset' => 'utf8mb4'
            ];

            $this->connection = DriverManager::getConnection($connectionParams);
            $this->logger->info("Berhasil terhubung ke database: {$_ENV['DB_NAME']}");
            
        } catch (\Exception $e) {
            $this->logger->error("Error koneksi database: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Simpan log SMS
     */
    public function saveSmsLog($data)
    {
        try {
            $sql = "INSERT INTO sms_logs (
                message_id, customer_id, phone_number, message, sender_id, 
                status, sent_at, response_data, cost
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->connection->executeStatement($sql, [
                $data['message_id'],
                $data['customer_id'],
                $data['phone_number'],
                $data['message'],
                $data['sender_id'],
                $data['status'],
                date('Y-m-d H:i:s'),
                json_encode($data['response_data'] ?? []),
                $data['cost'] ?? 0
            ]);

            $this->logger->info("Log SMS berhasil disimpan: {$data['message_id']}");
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Error simpan log SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Simpan data customer
     */
    public function saveCustomer($data)
    {
        try {
            $sql = "INSERT INTO customers (
                name, email, phone, company, api_key, 
                balance, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $this->connection->executeStatement($sql, [
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['company'],
                $data['api_key'],
                $data['balance'] ?? 0,
                $data['status'] ?? 'active',
                date('Y-m-d H:i:s')
            ]);

            return $this->connection->lastInsertId();
            
        } catch (\Exception $e) {
            $this->logger->error("Error simpan customer: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cek balance customer
     */
    public function checkBalance($apiKey)
    {
        try {
            $sql = "SELECT balance, status FROM customers WHERE api_key = ? AND status = 'active'";
            $result = $this->connection->fetchAssociative($sql, [$apiKey]);
            
            return $result ?: false;
            
        } catch (\Exception $e) {
            $this->logger->error("Error cek balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update balance customer
     */
    public function updateBalance($apiKey, $amount)
    {
        try {
            $sql = "UPDATE customers SET balance = balance + ? WHERE api_key = ?";
            $this->connection->executeStatement($sql, [$amount, $apiKey]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Error update balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil riwayat SMS
     */
    public function getSmsHistory($apiKey, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT sl.* FROM sms_logs sl 
                    JOIN customers c ON sl.customer_id = c.id 
                    WHERE c.api_key = ? 
                    ORDER BY sl.sent_at DESC 
                    LIMIT ? OFFSET ?";
            
            return $this->connection->fetchAllAssociative($sql, [$apiKey, $limit, $offset]);
            
        } catch (\Exception $e) {
            $this->logger->error("Error ambil riwayat SMS: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buat tabel database
     */
    public function createTables()
    {
        try {
            // Tabel customers
            $this->connection->executeStatement("
                CREATE TABLE IF NOT EXISTS customers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    company VARCHAR(255),
                    api_key VARCHAR(64) UNIQUE NOT NULL,
                    balance DECIMAL(10,2) DEFAULT 0,
                    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Tabel sms_logs
            $this->connection->executeStatement("
                CREATE TABLE IF NOT EXISTS sms_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message_id VARCHAR(64) UNIQUE NOT NULL,
                    customer_id INT,
                    phone_number VARCHAR(20) NOT NULL,
                    message TEXT NOT NULL,
                    sender_id VARCHAR(20),
                    status ENUM('sent', 'delivered', 'failed', 'pending') DEFAULT 'pending',
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    delivered_at TIMESTAMP NULL,
                    response_data JSON,
                    cost DECIMAL(5,2) DEFAULT 0,
                    FOREIGN KEY (customer_id) REFERENCES customers(id)
                )
            ");

            // Tabel pricing
            $this->connection->executeStatement("
                CREATE TABLE IF NOT EXISTS pricing (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    country_code VARCHAR(5) NOT NULL,
                    price_per_sms DECIMAL(5,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'IDR',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $this->logger->info("Tabel database berhasil dibuat");
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Error buat tabel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua SMS logs dengan pagination
     */
    public function getAllSmsLogs($limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT sl.*, c.name as customer_name 
                    FROM sms_logs sl 
                    LEFT JOIN customers c ON sl.customer_id = c.id 
                    ORDER BY sl.sent_at DESC 
                    LIMIT ? OFFSET ?";
            
            return $this->connection->fetchAllAssociative($sql, [(int)$limit, (int)$offset]);
            
        } catch (\Exception $e) {
            $this->logger->error("Error ambil semua SMS logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil total SMS logs
     */
    public function getTotalSmsLogs()
    {
        try {
            return $this->connection->fetchOne("SELECT COUNT(*) FROM sms_logs");
        } catch (\Exception $e) {
            $this->logger->error("Error ambil total SMS logs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil SMS logs berdasarkan status
     */
    public function getSmsLogsByStatus($status, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT sl.*, c.name as customer_name 
                    FROM sms_logs sl 
                    LEFT JOIN customers c ON sl.customer_id = c.id 
                    WHERE sl.status = ? 
                    ORDER BY sl.sent_at DESC 
                    LIMIT ? OFFSET ?";
            
            return $this->connection->fetchAllAssociative($sql, [$status, (int)$limit, (int)$offset]);
            
        } catch (\Exception $e) {
            $this->logger->error("Error ambil SMS logs by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil jumlah SMS berdasarkan status
     */
    public function getSmsCountByStatus($status)
    {
        try {
            return $this->connection->fetchOne("SELECT COUNT(*) FROM sms_logs WHERE status = ?", [$status]);
        } catch (\Exception $e) {
            $this->logger->error("Error ambil SMS count by status: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil statistik untuk admin dashboard
     */
    public function getAdminStats()
    {
        try {
            // Total SMS
            $totalSms = $this->connection->fetchOne("SELECT COUNT(*) FROM sms_logs");
            
            // Success rate
            $successCount = $this->connection->fetchOne("SELECT COUNT(*) FROM sms_logs WHERE status = 'sent'");
            $successRate = $totalSms > 0 ? round(($successCount / $totalSms) * 100, 1) : 0;
            
            // Active customers
            $activeCustomers = $this->connection->fetchOne("SELECT COUNT(*) FROM customers WHERE status = 'active'");
            
            // Total revenue
            $totalRevenue = $this->connection->fetchOne("SELECT SUM(cost) FROM sms_logs WHERE status = 'sent'");
            
            // Recent logs
            $recentLogs = $this->connection->fetchAllAssociative(
                "SELECT phone_number, status, sent_at FROM sms_logs 
                 ORDER BY sent_at DESC LIMIT 10"
            );
            
            return [
                'total_sms' => $totalSms,
                'success_rate' => $successRate,
                'active_customers' => $activeCustomers,
                'total_revenue' => $totalRevenue ?? 0,
                'recent_logs' => $recentLogs
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Error getting admin stats: " . $e->getMessage());
            return [
                'total_sms' => 0,
                'success_rate' => 0,
                'active_customers' => 0,
                'total_revenue' => 0,
                'recent_logs' => []
            ];
        }
    }

    /**
     * Ambil semua customers dengan pagination
     */
    public function getAllCustomers($limit = 20, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM customers ORDER BY created_at DESC LIMIT ? OFFSET ?";
            return $this->connection->fetchAllAssociative($sql, [(int)$limit, (int)$offset]);
        } catch (\Exception $e) {
            $this->logger->error("Error ambil semua customers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil total customers
     */
    public function getTotalCustomers()
    {
        try {
            return $this->connection->fetchOne("SELECT COUNT(*) FROM customers");
        } catch (\Exception $e) {
            $this->logger->error("Error ambil total customers: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil jumlah customers berdasarkan status
     */
    public function getCustomerCountByStatus($status)
    {
        try {
            return $this->connection->fetchOne("SELECT COUNT(*) FROM customers WHERE status = ?", [$status]);
        } catch (\Exception $e) {
            $this->logger->error("Error ambil customer count by status: " . $e->getMessage());
            return 0;
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
