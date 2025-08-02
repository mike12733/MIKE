<?php
// LNHS Documents Request Portal Installation Script

// Check if already installed
if (file_exists('config/installed.txt')) {
    die('System is already installed. Remove config/installed.txt to reinstall.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbUsername = $_POST['db_username'] ?? '';
    $dbPassword = $_POST['db_password'] ?? '';
    $dbName = $_POST['db_name'] ?? 'lnhs_documents_portal';
    
    if (empty($dbUsername) || empty($dbName)) {
        $error = 'Database username and name are required.';
    } else {
        try {
            // Test database connection
            $pdo = new PDO(
                "mysql:host=$dbHost;charset=utf8mb4",
                $dbUsername,
                $dbPassword,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");
            
            // Import database schema
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
            
            // Update config file
            $configContent = file_get_contents('config/database.php');
            $configContent = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$dbHost');", $configContent);
            $configContent = str_replace("define('DB_USERNAME', 'root');", "define('DB_USERNAME', '$dbUsername');", $configContent);
            $configContent = str_replace("define('DB_PASSWORD', '');", "define('DB_PASSWORD', '$dbPassword');", $configContent);
            $configContent = str_replace("define('DB_NAME', 'lnhs_documents_portal');", "define('DB_NAME', '$dbName');", $configContent);
            
            file_put_contents('config/database.php', $configContent);
            
            // Create installed marker
            file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));
            
            $success = 'Installation completed successfully! You can now login with:<br><strong>Email:</strong> admin@lnhs.edu.ph<br><strong>Password:</strong> password';
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - LNHS Documents Request Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .install-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .install-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
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
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <div class="school-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>LNHS Documents Request Portal</h1>
                <p class="mb-0">Installation Wizard</p>
            </div>
            
            <div class="install-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <h5 class="mb-4"><i class="fas fa-database me-2"></i>Database Configuration</h5>
                        
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_username" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_username" name="db_username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_password" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_password" name="db_password">
                        </div>
                        
                        <div class="mb-4">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="lnhs_documents_portal" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cog me-2"></i>Install System
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Requirements:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>PHP 7.4 or higher</li>
                            <li><i class="fas fa-check text-success me-2"></i>MySQL 5.7 or higher</li>
                            <li><i class="fas fa-check text-success me-2"></i>PDO MySQL extension</li>
                            <li><i class="fas fa-check text-success me-2"></i>File upload support</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>