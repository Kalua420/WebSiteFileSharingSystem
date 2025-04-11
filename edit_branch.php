<?php
session_start();
require_once 'db_connection.php';

// Determine if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if it's a POST request with an ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $branchId = $_GET['id'];
    
    // Get form data
    $branch_name = $_POST['branch_name'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $zip_code = $_POST['zip_code'];
    $opening_date = $_POST['opening_date'];

    // Update query
    $updateSql = "UPDATE branch SET branch_name = ?, state = ?, city = ?, zip_code = ?, opening_date = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sssssi", $branch_name, $state, $city, $zip_code, $opening_date, $branchId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Branch updated successfully!';
        
        if ($isAjax) {
            // For AJAX requests, return JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Branch updated successfully!', 'redirect' => 'admin_dashboard.php#branches']);
        } else {
            // For traditional form submission, redirect
            header('Location: admin_dashboard.php#branches');
            exit;
        }
    } else {
        $errorMsg = 'Failed to update branch: ' . $conn->error;
        
        if ($isAjax) {
            // For AJAX requests, return JSON error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        } else {
            // For traditional form submission, set error in session and redirect
            $_SESSION['error'] = $errorMsg;
            header('Location: admin_dashboard.php#branches');
            exit;
        }
    }
    $stmt->close();
} else {
    $errorMsg = 'Invalid request or missing branch ID';
    
    if ($isAjax) {
        // For AJAX requests, return JSON error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    } else {
        // For traditional form submission, set error in session and redirect
        $_SESSION['error'] = $errorMsg;
        header('Location: admin_dashboard.php#branches');
        exit;
    }
}
?>