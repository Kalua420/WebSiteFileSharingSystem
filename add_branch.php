<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $state = $conn->real_escape_string($_POST['state']);
    $city = $conn->real_escape_string($_POST['city']);
    $zip_code = $conn->real_escape_string($_POST['zip_code']);
    $opening_date = $conn->real_escape_string($_POST['opening_date']);

    $stmt = $conn->prepare("INSERT INTO branch (branch_name, state, city, zip_code, opening_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $branch_name, $state, $city, $zip_code, $opening_date);

    if ($stmt->execute()) {
        $_SESSION['delete_success'] = 'Branch Added successfully!';
    } else {
        $_SESSION['delete_error'] = 'Failed to Add Branch. Please try again.';
    }

    $stmt->close();
    header("Location: admin_dashboard.php?section=branches");
    exit();
}