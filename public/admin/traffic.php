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

// Get traffic data
$dbService = new App\Services\DatabaseService();
$period = $_GET['period'] ?? '7d'; // 7d, 30d, 90d
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Analytics - Admin Dashboard</title>
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
        .chart-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .chart-card:hover {
            transform: translateY(-5px);
        }
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
                        <a class="nav-link active" href="traffic.php">
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
                        <h5 class="mb-0">Traffic Analytics</h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group me-3" role="group">
                                <input type="radio" class="btn-check" name="period" id="7d" value="7d" <?php echo $period === '7d' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary btn-sm" for="7d">7 Days</label>
                                
                                <input type="radio" class="btn-check" name="period" id="30d" value="30d" <?php echo $period === '30d' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary btn-sm" for="30d">30 Days</label>
                                
                                <input type="radio" class="btn-check" name="period" id="90d" value="90d" <?php echo $period === '90d' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary btn-sm" for="90d">90 Days</label>
                            </div>
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
                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-primary"><?php echo number_format($dbService->getTotalSmsLogs()); ?></h4>
                                <small class="text-muted">Total SMS Sent</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-success"><?php echo number_format($dbService->getSmsCountByStatus('sent')); ?></h4>
                                <small class="text-muted">Successfully Sent</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-danger"><?php echo number_format($dbService->getSmsCountByStatus('failed')); ?></h4>
                                <small class="text-muted">Failed</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <h4 class="text-info"><?php echo number_format($dbService->getTotalCustomers()); ?></h4>
                                <small class="text-muted">Active Customers</small>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8 mb-3">
                            <div class="card chart-card">
                                <div class="card-header">
                                    <h6 class="mb-0">SMS Traffic Over Time</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="trafficChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="card chart-card">
                                <div class="card-header">
                                    <h6 class="mb-0">SMS Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Charts -->
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="card chart-card">
                                <div class="card-header">
                                    <h6 class="mb-0">Hourly Traffic Pattern</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="hourlyChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="card chart-card">
                                <div class="card-header">
                                    <h6 class="mb-0">Top Customers by SMS Volume</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="customersChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        // Period selector
        document.querySelectorAll('input[name="period"]').forEach(radio => {
            radio.addEventListener('change', function() {
                window.location.href = 'traffic.php?period=' + this.value;
            });
        });

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
                labels: ['Sent', 'Delivered', 'Failed', 'Pending'],
                datasets: [{
                    data: [65, 20, 10, 5],
                    backgroundColor: ['#11998e', '#667eea', '#f5576c', '#f093fb'],
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

        // Hourly Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: ['00', '06', '12', '18', '24'],
                datasets: [{
                    label: 'SMS Volume',
                    data: [50, 120, 300, 250, 80],
                    backgroundColor: '#667eea',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Customers Chart
        const customersCtx = document.getElementById('customersChart').getContext('2d');
        new Chart(customersCtx, {
            type: 'bar',
            data: {
                labels: ['Customer A', 'Customer B', 'Customer C', 'Customer D'],
                datasets: [{
                    label: 'SMS Count',
                    data: [1200, 800, 600, 400],
                    backgroundColor: ['#667eea', '#11998e', '#f5576c', '#f093fb'],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>



