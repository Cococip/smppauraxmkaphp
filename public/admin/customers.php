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

// Get customers data
$dbService = new App\Services\DatabaseService();
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get customers with pagination
$customers = $dbService->getAllCustomers($limit, $offset);
$totalCustomers = $dbService->getTotalCustomers();
$totalPages = ceil($totalCustomers / $limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Admin Dashboard</title>
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
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-suspended { background: #fff3cd; color: #856404; }
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .customer-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .customer-card:hover {
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
                        <a class="nav-link active" href="customers.php">
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
                        <h5 class="mb-0">Customer Management</h5>
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
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-success"><?php echo number_format($dbService->getCustomerCountByStatus('active')); ?></h5>
                                    <small class="text-muted">Active Customers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-warning"><?php echo number_format($dbService->getCustomerCountByStatus('inactive')); ?></h5>
                                    <small class="text-muted">Inactive Customers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-danger"><?php echo number_format($dbService->getCustomerCountByStatus('suspended')); ?></h5>
                                    <small class="text-muted">Suspended Customers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-info"><?php echo number_format($dbService->getConnection()->fetchOne("SELECT COUNT(*) FROM customers WHERE client_type = 'api'")); ?></h5>
                                    <small class="text-muted">API Only</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-warning"><?php echo number_format($dbService->getConnection()->fetchOne("SELECT COUNT(*) FROM customers WHERE client_type = 'smpp'")); ?></h5>
                                    <small class="text-muted">SMPP Only</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card customer-card text-center">
                                <div class="card-body">
                                    <h5 class="text-success"><?php echo number_format($dbService->getConnection()->fetchOne("SELECT COUNT(*) FROM customers WHERE client_type = 'both'")); ?></h5>
                                    <small class="text-muted">Both API & SMPP</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Customer Actions</h6>
                                <div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                        <i class="bi bi-plus-circle"></i> Add Customer
                                    </button>
                                    <button class="btn btn-outline-success" onclick="exportCustomers()">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customers Table -->
                    <div class="card table-custom">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Customers (<?php echo number_format($totalCustomers); ?> total)</h6>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Company</th>
                                            <th>Client Type</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                        <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($customer['name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['company'] ?? '-'); ?></td>
                                            <td>
                                                <?php 
                                                $clientType = $customer['client_type'] ?? 'api';
                                                $clientTypeClass = '';
                                                $clientTypeIcon = '';
                                                
                                                switch($clientType) {
                                                    case 'api':
                                                        $clientTypeClass = 'bg-info';
                                                        $clientTypeIcon = 'bi-code-slash';
                                                        break;
                                                    case 'smpp':
                                                        $clientTypeClass = 'bg-warning';
                                                        $clientTypeIcon = 'bi-wifi';
                                                        break;
                                                    case 'both':
                                                        $clientTypeClass = 'bg-success';
                                                        $clientTypeIcon = 'bi-layers';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $clientTypeClass; ?>">
                                                    <i class="bi <?php echo $clientTypeIcon; ?> me-1"></i>
                                                    <?php echo strtoupper($clientType); ?>
                                                </span>
                                            </td>
                                            <td>Rp <?php echo number_format($customer['balance'], 0); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $customer['status']; ?>">
                                                    <?php echo ucfirst($customer['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewCustomer(<?php echo $customer['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
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
                                <a class="page-link" href="?page=<?php echo $i; ?>">
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" name="company">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Client Type</label>
                            <select class="form-select" name="client_type" required>
                                <option value="api">API Only</option>
                                <option value="smpp">SMPP Only</option>
                                <option value="both" selected>Both API & SMPP</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                API: REST API access, SMPP: Direct SMPP connection, Both: Access to both
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initial Balance</label>
                            <input type="number" class="form-control" name="balance" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCustomer(id) {
            // Implement view customer details
            alert('View customer ' + id);
        }

        function editCustomer(id) {
            // Implement edit customer
            alert('Edit customer ' + id);
        }

        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                // Implement delete customer
                alert('Delete customer ' + id);
            }
        }

        function saveCustomer() {
            // Implement save customer
            alert('Customer saved successfully!');
            location.reload();
        }

        function exportCustomers() {
            // Implement export customers
            alert('Exporting customers...');
        }

        function refreshTable() {
            location.reload();
        }
    </script>
</body>
</html>

