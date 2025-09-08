<?php
/**
 * API Test Script
 * 
 * Script untuk testing API SMS notification
 */

$baseUrl = 'http://localhost:8000/api/v1';
$apiKey = 'demo_api_key_123456789';

echo "🧪 Testing SMS Notification API\n";
echo "================================\n\n";

// Test 1: Health Check
echo "1. Testing Health Check...\n";
$response = curl_request("$baseUrl/../health", 'GET');
echo "Response: " . $response . "\n\n";

// Test 2: Check Balance
echo "2. Testing Check Balance...\n";
$response = curl_request("$baseUrl/sms/balance", 'GET', null, $apiKey);
echo "Response: " . $response . "\n\n";

// Test 3: Send Single SMS
echo "3. Testing Send Single SMS...\n";
$data = [
    'phone_number' => '081234567890',
    'message' => 'Test SMS dari API - ' . date('Y-m-d H:i:s'),
    'sender_id' => 'SMSNOTIF'
];
$response = curl_request("$baseUrl/sms/send", 'POST', $data, $apiKey);
echo "Response: " . $response . "\n\n";

// Parse response untuk mendapatkan message_id
$responseData = json_decode($response, true);
if (isset($responseData['message_id'])) {
    $messageId = $responseData['message_id'];
    
    // Test 4: Check SMS Status
    echo "4. Testing Check SMS Status...\n";
    $response = curl_request("$baseUrl/sms/status/$messageId", 'GET', null, $apiKey);
    echo "Response: " . $response . "\n\n";
}

// Test 5: Send Bulk SMS
echo "5. Testing Send Bulk SMS...\n";
$data = [
    'phone_numbers' => [
        '081234567890',
        '081234567891',
        '081234567892'
    ],
    'message' => 'Test Bulk SMS dari API - ' . date('Y-m-d H:i:s'),
    'sender_id' => 'SMSNOTIF'
];
$response = curl_request("$baseUrl/sms/send-bulk", 'POST', $data, $apiKey);
echo "Response: " . $response . "\n\n";

// Test 6: Get SMS History
echo "6. Testing Get SMS History...\n";
$response = curl_request("$baseUrl/sms/history?limit=5", 'GET', null, $apiKey);
echo "Response: " . $response . "\n\n";

echo "✅ Testing selesai!\n";

/**
 * Helper function untuk melakukan HTTP request
 */
function curl_request($url, $method = 'GET', $data = null, $apiKey = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = ['Content-Type: application/json'];
    if ($apiKey) {
        $headers[] = "X-API-Key: $apiKey";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        $response = json_encode(['error' => curl_error($ch)]);
    }
    
    curl_close($ch);
    
    return $response;
}



