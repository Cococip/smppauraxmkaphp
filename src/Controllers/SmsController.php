<?php
/**
 * SMS Controller
 * 
 * Controller untuk menangani request API SMS
 */

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\SmppService;
use App\Services\DatabaseService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SmsController
{
    private $smppService;
    private $dbService;

    public function __construct(SmppService $smppService, DatabaseService $dbService)
    {
        $this->smppService = $smppService;
        $this->dbService = $dbService;
    }

    /**
     * Kirim SMS tunggal
     */
    public function sendSingleSms(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Debug: log request data
            error_log("Request data: " . json_encode($data));
            
            // Validasi input
            if (empty($data['phone_number']) || empty($data['message'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Phone number dan message harus diisi',
                    'debug' => $data
                ], 400);
            }

            // Validasi API key
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            // Cek balance
            $balance = $this->dbService->checkBalance($apiKey);
            if (!$balance || $balance['balance'] < 100) { // Minimum balance 100
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Balance tidak mencukupi'
                ], 402);
            }

            // Kirim SMS
            $result = $this->smppService->sendSms(
                $data['phone_number'],
                $data['message'],
                $data['sender_id'] ?? null
            );

            if ($result['success']) {
                // Simpan log
                $logData = [
                    'message_id' => $result['message_id'],
                    'phone_number' => $result['phone_number'],
                    'message' => $data['message'],
                    'sender_id' => $data['sender_id'] ?? $_ENV['SMS_SENDER_ID'],
                    'status' => 'sent',
                    'cost' => 100, // Harga per SMS
                    'customer_id' => $this->getCustomerId($apiKey)
                ];
                
                $this->dbService->saveSmsLog($logData);
                
                // Update balance
                $this->dbService->updateBalance($apiKey, -100);
            }

            return $this->jsonResponse($response, $result);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim SMS bulk
     */
    public function sendBulkSms(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validasi input
            if (empty($data['phone_numbers']) || empty($data['message'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Phone numbers dan message harus diisi'
                ], 400);
            }

            $phoneNumbers = $data['phone_numbers'];
            if (!is_array($phoneNumbers)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Phone numbers harus berupa array'
                ], 400);
            }

            // Validasi API key
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            // Cek balance
            $balance = $this->dbService->checkBalance($apiKey);
            $totalCost = count($phoneNumbers) * 100; // 100 per SMS
            
            if (!$balance || $balance['balance'] < $totalCost) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Balance tidak mencukupi untuk ' . count($phoneNumbers) . ' SMS'
                ], 402);
            }

            $results = [];
            $successCount = 0;
            $failedCount = 0;

            foreach ($phoneNumbers as $phoneNumber) {
                $result = $this->smppService->sendSms(
                    $phoneNumber,
                    $data['message'],
                    $data['sender_id'] ?? null
                );

                if ($result['success']) {
                    $successCount++;
                    
                    // Simpan log
                    $logData = [
                        'message_id' => $result['message_id'],
                        'phone_number' => $result['phone_number'],
                        'message' => $data['message'],
                        'sender_id' => $data['sender_id'] ?? $_ENV['SMS_SENDER_ID'],
                        'status' => 'sent',
                        'cost' => 100
                    ];
                    
                    $this->dbService->saveSmsLog($logData);
                } else {
                    $failedCount++;
                }

                $results[] = $result;
            }

            // Update balance
            $this->dbService->updateBalance($apiKey, -($successCount * 100));

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => "Bulk SMS selesai. Berhasil: $successCount, Gagal: $failedCount",
                'total_sent' => count($phoneNumbers),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cek status SMS
     */
    public function checkSmsStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $messageId = $args['message_id'];
            
            // Validasi API key
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            // Ambil status dari database
            $sql = "SELECT sl.* FROM sms_logs sl 
                    JOIN customers c ON sl.customer_id = c.id 
                    WHERE sl.message_id = ? AND c.api_key = ?";
            
            $result = $this->dbService->getConnection()->fetchAssociative($sql, [$messageId, $apiKey]);
            
            if (!$result) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Message ID tidak ditemukan'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cek balance
     */
    public function checkBalance(Request $request, Response $response): Response
    {
        try {
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            $balance = $this->dbService->checkBalance($apiKey);
            
            if (!$balance) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key tidak valid'
                ], 401);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'balance' => $balance['balance'],
                    'status' => $balance['status']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil riwayat SMS
     */
    public function getSmsHistory(Request $request, Response $response): Response
    {
        try {
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            $limit = (int)($request->getQueryParams()['limit'] ?? 50);
            $offset = (int)($request->getQueryParams()['offset'] ?? 0);

            $history = $this->dbService->getSmsHistory($apiKey, $limit, $offset);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $history,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => count($history)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cek status koneksi SMPP
     */
    public function checkSmppStatus(Request $request, Response $response): Response
    {
        try {
            $apiKey = $request->getHeaderLine('X-API-Key');
            if (empty($apiKey)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'API Key diperlukan'
                ], 401);
            }

            $smppService = $this->smppService;
            $isConnected = $smppService->isConnected();
            
            $status = [
                'connected' => $isConnected,
                'host' => $_ENV['SMPP_HOST'],
                'port' => $_ENV['SMPP_PORT'],
                'username' => $_ENV['SMPP_USERNAME'],
                'last_check' => date('Y-m-d H:i:s')
            ];

            if ($isConnected) {
                // Test enquire link
                $enquireResult = $smppService->enquireLink();
                $status['enquire_link'] = $enquireResult;
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk mendapatkan customer ID dari API key
     */
    private function getCustomerId($apiKey)
    {
        try {
            $sql = "SELECT id FROM customers WHERE api_key = ?";
            $result = $this->dbService->getConnection()->fetchAssociative($sql, [$apiKey]);
            return $result ? $result['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper untuk response JSON
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
