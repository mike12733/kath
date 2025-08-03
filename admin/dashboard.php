<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM document_requests");
$stmt->execute();
$total_requests = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM document_requests WHERE status = 'pending'");
$stmt->execute();
$pending_requests = $stmt->fetch()['pending'];

$stmt = $pdo->prepare("SELECT COUNT(*) as processing FROM document_requests WHERE status = 'processing'");
$stmt->execute();
$processing_requests = $stmt->fetch()['processing'];

$stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM document_requests WHERE status = 'completed'");
$stmt->execute();
$completed_requests = $stmt->fetch()['completed'];

// Get recent requests
$stmt = $pdo->prepare("
    SELECT dr.*, u.full_name, u.email, dt.name as document_name, dt.fee
    FROM document_requests dr
    JOIN users u ON dr.user_id = u.id
    JOIN document_types dt ON dr.document_type_id = dt.id
    ORDER BY dr.request_date DESC
    LIMIT 10
");
$stmt->execute();
$recent_requests = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Handle notification read
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LNHS Documents Request Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .notification-item {
            border-left: 4px solid #667eea;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> LNHS Admin Portal
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_requests.php"><i class="fas fa-list"></i> Manage Requests</a></li>
                        <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h4>
                        <p class="text-muted">Manage document requests and system operations.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <h5><?php echo $total_requests; ?></h5>
                    <p class="mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h5><?php echo $pending_requests; ?></h5>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-cogs fa-2x mb-2"></i>
                    <h5><?php echo $processing_requests; ?></h5>
                    <p class="mb-0">Processing</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h5><?php echo $completed_requests; ?></h5>
                    <p class="mb-0">Completed</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="manage_requests.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> Manage Requests
                            </a>
                            <a href="manage_users.php" class="btn btn-outline-primary">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="reports.php" class="btn btn-outline-primary">
                                <i class="fas fa-chart-bar"></i> Generate Reports
                            </a>
                            <a href="export_data.php" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted text-center">No new notifications</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-server"></i> System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Database:</strong> 
                            <span class="badge bg-success">Connected</span>
                        </div>
                        <div class="mb-3">
                            <strong>Upload Directory:</strong> 
                            <?php if (is_dir('../uploads/')): ?>
                                <span class="badge bg-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Not Found</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <strong>PHP Version:</strong> 
                            <span class="badge bg-info"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>Server Time:</strong> 
                            <small class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requests)): ?>
                            <p class="text-muted text-center">No requests found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>User</th>
                                            <th>Document</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Fee</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requests as $request): ?>
                                            <tr>
                                                <td>#<?php echo $request['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($request['full_name']); ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_colors = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'approved' => 'success',
                                                        'denied' => 'danger',
                                                        'ready_pickup' => 'primary',
                                                        'completed' => 'secondary'
                                                    ];
                                                    $status_text = ucfirst(str_replace('_', ' ', $request['status']));
                                                    $color = $status_colors[$request['status']];
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?> status-badge">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                <td>₱<?php echo number_format($request['fee'], 2); ?></td>
                                                <td>
                                                    <a href="view_request.php?id=<?php echo $request['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="update_status.php?id=<?php echo $request['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-edit"></i> Update
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="manage_requests.php" class="btn btn-primary">
                                    <i class="fas fa-list"></i> View All Requests
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>