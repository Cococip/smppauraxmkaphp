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

// Get system status
$smppService = new App\Services\SmppService();
$dbService = new App\Services\DatabaseService();

// Check SMPP connection
$smppStatus = $smppService->isConnected();
$smppInfo = [
    'host' => $_ENV['SMPP_HOST'],
    'port' => $_ENV['SMPP_PORT'],
    'username' => $_ENV['SMPP_USERNAME'],
    'connected' => $smppStatus
];

// Check database connection
$dbStatus = $dbService->getConnection() ? true : false;

// Get recent errors from logs
$recentErrors = [];
$logFile = $_ENV['LOG_FILE'] ?? 'logs/smpp.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = array_slice(explode("\n", $logContent), -50); // Last 50 lines
    foreach ($lines as $line) {
        if (strpos($line, 'ERROR') !== false) {
            $recentErrors[] = $line;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troubleshooting - Admin Dashboard</title>
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
        .log-container {
            background: #1e1e1e;
            color: #f8f8f2;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
        .log-error { color: #ff6b6b; }
        .log-warning { color: #ffd93d; }
        .log-info { color: #6bcf7f; }
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
                        <a class="nav-link" href="smpp-status.php">
                            <i class="bi bi-wifi me-2"></i> SMPP Status
                        </a>
                        <a class="nav-link active" href="troubleshooting.php">
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
                        <h5 class="mb-0">System Troubleshooting</h5>
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
                    <!-- System Status Overview -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card status-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="status-indicator status-<?php echo $smppStatus ? 'online' : 'offline'; ?>"></span>
                                        <div>
                                            <h6 class="mb-1">SMPP Connection</h6>
                                            <small class="text-muted">
                                                <?php echo $smppInfo['host']; ?>:<?php echo $smppInfo['port']; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="testSmppConnection()">
                                            <i class="bi bi-wifi"></i> Test Connection
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="reconnectSmpp()">
                                            <i class="bi bi-arrow-clockwise"></i> Reconnect
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card status-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="status-indicator status-<?php echo $dbStatus ? 'online' : 'offline'; ?>"></span>
                                        <div>
                                            <h6 class="mb-1">Database Connection</h6>
                                            <small class="text-muted">
                                                <?php echo $_ENV['DB_HOST']; ?>:<?php echo $_ENV['DB_PORT'] ?? 3306; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="testDatabaseConnection()">
                                            <i class="bi bi-database"></i> Test Connection
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="optimizeDatabase()">
                                            <i class="bi bi-gear"></i> Optimize
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card status-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="status-indicator status-online"></span>
                                        <div>
                                            <h6 class="mb-1">API Service</h6>
                                            <small class="text-muted">REST API Running</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="testApiHealth()">
                                            <i class="bi bi-heart-pulse"></i> Health Check
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="clearCache()">
                                            <i class="bi bi-trash"></i> Clear Cache
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-primary"><?php echo number_format(memory_get_usage(true) / 1024 / 1024, 1); ?> MB</h4>
                                <small class="text-muted">Memory Usage</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-success"><?php echo number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3); ?>s</h4>
                                <small class="text-muted">Response Time</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-info"><?php echo number_format($dbService->getTotalSmsLogs()); ?></h4>
                                <small class="text-muted">Total SMS</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-warning"><?php echo count($recentErrors); ?></h4>
                                <small class="text-muted">Recent Errors</small>
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting Tools -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Quick Diagnostics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" onclick="runDiagnostics()">
                                            <i class="bi bi-search"></i> Run Full Diagnostics
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="checkDiskSpace()">
                                            <i class="bi bi-hdd"></i> Check Disk Space
                                        </button>
                                        <button class="btn btn-outline-info" onclick="checkLogFiles()">
                                            <i class="bi bi-file-text"></i> Check Log Files
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="clearOldLogs()">
                                            <i class="bi bi-trash"></i> Clear Old Logs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">System Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-success" onclick="restartServices()">
                                            <i class="bi bi-arrow-clockwise"></i> Restart Services
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="backupDatabase()">
                                            <i class="bi bi-download"></i> Backup Database
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="updateSystem()">
                                            <i class="bi bi-arrow-up-circle"></i> Check Updates
                                        </button>
                                        <button class="btn btn-outline-info" onclick="generateReport()">
                                            <i class="bi bi-file-earmark-text"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Errors -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Recent System Errors</h6>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshErrors()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="clearErrors()">
                                    <i class="bi bi-trash"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="log-container">
                                <?php if (empty($recentErrors)): ?>
                                    <div class="text-success">✓ No recent errors found</div>
                                <?php else: ?>
                                    <?php foreach (array_reverse($recentErrors) as $error): ?>
                                        <div class="log-error"><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Results Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Action Results</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="actionResults">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testSmppConnection() {
            fetch('api/test-smpp.php')
                .then(response => response.json())
                .then(data => {
                    showResults('SMPP Connection Test', data);
                });
        }

        function testDatabaseConnection() {
            fetch('api/test-database.php')
                .then(response => response.json())
                .then(data => {
                    showResults('Database Connection Test', data);
                });
        }

        function testApiHealth() {
            fetch('http://localhost:8000/health')
                .then(response => response.json())
                .then(data => {
                    showResults('API Health Check', data);
                });
        }

        function runDiagnostics() {
            fetch('api/run-diagnostics.php')
                .then(response => response.json())
                .then(data => {
                    showResults('System Diagnostics', data);
                });
        }

        function showResults(title, data) {
            document.getElementById('actionResults').innerHTML = `
                <h6>${title}</h6>
                <pre class="bg-light p-3 rounded">${JSON.stringify(data, null, 2)}</pre>
            `;
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }

        function refreshErrors() {
            location.reload();
        }

        function clearErrors() {
            if (confirm('Are you sure you want to clear all error logs?')) {
                fetch('api/clear-logs.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            // Refresh only the status indicators
            fetch('api/system-status.php')
                .then(response => response.json())
                .then(data => {
                    // Update status indicators
                    console.log('Status updated:', data);
                });
        }, 30000);
    </script>
</body>
</html>



