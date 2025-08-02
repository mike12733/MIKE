<?php
session_start();
require_once 'classes/User.php';

$user = new User();

// Redirect to dashboard if already logged in
if ($user->isLoggedIn()) {
    $currentUser = $user->getCurrentUser();
    if ($currentUser['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LNHS Documents Request Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            margin-top: 50px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .features-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 60px 0;
            margin-top: 80px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffd700;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px;
            margin-right: 10px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <h1 class="hero-title">
                <i class="fas fa-graduation-cap"></i>
                LNHS Documents Request Portal
            </h1>
            <p class="hero-subtitle">
                Request your academic documents online - Fast, Easy, and Secure
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="login-card">
                    <div id="alert-container"></div>
                    
                    <ul class="nav nav-tabs" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="authTabsContent">
                        <!-- Login Form -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <form id="loginForm">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <input type="password" class="form-control" id="loginPassword" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                        </div>

                        <!-- Register Form -->
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <form id="registerForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="userType" class="form-label">
                                            <i class="fas fa-user-tag"></i> Account Type
                                        </label>
                                        <select class="form-control" id="userType" name="user_type" required>
                                            <option value="">Select Type</option>
                                            <option value="student">Current Student</option>
                                            <option value="alumni">Alumni</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="studentId" class="form-label">
                                            <i class="fas fa-id-badge"></i> Student ID
                                        </label>
                                        <input type="text" class="form-control" id="studentId" name="student_id" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="firstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="middleName" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middleName" name="middle_name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="contactNumber" class="form-label">
                                            <i class="fas fa-phone"></i> Contact Number
                                        </label>
                                        <input type="tel" class="form-control" id="contactNumber" name="contact_number">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="course" class="form-label">
                                            <i class="fas fa-book"></i> Course/Program
                                        </label>
                                        <input type="text" class="form-control" id="course" name="course">
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="yearGraduatedDiv" style="display: none;">
                                    <label for="yearGraduated" class="form-label">
                                        <i class="fas fa-calendar"></i> Year Graduated
                                    </label>
                                    <input type="number" class="form-control" id="yearGraduated" name="year_graduated" min="1950" max="2030">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Address
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock"></i> Password
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirmPassword" class="form-label">
                                            <i class="fas fa-lock"></i> Confirm Password
                                        </label>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus"></i> Register
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h4>Online Document Requests</h4>
                        <p>Request certificates and transcripts from anywhere, anytime</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Real-time Tracking</h4>
                        <p>Track your request status from submission to completion</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Instant Notifications</h4>
                        <p>Get notified when your documents are ready for pickup</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide year graduated field based on user type
        document.getElementById('userType').addEventListener('change', function() {
            const yearGraduatedDiv = document.getElementById('yearGraduatedDiv');
            if (this.value === 'alumni') {
                yearGraduatedDiv.style.display = 'block';
                document.getElementById('yearGraduated').required = true;
            } else {
                yearGraduatedDiv.style.display = 'none';
                document.getElementById('yearGraduated').required = false;
            }
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('auth/login_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        if (result.user_type === 'admin') {
                            window.location.href = 'admin/dashboard.php';
                        } else {
                            window.location.href = 'user/dashboard.php';
                        }
                    }, 1500);
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                showAlert('Login failed. Please try again.', 'danger');
            }
        });

        // Register form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match!', 'danger');
                return;
            }
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('auth/register_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Registration successful! You can now login.', 'success');
                    document.getElementById('login-tab').click();
                    this.reset();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                showAlert('Registration failed. Please try again.', 'danger');
            }
        });

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
        }
    </script>
</body>
</html>