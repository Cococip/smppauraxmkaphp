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
    <title>Profile - Admin Dashboard</title>
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
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .profile-card:hover {
            transform: translateY(-5px);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 20px;
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
                        <h5 class="mb-0">Admin Profile</h5>
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
                    <div class="row">
                        <div class="col-md-4">
                            <!-- Profile Info -->
                            <div class="card profile-card mb-4">
                                <div class="card-body text-center">
                                    <div class="profile-avatar">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <h5 class="card-title">System Administrator</h5>
                                    <p class="text-muted">Super Admin</p>
                                    <div class="d-grid">
                                        <button class="btn btn-outline-primary" onclick="changeAvatar()">
                                            <i class="bi bi-camera"></i> Change Avatar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="card profile-card">
                                <div class="card-header">
                                    <h6 class="mb-0">Activity Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h5 class="text-primary">150</h5>
                                            <small class="text-muted">Logins</small>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="text-success">24</h5>
                                            <small class="text-muted">Days Active</small>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h5 class="text-warning">5</h5>
                                            <small class="text-muted">Settings Changed</small>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="text-info">12</h5>
                                            <small class="text-muted">Reports Generated</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <!-- Profile Details -->
                            <div class="card profile-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Profile Information</h6>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" value="System Administrator">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" value="admin@smsnotification.com">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" class="form-control" value="+62 812 3456 7890">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Role</label>
                                                    <input type="text" class="form-control" value="Super Administrator" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Bio</label>
                                            <textarea class="form-control" rows="3">System administrator for SMS notification service.</textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="saveProfile()">
                                            <i class="bi bi-check"></i> Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Security Settings -->
                            <div class="card profile-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Security Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" placeholder="Enter current password">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control" placeholder="Enter new password">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" placeholder="Confirm new password">
                                        </div>
                                        <button type="button" class="btn btn-warning" onclick="changePassword()">
                                            <i class="bi bi-key"></i> Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Two-Factor Authentication -->
                            <div class="card profile-card">
                                <div class="card-header">
                                    <h6 class="mb-0">Two-Factor Authentication</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1">SMS Authentication</h6>
                                            <small class="text-muted">Receive verification codes via SMS</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="sms2fa" checked>
                                            <label class="form-check-label" for="sms2fa"></label>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1">Email Authentication</h6>
                                            <small class="text-muted">Receive verification codes via email</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="email2fa">
                                            <label class="form-check-label" for="email2fa"></label>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Authenticator App</h6>
                                            <small class="text-muted">Use Google Authenticator or similar</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="app2fa">
                                            <label class="form-check-label" for="app2fa"></label>
                                        </div>
                                    </div>
                                    <hr>
                                    <button type="button" class="btn btn-info" onclick="setup2FA()">
                                        <i class="bi bi-shield-lock"></i> Setup 2FA
                                    </button>
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
        function changeAvatar() {
            alert('Avatar change feature coming soon!');
        }

        function saveProfile() {
            alert('Profile updated successfully!');
        }

        function changePassword() {
            alert('Password changed successfully!');
        }

        function setup2FA() {
            alert('Two-factor authentication setup wizard will open...');
        }
    </script>
</body>
</html>



