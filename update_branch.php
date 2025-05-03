<?php
session_start();
require_once 'db_connection.php';

// Set header to JSON response
header('Content-Type: application/json');

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
        echo json_encode([
            'success' => false,
            'error' => "All fields are required"
        ]);
        exit;
    }
    
    // Update branch in database
    $stmt = $conn->prepare("UPDATE branch SET branch_name = ?, state = ?, city = ?, zip_code = ?, opening_date = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $branch_name, $state, $city, $zip_code, $opening_date, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Branch updated successfully"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => "Error updating branch: " . $conn->error
        ]);
    }
    
    $stmt->close();
} else {
    // If not a POST request, return error
    echo json_encode([
        'success' => false,
        'error' => "Invalid request method"
    ]);
}
?>