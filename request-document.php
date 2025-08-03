<?php
require_once 'config/database.php';
require_once 'classes/DocumentRequest.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$docRequest = new DocumentRequest($db);

$message = '';
$error = '';

// Get available document types
$documentTypes = $docRequest->getDocumentTypes();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_type_id = intval($_POST['document_type_id']);
    $purpose = sanitize($_POST['purpose']);
    $preferred_release_date = $_POST['preferred_release_date'] ?? null;
    
    // Validation
    $errors = [];
    
    if (empty($document_type_id) || empty($purpose)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if (!empty($preferred_release_date) && strtotime($preferred_release_date) < strtotime('+3 days')) {
        $errors[] = 'Preferred release date must be at least 3 days from today.';
    }
    
    if (empty($errors)) {
        // Create document request
        $docRequest->user_id = $_SESSION['user_id'];
        $docRequest->document_type_id = $document_type_id;
        $docRequest->purpose = $purpose;
        $docRequest->preferred_release_date = $preferred_release_date;
        
        if ($docRequest->create()) {
            $request_id = $docRequest->id;
            
            // Handle file uploads
            $upload_success = true;
            if (!empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                            'error' => $_FILES['attachments']['error'][$key]
                        ];
                        
                        if (!$docRequest->uploadAttachment($request_id, $file)) {
                            $upload_success = false;
                        }
                    }
                }
            }
            
            if ($upload_success) {
                $message = 'Document request submitted successfully! Request number: ' . $docRequest->request_number;
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Request submitted but some files failed to upload. Please check file size and format.';
            }
        } else {
            $error = 'Failed to submit request. Please try again.';
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
    <title>Request Document - LNHS Documents Request Portal</title>
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
                        <a class="nav-link active" href="request-document.php">
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
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Request Document
                        </h4>
                        <p class="mb-0 mt-2 text-light">
                            Fill out the form below to request official documents from LNHS
                        </p>
                    </div>
                    <div class="card-body">
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

                        <form method="POST" action="" enctype="multipart/form-data" id="requestForm">
                            <div class="mb-4">
                                <label for="document_type_id" class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>Document Type *
                                </label>
                                <select class="form-select" id="document_type_id" name="document_type_id" required>
                                    <option value="">Select document type</option>
                                    <?php foreach ($documentTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" 
                                                data-fee="<?php echo $type['fee']; ?>"
                                                data-processing="<?php echo $type['processing_days']; ?>"
                                                data-requirements="<?php echo htmlspecialchars($type['requirements']); ?>"
                                                <?php echo (isset($_POST['document_type_id']) && $_POST['document_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                            <?php echo $type['name']; ?>
                                            <?php if ($type['fee'] > 0): ?>
                                                - ₱<?php echo number_format($type['fee'], 2); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="documentInfo" class="mt-2 text-muted" style="display: none;">
                                    <div id="processingDays"></div>
                                    <div id="documentFee"></div>
                                    <div id="documentRequirements"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="purpose" class="form-label">
                                    <i class="fas fa-question-circle me-2"></i>Purpose of Request *
                                </label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                          placeholder="Please specify the purpose of your document request..." required><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : ''; ?></textarea>
                                <small class="text-muted">Be specific about why you need this document (e.g., employment, college application, etc.)</small>
                            </div>

                            <div class="mb-4">
                                <label for="preferred_release_date" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Preferred Release Date (Optional)
                                </label>
                                <input type="date" class="form-control" id="preferred_release_date" name="preferred_release_date" 
                                       min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" 
                                       value="<?php echo isset($_POST['preferred_release_date']) ? $_POST['preferred_release_date'] : ''; ?>">
                                <small class="text-muted">Minimum 3 days from today. Leave blank for standard processing time.</small>
                            </div>

                            <div class="mb-4">
                                <label for="attachments" class="form-label">
                                    <i class="fas fa-paperclip me-2"></i>Upload Requirements
                                </label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h5>Drag & Drop Files Here</h5>
                                        <p class="text-muted">or click to browse files</p>
                                        <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                               multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('attachments').click()">
                                            <i class="fas fa-plus me-2"></i>Choose Files
                                        </button>
                                    </div>
                                </div>
                                <div id="fileList" class="mt-3"></div>
                                <small class="text-muted">
                                    Supported formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB per file)<br>
                                    Common requirements: Valid ID, Student ID, Previous certificates
                                </small>
                            </div>

                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-info-circle me-2"></i>Important Notes:</h6>
                                        <ul class="mb-0">
                                            <li>All fields marked with (*) are required</li>
                                            <li>Processing time varies by document type</li>
                                            <li>You will receive email notifications about your request status</li>
                                            <li>Ensure all uploaded files are clear and readable</li>
                                            <li>Contact the registrar's office for any questions</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I confirm that all information provided is accurate and complete *
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Document type selection handler
        document.getElementById('document_type_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('documentInfo');
            
            if (this.value) {
                const fee = selectedOption.dataset.fee;
                const processing = selectedOption.dataset.processing;
                const requirements = selectedOption.dataset.requirements;
                
                document.getElementById('processingDays').innerHTML = 
                    '<strong>Processing Time:</strong> ' + processing + ' business days';
                document.getElementById('documentFee').innerHTML = 
                    '<strong>Fee:</strong> ₱' + parseFloat(fee).toFixed(2);
                document.getElementById('documentRequirements').innerHTML = 
                    '<strong>Requirements:</strong> ' + requirements;
                
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('attachments');
        const fileList = document.getElementById('fileList');

        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            fileList.innerHTML = '';
            Array.from(files).forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded';
                fileItem.innerHTML = `
                    <div>
                        <i class="fas fa-file me-2"></i>
                        <span>${file.name}</span>
                        <small class="text-muted">(${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (index !== i) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            handleFiles(fileInput.files);
        }

        // Form submission
        document.getElementById('requestForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<span class="loading me-2"></span>Submitting...';
            btn.disabled = true;
        });

        // Auto-focus on document type
        document.getElementById('document_type_id').focus();
    </script>
</body>
</html>