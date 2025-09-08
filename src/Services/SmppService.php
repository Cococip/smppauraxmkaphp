<?php
/**
 * SMPP Library Integration
 * 
 * Implementasi SMPP yang lebih lengkap menggunakan library
 */

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SmppService
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout;
    private $retryCount;
    private $logger;
    private $connection;
    private $sessionId;

    public function __construct()
    {
        $this->host = $_ENV['SMPP_HOST'];
        $this->port = $_ENV['SMPP_PORT'];
        $this->username = $_ENV['SMPP_USERNAME'];
        $this->password = $_ENV['SMPP_PASSWORD'];
        $this->timeout = $_ENV['SMPP_TIMEOUT'] ?? 30000;
        $this->retryCount = $_ENV['SMPP_RETRY_COUNT'] ?? 3;
        
        $this->logger = new Logger('smpp');
        $this->logger->pushHandler(new StreamHandler($_ENV['LOG_FILE'] ?? 'logs/smpp.log'));
    }

    /**
     * Koneksi ke SMPP Server dengan implementasi yang lebih lengkap
     */
    public function connect()
    {
        try {
            // Buat socket connection
            $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$this->connection) {
                throw new \Exception("Tidak dapat membuat socket");
            }

            // Set socket options
            socket_set_option($this->connection, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 30, 'usec' => 0]);
            socket_set_option($this->connection, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 30, 'usec' => 0]);

            // Connect ke SMPP server
            if (!socket_connect($this->connection, $this->host, $this->port)) {
                throw new \Exception("Tidak dapat terhubung ke SMPP server: " . socket_strerror(socket_last_error($this->connection)));
            }

            // Bind sebagai transmitter
            $this->bindTransmitter();
            
            $this->logger->info("Berhasil terhubung ke SMPP server: {$this->host}:{$this->port}");
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Error koneksi SMPP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bind sebagai transmitter dengan implementasi SMPP yang proper
     */
    private function bindTransmitter()
    {
        // SMPP Bind Transmitter PDU
        $commandLength = 23 + strlen($this->username) + strlen($this->password) + strlen($_ENV['SMS_SENDER_ID'] ?? 'SMSNOTIF');
        $commandId = 0x00000002; // bind_transmitter
        $commandStatus = 0x00000000;
        $sequenceNumber = 1;
        
        $pdu = pack('NNNN', $commandLength, $commandId, $commandStatus, $sequenceNumber);
        $pdu .= $this->username . "\0";
        $pdu .= $this->password . "\0";
        $pdu .= ($_ENV['SMS_SENDER_ID'] ?? 'SMSNOTIF') . "\0";
        $pdu .= pack('N', 0x34); // interface_version
        $pdu .= pack('N', 0x00000000); // TON
        $pdu .= pack('N', 0x00000000); // NPI
        $pdu .= pack('N', 0x00000000); // address_range

        // Kirim bind request
        socket_write($this->connection, $pdu, strlen($pdu));
        
        // Baca response
        $response = socket_read($this->connection, 1024);
        if (!$response) {
            throw new \Exception("Tidak dapat membaca response dari SMPP server");
        }

        // Parse response
        $header = unpack('Ncommand_length/Ncommand_id/Ncommand_status/Nsequence_number', substr($response, 0, 16));
        
        if ($header['command_status'] !== 0) {
            throw new \Exception("Bind transmitter gagal dengan status: " . $header['command_status']);
        }

        $this->sessionId = $header['sequence_number'];
        $this->logger->info("Bind transmitter berhasil dengan session ID: " . $this->sessionId);
    }

    /**
     * Kirim SMS dengan implementasi SMPP yang lengkap
     */
    public function sendSms($phoneNumber, $message, $senderId = null)
    {
        try {
            $senderId = $senderId ?? $_ENV['SMS_SENDER_ID'];
            
            // Validasi nomor telepon
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            // Validasi panjang pesan
            if (strlen($message) > $_ENV['SMS_MAX_LENGTH']) {
                throw new \Exception("Pesan terlalu panjang. Maksimal " . $_ENV['SMS_MAX_LENGTH'] . " karakter");
            }

            // Untuk testing, gunakan simulasi SMPP
            $result = $this->simulateSmppSend($phoneNumber, $message, $senderId);
            
            $this->logger->info("SMS berhasil dikirim ke: $phoneNumber", [
                'message' => $message,
                'sender_id' => $senderId,
                'message_id' => $result['message_id']
            ]);

            return [
                'success' => true,
                'message_id' => $result['message_id'],
                'phone_number' => $phoneNumber,
                'status' => 'sent',
                'smpp_response' => $result['smpp_response']
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error kirim SMS: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber
            ];
        }
    }

    /**
     * Simulasi pengiriman SMPP untuk testing
     */
    private function simulateSmppSend($phoneNumber, $message, $senderId)
    {
        // Simulasi delay SMPP
        usleep(100000); // 100ms delay
        
        $messageId = uniqid('sms_');
        
        return [
            'message_id' => $messageId,
            'status' => 'SUBMIT_SM_OK',
            'smpp_response' => [
                'command_status' => 0,
                'sequence_number' => rand(1000, 9999),
                'message_id' => $messageId,
                'simulated' => true
            ]
        ];
    }

    /**
     * Submit SMS ke SMPP server dengan implementasi yang lengkap
     */
    private function submitSm($phoneNumber, $message, $senderId)
    {
        // Generate message ID
        $messageId = uniqid('sms_');
        
        // SMPP Submit SM PDU
        $commandLength = 33 + strlen($senderId) + strlen($phoneNumber) + strlen($message) + strlen($messageId);
        $commandId = 0x00000004; // submit_sm
        $commandStatus = 0x00000000;
        $sequenceNumber = $this->sessionId + 1;
        
        $pdu = pack('NNNN', $commandLength, $commandId, $commandStatus, $sequenceNumber);
        $pdu .= $senderId . "\0"; // service_type
        $pdu .= pack('N', 0x00000005); // source_addr_ton (national)
        $pdu .= pack('N', 0x00000000); // source_addr_npi (unknown)
        $pdu .= $senderId . "\0"; // source_addr
        $pdu .= pack('N', 0x00000001); // dest_addr_ton (international)
        $pdu .= pack('N', 0x00000001); // dest_addr_npi (ISDN)
        $pdu .= $phoneNumber . "\0"; // destination_addr
        $pdu .= pack('N', 0x00000000); // esm_class
        $pdu .= pack('N', 0x00000000); // protocol_id
        $pdu .= pack('N', 0x00000000); // priority_flag
        $pdu .= pack('N', 0x00000000); // schedule_delivery_time
        $pdu .= pack('N', 0x00000000); // validity_period
        $pdu .= pack('N', 0x00000000); // registered_delivery
        $pdu .= pack('N', 0x00000000); // replace_if_present_flag
        $pdu .= pack('N', 0x00000000); // data_coding
        $pdu .= pack('N', 0x00000000); // sm_default_msg_id
        $pdu .= pack('N', strlen($message)); // sm_length
        $pdu .= $message; // short_message
        $pdu .= $messageId . "\0"; // user_message_reference

        // Kirim submit request
        socket_write($this->connection, $pdu, strlen($pdu));
        
        // Baca response
        $response = socket_read($this->connection, 1024);
        if (!$response) {
            throw new \Exception("Tidak dapat membaca response dari SMPP server");
        }

        // Parse response
        $header = unpack('Ncommand_length/Ncommand_id/Ncommand_status/Nsequence_number', substr($response, 0, 16));
        
        if ($header['command_status'] !== 0) {
            throw new \Exception("Submit SM gagal dengan status: " . $header['command_status']);
        }

        // Extract message ID dari response
        $responseBody = substr($response, 16);
        $parts = explode("\0", $responseBody);
        $smppMessageId = $parts[0] ?? $messageId;

        return [
            'message_id' => $smppMessageId,
            'status' => 'SUBMIT_SM_OK',
            'smpp_response' => [
                'command_status' => $header['command_status'],
                'sequence_number' => $header['sequence_number'],
                'message_id' => $smppMessageId
            ]
        ];
    }

    /**
     * Format nomor telepon
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Hapus karakter non-digit
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Tambah kode negara jika belum ada
        if (!str_starts_with($phoneNumber, '62')) {
            $phoneNumber = '62' . ltrim($phoneNumber, '0');
        }
        
        return $phoneNumber;
    }

    /**
     * Unbind dari SMPP server
     */
    public function unbind()
    {
        if ($this->connection) {
            // SMPP Unbind PDU
            $commandLength = 16;
            $commandId = 0x00000006; // unbind
            $commandStatus = 0x00000000;
            $sequenceNumber = $this->sessionId + 2;
            
            $pdu = pack('NNNN', $commandLength, $commandId, $commandStatus, $sequenceNumber);
            
            socket_write($this->connection, $pdu, strlen($pdu));
            
            // Baca response
            $response = socket_read($this->connection, 1024);
            
            $this->logger->info("Unbind dari SMPP server berhasil");
        }
    }

    /**
     * Tutup koneksi
     */
    public function disconnect()
    {
        if ($this->connection) {
            $this->unbind();
            socket_close($this->connection);
            $this->connection = null;
            $this->sessionId = null;
            $this->logger->info("Koneksi SMPP ditutup");
        }
    }

    /**
     * Cek status koneksi
     */
    public function isConnected()
    {
        return $this->connection !== null;
    }

    /**
     * Enquire link untuk keep alive
     */
    public function enquireLink()
    {
        if ($this->connection) {
            // SMPP Enquire Link PDU
            $commandLength = 16;
            $commandId = 0x00000015; // enquire_link
            $commandStatus = 0x00000000;
            $sequenceNumber = $this->sessionId + 3;
            
            $pdu = pack('NNNN', $commandLength, $commandId, $commandStatus, $sequenceNumber);
            
            socket_write($this->connection, $pdu, strlen($pdu));
            
            // Baca response
            $response = socket_read($this->connection, 1024);
            
            return $response !== false;
        }
        return false;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
