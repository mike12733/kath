<?php
require_once 'config/database.php';
require_once 'classes/DocumentRequest.php';
require_once 'classes/Notification.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$docRequest = new DocumentRequest($db);
$notification = new Notification($db);

// Get user statistics
$stats = $docRequest->getDashboardStats($_SESSION['user_id']);
$recentRequests = $docRequest->getUserRequests($_SESSION['user_id']);
$recentRequests = array_slice($recentRequests, 0, 5); // Limit to 5 recent requests

// Get unread notifications count
$unreadNotifications = $notification->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LNHS Documents Request Portal</title>
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
                        <a class="nav-link active" href="dashboard.php">
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
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $unreadNotifications; ?></span>
                            <?php endif; ?>
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">
                            <i class="fas fa-home me-2"></i>Welcome back, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                        </h4>
                        <p class="card-text text-muted mb-0">
                            Student ID: <strong><?php echo $_SESSION['student_id']; ?></strong> | 
                            Account Type: <strong><?php echo ucfirst($_SESSION['user_type']); ?></strong>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">Manage your document requests and track their progress from this dashboard.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card primary">
                    <div class="icon text-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="number"><?php echo $stats['total_requests'] ?: 0; ?></div>
                    <div class="label">Total Requests</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card warning">
                    <div class="icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="number"><?php echo $stats['pending'] ?: 0; ?></div>
                    <div class="label">Pending</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card info">
                    <div class="icon text-info">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="number"><?php echo $stats['processing'] ?: 0; ?></div>
                    <div class="label">Processing</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card success">
                    <div class="icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="number"><?php echo $stats['approved'] ?: 0; ?></div>
                    <div class="label">Approved</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card primary">
                    <div class="icon text-primary">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="number"><?php echo $stats['ready_for_pickup'] ?: 0; ?></div>
                    <div class="label">Ready</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card success">
                    <div class="icon text-success">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="number"><?php echo $stats['completed'] ?: 0; ?></div>
                    <div class="label">Completed</div>
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
                            <a href="request-document.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Document Request
                            </a>
                            <a href="track-requests.php" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Track My Requests
                            </a>
                            <a href="notifications.php" class="btn btn-outline-info">
                                <i class="fas fa-bell me-2"></i>View Notifications
                                <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $unreadNotifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-edit me-2"></i>Update Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Document Types -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Available Documents</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-certificate text-primary me-2"></i>
                                Certificate of Enrollment
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-medal text-success me-2"></i>
                                Good Moral Certificate
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-file-alt text-info me-2"></i>
                                Transcript of Records
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-scroll text-warning me-2"></i>
                                Diploma Copy
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-graduation-cap text-danger me-2"></i>
                                Certificate of Graduation
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Requests -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Requests</h5>
                        <a href="track-requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
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
                                                <td><?php echo $request['document_name']; ?></td>
                                                <td><?php echo getStatusBadge($request['status']); ?></td>
                                                <td>
                                                    <small><?php echo formatDate($request['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No requests yet</h5>
                                <p class="text-muted">Start by creating your first document request.</p>
                                <a href="request-document.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Request
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-phone me-2"></i>Contact Information</h6>
                                <p class="mb-2">
                                    <strong>Registrar's Office:</strong><br>
                                    Phone: (02) 8123-4567<br>
                                    Email: registrar@lnhs.edu.ph
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock me-2"></i>Office Hours</h6>
                                <p class="mb-2">
                                    <strong>Monday - Friday</strong><br>
                                    8:00 AM - 5:00 PM<br>
                                    <small class="text-muted">Closed on weekends and holidays</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>LNHS Documents Request Portal</h5>
                    <p class="mb-0">Streamlining document requests for students and alumni.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small>&copy; 2024 Laguna National High School. All rights reserved.</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh page every 30 seconds to show real-time updates
        setTimeout(function() {
            location.reload();
        }, 30000);

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