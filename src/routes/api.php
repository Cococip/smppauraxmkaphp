<?php
/**
 * API Routes
 * 
 * Definisi route untuk API SMS notification
 */

use App\Controllers\SmsController;

// SMS Routes
$app->group('/api/v1/sms', function($group) {
    // Kirim SMS tunggal
    $group->post('/send', [SmsController::class, 'sendSingleSms']);
    
    // Kirim SMS bulk
    $group->post('/send-bulk', [SmsController::class, 'sendBulkSms']);
    
    // Cek status SMS
    $group->get('/status/{message_id}', [SmsController::class, 'checkSmsStatus']);
    
    // Cek balance
    $group->get('/balance', [SmsController::class, 'checkBalance']);
    
    // Riwayat SMS
    $group->get('/history', [SmsController::class, 'getSmsHistory']);
    
    // Cek status SMPP
    $group->get('/smpp-status', [SmsController::class, 'checkSmppStatus']);
});

// Health check
$app->get('/health', function($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
