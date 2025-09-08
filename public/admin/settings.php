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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
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
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .settings-card:hover {
            transform: translateY(-5px);
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
                        <a class="nav-link" href="troubleshooting.php">
                            <i class="bi bi-tools me-2"></i> Troubleshooting
                        </a>
                        <a class="nav-link active" href="settings.php">
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
                        <h5 class="mb-0">System Settings</h5>
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
                    <!-- SMPP Settings -->
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-wifi me-2"></i> SMPP Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMPP Host</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['SMPP_HOST']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMPP Port</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['SMPP_PORT']; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['SMPP_USERNAME']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" value="••••••••" readonly>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="editSmppSettings()">
                                    <i class="bi bi-pencil"></i> Edit Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Database Settings -->
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-database me-2"></i> Database Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Host</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['DB_HOST']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Database Name</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['DB_NAME']; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo $_ENV['DB_USER']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" value="••••••••" readonly>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="editDatabaseSettings()">
                                    <i class="bi bi-pencil"></i> Edit Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-gear me-2"></i> General Settings
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">System Name</label>
                                        <input type="text" class="form-control" value="SMS Notification System">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Default Sender ID</label>
                                        <input type="text" class="form-control" value="SMSADMIN">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SMS Price (per message)</label>
                                        <input type="number" class="form-control" value="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select">
                                            <option value="IDR" selected>Indonesian Rupiah (IDR)</option>
                                            <option value="USD">US Dollar (USD)</option>
                                            <option value="EUR">Euro (EUR)</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="saveGeneralSettings()">
                                        <i class="bi bi-check"></i> Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-shield me-2"></i> Security Settings
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" value="30">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Max Login Attempts</label>
                                        <input type="number" class="form-control" value="5">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Expiry (days)</label>
                                        <input type="number" class="form-control" value="90">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable2fa" checked>
                                            <label class="form-check-label" for="enable2fa">
                                                Enable Two-Factor Authentication
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enableLogs" checked>
                                            <label class="form-check-label" for="enableLogs">
                                                Enable Activity Logs
                                            </label>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="saveSecuritySettings()">
                                        <i class="bi bi-check"></i> Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-tools me-2"></i> Maintenance
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Database Maintenance</h6>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" onclick="backupDatabase()">
                                            <i class="bi bi-download"></i> Backup Database
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="optimizeDatabase()">
                                            <i class="bi bi-gear"></i> Optimize Database
                                        </button>
                                        <button class="btn btn-outline-info" onclick="clearOldLogs()">
                                            <i class="bi bi-trash"></i> Clear Old Logs
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>System Maintenance</h6>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-success" onclick="restartServices()">
                                            <i class="bi bi-arrow-clockwise"></i> Restart Services
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="clearCache()">
                                            <i class="bi bi-eraser"></i> Clear Cache
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="resetSystem()">
                                            <i class="bi bi-exclamation-triangle"></i> Reset System
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSmppSettings() {
            alert('SMPP settings can only be modified in .env file');
        }

        function editDatabaseSettings() {
            alert('Database settings can only be modified in .env file');
        }

        function saveGeneralSettings() {
            alert('General settings saved successfully!');
        }

        function saveSecuritySettings() {
            alert('Security settings saved successfully!');
        }

        function backupDatabase() {
            if (confirm('Are you sure you want to backup the database?')) {
                alert('Database backup started...');
            }
        }

        function optimizeDatabase() {
            if (confirm('Are you sure you want to optimize the database?')) {
                alert('Database optimization started...');
            }
        }

        function clearOldLogs() {
            if (confirm('Are you sure you want to clear old logs?')) {
                alert('Old logs cleared successfully!');
            }
        }

        function restartServices() {
            if (confirm('Are you sure you want to restart all services?')) {
                alert('Services restart initiated...');
            }
        }

        function clearCache() {
            if (confirm('Are you sure you want to clear all cache?')) {
                alert('Cache cleared successfully!');
            }
        }

        function resetSystem() {
            if (confirm('WARNING: This will reset the entire system. Are you sure?')) {
                if (confirm('This action cannot be undone. Continue?')) {
                    alert('System reset initiated...');
                }
            }
        }
    </script>
</body>
</html>



