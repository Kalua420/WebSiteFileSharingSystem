<?php
session_start();
require_once 'db_connection.php';

// Check if manager ID is provided
if (isset($_GET['id'])) {
    $managerId = $_GET['id'];

    // Prepare the SQL query to delete the manager
    $sql = "DELETE FROM `manager` WHERE `manager`.`id` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $managerId);

    if ($stmt->execute()) {
        $_SESSION['delete_success'] = 'Manager deleted successfully!';
    } else {
        $_SESSION['delete_error'] = 'Failed to delete Manager. Please try again.';
    } 

    // Redirect back to the 'Managers' section
    header("Location: admin_dashboard.php?section=managers"); 
    exit();
} else {
    $_SESSION['error'] = 'No manager ID provided.';
    header("Location: admin_dashboard.php?section=managers");
    exit();
}
?>
