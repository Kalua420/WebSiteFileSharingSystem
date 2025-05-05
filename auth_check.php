<?php
// auth_check.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirectWithError($location, $message) {
    $_SESSION['error_message'] = $message;
    header("Location: $location");
    exit();
}

// Function to handle user logout
function logoutUser() {
    $user_role = '';
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $user_role = 'admin';
    } elseif (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true) {
        $user_role = 'manager';
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown';
    
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    session_start(); // Start a new session to store the message
    $_SESSION['success_message'] = 'You have been successfully logged out.';
    
    // Redirect to login page
    header('Location: index.php');
    exit();
}

if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) && 
    (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true)) {
    // User is not logged in - redirect to login page
    redirectWithError('index.php', 'Please log in to access this page.');
}

$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Session has expired
    session_unset();     // unset $_SESSION variables
    session_destroy();   // destroy session data
    redirectWithError('index.php', 'Your session has expired due to inactivity. Please log in again.');
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page === 'admin_dashboard.php') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        redirectWithError('index.php', 'Access denied. Admin privileges required for this page.');
    }
} 
elseif ($current_page === 'manager_dashboard.php') {
    if ((!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) && 
        (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
        redirectWithError('index.php', 'Access denied. Manager privileges required for this page.');
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_role = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true ? 'admin' : 'manager';
}
?>