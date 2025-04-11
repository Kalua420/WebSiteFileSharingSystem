<?php
session_start();
require_once 'db_connection.php';

// Ensure the request is properly formed
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$type = $_GET['type'];
$id = (int)$_GET['id'];

// Security check - make sure ID is an integer
if (!is_numeric($id) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// Get data based on type
if ($type === 'branch') {
    $stmt = $conn->prepare("SELECT id, branch_name, state, city, zip_code, opening_date FROM branch WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Branch not found']);
        exit;
    }
    
    $branch = $result->fetch_assoc();
    echo json_encode($branch);
    
} elseif ($type === 'manager') {
    $stmt = $conn->prepare("SELECT id, username, email, bid FROM manager WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Manager not found']);
        exit;
    }
    
    $manager = $result->fetch_assoc();
    echo json_encode($manager);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit;
}
?>