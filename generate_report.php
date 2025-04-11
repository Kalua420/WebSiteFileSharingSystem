<?php
// generate_report.php
// This script generates a report based on the search criteria
// Enhanced to support separate sender_id and receiver_id fields

// Start session and include database connection
session_start();
require_once 'db_connection.php';

// Set headers for PDF or Excel output
// For this example, we'll generate an HTML report that can be printed
header('Content-Type: text/html');
header('Content-Disposition: filename="log_report.html"');

// Get search parameters
$sender_id = isset($_POST['sender_id']) ? trim($_POST['sender_id']) : '';
$receiver_id = isset($_POST['receiver_id']) ? trim($_POST['receiver_id']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Build search query with parameters
$searchConditions = [];
$searchParams = [];
$bindTypes = '';

if (!empty($sender_id)) {
    $searchConditions[] = "l.sender_id = ?";
    $searchParams[] = $sender_id;
    $bindTypes .= 's';
}

if (!empty($receiver_id)) {
    $searchConditions[] = "l.receiver_id = ?";
    $searchParams[] = $receiver_id;
    $bindTypes .= 's';
}

if (!empty($email)) {
    $searchConditions[] = "(s.email LIKE ? OR r.email LIKE ?)";
    $searchParams[] = '%' . $email . '%';
    $searchParams[] = '%' . $email . '%';
    $bindTypes .= 'ss';
}

if (!empty($phone)) {
    $searchConditions[] = "(s.phone LIKE ? OR r.phone LIKE ?)";
    $searchParams[] = '%' . $phone . '%';
    $searchParams[] = '%' . $phone . '%';
    $bindTypes .= 'ss';
}

if (!empty($filename)) {
    $searchConditions[] = "l.filename LIKE ?";
    $searchParams[] = '%' . $filename . '%';
    $bindTypes .= 's';
}

// Build the SQL query with phone numbers included
$sql = "SELECT l.log_id, 
        l.sender_id, s.email as sender_email, s.phone as sender_phone,
        l.receiver_id, r.email as receiver_email, r.phone as receiver_phone,
        l.source_mac, l.destination_mac, l.filename, l.file_size, l.timestamp
        FROM logs l
        LEFT JOIN users s ON l.sender_id = s.id
        LEFT JOIN users r ON l.receiver_id = r.id";

// Add search conditions if any
if (!empty($searchConditions)) {
    $sql .= " WHERE " . implode(" AND ", $searchConditions);
}

// Default ordering by timestamp
$sql .= " ORDER BY l.timestamp DESC";

// Prepare and execute statement
$stmt = $conn->prepare($sql);

if (!empty($searchParams)) {
    $stmt->bind_param($bindTypes, ...$searchParams);
}

$stmt->execute();
$result = $stmt->get_result();
$resultCount = $result->num_rows;

// Count sender and receiver matches for statistics
$senderMatchCount = 0;
$receiverMatchCount = 0;

if ($resultCount > 0) {
    $tempResult = $result->fetch_all(MYSQLI_ASSOC);
    foreach ($tempResult as $row) {
        if (!empty($sender_id) && $row['sender_id'] == $sender_id) {
            $senderMatchCount++;
        }
        if (!empty($receiver_id) && $row['receiver_id'] == $receiver_id) {
            $receiverMatchCount++;
        }
    }
    // Reset result pointer
    $result->data_seek(0);
}

// Get current date and time for report header
$reportDate = date('Y-m-d H:i:s');

// Build report title based on search criteria
$reportTitle = "File Sharing System - Log Report";
$criteria = [];
if (!empty($sender_id)) {
    $criteria[] = "Sender ID: $sender_id";
}
if (!empty($receiver_id)) {
    $criteria[] = "Receiver ID: $receiver_id";
}
if (!empty($email)) $criteria[] = "Email: $email";
if (!empty($phone)) $criteria[] = "Phone: $phone";
if (!empty($filename)) $criteria[] = "Filename: $filename";

// Begin HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Sharing System - Log Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .report-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .report-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .report-meta {
            font-size: 14px;
            color: #666;
        }
        .report-criteria {
            margin: 15px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .report-criteria p {
            margin: 5px 0;
        }
        .report-summary {
            margin-bottom: 20px;
            font-weight: bold;
        }
        .sender-highlight {
            background-color: #e8f4fd;
        }
        .receiver-highlight {
            background-color: #f4e8fd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .log-id {
            color: #e74c3c;
            font-weight: bold;
        }
        .source-mac {
            color: #2ecc71;
        }
        .dest-mac {
            color: #3498db;
        }
        .print-button {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        @media print {
            .print-button {
                display: none;
            }
        }
        .no-results {
            padding: 20px;
            text-align: center;
            background: #f9f9f9;
            border-radius: 4px;
            color: #666;
        }
        .user-info {
            font-size: 13px;
        }
        .sender-id {
            font-weight: bold;
            color: #2980b9;
        }
        .receiver-id {
            font-weight: bold;
            color: #8e44ad;
        }
        .phone-number {
            color: #555;
            font-family: monospace;
        }
        .stats-box {
            margin: 10px 0;
            padding: 8px;
            border-radius: 3px;
        }
        .sender-stats {
            background-color: #e8f4fd;
            border-left: 4px solid #2980b9;
        }
        .receiver-stats {
            background-color: #f4e8fd;
            border-left: 4px solid #8e44ad;
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print Report</button>
    
    <div class="report-header">
        <div class="report-title"><?php echo $reportTitle; ?></div>
        <div class="report-meta">Generated on: <?php echo $reportDate; ?></div>
    </div>
    
    <?php if (!empty($criteria)): ?>
    <div class="report-criteria">
        <strong>Search Criteria:</strong>
        <?php foreach($criteria as $criterion): ?>
            <p><?php echo $criterion; ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="report-summary">
        Total Records Found: <?php echo $resultCount; ?>
        <?php if (!empty($sender_id) && $resultCount > 0): ?>
            <div class="stats-box sender-stats">
                Records where Sender ID <?php echo $sender_id; ?>: <?php echo $senderMatchCount; ?> 
                (<?php echo round(($senderMatchCount/$resultCount)*100); ?>%)
            </div>
        <?php endif; ?>
        <?php if (!empty($receiver_id) && $resultCount > 0): ?>
            <div class="stats-box receiver-stats">
                Records where Receiver ID <?php echo $receiver_id; ?>: <?php echo $receiverMatchCount; ?> 
                (<?php echo round(($receiverMatchCount/$resultCount)*100); ?>%)
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($resultCount > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Source MAC</th>
                <th>Destination MAC</th>
                <th>Filename</th>
                <th>File Size</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php while($log = $result->fetch_assoc()): 
                $isSenderMatch = !empty($sender_id) && $log['sender_id'] == $sender_id;
                $isReceiverMatch = !empty($receiver_id) && $log['receiver_id'] == $receiver_id;
                $rowClass = "";
                if ($isSenderMatch) $rowClass = "sender-highlight";
                if ($isReceiverMatch) $rowClass = "receiver-highlight";
                if ($isSenderMatch && $isReceiverMatch) $rowClass = "sender-highlight receiver-highlight";
            ?>
            <tr class="<?php echo $rowClass; ?>">
                <td class="log-id"><?php echo $log['log_id']; ?></td>
                <td class="user-info">
                    <span class="<?php echo $isSenderMatch ? 'sender-id' : ''; ?>">
                        <?php echo $log['sender_id']; ?>
                    </span>
                    <?php if(!empty($log['sender_email']) || !empty($log['sender_phone'])): ?>
                        <div>
                            <?php if(!empty($log['sender_email'])): ?>
                                <?php echo $log['sender_email']; ?><br>
                            <?php endif; ?>
                            <?php if(!empty($log['sender_phone'])): ?>
                                <span class="phone-number"><?php echo $log['sender_phone']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="user-info">
                    <span class="<?php echo $isReceiverMatch ? 'receiver-id' : ''; ?>">
                        <?php echo $log['receiver_id']; ?>
                    </span>
                    <?php if(!empty($log['receiver_email']) || !empty($log['receiver_phone'])): ?>
                        <div>
                            <?php if(!empty($log['receiver_email'])): ?>
                                <?php echo $log['receiver_email']; ?><br>
                            <?php endif; ?>
                            <?php if(!empty($log['receiver_phone'])): ?>
                                <span class="phone-number"><?php echo $log['receiver_phone']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="source-mac"><?php echo $log['source_mac']; ?></td>
                <td class="dest-mac"><?php echo $log['destination_mac']; ?></td>
                <td><?php echo $log['filename']; ?></td>
                <td><?php echo formatFileSize($log['file_size']); ?></td>
                <td><?php echo $log['timestamp']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-results">
        <p>No logs found matching your search criteria.</p>
    </div>
    <?php endif; ?>
    
    <script>
        // Auto-print when the page loads
        window.onload = function() {
            // Uncomment the line below to auto-print
            // window.print();
        }
    </script>
</body>
</html>
<?php
// Close the statement and connection
$stmt->close();
$conn->close();
?>