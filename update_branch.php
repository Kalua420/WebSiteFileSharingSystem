<?php
session_start();
require_once 'db_connection.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $id = $_POST['id'];
    $branch_name = $_POST['branch_name'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $zip_code = $_POST['zip_code'];
    $opening_date = $_POST['opening_date'];
    
    // Validate inputs
    if (empty($branch_name) || empty($state) || empty($city) || empty($zip_code) || empty($opening_date)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: index.php#branches");
        exit;
    }
    
    // Update branch in database
    $stmt = $conn->prepare("UPDATE branch SET branch_name = ?, state = ?, city = ?, zip_code = ?, opening_date = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $branch_name, $state, $city, $zip_code, $opening_date, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Branch updated successfully";
    } else {
        $_SESSION['error'] = "Error updating branch: " . $conn->error;
    }
    
    $stmt->close();
    
    // Redirect back to branches page
    header("Location: index.php#branches");
    exit;
} else {
    // If not a POST request, redirect to index
    header("Location: index.php");
    exit;
}
?>