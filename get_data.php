<?php
session_start();
require_once 'db_connection.php';

// Log incoming data for debugging
error_log('UPDATE MANAGER - POST data: ' . print_r($_POST, true));

// Validate POST input
if (
    !isset($_POST['id']) || 
    !isset($_POST['username']) || 
    !isset($_POST['email']) || 
    !isset($_POST['bid'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
    exit;
}

// Convert and validate ID
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid manager ID.']);
    exit;
}

// Sanitize inputs
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$bid = !empty($_POST['bid']) ? (int)$_POST['bid'] : null;
$password = !empty($_POST['password']) ? trim($_POST['password']) : null;

// Validate required fields
if (empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Username and email are required.']);
    exit;
}

// Check if manager exists
$check = $conn->prepare("SELECT id FROM manager WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Manager not found.']);
    exit;
}
$check->close();

// Start building the query
try {
    if (!empty($password)) {
        // Hash password if updating it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE manager SET username = ?, email = ?, bid = ?, password = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ssisi", $username, $email, $bid, $hashedPassword, $id);
    } else {
        // Do not update password
        $stmt = $conn->prepare("UPDATE manager SET username = ?, email = ?, bid = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ssii", $username, $email, $bid, $id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Manager updated successfully.', 
            'redirect' => 'dashboard.php?section=managers'
        ]);
    } else {
        throw new Exception($stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()]);
}

$conn->close();
?>