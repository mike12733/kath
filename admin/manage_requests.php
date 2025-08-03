<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

// Handle status updates
if (isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $admin_notes = trim($_POST['admin_notes']);
    
    try {
        $pdo->beginTransaction();
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE document_requests SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_status, $admin_notes, $request_id]);
        
        // Get request details for notification
        $stmt = $pdo->prepare("
            SELECT dr.*, u.full_name, u.email, dt.name as document_name 
            FROM document_requests dr 
            JOIN users u ON dr.user_id = u.id 
            JOIN document_types dt ON dr.document_type_id = dt.id 
            WHERE dr.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        // Create notification for user
        $notification_title = 'Request Status Updated';
        $notification_message = "Your request for {$request['document_name']} (Request #{$request_id}) has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
        
        if (!empty($admin_notes)) {
            $notification_message .= "\n\nAdmin Notes: " . $admin_notes;
        }
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$request['user_id'], $notification_title, $notification_message, 'portal']);
        
        // Log the action
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'status_update',
            "Updated request #{$request_id} status to {$new_status}",
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        $pdo->commit();
        $success_message = "Request status updated successfully!";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action'])) {
    $selected_requests = $_POST['selected_requests'] ?? [];
    $bulk_status = $_POST['bulk_status'];
    
    if (!empty($selected_requests)) {
        try {
            $pdo->beginTransaction();
            
            $placeholders = str_repeat('?,', count($selected_requests) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE document_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$bulk_status], $selected_requests));
            
            // Log bulk action
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'bulk_status_update',
                "Bulk updated " . count($selected_requests) . " requests to {$bulk_status}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $pdo->commit();
            $success_message = "Bulk status update completed successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error in bulk update: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$document_filter = $_GET['document_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if (!empty($document_filter)) {
    $where_conditions[] = "dr.document_type_id = ?";
    $params[] = $document_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(dr.request_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(dr.request_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get requests with filters
$query = "
    SELECT dr.*, u.full_name, u.email, u.contact_number, dt.name as document_name, dt.fee
    FROM document_requests dr
    JOIN users u ON dr.user_id = u.id
    JOIN document_types dt ON dr.document_type_id = dt.id
    $where_clause
    ORDER BY dr.request_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get document types for filter
$stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
$stmt->execute();
$document_types = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - LNHS Admin</title>
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
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> LNHS Admin Portal
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4><i class="fas fa-list"></i> Manage Document Requests</h4>
                        <p class="text-muted">View and manage all document requests from students and alumni.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-section">
            <h5><i class="fas fa-filter"></i> Filters</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="denied" <?php echo $status_filter === 'denied' ? 'selected' : ''; ?>>Denied</option>
                        <option value="ready_pickup" <?php echo $status_filter === 'ready_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="document_type" class="form-label">Document Type</label>
                    <select class="form-control" id="document_type" name="document_type">
                        <option value="">All Documents</option>
                        <?php foreach ($document_types as $doc_type): ?>
                            <option value="<?php echo $doc_type['id']; ?>" <?php echo $document_filter == $doc_type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doc_type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Bulk Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="bulkForm">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="bulk_status" class="form-label">Update Status To:</label>
                            <select class="form-control" id="bulk_status" name="bulk_status" required>
                                <option value="">Select Status</option>
                                <option value="processing">Processing</option>
                                <option value="approved">Approved</option>
                                <option value="denied">Denied</option>
                                <option value="ready_pickup">Ready for Pickup</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="bulk_action" class="btn btn-warning" onclick="return confirm('Are you sure you want to update the selected requests?')">
                                <i class="fas fa-edit"></i> Update Selected
                            </button>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="text-muted">Selected: <span id="selectedCount">0</span></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table"></i> Document Requests (<?php echo count($requests); ?> results)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <p class="text-muted text-center">No requests found matching your criteria.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Request ID</th>
                                    <th>User</th>
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
                                        <td>
                                            <input type="checkbox" name="selected_requests[]" value="<?php echo $request['id']; ?>" 
                                                   class="form-check-input request-checkbox">
                                        </td>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['contact_number']); ?></small>
                                        </td>
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
                                            <div class="btn-group" role="group">
                                                <a href="view_request.php?id=<?php echo $request['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $request['id']; ?>" 
                                                        title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
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

    <!-- Update Status Modals -->
    <?php foreach ($requests as $request): ?>
        <div class="modal fade" id="updateModal<?php echo $request['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Request #<?php echo $request['id']; ?> Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="new_status<?php echo $request['id']; ?>" class="form-label">New Status</label>
                                <select class="form-control" id="new_status<?php echo $request['id']; ?>" name="new_status" required>
                                    <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $request['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="denied" <?php echo $request['status'] === 'denied' ? 'selected' : ''; ?>>Denied</option>
                                    <option value="ready_pickup" <?php echo $request['status'] === 'ready_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                    <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_notes<?php echo $request['id']; ?>" class="form-label">Admin Notes (Optional)</label>
                                <textarea class="form-control" id="admin_notes<?php echo $request['id']; ?>" name="admin_notes" rows="3" 
                                          placeholder="Add any notes or comments..."><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });

        // Update selected count
        document.querySelectorAll('.request-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.request-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected;
        }
    </script>
</body>
</html>