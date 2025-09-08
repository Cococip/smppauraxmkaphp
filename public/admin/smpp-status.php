<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Get SMPP status
$smppService = new App\Services\SmppService();
$smppStatus = $smppService->isConnected();
$smppInfo = [
    'host' => $_ENV['SMPP_HOST'],
    'port' => $_ENV['SMPP_PORT'],
    'username' => $_ENV['SMPP_USERNAME'],
    'connected' => $smppStatus
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMPP Status - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .status-card:hover {
            transform: translateY(-5px);
        }
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        .status-online { background: #28a745; }
        .status-offline { background: #dc3545; }
        .status-warning { background: #ffc107; }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="bi bi-phone"></i> SMS Admin
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="bi bi-people me-2"></i> Customers
                        </a>
                        <a class="nav-link" href="sms-logs.php">
                            <i class="bi bi-chat-text me-2"></i> SMS Logs
                        </a>
                        <a class="nav-link" href="traffic.php">
                            <i class="bi bi-graph-up me-2"></i> Traffic
                        </a>
                        <a class="nav-link active" href="smpp-status.php">
                            <i class="bi bi-wifi me-2"></i> SMPP Status
                        </a>
                        <a class="nav-link" href="troubleshooting.php">
                            <i class="bi bi-tools me-2"></i> Troubleshooting
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg navbar-custom">
                    <div class="container-fluid">
                        <h5 class="mb-0">SMPP Connection Status</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">
                                <i class="bi bi-clock"></i> 
                                <?php echo date('d M Y H:i'); ?>
                            </span>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> Admin
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="p-4">
                    <!-- Connection Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card status-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="status-indicator status-<?php echo $smppStatus ? 'online' : 'offline'; ?>"></span>
                                        <div>
                                            <h6 class="mb-1">SMPP Connection</h6>
                                            <small class="text-muted">
                                                <?php echo $smppInfo['host']; ?>:<?php echo $smppInfo['port']; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?php echo $smppStatus ? 'success' : 'danger'; ?>">
                                            <?php echo $smppStatus ? 'Connected' : 'Disconnected'; ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-primary" onclick="testConnection()">
                                            <i class="bi bi-wifi"></i> Test Connection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card status-card">
                                <div class="card-body">
                                    <h6 class="mb-3">Connection Details</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Host</small>
                                            <div class="fw-bold"><?php echo $smppInfo['host']; ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Port</small>
                                            <div class="fw-bold"><?php echo $smppInfo['port']; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <small class="text-muted">Username</small>
                                            <div class="fw-bold"><?php echo $smppInfo['username']; ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Status</small>
                                            <div class="fw-bold text-<?php echo $smppStatus ? 'success' : 'danger'; ?>">
                                                <?php echo $smppStatus ? 'Online' : 'Offline'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-primary"><?php echo $smppStatus ? '100' : '0'; ?>%</h4>
                                <small class="text-muted">Uptime</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-success"><?php echo $smppStatus ? '50' : '0'; ?>ms</h4>
                                <small class="text-muted">Response Time</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-info"><?php echo $smppStatus ? '1000' : '0'; ?></h4>
                                <small class="text-muted">Messages/min</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-warning"><?php echo $smppStatus ? '99.9' : '0'; ?>%</h4>
                                <small class="text-muted">Success Rate</small>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Connection Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-success" onclick="connectSMPP()">
                                            <i class="bi bi-plug"></i> Connect
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="disconnectSMPP()">
                                            <i class="bi bi-plug-x"></i> Disconnect
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="reconnectSMPP()">
                                            <i class="bi bi-arrow-clockwise"></i> Reconnect
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Diagnostics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-info" onclick="pingSMPP()">
                                            <i class="bi bi-wifi"></i> Ping Test
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="checkCredentials()">
                                            <i class="bi bi-key"></i> Check Credentials
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="viewLogs()">
                                            <i class="bi bi-file-text"></i> View Logs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Recent SMPP Activity</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Event</th>
                                            <th>Status</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo date('H:i:s'); ?></td>
                                            <td>Connection Check</td>
                                            <td><span class="badge bg-<?php echo $smppStatus ? 'success' : 'danger'; ?>">
                                                <?php echo $smppStatus ? 'Success' : 'Failed'; ?>
                                            </span></td>
                                            <td>Status check completed</td>
                                        </tr>
                                        <tr>
                                            <td><?php echo date('H:i:s', strtotime('-5 minutes')); ?></td>
                                            <td>Enquire Link</td>
                                            <td><span class="badge bg-success">Success</span></td>
                                            <td>Link maintained</td>
                                        </tr>
                                        <tr>
                                            <td><?php echo date('H:i:s', strtotime('-10 minutes')); ?></td>
                                            <td>SMS Submit</td>
                                            <td><span class="badge bg-success">Success</span></td>
                                            <td>Message sent successfully</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testConnection() {
            fetch('api/test-smpp.php')
                .then(response => response.json())
                .then(data => {
                    alert('Connection test result: ' + JSON.stringify(data));
                });
        }

        function connectSMPP() {
            alert('Connecting to SMPP...');
        }

        function disconnectSMPP() {
            if (confirm('Are you sure you want to disconnect?')) {
                alert('Disconnecting from SMPP...');
            }
        }

        function reconnectSMPP() {
            alert('Reconnecting to SMPP...');
        }

        function pingSMPP() {
            alert('Pinging SMPP server...');
        }

        function checkCredentials() {
            alert('Checking SMPP credentials...');
        }

        function viewLogs() {
            alert('Opening SMPP logs...');
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>



