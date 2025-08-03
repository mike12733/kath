<?php
// LNHS Documents Request Portal Installation Script

// Check if already installed
if (file_exists('config/installed.txt')) {
    die('System is already installed. Remove config/installed.txt to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Step 1: System Requirements Check
if ($step == 1) {
    $requirements = [
        'PHP Version (>= 7.4)' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'File Upload Support' => ini_get('file_uploads'),
        'Upload Directory Writable' => is_writable('.') || is_writable('uploads'),
        'Session Support' => function_exists('session_start'),
    ];
    
    $all_passed = true;
    foreach ($requirements as $requirement => $passed) {
        if (!$passed) $all_passed = false;
    }
    
    if ($all_passed && isset($_POST['continue'])) {
        header('Location: install.php?step=2');
        exit();
    }
}

// Step 2: Database Configuration
if ($step == 2) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? 'lnhs_documents_portal';
        $db_user = $_POST['db_user'] ?? 'root';
        $db_pass = $_POST['db_pass'] ?? '';
        
        try {
            // Test database connection
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");
            
            // Read and execute SQL file
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
            
            // Create config file
            $config_content = "<?php
// Database configuration for XAMPP
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Create database connection
function getDBConnection() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return \$pdo;
    } catch(PDOException \$e) {
        die(\"Connection failed: \" . \$e->getMessage());
    }
}

// Initialize database if not exists
function initializeDatabase() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if not exists
        \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \" . DB_NAME);
        \$pdo->exec(\"USE \" . DB_NAME);
        
        return true;
    } catch(PDOException \$e) {
        return false;
    }
}
?>";
            
            // Create config directory if not exists
            if (!is_dir('config')) {
                mkdir('config', 0755, true);
            }
            
            file_put_contents('config/database.php', $config_content);
            
            // Create uploads directory
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            // Mark as installed
            file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));
            
            $success = 'Installation completed successfully!';
            
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LNHS Documents Request Portal - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .install-body {
            padding: 40px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .requirement-item {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .requirement-passed {
            background: #d4edda;
            color: #155724;
        }
        .requirement-failed {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="mb-3">
                <i class="fas fa-graduation-cap fa-3x"></i>
            </div>
            <h2>LNHS Documents Request Portal</h2>
            <p class="mb-0">Installation Wizard</p>
        </div>
        
        <div class="install-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: System Requirements -->
            <?php if ($step == 1): ?>
                <h4><i class="fas fa-cogs"></i> System Requirements Check</h4>
                <p class="text-muted">The system will check if your server meets the requirements.</p>
                
                <div class="mb-4">
                    <?php foreach ($requirements as $requirement => $passed): ?>
                        <div class="requirement-item <?php echo $passed ? 'requirement-passed' : 'requirement-failed'; ?>">
                            <i class="fas fa-<?php echo $passed ? 'check' : 'times'; ?>"></i>
                            <?php echo htmlspecialchars($requirement); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($all_passed): ?>
                    <form method="POST">
                        <button type="submit" name="continue" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continue to Database Setup
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Please fix the failed requirements before continuing.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Step 2: Database Configuration -->
            <?php if ($step == 2): ?>
                <h4><i class="fas fa-database"></i> Database Configuration</h4>
                <p class="text-muted">Configure your database connection settings.</p>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                                <small class="text-muted">Usually 'localhost' for XAMPP</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?php echo $_POST['db_name'] ?? 'lnhs_documents_portal'; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Database Username</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                       value="<?php echo $_POST['db_user'] ?? 'root'; ?>" required>
                                <small class="text-muted">Usually 'root' for XAMPP</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Database Password</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                       value="<?php echo $_POST['db_pass'] ?? ''; ?>">
                                <small class="text-muted">Usually empty for XAMPP</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Default Admin Credentials:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>password</code>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Install System
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>