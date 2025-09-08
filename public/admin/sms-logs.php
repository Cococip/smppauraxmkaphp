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

// Get SMS logs with pagination
$dbService = new App\Services\DatabaseService();
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = $dbService->getAllSmsLogs($limit, $offset);
$totalLogs = $dbService->getTotalSmsLogs();
$totalPages = ceil($totalLogs / $limit);

// Filter by status
$statusFilter = $_GET['status'] ?? '';
if ($statusFilter) {
    $logs = $dbService->getSmsLogsByStatus($statusFilter, $limit, $offset);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Logs - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.0/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-sent { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .filter-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
                        <a class="nav-link active" href="sms-logs.php">
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
                        <h5 class="mb-0">SMS Logs Monitoring</h5>
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
                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Status Filter</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="text-success"><?php echo number_format($dbService->getSmsCountByStatus('sent')); ?></h5>
                                    <small class="text-muted">Sent</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="text-info"><?php echo number_format($dbService->getSmsCountByStatus('delivered')); ?></h5>
                                    <small class="text-muted">Delivered</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="text-danger"><?php echo number_format($dbService->getSmsCountByStatus('failed')); ?></h5>
                                    <small class="text-muted">Failed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="text-warning"><?php echo number_format($dbService->getSmsCountByStatus('pending')); ?></h5>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS Logs Table -->
                    <div class="card table-custom">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">SMS Logs (<?php echo number_format($totalLogs); ?> total)</h6>
                            <div>
                                <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()">
                                    <i class="bi bi-download"></i> Export CSV
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="smsLogsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Message ID</th>
                                            <th>Customer</th>
                                            <th>Phone Number</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Cost</th>
                                            <th>Sent At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo substr($log['message_id'], 0, 12) . '...'; ?></small>
                                            </td>
                                            <td>
                                                <?php echo $log['customer_name'] ?? 'Unknown'; ?>
                                            </td>
                                            <td><?php echo $log['phone_number']; ?></td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      title="<?php echo htmlspecialchars($log['message']); ?>">
                                                    <?php echo htmlspecialchars(substr($log['message'], 0, 50)) . (strlen($log['message']) > 50 ? '...' : ''); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                                    <?php echo ucfirst($log['status']); ?>
                                                </span>
                                            </td>
                                            <td>Rp <?php echo number_format($log['cost'], 0); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($log['sent_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewDetails('<?php echo $log['message_id']; ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">SMS Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.0/js/dataTables.bootstrap5.min.js"></script>
    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            let url = 'sms-logs.php?';
            if (status) url += 'status=' + status + '&';
            if (dateFrom) url += 'date_from=' + dateFrom + '&';
            if (dateTo) url += 'date_to=' + dateTo;
            
            window.location.href = url;
        }

        function viewDetails(messageId) {
            fetch('api/sms-detail.php?message_id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('detailContent').innerHTML = data.html;
                    new bootstrap.Modal(document.getElementById('detailModal')).show();
                });
        }

        function exportToCSV() {
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            let url = 'api/export-sms-logs.php?';
            if (status) url += 'status=' + status + '&';
            if (dateFrom) url += 'date_from=' + dateFrom + '&';
            if (dateTo) url += 'date_to=' + dateTo;
            
            window.location.href = url;
        }

        function refreshTable() {
            location.reload();
        }

        // Auto refresh every 60 seconds
        setInterval(refreshTable, 60000);
    </script>
</body>
</html>



