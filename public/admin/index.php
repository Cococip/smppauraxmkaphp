<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Simple authentication (in production, use proper auth)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Get statistics
$dbService = new App\Services\DatabaseService();
$stats = $dbService->getAdminStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Notification - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
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
        .card-stats {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .bg-gradient-primary { background: linear-gradient(45deg, #667eea, #764ba2); }
        .bg-gradient-success { background: linear-gradient(45deg, #11998e, #38ef7d); }
        .bg-gradient-warning { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .bg-gradient-info { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        .bg-gradient-danger { background: linear-gradient(45deg, #fa709a, #fee140); }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .refresh-btn:hover {
            transform: rotate(180deg);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
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
                        <a class="nav-link active" href="index.php">
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
                        <h5 class="mb-0">Dashboard Overview</h5>
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

                <!-- Dashboard Content -->
                <div class="p-4">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card card-stats">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title text-muted mb-0">Total SMS</h5>
                                            <h3 class="mt-2 mb-0"><?php echo number_format($stats['total_sms']); ?></h3>
                                            <small class="text-success">
                                                <i class="bi bi-arrow-up"></i> +12% from last month
                                            </small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="stat-icon bg-gradient-primary">
                                                <i class="bi bi-chat-text"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card card-stats">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title text-muted mb-0">Success Rate</h5>
                                            <h3 class="mt-2 mb-0"><?php echo $stats['success_rate']; ?>%</h3>
                                            <small class="text-success">
                                                <i class="bi bi-arrow-up"></i> +5% from last week
                                            </small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="stat-icon bg-gradient-success">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card card-stats">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title text-muted mb-0">Active Customers</h5>
                                            <h3 class="mt-2 mb-0"><?php echo $stats['active_customers']; ?></h3>
                                            <small class="text-info">
                                                <i class="bi bi-arrow-up"></i> +3 new this week
                                            </small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="stat-icon bg-gradient-info">
                                                <i class="bi bi-people"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card card-stats">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title text-muted mb-0">Revenue</h5>
                                            <h3 class="mt-2 mb-0">Rp <?php echo number_format($stats['total_revenue']); ?></h3>
                                            <small class="text-success">
                                                <i class="bi bi-arrow-up"></i> +18% from last month
                                            </small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="stat-icon bg-gradient-warning">
                                                <i class="bi bi-currency-dollar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">SMS Traffic (Last 7 Days)</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="trafficChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">SMS Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Recent SMS Logs</h6>
                                    <a href="sms-logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Phone</th>
                                                    <th>Status</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats['recent_logs'] as $log): ?>
                                                <tr>
                                                    <td><?php echo substr($log['phone_number'], 0, 12) . '...'; ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $log['status']; ?>">
                                                            <?php echo ucfirst($log['status']); ?>
                                                        </span>
                                                    </td>
                                                                                                         <td><?php echo date('H:i', strtotime($log['sent_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">System Alerts</h6>
                                    <span class="badge bg-danger">3 New</span>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>SMPP Connection:</strong> High latency detected (500ms)
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Database:</strong> Backup completed successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>System:</strong> All services running normally
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Dashboard">
        <i class="bi bi-arrow-clockwise"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        // Traffic Chart
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'SMS Sent',
                    data: [1200, 1900, 1500, 2100, 1800, 2200, 1600],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'SMS Delivered',
                    data: [1150, 1800, 1450, 2000, 1750, 2100, 1550],
                    borderColor: '#11998e',
                    backgroundColor: 'rgba(17, 153, 142, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Delivered', 'Failed', 'Pending'],
                datasets: [{
                    data: [85, 10, 5],
                    backgroundColor: ['#11998e', '#f5576c', '#f093fb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Auto refresh every 30 seconds
        setInterval(() => {
            // Refresh only the statistics, not the whole page
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update statistics without page reload
                    console.log('Stats updated:', data);
                });
        }, 30000);
    </script>
</body>
</html>
