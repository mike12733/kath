<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] === 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get user's requests
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee 
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    WHERE dr.user_id = ? 
    ORDER BY dr.request_date DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$success_message = '';
$error_message = '';

// Handle notification read
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LNHS Documents Request Portal</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        .notification-item {
            border-left: 4px solid #667eea;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> LNHS Documents Portal
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
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
                        <h4><i class="fas fa-home"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h4>
                        <p class="text-muted">Manage your document requests and track their status.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <h5><?php echo count($requests); ?></h5>
                    <p class="mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h5><?php echo count(array_filter($requests, function($r) { return $r['status'] === 'pending'; })); ?></h5>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h5><?php echo count(array_filter($requests, function($r) { return $r['status'] === 'approved'; })); ?></h5>
                    <p class="mb-0">Approved</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-bell fa-2x mb-2"></i>
                    <h5><?php echo count($notifications); ?></h5>
                    <p class="mb-0">Notifications</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Document Request Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> New Document Request</h5>
                    </div>
                    <div class="card-body">
                        <form action="request_document.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type *</label>
                                <select class="form-control" id="document_type" name="document_type_id" required>
                                    <option value="">Select document type</option>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
                                    $stmt->execute();
                                    $document_types = $stmt->fetchAll();
                                    foreach ($document_types as $doc_type) {
                                        echo "<option value='{$doc_type['id']}' data-fee='{$doc_type['fee']}' data-days='{$doc_type['processing_days']}'>";
                                        echo htmlspecialchars($doc_type['name']) . " - ₱" . number_format($doc_type['fee'], 2);
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose of Request *</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required 
                                          placeholder="Please specify the purpose of your request..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="preferred_release_date" class="form-label">Preferred Release Date *</label>
                                <input type="date" class="form-control" id="preferred_release_date" name="preferred_release_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Upload Requirements</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                <small class="text-muted">Upload valid ID or other required documents (PDF, JPG, PNG)</small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Processing Time:</strong> <span id="processing_time">Select document type</span><br>
                                <strong>Fee:</strong> <span id="document_fee">Select document type</span>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-md-6">
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
        </div>

        <!-- Request History -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Request History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <p class="text-muted text-center">No requests found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Document</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Fee</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td>#<?php echo $request['id']; ?></td>
                                                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?></td>
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
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update processing time and fee when document type changes
        document.getElementById('document_type').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fee = selectedOption.dataset.fee || 'Select document type';
            const days = selectedOption.dataset.days || 'Select document type';
            
            document.getElementById('document_fee').textContent = fee === 'Select document type' ? fee : '₱' + parseFloat(fee).toFixed(2);
            document.getElementById('processing_time').textContent = days === 'Select document type' ? days : days + ' business days';
        });
    </script>
</body>
</html>