<?php
require_once 'config/database.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $user_type = sanitize($_POST['user_type']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $graduation_year = $_POST['graduation_year'] ?? null;
    $course_strand = sanitize($_POST['course_strand']);
    
    // Validation
    $errors = [];
    
    if (empty($student_id) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($user_type)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (!in_array($user_type, ['student', 'alumni'])) {
        $errors[] = 'Please select a valid user type.';
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        // Check if email already exists
        if ($user->emailExists($email)) {
            $error = 'Email address is already registered.';
        } elseif ($user->studentIdExists($student_id)) {
            $error = 'Student ID is already registered.';
        } else {
            // Create new user
            $user->student_id = $student_id;
            $user->email = $email;
            $user->password = $password;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->user_type = $user_type;
            $user->phone = $phone;
            $user->address = $address;
            $user->graduation_year = $graduation_year;
            $user->course_strand = $course_strand;
            
            if ($user->register()) {
                $message = 'Registration successful! You can now login with your credentials.';
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LNHS Documents Request Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="auth-card fade-in">
                        <div class="auth-header">
                            <div class="mb-3">
                                <i class="fas fa-user-plus fa-3x text-primary"></i>
                            </div>
                            <h3>Create Account</h3>
                            <p>Join LNHS Documents Request Portal</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>Student ID *
                                    </label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           placeholder="Enter student ID" required 
                                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="user_type" class="form-label">
                                        <i class="fas fa-users me-2"></i>Account Type *
                                    </label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <option value="">Select type</option>
                                        <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : ''; ?>>Current Student</option>
                                        <option value="alumni" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user me-2"></i>First Name *
                                    </label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           placeholder="Enter first name" required 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user me-2"></i>Last Name *
                                    </label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           placeholder="Enter last name" required 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter email address" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter password" required minlength="6">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirm Password *
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm password" required minlength="6">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Enter phone number" 
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>

                                <div class="col-md-6 mb-3" id="graduationYearDiv">
                                    <label for="graduation_year" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Graduation Year
                                    </label>
                                    <select class="form-select" id="graduation_year" name="graduation_year">
                                        <option value="">Select year</option>
                                        <?php for ($year = date('Y') + 4; $year >= 2000; $year--): ?>
                                            <option value="<?php echo $year; ?>" 
                                                <?php echo (isset($_POST['graduation_year']) && $_POST['graduation_year'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="course_strand" class="form-label">
                                    <i class="fas fa-book me-2"></i>Course/Strand
                                </label>
                                <input type="text" class="form-control" id="course_strand" name="course_strand" 
                                       placeholder="Enter course or strand" 
                                       value="<?php echo isset($_POST['course_strand']) ? htmlspecialchars($_POST['course_strand']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Enter complete address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-primary">Terms and Conditions</a> *
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3" id="registerBtn">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-2">Already have an account?</p>
                            <a href="login.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });

        // Form submission with loading state
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('registerBtn');
            btn.innerHTML = '<span class="loading me-2"></span>Creating Account...';
            btn.disabled = true;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Show/hide graduation year based on user type
        document.getElementById('user_type').addEventListener('change', function() {
            const graduationDiv = document.getElementById('graduationYearDiv');
            const graduationSelect = document.getElementById('graduation_year');
            
            if (this.value === 'alumni') {
                graduationDiv.style.display = 'block';
                graduationSelect.required = true;
            } else if (this.value === 'student') {
                graduationDiv.style.display = 'block';
                graduationSelect.required = false;
            } else {
                graduationDiv.style.display = 'none';
                graduationSelect.required = false;
            }
        });

        // Auto-focus on student ID field
        document.getElementById('student_id').focus();
    </script>
</body>
</html>