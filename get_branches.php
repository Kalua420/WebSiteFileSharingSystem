<?php
require_once 'db_connection.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Add detailed logging
error_log("get_branches.php called with params: " . json_encode($_GET));

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    error_log("Database connection successful");

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        error_log("Fetching single branch with ID: $id");

        $stmt = $conn->prepare("SELECT * FROM branch WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $branch = $result->fetch_assoc();

        if ($branch) {
            echo json_encode($branch);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Branch not found']);
        }

        $stmt->close();
    } else {
        // Default: get all branches
        $branches = [];

        if (isset($_GET['unassigned_only']) && $_GET['unassigned_only'] === 'true') {
            error_log("Fetching unassigned branches only");
            $query = "SELECT * FROM branch 
                      WHERE id NOT IN (
                          SELECT DISTINCT bid FROM manager 
                          WHERE bid IS NOT NULL
                      )
                      ORDER BY branch_name";
        } else {
            error_log("Fetching all branches");
            $query = "SELECT * FROM branch ORDER BY branch_name";
        }

        error_log("Executing query: $query");
        $result = $conn->query($query);

        if ($result) {
            $count = $result->num_rows;
            error_log("Query returned $count rows");
            
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
            echo json_encode($branches);
        } else {
            error_log("Query error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch branches: ' . $conn->error]);
        }
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>