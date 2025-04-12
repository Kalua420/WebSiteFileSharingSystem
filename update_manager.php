<?php
session_start();
require_once 'db_connection.php';

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Debugging: Log the POST data to see what's being received
error_log('POST data: ' . print_r($_POST, true));

// Validate POST input
if (
    !isset($_POST['id']) || 
    !isset($_POST['username']) || 
    !isset($_POST['email']) || 
    !isset($_POST['bid']) ||
    empty($_POST['username']) || 
    empty($_POST['email'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
    exit;
}

// Sanitize and validate input
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid manager ID.']);
    exit;
}

$username = trim($_POST['username']);
$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
if ($email === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
    exit;
}

$bid = !empty($_POST['bid']) ? filter_var($_POST['bid'], FILTER_VALIDATE_INT) : null;
$password = isset($_POST['password']) && !empty($_POST['password']) ? trim($_POST['password']) : null;

// Check if manager exists
$check = $conn->prepare("SELECT id FROM manager WHERE id = ?");
if (!$check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

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
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssisi", $username, $email, $bid, $hashedPassword, $id);
    } else {
        // Do not update password
        $stmt = $conn->prepare("UPDATE manager SET username = ?, email = ?, bid = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssii", $username, $email, $bid, $id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Manager updated successfully.', 
            'redirect' => 'admin_dashboard.php?section=managers'
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()]);
}

$conn->close();
?>