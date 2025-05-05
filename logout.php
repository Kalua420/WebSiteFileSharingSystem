<?php
// logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = '';
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $user_role = 'admin';
} elseif (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true) {
    $user_role = 'manager';
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start(); // Start a new session to store the message
$_SESSION['success_message'] = 'You have been successfully logged out.';

header('Location: index.php');
exit();
?>