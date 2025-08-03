<?php
require_once 'config/database.php';
require_once 'classes/DocumentRequest.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$docRequest = new DocumentRequest($db);

// Get filter status
$filter_status = $_GET['status'] ?? '';

// Get user's requests
$requests = $docRequest->getUserRequests($_SESSION['user_id']);

// Filter by status if specified
if (!empty($filter_status)) {
    $requests = array_filter($requests, function($request) use ($filter_status) {
        return $request['status'] === $filter_status;
    });
}

// Get statistics for filter tabs
$all_requests = $docRequest->getUserRequests($_SESSION['user_id']);
$status_counts = [
    'all' => count($all_requests),
    'pending' => count(array_filter($all_requests, fn($r) => $r['status'] === 'pending')),
    'processing' => count(array_filter($all_requests, fn($r) => $r['status'] === 'processing')),
    'approved' => count(array_filter($all_requests, fn($r) => $r['status'] === 'approved')),
    'ready_for_pickup' => count(array_filter($all_requests, fn($r) => $r['status'] === 'ready_for_pickup')),
    'completed' => count(array_filter($all_requests, fn($r) => $r['status'] === 'completed')),
    'denied' => count(array_filter($all_requests, fn($r) => $r['status'] === 'denied'))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Requests - LNHS Documents Request Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                LNHS Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request-document.php">
                            <i class="fas fa-file-alt me-1"></i>Request Document
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="track-requests.php">
                            <i class="fas fa-search me-1"></i>Track Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifications
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['first_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-search me-2"></i>Track My Requests
                    </h4>
                    <a href="request-document.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>New Request
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Tabs -->
                <ul class="nav nav-pills mb-4" id="statusFilter">
                    <li class="nav-item">
                        <a class="nav-link <?php echo empty($filter_status) ? 'active' : ''; ?>" 
                           href="track-requests.php">
                            All <span class="badge bg-secondary ms-1"><?php echo $status_counts['all']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'pending' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=pending">
                            Pending <span class="badge bg-warning ms-1"><?php echo $status_counts['pending']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'processing' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=processing">
                            Processing <span class="badge bg-info ms-1"><?php echo $status_counts['processing']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'approved' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=approved">
                            Approved <span class="badge bg-success ms-1"><?php echo $status_counts['approved']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'ready_for_pickup' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=ready_for_pickup">
                            Ready <span class="badge bg-primary ms-1"><?php echo $status_counts['ready_for_pickup']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'completed' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=completed">
                            Completed <span class="badge bg-success ms-1"><?php echo $status_counts['completed']; ?></span>
                        </a>
                    </li>
                    <?php if ($status_counts['denied'] > 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter_status === 'denied' ? 'active' : ''; ?>" 
                           href="track-requests.php?status=denied">
                            Denied <span class="badge bg-danger ms-1"><?php echo $status_counts['denied']; ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Requests Table -->
                <?php if (!empty($requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Fee</th>
                                    <th>Date Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $request['request_number']; ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-file-alt text-primary me-2"></i>
                                            <?php echo $request['document_name']; ?>
                                        </td>
                                        <td>
                                            <small><?php echo substr($request['purpose'], 0, 50); ?><?php echo strlen($request['purpose']) > 50 ? '...' : ''; ?></small>
                                        </td>
                                        <td><?php echo getStatusBadge($request['status']); ?></td>
                                        <td>
                                            <strong>₱<?php echo number_format($request['fee'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo formatDate($request['created_at']); ?><br>
                                                <span class="text-muted"><?php echo date('h:i A', strtotime($request['created_at'])); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning" 
                                                            onclick="editRequest(<?php echo $request['id']; ?>)"
                                                            title="Edit Request">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="cancelRequest(<?php echo $request['id']; ?>)"
                                                            title="Cancel Request">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-chart-bar me-2"></i>Request Summary</h6>
                                    <p class="mb-1">Total Requests: <strong><?php echo count($requests); ?></strong></p>
                                    <p class="mb-1">Total Fees: <strong>₱<?php echo number_format(array_sum(array_column($requests, 'fee')), 2); ?></strong></p>
                                    <p class="mb-0">Showing: <strong><?php echo empty($filter_status) ? 'All' : ucfirst(str_replace('_', ' ', $filter_status)); ?></strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-info-circle me-2"></i>Status Legend</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <small>
                                                <?php echo getStatusBadge('pending'); ?> Pending<br>
                                                <?php echo getStatusBadge('processing'); ?> Processing<br>
                                                <?php echo getStatusBadge('approved'); ?> Approved
                                            </small>
                                        </div>
                                        <div class="col-6">
                                            <small>
                                                <?php echo getStatusBadge('ready_for_pickup'); ?> Ready<br>
                                                <?php echo getStatusBadge('completed'); ?> Completed<br>
                                                <?php echo getStatusBadge('denied'); ?> Denied
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No Requests Found -->
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">
                            <?php if (!empty($filter_status)): ?>
                                No <?php echo str_replace('_', ' ', $filter_status); ?> requests found
                            <?php else: ?>
                                No requests found
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php if (!empty($filter_status)): ?>
                                You don't have any requests with "<?php echo str_replace('_', ' ', $filter_status); ?>" status.
                            <?php else: ?>
                                You haven't submitted any document requests yet.
                            <?php endif; ?>
                        </p>
                        
                        <?php if (!empty($filter_status)): ?>
                            <a href="track-requests.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-list me-2"></i>View All Requests
                            </a>
                        <?php endif; ?>
                        
                        <a href="request-document.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Quick Tips</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="fas fa-clock me-2"></i>Processing Times</h6>
                        <ul class="list-unstyled">
                            <li><small>Certificate of Enrollment: 3 days</small></li>
                            <li><small>Good Moral Certificate: 5 days</small></li>
                            <li><small>Transcript of Records: 7 days</small></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-bell me-2"></i>Notifications</h6>
                        <ul class="list-unstyled">
                            <li><small>You'll receive email updates</small></li>
                            <li><small>Check the notifications page</small></li>
                            <li><small>Status changes are logged</small></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-hand-holding me-2"></i>Pickup</h6>
                        <ul class="list-unstyled">
                            <li><small>Bring valid ID for pickup</small></li>
                            <li><small>Office hours: 8AM - 5PM</small></li>
                            <li><small>Payment upon pickup</small></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRequest(requestId) {
            // Redirect to edit page (would need to be implemented)
            window.location.href = 'edit-request.php?id=' + requestId;
        }

        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this request? This action cannot be undone.')) {
                // Send AJAX request to cancel
                fetch('cancel-request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: requestId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to cancel request: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling request. Please try again.');
                });
            }
        }

        // Auto-refresh every 30 seconds for real-time updates
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Add loading animation to action buttons
        document.querySelectorAll('.btn-outline-primary, .btn-outline-warning, .btn-outline-danger').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.onclick) { // Only for view buttons
                    this.innerHTML = '<span class="loading"></span>';
                }
            });
        });
    </script>
</body>
</html>