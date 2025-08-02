<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $redirectUrl = $auth->isAdmin() ? 'admin/dashboard.php' : 'dashboard.php';
    header("Location: $redirectUrl");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                $error = 'Please enter both email and password.';
            } else {
                $result = $auth->login($email, $password);
                if ($result['success']) {
                    header("Location: " . $result['redirect']);
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
        }
    } elseif ($_POST['action'] === 'register') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $data = [
                'email' => sanitizeInput($_POST['email']),
                'password' => $_POST['password'],
                'full_name' => sanitizeInput($_POST['full_name']),
                'user_type' => sanitizeInput($_POST['user_type']),
                'contact_number' => sanitizeInput($_POST['contact_number'] ?? ''),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'graduation_year' => !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null,
                'course' => sanitizeInput($_POST['course'] ?? '')
            ];
            
            $result = $auth->register($data);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #667eea;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .nav-tabs {
            border: none;
            margin-bottom: 30px;
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px;
            margin-right: 10px;
            padding: 12px 25px;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="school-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Lipa National High School</p>
                <p class="mb-0">Document Request Portal</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="authTabsContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="login_email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="login_email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="login_password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="login_password" name="password" required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('login_password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">
                                <strong>Default Admin Login:</strong><br>
                                Email: admin@lnhs.edu.ph<br>
                                Password: password
                            </p>
                        </div>
                    </div>
                    
                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_type" class="form-label">User Type</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-users"></i>
                                            </span>
                                            <select class="form-control" id="user_type" name="user_type" required>
                                                <option value="">Select User Type</option>
                                                <option value="student">Student</option>
                                                <option value="alumni">Alumni</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="contact_number" name="contact_number">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="graduation_year" class="form-label">Graduation Year (Alumni only)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar"></i>
                                            </span>
                                            <input type="number" class="form-control" id="graduation_year" name="graduation_year" min="1950" max="<?php echo date('Y'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="course" class="form-label">Course/Program (Alumni only)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-graduation-cap"></i>
                                            </span>
                                            <input type="text" class="form-control" id="course" name="course">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Register
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
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
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
        
        // Show/hide graduation year and course fields based on user type
        document.getElementById('user_type').addEventListener('change', function() {
            const graduationYear = document.getElementById('graduation_year');
            const course = document.getElementById('course');
            
            if (this.value === 'alumni') {
                graduationYear.required = true;
                course.required = true;
            } else {
                graduationYear.required = false;
                course.required = false;
            }
        });
    </script>
</body>
</html>