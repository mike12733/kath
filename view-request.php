<?php
require_once 'config/database.php';
require_once 'classes/DocumentRequest.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$docRequest = new DocumentRequest($db);

$request_id = intval($_GET['id']);
$request = $docRequest->getRequestById($request_id);

// Check if request exists and belongs to the user (or if admin)
if (!$request || ($request['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
    redirect('track-requests.php');
}

// Get attachments
$attachments = $docRequest->getRequestAttachments($request_id);

// Status timeline
$statuses = [
    'pending' => 'Request Submitted',
    'processing' => 'Under Review',
    'approved' => 'Request Approved',
    'ready_for_pickup' => 'Ready for Pickup',
    'completed' => 'Completed',
    'denied' => 'Request Denied'
];

$current_status = $request['status'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - LNHS Documents Request Portal</title>
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
                        <a class="nav-link" href="track-requests.php">
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
        <div class="row mb-3">
            <div class="col-12">
                <a href="track-requests.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Requests
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Request Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Request Details
                        </h4>
                        <p class="mb-0 mt-2 text-light">
                            Request #<?php echo $request['request_number']; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Request Number:</td>
                                        <td><?php echo $request['request_number']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Document Type:</td>
                                        <td><?php echo $request['document_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Status:</td>
                                        <td><?php echo getStatusBadge($request['status']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Fee:</td>
                                        <td><strong>₱<?php echo number_format($request['fee'], 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar me-2"></i>Dates</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Date Requested:</td>
                                        <td><?php echo formatDateTime($request['created_at']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Preferred Release:</td>
                                        <td>
                                            <?php if ($request['preferred_release_date']): ?>
                                                <?php echo formatDate($request['preferred_release_date']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($request['processed_at']): ?>
                                    <tr>
                                        <td class="fw-bold">Processed:</td>
                                        <td><?php echo formatDateTime($request['processed_at']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="fw-bold">Processing Time:</td>
                                        <td><?php echo $request['processing_days']; ?> business days</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6><i class="fas fa-comment me-2"></i>Purpose</h6>
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($request['purpose'])); ?>
                            </div>
                        </div>

                        <?php if ($request['admin_notes']): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-sticky-note me-2"></i>Admin Notes</h6>
                            <div class="alert alert-info">
                                <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['denial_reason']): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Denial Reason</h6>
                            <div class="alert alert-warning">
                                <?php echo nl2br(htmlspecialchars($request['denial_reason'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Requirements -->
                        <div class="mb-4">
                            <h6><i class="fas fa-list me-2"></i>Requirements</h6>
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($request['requirements'])); ?>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <?php if (!empty($attachments)): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-paperclip me-2"></i>Uploaded Files</h6>
                            <div class="row">
                                <?php foreach ($attachments as $attachment): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-file me-2"></i>
                                                    <small><?php echo $attachment['file_name']; ?></small><br>
                                                    <small class="text-muted">
                                                        <?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB
                                                    </small>
                                                </div>
                                                <a href="<?php echo $attachment['file_path']; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Request Timeline
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="status-timeline">
                            <?php foreach ($statuses as $status => $label): ?>
                                <?php 
                                $is_active = false;
                                $is_current = false;
                                
                                switch ($current_status) {
                                    case 'pending':
                                        $is_active = $status === 'pending';
                                        $is_current = $status === 'pending';
                                        break;
                                    case 'processing':
                                        $is_active = in_array($status, ['pending', 'processing']);
                                        $is_current = $status === 'processing';
                                        break;
                                    case 'approved':
                                        $is_active = in_array($status, ['pending', 'processing', 'approved']);
                                        $is_current = $status === 'approved';
                                        break;
                                    case 'ready_for_pickup':
                                        $is_active = in_array($status, ['pending', 'processing', 'approved', 'ready_for_pickup']);
                                        $is_current = $status === 'ready_for_pickup';
                                        break;
                                    case 'completed':
                                        $is_active = in_array($status, ['pending', 'processing', 'approved', 'ready_for_pickup', 'completed']);
                                        $is_current = $status === 'completed';
                                        break;
                                    case 'denied':
                                        if ($status === 'pending') $is_active = true;
                                        if ($status === 'processing') $is_active = true;
                                        $is_current = $status === 'denied';
                                        break;
                                }
                                
                                if ($current_status === 'denied' && !in_array($status, ['pending', 'processing', 'denied'])) {
                                    continue; // Skip other statuses if denied
                                }
                                ?>
                                
                                <div class="timeline-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                    <h6 class="mb-1"><?php echo $label; ?></h6>
                                    <small class="text-muted">
                                        <?php if ($is_active): ?>
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            <?php if ($status === $current_status): ?>
                                                Current status
                                            <?php else: ?>
                                                Completed
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <i class="fas fa-circle text-muted me-1"></i>
                                            Pending
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($request['status'] === 'pending'): ?>
                                <button class="btn btn-outline-warning" onclick="editRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-edit me-2"></i>Edit Request
                                </button>
                                <button class="btn btn-outline-danger" onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-times me-2"></i>Cancel Request
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-primary no-print" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Details
                            </button>
                            
                            <?php if ($request['status'] === 'ready_for_pickup'): ?>
                                <div class="alert alert-primary mb-0">
                                    <h6><i class="fas fa-hand-holding me-2"></i>Ready for Pickup</h6>
                                    <small>
                                        Your document is ready! Please visit the registrar's office during office hours.
                                        <br><strong>Office Hours:</strong> 8:00 AM - 5:00 PM
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-phone me-2"></i>Need Help?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Registrar's Office</strong></p>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2"></i>(02) 8123-4567
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>registrar@lnhs.edu.ph
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock me-2"></i>Mon-Fri, 8AM-5PM
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRequest(requestId) {
            window.location.href = 'edit-request.php?id=' + requestId;
        }

        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this request? This action cannot be undone.')) {
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
                        window.location.href = 'track-requests.php';
                    } else {
                        alert('Failed to cancel request: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling request. Please try again.');
                });
            }
        }

        // Auto-refresh every 30 seconds for status updates
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>