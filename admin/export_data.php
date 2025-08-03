<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $export_type = $_POST['export_type'];
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $status_filter = $_POST['status'] ?? '';
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(dr.request_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(dr.request_date) <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "dr.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get data
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
    
    if ($export_type === 'excel') {
        exportToExcel($requests, $date_from, $date_to);
    } elseif ($export_type === 'pdf') {
        exportToPDF($requests, $date_from, $date_to);
    }
}

function exportToExcel($requests, $date_from, $date_to) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="lnhs_requests_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<tr style="background-color: #667eea; color: white;">';
    echo '<th>Request ID</th>';
    echo '<th>User Name</th>';
    echo '<th>Email</th>';
    echo '<th>Contact</th>';
    echo '<th>Document</th>';
    echo '<th>Purpose</th>';
    echo '<th>Status</th>';
    echo '<th>Request Date</th>';
    echo '<th>Preferred Date</th>';
    echo '<th>Fee</th>';
    echo '<th>Admin Notes</th>';
    echo '</tr>';
    
    foreach ($requests as $request) {
        echo '<tr>';
        echo '<td>#' . $request['id'] . '</td>';
        echo '<td>' . htmlspecialchars($request['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($request['email']) . '</td>';
        echo '<td>' . htmlspecialchars($request['contact_number']) . '</td>';
        echo '<td>' . htmlspecialchars($request['document_name']) . '</td>';
        echo '<td>' . htmlspecialchars($request['purpose']) . '</td>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $request['status'])) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($request['request_date'])) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($request['preferred_release_date'])) . '</td>';
        echo '<td>₱' . number_format($request['fee'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($request['admin_notes'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit();
}

function exportToPDF($requests, $date_from, $date_to) {
    // Simple HTML to PDF conversion
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<title>LNHS Document Requests Report</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #667eea; color: white; }';
    echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
    echo '.header { text-align: center; margin-bottom: 20px; }';
    echo '.filters { margin-bottom: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="header">';
    echo '<h1>LNHS Document Requests Report</h1>';
    echo '<p>Generated on: ' . date('F d, Y H:i:s') . '</p>';
    echo '</div>';
    
    echo '<div class="filters">';
    if (!empty($date_from) || !empty($date_to)) {
        echo '<p><strong>Date Range:</strong> ';
        if (!empty($date_from)) echo 'From: ' . date('M d, Y', strtotime($date_from));
        if (!empty($date_to)) echo ' To: ' . date('M d, Y', strtotime($date_to));
        echo '</p>';
    }
    echo '<p><strong>Total Requests:</strong> ' . count($requests) . '</p>';
    echo '</div>';
    
    echo '<table>';
    echo '<tr>';
    echo '<th>Request ID</th>';
    echo '<th>User Name</th>';
    echo '<th>Document</th>';
    echo '<th>Status</th>';
    echo '<th>Request Date</th>';
    echo '<th>Fee</th>';
    echo '</tr>';
    
    foreach ($requests as $request) {
        echo '<tr>';
        echo '<td>#' . $request['id'] . '</td>';
        echo '<td>' . htmlspecialchars($request['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($request['document_name']) . '</td>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $request['status'])) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($request['request_date'])) . '</td>';
        echo '<td>₱' . number_format($request['fee'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - LNHS Admin</title>
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
                <a class="nav-link" href="manage_requests.php">
                    <i class="fas fa-list"></i> Manage Requests
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
                        <h4><i class="fas fa-download"></i> Export Data</h4>
                        <p class="text-muted">Export document requests data in Excel or PDF format.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Form -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-export"></i> Export Options</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="export_type" class="form-label">Export Format</label>
                                        <select class="form-control" id="export_type" name="export_type" required>
                                            <option value="">Select format</option>
                                            <option value="excel">Excel (.xls)</option>
                                            <option value="pdf">PDF</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status Filter (Optional)</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="processing">Processing</option>
                                            <option value="approved">Approved</option>
                                            <option value="denied">Denied</option>
                                            <option value="ready_pickup">Ready for Pickup</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_from" class="form-label">From Date (Optional)</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_to" class="form-label">To Date (Optional)</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="row mt-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Export Instructions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-file-excel text-success"></i> Excel Export</h6>
                                <ul class="list-unstyled">
                                    <li>• Complete data with all fields</li>
                                    <li>• Suitable for data analysis</li>
                                    <li>• Can be opened in Excel, Google Sheets</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-file-pdf text-danger"></i> PDF Export</h6>
                                <ul class="list-unstyled">
                                    <li>• Formatted for printing</li>
                                    <li>• Summary view with key fields</li>
                                    <li>• Professional report format</li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Tip:</strong> Use date filters to export specific time periods. Leave filters empty to export all data.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>