<?php
// login.php

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$login_type = isset($_GET['type']) ? $_GET['type'] : 'admin'; // Default to admin login

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'];
    $type = $_POST['login_type'];
    
    if ($type === 'admin') {
        // Admin login logic
        $username = $_POST['username'];
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header("Location: admin_dashboard.php?section=dashboard");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        // Manager login logic with email instead of username
        $email = $_POST['email']; // Changed from username to email
        $stmt = $conn->prepare("SELECT * FROM manager WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $manager = $result->fetch_assoc();
            if (password_verify($password, $manager['password'])) {
                $_SESSION['manager_logged_in'] = true;
                $_SESSION['manager_username'] = $manager['username']; // Still store username in session
                $_SESSION['manager_email'] = $email; // Also store email
                $_SESSION['manager_id'] = $manager['id'];
                $_SESSION['branch_id'] = $manager['bid'];
                header("Location: manager_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid email or password.";
                $login_type = 'manager'; // Set the login type for error display
            }
        } else {
            $error_message = "Invalid email or password.";
            $login_type = 'manager'; // Set the login type for error display
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Android File Sharing System - <?php echo ucfirst($login_type); ?> Login</title>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4285f4;
            --primary-dark: #3367d6;
            --accent-color: #34a853;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
            --danger-color: #ea4335;
            --card-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body { 
            font-family: 'Roboto', Arial, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
        }

        .login-container { 
            background: #fff; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 450px; 
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .system-name {
            text-align: center;
            margin-bottom: 15px;
        }

        .system-logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .system-logo i {
            font-size: 48px;
            color: var(--primary-color);
        }

        h1 {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }

        h2 {
            font-size: 20px;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: 500;
        }

        .tab-container {
            display: flex;
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--medium-gray);
        }

        .tab {
            flex: 1;
            padding: 14px;
            text-align: center;
            background-color: var(--light-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab.active {
            background-color: #fff;
            color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .form-group { 
            margin-bottom: 24px; 
            position: relative;
        }

        .form-group label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: 500;
            color: #444;
            font-size: 15px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
        }

        .form-group input { 
            width: 100%; 
            padding: 14px 14px 14px 45px; 
            border: 1px solid var(--medium-gray); 
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.2);
            outline: none;
        }

        .submit-btn { 
            background: var(--primary-color); 
            color: white; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .submit-btn:hover { 
            background: var(--primary-dark); 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background-color: #fdecea;
            color: var(--danger-color);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            border-left: 4px solid var(--danger-color);
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: var(--dark-gray);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="system-logo">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <div class="system-name">
            <h1>Android File Sharing System</h1>
        </div>
        <h2>Welcome Back</h2>
        
        <div class="tab-container">
            <div class="tab <?php echo ($login_type === 'admin') ? 'active' : ''; ?>" onclick="window.location.href='?type=admin'">
                <i class="fas fa-user-shield"></i> Admin
            </div>
            <div class="tab <?php echo ($login_type === 'manager') ? 'active' : ''; ?>" onclick="window.location.href='?type=manager'">
                <i class="fas fa-user-tie"></i> Manager
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="login_type" value="<?php echo $login_type; ?>">
            
            <?php if ($login_type === 'admin'): ?>
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                </div>
            </div>
            
            <div class="forgot-password">
                <a href="#">Forgot password?</a>
            </div>
            
            <button type="submit" name="login" class="submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                Login as <?php echo ucfirst($login_type); ?>
            </button>
        </form>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Android File Sharing System. All rights reserved.
        </div>
    </div>
</body>
</html>