<?php
// Start the session at the very beginning
session_start();
require_once 'db_connection.php';

// Success and error messages
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php

// Fetch data for dashboard
$managers = $conn->query("SELECT m.*, b.branch_name FROM manager m LEFT JOIN branch b ON m.bid = b.id");
$branches = $conn->query("SELECT b.*, m.username FROM branch b left JOIN manager m ON b.id = m.bid ORDER BY b.id");
$users = $conn->query("SELECT u.*, b.branch_name FROM users u LEFT JOIN branch b ON u.bid = b.id ORDER BY u.created_at DESC");
$logs = $conn->query("SELECT l.*,  s.id as sender_id, r.id as receiver_id FROM logs l LEFT JOIN users s ON l.sender_id = s.id LEFT JOIN users r ON l.receiver_id = r.id ORDER BY l.timestamp DESC");

// Count statistics
$totalManagers = $conn->query("SELECT COUNT(*) as count FROM manager")->fetch_assoc()['count'];
$totalBranches = $conn->query("SELECT COUNT(*) as count FROM branch")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$pendingUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='pending'")->fetch_assoc()['count'];
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#dashboard" class="active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#managers" data-section="managers"><i class="fas fa-user-tie"></i> Managers</a></li>
                    <li><a href="#branches" data-section="branches"><i class="fas fa-building"></i> Branches</a></li>
                    <li><a href="#users" data-section="users"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="#logs" data-section="logs"><i class="fas fa-history"></i> Logs</a></li>
                </ul>
            </nav>
            <a href="logout.php" class="logout-button">Logout</a>

<style>
.logout-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #e74c3c;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.logout-button:hover {
    background-color: #c0392b;
}
</style>

        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <h1>Dashboard Overview</h1>
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-user-tie"></i>
                        <h3>Total Managers</h3>
                        <p><?php echo $totalManagers; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-building"></i>
                        <h3>Total Branches</h3>
                        <p><?php echo $totalBranches; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3>Total Users</h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3>Pending Users</h3>
                        <p><?php echo $pendingUsers; ?></p>
                    </div>
                </div>
            </section>

            <!-- Managers Section -->
<section id="managers" class="section">
    <div class="section-header">
        <h1>Managers</h1>
        <button onclick="showModal('addManagerModal')" class="btn-add">
            <i class="fas fa-plus"></i> Add Manager
        </button>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($manager = $managers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $manager['id']; ?></td>
                    <td><?php echo $manager['username']; ?></td>
                    <td><?php echo $manager['email']; ?></td>
                    <td><?php echo $manager['branch_name']; ?></td>
                    <td>
                        <!-- Edit button (optional, you can implement it later) -->
                        <a href="#" onclick="showPanel('managerPanel', 'edit', {id: '<?php echo $manager['id']; ?>', username: '<?php echo $manager['username']; ?>', email: '<?php echo $manager['email']; ?>', bid: '<?php echo $manager['bid']; ?>'})" class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <!-- Delete button with confirmation -->
                        <a href="delete_manager.php?id=<?php echo $manager['id']; ?>" onclick="return confirm('Are you sure you want to delete this manager?');">
                            <button class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>


           <!-- Branches Section -->
<section id="branches" class="section">
    <div class="section-header">
        <h1>Branches</h1>
        <button onclick="showModal('addBranchModal')" class="btn-add">
            <i class="fas fa-plus"></i> Add Branch
        </button>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Branch Name</th>
                    <th>State</th>
                    <th>City</th>
                    <th>ZIP Code</th>
                    <th>Assigned Manager</th>
                    <th>Opening Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($branch = $branches->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $branch['id']; ?></td>
                    <td><?php echo $branch['branch_name']; ?></td>
                    <td><?php echo $branch['state']; ?></td>
                    <td><?php echo $branch['city']; ?></td>
                    <td><?php echo $branch['zip_code']; ?></td>
                    <td><?php echo $branch['username']; ?></td>
                    <td><?php echo $branch['opening_date']; ?></td>
                    <td>
                        <!-- Edit button (optional, can be implemented later) -->
                        <a href="#" onclick="showPanel('branchPanel', 'edit', {id: '<?php echo $branch['id']; ?>', branch_name: '<?php echo $branch['branch_name']; ?>', state: '<?php echo $branch['state']; ?>', city: '<?php echo $branch['city']; ?>', zip_code: '<?php echo $branch['zip_code']; ?>', opening_date: '<?php echo $branch['opening_date']; ?>'})" class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <!-- Delete button with confirmation -->
                        <a href="delete_branch.php?id=<?php echo $branch['id']; ?>" onclick="return confirm('Are you sure you want to delete this branch?');">
                            <button class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>


<!-- Users Section -->
<section id="users" class="section">
    <h1>Users</h1>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo $user['branch_name']; ?></td>
                    <td><span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span></td>
                    <td><?php echo $user['created_at']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
// Logs Section with Optimized Search and Report Features
$section_id = 'logs';
?>
<section id="<?php echo $section_id; ?>" class="section">
    <h1>System Logs</h1>
    
    <!-- Optimized Search Form -->
    <div class="search-container">
        <form id="logSearchForm" method="GET" action="#<?php echo $section_id; ?>">
            <div class="search-row">
                <div class="search-group">
                    <label for="sender_id">Sender ID:</label>
                    <input type="text" id="sender_id" name="sender_id" placeholder="Enter sender ID" 
                           value="<?php echo isset($_GET['sender_id']) ? htmlspecialchars($_GET['sender_id']) : ''; ?>">
                </div>
                <div class="search-group">
                    <label for="receiver_id">Receiver ID:</label>
                    <input type="text" id="receiver_id" name="receiver_id" placeholder="Enter receiver ID" 
                           value="<?php echo isset($_GET['receiver_id']) ? htmlspecialchars($_GET['receiver_id']) : ''; ?>">
                </div>
            </div>
            <div class="search-row">
                <div class="search-group">
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" placeholder="Enter email" 
                           value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                </div>
                <div class="search-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter phone number" 
                           value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                </div>
            </div>
            <div class="search-row">
                <div class="search-group">
                    <label for="filename">Filename:</label>
                    <input type="text" id="filename" name="filename" placeholder="Enter filename" 
                           value="<?php echo isset($_GET['filename']) ? htmlspecialchars($_GET['filename']) : ''; ?>">
                </div>
                <div class="search-group">
                    <!-- Empty for balance, can be used for additional search fields later -->
                </div>
            </div>
            <div class="search-buttons">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <button type="button" onclick="clearLogSearch()" class="btn-clear"><i class="fas fa-times"></i> Clear</button>
                
                <?php if(isset($_GET['search_performed']) && $_GET['search_performed'] == 'true'): ?>
                <button type="button" onclick="generateLogReport()" class="btn-report"><i class="fas fa-file-export"></i> Generate Report</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php
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
    
    // Handle search functionality with optimized query
    $searchConditions = [];
    $searchParams = [];
    $bindTypes = '';
    $hasSearchCriteria = false;
    
    // Build search query - More efficient with clear parameter handling
    if (isset($_GET['sender_id']) && trim($_GET['sender_id']) !== '') {
        $searchConditions[] = "l.sender_id = ?";
        $searchParams[] = trim($_GET['sender_id']);
        $bindTypes .= 's';
        $hasSearchCriteria = true;
    }
    
    if (isset($_GET['receiver_id']) && trim($_GET['receiver_id']) !== '') {
        $searchConditions[] = "l.receiver_id = ?";
        $searchParams[] = trim($_GET['receiver_id']);
        $bindTypes .= 's';
        $hasSearchCriteria = true;
    }
    
    if (isset($_GET['email']) && trim($_GET['email']) !== '') {
        $searchConditions[] = "(s.email LIKE ? OR r.email LIKE ?)";
        $searchParams[] = '%' . trim($_GET['email']) . '%';
        $searchParams[] = '%' . trim($_GET['email']) . '%';
        $bindTypes .= 'ss';
        $hasSearchCriteria = true;
    }
    
    if (isset($_GET['phone']) && trim($_GET['phone']) !== '') {
        $searchConditions[] = "(s.phone LIKE ? OR r.phone LIKE ?)";
        $searchParams[] = '%' . trim($_GET['phone']) . '%';
        $searchParams[] = '%' . trim($_GET['phone']) . '%';
        $bindTypes .= 'ss';
        $hasSearchCriteria = true;
    }
    
    if (isset($_GET['filename']) && trim($_GET['filename']) !== '') {
        $searchConditions[] = "l.filename LIKE ?";
        $searchParams[] = '%' . trim($_GET['filename']) . '%';
        $bindTypes .= 's';
        $hasSearchCriteria = true;
    }
    
    // Only execute the search if there are criteria or the search was explicitly requested
    $searchRequested = isset($_GET['search_performed']) && $_GET['search_performed'] == 'true';
    
    if ($hasSearchCriteria || $searchRequested) {
        // Base query with optimized joins and column selection
        $sql = "SELECT l.log_id, 
                l.sender_id, s.email as sender_email, s.phone as sender_phone,
                l.receiver_id, r.email as receiver_email, r.phone as receiver_phone,
                l.source_mac, l.destination_mac, l.filename, l.file_size, l.timestamp
                FROM logs l
                LEFT JOIN users s ON l.sender_id = s.id
                LEFT JOIN users r ON l.receiver_id = r.id";
        
        // Add conditions if any
        if (!empty($searchConditions)) {
            $sql .= " WHERE " . implode(" AND ", $searchConditions);
        }
        
        // Order by timestamp for most recent first
        $sql .= " ORDER BY l.timestamp DESC";
        
        // Prepare and execute the query with proper parameter binding
        $stmt = $conn->prepare($sql);
        
        if (!empty($searchParams)) {
            $stmt->bind_param($bindTypes, ...$searchParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $resultCount = $result->num_rows;
        
        // Show search results if there are any
        if ($resultCount > 0):
        ?>
        
        <!-- Search Results with Count -->
        <div class="results-header">
            <h2>Search Results (<?php echo $resultCount; ?> records found)</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Source Mac</th>
                        <th>Destination Mac</th>
                        <th>Filename</th>
                        <th>File Size</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="log-id"><?php echo $log['log_id']; ?></td>
                        <td><?php echo $log['sender_id'] . ' (' . $log['sender_email'] . ')'; ?></td>
                        <td><?php echo $log['receiver_id'] . ' (' . $log['receiver_email'] . ')'; ?></td>
                        <td class="mac-address source"><?php echo $log['source_mac']; ?></td>
                        <td class="mac-address dest"><?php echo $log['destination_mac']; ?></td>
                        <td><?php echo $log['filename']; ?></td>
                        <td><?php echo formatFileSize($log['file_size']); ?></td>
                        <td><?php echo $log['timestamp']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <!-- No results message -->
        <div class="no-results">
            <p>No logs found matching your search criteria.</p>
        </div>
        <?php endif;
        
        // Close the statement
        $stmt->close();
    } else {
        // Default view - show recent logs
        $defaultLimit = 20; // Show last 20 logs by default
        $sql = "SELECT l.log_id, 
                l.sender_id, l.receiver_id,
                l.source_mac, l.destination_mac, l.filename, l.file_size, l.timestamp
                FROM logs l
                ORDER BY l.timestamp DESC LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $defaultLimit);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>
        
        <!-- Default Logs Table -->
        <div class="results-header">
            <h2>Recent Logs</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Sender ID</th>
                        <th>Receiver ID</th>
                        <th>Source Mac</th>
                        <th>Destination Mac</th>
                        <th>Filename</th>
                        <th>File Size</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="log-id"><?php echo $log['log_id']; ?></td>
                        <td><?php echo $log['sender_id']; ?></td>
                        <td><?php echo $log['receiver_id']; ?></td>
                        <td class="mac-address source"><?php echo $log['source_mac']; ?></td>
                        <td class="mac-address dest"><?php echo $log['destination_mac']; ?></td>
                        <td><?php echo $log['filename']; ?></td>
                        <td><?php echo formatFileSize($log['file_size']); ?></td>
                        <td><?php echo $log['timestamp']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php
        $stmt->close();
    }
    ?>
    
    <!-- Hidden form for report generation -->
    <form id="reportForm" action="generate_report.php" method="POST" target="_blank" style="display: none;">
        <input type="hidden" id="report_sender_id" name="sender_id" value="<?php echo isset($_GET['sender_id']) ? htmlspecialchars($_GET['sender_id']) : ''; ?>">
        <input type="hidden" id="report_receiver_id" name="receiver_id" value="<?php echo isset($_GET['receiver_id']) ? htmlspecialchars($_GET['receiver_id']) : ''; ?>">
        <input type="hidden" id="report_email" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
        <input type="hidden" id="report_phone" name="phone" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
        <input type="hidden" id="report_filename" name="filename" value="<?php echo isset($_GET['filename']) ? htmlspecialchars($_GET['filename']) : ''; ?>">
        <input type="hidden" name="generate_report" value="true">
    </form>
</section>

<style>
/* Enhanced styles for the logs section */
.search-container {
    background: #f7f7f7;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.search-row {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
}

.search-group {
    flex: 1;
}

.search-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.search-group input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-buttons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.search-buttons button {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-search {
    background-color: #4CAF50;
    color: white;
}

.btn-clear {
    background-color: #f44336;
    color: white;
}

.btn-report {
    background-color: #2196F3;
    color: white;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.table-container {
    overflow-x: auto;
    margin-top: 10px;
}

.no-results {
    padding: 20px;
    text-align: center;
    background: #f9f9f9;
    border-radius: 4px;
    color: #666;
}

/* Log entry styling */
td.log-id {
    color: #e74c3c;
    font-weight: bold;
}

td.mac-address.source {
    color: #2ecc71;
    font-family: monospace;
}

td.mac-address.dest {
    color: #3498db;
    font-family: monospace;
}
</style>

<script>
// Enhanced JavaScript functions
function clearLogSearch() {
    document.getElementById('sender_id').value = '';
    document.getElementById('receiver_id').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('filename').value = '';
    
    // Add a parameter to indicate this is a clear action
    const form = document.getElementById('logSearchForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'clear_search';
    input.value = 'true';
    form.appendChild(input);
    
    form.submit();
}

function generateLogReport() {
    // Update the hidden form fields with current search values
    document.getElementById('report_sender_id').value = document.getElementById('sender_id').value;
    document.getElementById('report_receiver_id').value = document.getElementById('receiver_id').value;
    document.getElementById('report_email').value = document.getElementById('email').value;
    document.getElementById('report_phone').value = document.getElementById('phone').value;
    document.getElementById('report_filename').value = document.getElementById('filename').value;
    
    // Submit the form
    document.getElementById('reportForm').submit();
}

// Add hidden input to mark a search was performed
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('logSearchForm');
    form.addEventListener('submit', function() {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'search_performed';
        input.value = 'true';
        form.appendChild(input);
    });
});
</script>



        </main>
    </div>

    <!-- Add Manager Modal -->
    <div id="addManagerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Manager</h2>
            <form id="managerForm" action="add_manager.php" method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Branch:</label>
                    <select name="bid" required>
                        <?php 
                        $branches->data_seek(0);
                        while($branch = $branches->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Add Manager</button>
            </form>
        </div>
    </div>

    <!-- Add Branch Modal -->
    <div id="addBranchModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Branch</h2>
            <form id="branchForm" action="add_branch.php" method="POST">
                <div class="form-group">
                    <label>Branch Name:</label>
                    <input type="text" name="branch_name" required>
                </div>
                <div class="form-group">
                    <label>State:</label>
                    <input type="text" name="state" required>
                </div>
                <div class="form-group">
                    <label>City:</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>ZIP Code:</label>
                    <input type="text" name="zip_code" required>
                </div>
                <div class="form-group">
                    <label>Opening Date:</label>
                    <input type="date" name="opening_date" required>
                </div>
                <button type="submit" class="btn-submit">Add Branch</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <!-- Slide Panel for Manager -->
<div id="managerPanel" class="side-panel">
    <div class="panel-content">
        <div class="panel-header">
            <h2 id="managerPanelTitle">Add New Manager</h2>
            <button class="close-panel">×</button>
        </div>
        <form id="managerForm" action="add_manager.php" method="POST">
            <div class="form-row">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password">
                    <small class="password-hint">Leave empty to keep current password when updating</small>
                </div>
                <div class="input-group">
                    <label>Branch</label>
                    <select name="bid" required>
                        <?php 
                        $branches->data_seek(0);
                        while($branch = $branches->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Save Manager</button>
            </div>
        </form>
    </div>
</div>

<!-- Slide Panel for Branch -->
<div id="branchPanel" class="side-panel">
    <div class="panel-content">
        <div class="panel-header">
            <h2 id="branchPanelTitle">Add New Branch</h2>
            <button class="close-panel">×</button>
        </div>
        <form id="branchForm" action="add_branch.php" method="POST">
            <div class="form-row">
                <div class="input-group">
                    <label>Branch Name</label>
                    <input type="text" name="branch_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>State</label>
                    <input type="text" name="state" required>
                </div>
                <div class="input-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
            </div>
            <div class="form-row">
                <div class="input-group">
                    <label>ZIP Code</label>
                    <input type="text" name="zip_code" required pattern="[0-9]{5,6}">
                </div>
                <div class="input-group">
                    <label>Opening Date</label>
                    <input type="date" name="opening_date" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Save Branch</button>
            </div>
        </form>
    </div>
</div>
<script>
function clearSearch() {
    document.getElementById('user_id').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('filename').value = '';
    document.getElementById('logSearchForm').submit();
}
</script>
</body>
</html>