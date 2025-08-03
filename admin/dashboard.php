<?php
require_once '../config/database.php';
require_once '../classes/DocumentRequest.php';
require_once '../classes/User.php';
require_once '../classes/Notification.php';

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$docRequest = new DocumentRequest($db);
$user = new User($db);
$notification = new Notification($db);

// Get admin statistics
$stats = $docRequest->getDashboardStats();
$recentRequests = $docRequest->getAllRequests(null, 10);

// Get user statistics
$userStats = [
    'total_users' => count($user->getAllUsers()),
    'students' => count($user->getAllUsers('student')),
    'alumni' => count($user->getAllUsers('alumni')),
    'active_users' => count(array_filter($user->getAllUsers(), fn($u) => $u['status'] === 'active'))
];

// Get pending requests count
$pendingRequests = $docRequest->getAllRequests('pending');
$processingRequests = $docRequest->getAllRequests('processing');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LNHS Documents Request Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                LNHS Admin Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-requests.php">
                            <i class="fas fa-file-alt me-1"></i>Manage Requests
                            <?php if (count($pendingRequests) > 0): ?>
                                <span class="badge bg-warning ms-1"><?php echo count($pendingRequests); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-users.php">
                            <i class="fas fa-users me-1"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i><?php echo $_SESSION['first_name']; ?> (Admin)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="../dashboard.php"><i class="fas fa-eye me-2"></i>User View</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
                        </h4>
                        <p class="card-text text-muted mb-0">
                            Welcome back, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                        </p>
                        <p class="card-text">
                            <small class="text-muted">Manage document requests, users, and system settings from this dashboard.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card primary">
                    <div class="icon text-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="number"><?php echo $stats['total_requests'] ?: 0; ?></div>
                    <div class="label">Total Requests</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card warning">
                    <div class="icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="number"><?php echo $stats['pending'] ?: 0; ?></div>
                    <div class="label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card info">
                    <div class="icon text-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="number"><?php echo $stats['processing'] ?: 0; ?></div>
                    <div class="label">Processing</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card success">
                    <div class="icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="number"><?php echo $stats['completed'] ?: 0; ?></div>
                    <div class="label">Completed</div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card success">
                    <div class="icon text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="number"><?php echo $userStats['total_users']; ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card info">
                    <div class="icon text-info">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="number"><?php echo $userStats['students']; ?></div>
                    <div class="label">Students</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card primary">
                    <div class="icon text-primary">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="number"><?php echo $userStats['alumni']; ?></div>
                    <div class="label">Alumni</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card danger">
                    <div class="icon text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="number"><?php echo $stats['denied'] ?: 0; ?></div>
                    <div class="label">Denied</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="manage-requests.php" class="btn btn-primary">
                                <i class="fas fa-tasks me-2"></i>Manage Requests
                                <?php if (count($pendingRequests) > 0): ?>
                                    <span class="badge bg-light text-primary ms-2"><?php echo count($pendingRequests); ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="manage-users.php" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                            <a href="reports.php" class="btn btn-outline-info">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </a>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>System Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Urgent Notifications -->
                <?php if (count($pendingRequests) > 0 || count($processingRequests) > 5): ?>
                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Attention Required</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingRequests) > 0): ?>
                            <div class="alert alert-warning mb-2">
                                <strong><?php echo count($pendingRequests); ?></strong> pending requests need review
                                <a href="manage-requests.php?status=pending" class="btn btn-sm btn-warning ms-2">Review</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (count($processingRequests) > 5): ?>
                            <div class="alert alert-info mb-0">
                                <strong><?php echo count($processingRequests); ?></strong> requests in processing
                                <a href="manage-requests.php?status=processing" class="btn btn-sm btn-info ms-2">Monitor</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Requests -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Requests</h5>
                        <a href="manage-requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Student</th>
                                            <th>Document</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentRequests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $request['request_number']; ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></strong><br>
                                                        <small class="text-muted"><?php echo $request['student_id']; ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo $request['document_name']; ?></td>
                                                <td><?php echo getStatusBadge($request['status']); ?></td>
                                                <td>
                                                    <small><?php echo formatDate($request['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success" 
                                                                    onclick="quickApprove(<?php echo $request['id']; ?>)"
                                                                    title="Quick Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No recent requests</h5>
                                <p class="text-muted">New requests will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>System Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Request Status Distribution</h6>
                                <div class="progress-stack">
                                    <?php $total = $stats['total_requests'] ?: 1; ?>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-warning" 
                                             style="width: <?php echo ($stats['pending'] / $total) * 100; ?>%"
                                             title="Pending: <?php echo $stats['pending']; ?>">
                                        </div>
                                        <div class="progress-bar bg-info" 
                                             style="width: <?php echo ($stats['processing'] / $total) * 100; ?>%"
                                             title="Processing: <?php echo $stats['processing']; ?>">
                                        </div>
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo ($stats['approved'] / $total) * 100; ?>%"
                                             title="Approved: <?php echo $stats['approved']; ?>">
                                        </div>
                                        <div class="progress-bar bg-primary" 
                                             style="width: <?php echo ($stats['ready_for_pickup'] / $total) * 100; ?>%"
                                             title="Ready: <?php echo $stats['ready_for_pickup']; ?>">
                                        </div>
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo ($stats['completed'] / $total) * 100; ?>%"
                                             title="Completed: <?php echo $stats['completed']; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <small>
                                            <span class="badge bg-warning">Pending</span> <?php echo $stats['pending']; ?><br>
                                            <span class="badge bg-info">Processing</span> <?php echo $stats['processing']; ?><br>
                                            <span class="badge bg-success">Approved</span> <?php echo $stats['approved']; ?>
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small>
                                            <span class="badge bg-primary">Ready</span> <?php echo $stats['ready_for_pickup']; ?><br>
                                            <span class="badge bg-success">Completed</span> <?php echo $stats['completed']; ?><br>
                                            <span class="badge bg-danger">Denied</span> <?php echo $stats['denied']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>User Distribution</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="text-info"><?php echo $userStats['students']; ?></h4>
                                            <small>Students</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="text-primary"><?php echo $userStats['alumni']; ?></h4>
                                            <small>Alumni</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p class="mb-1">
                                        <strong>Total Active Users:</strong> <?php echo $userStats['active_users']; ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>System Uptime:</strong> 
                                        <span class="badge bg-success">Online</span>
                                    </p>
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
        function quickApprove(requestId) {
            if (confirm('Quick approve this request? It will be moved to processing status.')) {
                fetch('update-request-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: requestId,
                        status: 'processing',
                        notes: 'Quick approved from dashboard'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to approve request: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error updating request. Please try again.');
                });
            }
        }

        // Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);

        // Add fade-in animation to stats cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stats-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>