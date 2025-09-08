<?php
/**
 * Database Setup Script
 * 
 * Script untuk membuat tabel database dan data awal
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\DatabaseService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    echo "🚀 Memulai setup database...\n";
    
    // Buat direktori logs jika belum ada
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
        echo "✅ Direktori logs berhasil dibuat\n";
    }
    
    // Inisialisasi database service
    $dbService = new DatabaseService();
    
    // Buat tabel
    echo "📋 Membuat tabel database...\n";
    $result = $dbService->createTables();
    
    if ($result) {
        echo "✅ Tabel database berhasil dibuat\n";
        
        // Insert data awal
        echo "📝 Menambahkan data awal...\n";
        
        // Insert sample customer
        $customerData = [
            'name' => 'Demo Customer',
            'email' => 'demo@smsnotif.com',
            'phone' => '081234567890',
            'company' => 'Demo Company',
            'api_key' => 'demo_api_key_123456789',
            'balance' => 10000.00,
            'status' => 'active'
        ];
        
        $customerId = $dbService->saveCustomer($customerData);
        echo "✅ Customer demo berhasil ditambahkan (ID: $customerId)\n";
        
        // Insert sample pricing
        $connection = $dbService->getConnection();
        $connection->executeStatement("
            INSERT INTO pricing (country_code, price_per_sms, currency) VALUES 
            ('62', 100.00, 'IDR'),
            ('65', 150.00, 'SGD'),
            ('60', 120.00, 'MYR')
            ON DUPLICATE KEY UPDATE price_per_sms = VALUES(price_per_sms)
        ");
        echo "✅ Data pricing berhasil ditambahkan\n";
        
        echo "\n🎉 Setup database selesai!\n";
        echo "\n📋 Informasi Login Demo:\n";
        echo "API Key: demo_api_key_123456789\n";
        echo "Balance: Rp 10.000,00\n";
        echo "\n🔗 Test API:\n";
        echo "curl -X POST http://localhost:8000/api/v1/sms/send \\\n";
        echo "  -H 'Content-Type: application/json' \\\n";
        echo "  -H 'X-API-Key: demo_api_key_123456789' \\\n";
        echo "  -d '{\"phone_number\":\"081234567890\",\"message\":\"Test SMS\"}'\n";
        
    } else {
        echo "❌ Gagal membuat tabel database\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}



