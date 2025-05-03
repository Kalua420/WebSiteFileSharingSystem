<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['manager_id'])) {
   header("Location: index.php");
   exit();
}

$manager_id = $_SESSION['manager_id'];
$manager_query = "SELECT m.*, b.branch_name 
                 FROM manager m 
                 LEFT JOIN branch b ON m.bid = b.id 
                 WHERE m.id = '$manager_id'";
$manager_result = $conn->query($manager_query);
$manager = $manager_result->fetch_assoc();

if (isset($_FILES['profile_pic'])) {
   $target_dir = "uploads/";
   $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
   $new_filename = "manager_" . $manager_id . "." . $file_extension;
   $target_file = $target_dir . $new_filename;
   
   if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
       $sql = "UPDATE manager SET profile_pic = '$new_filename' WHERE id = '$manager_id'";
       $conn->query($sql);
       header("Location: manager_dashboard.php?view=profile");
       exit();
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
   $user_id = $conn->real_escape_string($_POST['user_id']);
   $status = $conn->real_escape_string($_POST['status']);
   
   $sql = "UPDATE users SET status = '$status' WHERE id = '$user_id'";
   $conn->query($sql);
   header("Location: manager_dashboard.php?view=users");
   exit();
}

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

$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$manager_branch_id = $manager['bid'];

// Get users data
$sql = "SELECT u.*, b.branch_name FROM users u LEFT JOIN branch b ON u.bid = b.id WHERE u.bid = $manager_branch_id ORDER BY u.created_at DESC";
$result = $conn->query($sql);
$users = [];
while ($row = $result->fetch_assoc()) {
   $users[] = $row;
}

// Get logs data
$logs_query = "SELECT l.*, 
               sender.email as sender_email,
               receiver.email as receiver_email,
               l.file_size
               FROM logs l 
               JOIN users sender ON l.sender_id = sender.id 
               LEFT JOIN users receiver ON l.receiver_id = receiver.id
               WHERE sender.bid = '$manager_branch_id' 
               OR receiver.bid = '$manager_branch_id'
               ORDER BY l.timestamp DESC";
$logs_result = $conn->query($logs_query);
$logs = [];
while ($row = $logs_result->fetch_assoc()) {
   $logs[] = $row;
}

// Get count for dashboard stats
$total_users = count($users);

$pending_users_query = "SELECT COUNT(*) as count FROM users WHERE bid = '$manager_branch_id' AND status = 'pending'";
$pending_result = $conn->query($pending_users_query);
$pending_users = $pending_result->fetch_assoc()['count'];

$total_logs = count($logs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Manager Dashboard</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
       :root {
           --primary: #2563eb;
           --primary-dark: #1d4ed8;
           --primary-light: #3b82f6;
           --secondary: #64748b;
           --success: #10b981;
           --danger: #ef4444;
           --warning: #f59e0b;
           --light: #f8fafc;
           --dark: #1e293b;
           --white: #ffffff;
           --sidebar-width: 250px;
           --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
           --radius: 8px;
           --radius-sm: 4px;
           --transition: all 0.3s ease;
       }
       
       * {
           margin: 0;
           padding: 0;
           box-sizing: border-box;
       }
       
       body { 
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           background: #f1f5f9;
           color: var(--dark);
           line-height: 1.6;
           display: flex;
           min-height: 100vh;
       }
       
       /* Sidebar Styles */
       .sidebar {
           width: var(--sidebar-width);
           background: var(--dark);
           color: var(--white);
           height: 100vh;
           position: fixed;
           top: 0;
           left: 0;
           padding: 20px 0;
           transition: var(--transition);
           overflow-y: auto;
           z-index: 100;
       }
       
       .sidebar-brand {
           padding: 15px 25px;
           text-align: center;
           border-bottom: 1px solid rgba(255, 255, 255, 0.1);
           margin-bottom: 20px;
       }
       
       .sidebar-brand h2 {
           color: var(--primary-light);
           font-size: 1.5rem;
           display: flex;
           align-items: center;
           justify-content: center;
           gap: 10px;
       }
       
       .sidebar-menu {
           padding: 0 15px;
       }
       
       .sidebar-menu ul {
           list-style-type: none;
       }
       
       .sidebar-menu li {
           margin-bottom: 8px;
       }
       
       .sidebar-menu a {
           display: flex;
           align-items: center;
           padding: 12px 15px;
           color: var(--light);
           text-decoration: none;
           border-radius: var(--radius-sm);
           transition: var(--transition);
           font-weight: 500;
       }
       
       .sidebar-menu a:hover {
           background: rgba(255, 255, 255, 0.1);
           transform: translateX(5px);
       }
       
       .sidebar-menu a.active {
           background: var(--primary);
           color: var(--white);
       }
       
       .sidebar-menu a i {
           margin-right: 12px;
           width: 20px;
           text-align: center;
       }
       
       .sidebar-footer {
           padding: 15px 25px;
           border-top: 1px solid rgba(255, 255, 255, 0.1);
           margin-top: 20px;
           position: absolute;
           bottom: 0;
           width: 100%;
       }
       
       .logout-btn {
           display: flex;
           align-items: center;
           padding: 12px 15px;
           background: var(--danger);
           color: var(--white);
           text-decoration: none;
           border-radius: var(--radius-sm);
           transition: var(--transition);
           font-weight: 600;
           width: 100%;
           justify-content: center;
           gap: 10px;
       }
       
       .logout-btn:hover {
           background: #dc2626;
           transform: translateY(-2px);
       }
       
       /* Main Content Styles */
       .main-content {
           flex: 1;
           margin-left: var(--sidebar-width);
           padding: 20px;
           transition: var(--transition);
       }
       
       /* Header Styles */
       .header {
           background: var(--white);
           padding: 15px 25px;
           border-radius: var(--radius);
           box-shadow: var(--shadow);
           margin-bottom: 25px;
           display: flex;
           justify-content: space-between;
           align-items: center;
       }
       
       .header h1 {
           color: var(--primary-dark);
           font-size: 1.8rem;
           font-weight: 600;
       }
       
       .header .welcome-text {
           color: var(--secondary);
           font-size: 1rem;
       }
       
       /* Dashboard Stats */
       .dashboard-stats {
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
           gap: 20px;
           margin-bottom: 25px;
       }
       
       .stat-card {
           background: var(--white);
           border-radius: var(--radius);
           box-shadow: var(--shadow);
           padding: 25px;
           display: flex;
           align-items: center;
           transition: var(--transition);
       }
       
       .stat-card:hover {
           transform: translateY(-5px);
           box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
       }
       
       .stat-icon {
           width: 60px;
           height: 60px;
           border-radius: 50%;
           display: flex;
           align-items: center;
           justify-content: center;
           margin-right: 20px;
           font-size: 1.8rem;
       }
       
       .stat-icon.users {
           background: rgba(37, 99, 235, 0.1);
           color: var(--primary);
       }
       
       .stat-icon.logs {
           background: rgba(245, 158, 11, 0.1);
           color: var(--warning);
       }
       
       .stat-icon.pending {
           background: rgba(239, 68, 68, 0.1);
           color: var(--danger);
       }
       
       .stat-info h3 {
           font-size: 1.8rem;
           font-weight: 700;
           margin-bottom: 5px;
       }
       
       .stat-info p {
           color: var(--secondary);
           font-size: 1rem;
       }
       
       /* Profile Section Styles */
       .profile-section {
           background: var(--white);
           border-radius: var(--radius);
           box-shadow: var(--shadow);
           padding: 30px;
           margin-bottom: 25px;
       }
       
       .profile-header {
           display: flex;
           align-items: center;
           margin-bottom: 30px;
       }
       
       .profile-pic-container {
           position: relative;
           margin-right: 30px;
       }
       
       .profile-pic {
           width: 120px;
           height: 120px;
           border-radius: 50%;
           object-fit: cover;
           border: 4px solid var(--white);
           box-shadow: var(--shadow);
           transition: var(--transition);
       }
       
       .profile-pic:hover {
           transform: scale(1.05);
       }
       
       .profile-info {
           flex: 1;
       }
       
       .profile-info h3 {
           font-size: 1.6rem;
           margin-bottom: 10px;
           color: var(--primary-dark);
       }
       
       .profile-info p {
           margin: 8px 0;
           color: var(--secondary);
           font-size: 1.1rem;
       }
       
       .profile-info p i {
           margin-right: 8px;
           color: var(--primary);
       }
       
       .upload-form {
           margin-top: 15px;
           display: flex;
           flex-direction: column;
           gap: 10px;
       }
       
       .file-input-wrapper {
           position: relative;
           overflow: hidden;
           display: inline-block;
       }
       
       .file-input-wrapper input[type=file] {
           font-size: 100px;
           position: absolute;
           left: 0;
           top: 0;
           opacity: 0;
           cursor: pointer;
       }
       
       .file-input-trigger {
           padding: 8px 16px;
           background: var(--white);
           color: var(--primary);
           border: 1px solid var(--primary);
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .file-input-trigger:hover {
           background: #f8fafc;
       }
       
       .selected-file {
           display: flex;
           align-items: center;
           gap: 8px;
           margin-top: 10px;
           font-size: 0.9rem;
           color: var(--secondary);
           padding: 8px 12px;
           background: rgba(37, 99, 235, 0.05);
           border-radius: var(--radius-sm);
           border: 1px dashed var(--primary-light);
       }
       
       .selected-file i {
           color: var(--primary);
       }
       
       .upload-actions {
           display: flex;
           align-items: center;
           gap: 10px;
           margin-top: 10px;
       }
       
       .upload-btn {
           padding: 8px 16px;
           background: var(--primary);
           color: var(--white);
           border: none;
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .upload-btn:hover {
           background: var(--primary-dark);
           transform: translateY(-2px);
       }
       
       .upload-btn:disabled {
           background: var(--secondary);
           cursor: not-allowed;
           transform: none;
       }
       
       .cancel-btn {
           padding: 8px 16px;
           background: transparent;
           color: var(--secondary);
           border: 1px solid var(--secondary);
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           gap: 5px;
       }
       
       .cancel-btn:hover {
           background: rgba(100, 116, 139, 0.1);
       }
       
       /* Content Section */
       .content-card {
           background: var(--white);
           border-radius: var(--radius);
           box-shadow: var(--shadow);
           padding: 25px;
           margin-bottom: 25px;
       }
       
       .content-header {
           display: flex;
           justify-content: space-between;
           align-items: center;
           margin-bottom: 20px;
           padding-bottom: 15px;
           border-bottom: 1px solid #e2e8f0;
       }
       
       .content-header h2 {
           color: var(--primary-dark);
           font-size: 1.5rem;
           font-weight: 600;
           display: flex;
           align-items: center;
           gap: 10px;
       }
       
       /* Table Styles */
       .table-wrapper {
           overflow-x: auto;
       }
       
       table {
           width: 100%;
           border-collapse: collapse;
           white-space: nowrap;
       }
       
       thead tr {
           background: var(--primary);
           color: var(--white);
       }
       
       th {
           padding: 12px 18px;
           text-align: left;
           font-weight: 600;
       }
       
       td {
           padding: 12px 18px;
           border-bottom: 1px solid #e2e8f0;
           max-width: 200px;
           overflow: hidden;
           text-overflow: ellipsis;
       }
       
       tbody tr {
           transition: var(--transition);
       }
       
       tbody tr:hover {
           background: #f8fafc;
       }
       
       tbody tr:last-child td {
           border-bottom: none;
       }
       
       /* Status Styles */
       .status {
           padding: 4px 8px;
           border-radius: 20px;
           font-size: 0.9rem;
           font-weight: 600;
           display: inline-block;
           text-align: center;
           min-width: 100px;
       }
       
       .status-pending {
           background: #fef3c7;
           color: var(--warning);
       }
       
       .status-approved {
           background: #d1fae5;
           color: var(--success);
       }
       
       .status-rejected {
           background: #fee2e2;
           color: var(--danger);
       }
       
       /* Action Buttons */
       .actions {
           display: flex;
           gap: 5px;
       }
       
       .action-btn {
           padding: 6px 12px;
           border: none;
           border-radius: var(--radius-sm);
           cursor: pointer;
           transition: var(--transition);
           font-weight: 600;
           display: flex;
           align-items: center;
           justify-content: center;
           gap: 5px;
       }
       
       .approve-btn {
           background: var(--success);
           color: var(--white);
       }
       
       .approve-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       .reject-btn {
           background: var(--danger);
           color: var(--white);
       }
       
       .reject-btn:hover {
           opacity: 0.9;
           transform: translateY(-2px);
       }
       
       /* Empty State */
       .empty-state {
           padding: 50px 20px;
           text-align: center;
           color: var(--secondary);
       }
       
       .empty-state i {
           font-size: 3rem;
           margin-bottom: 15px;
           color: var(--primary);
       }
       
       /* File size styles */
       .file-size {
           font-weight: 500;
           color: var(--secondary);
       }
       
       /* Recent Activity */
       .recent-activity {
           margin-top: 20px;
       }
       
       .activity-item {
           display: flex;
           align-items: center;
           padding: 12px 0;
           border-bottom: 1px solid #e2e8f0;
       }
       
       .activity-item:last-child {
           border-bottom: none;
       }
       
       .activity-icon {
           width: 40px;
           height: 40px;
           border-radius: 50%;
           background: rgba(37, 99, 235, 0.1);
           color: var(--primary);
           display: flex;
           align-items: center;
           justify-content: center;
           margin-right: 15px;
           font-size: 1.1rem;
       }
       
       .activity-info {
           flex: 1;
       }
       
       .activity-info p {
           margin: 0;
           font-size: 0.95rem;
       }
       
       .activity-info .meta {
           color: var(--secondary);
           font-size: 0.85rem;
           margin-top: 3px;
       }
       
       /* Responsive Adjustments */
       @media (max-width: 992px) {
           .sidebar {
               width: 70px;
               overflow: visible;
           }
           
           .sidebar-brand h2 span,
           .sidebar-menu a span {
               display: none;
           }
           
           .sidebar-menu a i {
               margin-right: 0;
           }
           
           .main-content {
               margin-left: 70px;
           }
           
           .sidebar-footer {
               display: none;
           }
       }
       
       @media (max-width: 768px) {
           .dashboard-stats {
               grid-template-columns: 1fr;
           }
           
           .profile-header {
               flex-direction: column;
               text-align: center;
           }
           
           .profile-pic-container {
               margin-right: 0;
               margin-bottom: 20px;
           }
           
           .upload-form {
               align-items: center;
           }
           
           .upload-actions {
               justify-content: center;
           }
       }
       
       @media (max-width: 576px) {
           .header {
               flex-direction: column;
               align-items: flex-start;
               gap: 10px;
           }
           
           .sidebar {
               width: 0;
               padding: 0;
               opacity: 0;
           }
           
           .main-content {
               margin-left: 0;
           }
       }
   </style>
</head>
<body>
   <!-- Sidebar -->
   <div class="sidebar">
       <div class="sidebar-brand">
           <h2><i class="fas fa-building"></i> <span>Manager Portal</span></h2>
       </div>
       
       <div class="sidebar-menu">
           <ul>
               <li>
                   <a href="?view=dashboard" class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>">
                       <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                   </a>
               </li>
               <li>
                   <a href="?view=users" class="<?php echo $view === 'users' ? 'active' : ''; ?>">
                       <i class="fas fa-users"></i> <span>Users</span>
                   </a>
               </li>
               <li>
                   <a href="?view=logs" class="<?php echo $view === 'logs' ? 'active' : ''; ?>">
                       <i class="fas fa-list-alt"></i> <span>Logs</span>
                   </a>
               </li>
               <li>
                   <a href="?view=profile" class="<?php echo $view === 'profile' ? 'active' : ''; ?>">
                       <i class="fas fa-user-circle"></i> <span>Profile</span>
                   </a>
               </li>
           </ul>
       </div>
       
       <div class="sidebar-footer">
           <a href="logout.php" class="logout-btn">
               <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
           </a>
       </div>
   </div>
   
   <!-- Main Content -->
   <div class="main-content">
       <div class="header">
           <div>
               <h1>
                   <?php 
                       if ($view === 'dashboard') echo 'Dashboard Overview';
                       elseif ($view === 'users') echo 'User Management';
                       elseif ($view === 'logs') echo 'Activity Logs';
                       elseif ($view === 'profile') echo 'Profile Settings';
                   ?>
               </h1>
               <p class="welcome-text">Welcome back, <?php echo htmlspecialchars($manager['username']); ?>!</p>
           </div>
           <div class="date">
               <p><i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></p>
           </div>
       </div>
       
       <?php if ($view === 'dashboard'): ?>
           <!-- Dashboard Stats -->
           <div class="dashboard-stats">
               <div class="stat-card">
                   <div class="stat-icon users">
                       <i class="fas fa-users"></i>
                   </div>
                   <div class="stat-info">
                       <h3><?php echo $total_users; ?></h3>
                       <p>Total Users</p>
                   </div>
               </div>
               
               <div class="stat-card">
                   <div class="stat-icon logs">
                       <i class="fas fa-list-alt"></i>
                   </div>
                   <div class="stat-info">
                       <h3><?php echo $total_logs; ?></h3>
                       <p>Total Logs</p>
                   </div>
               </div>
               
               <div class="stat-card">
                   <div class="stat-icon pending">
                       <i class="fas fa-user-clock"></i>
                   </div>
                   <div class="stat-info">
                       <h3><?php echo $pending_users; ?></h3>
                       <p>Pending Users</p>
                   </div>
               </div>
           </div>
           
           <!-- Recent Users -->
           <div class="content-card">
               <div class="content-header">
                   <h2><i class="fas fa-users"></i> Recent Users</h2>
                   <a href="?view=users" class="upload-btn">
                       <i class="fas fa-arrow-right"></i> View All
                   </a>
               </div>
               
               <div class="table-wrapper">
                   <?php if (count($users) > 0): ?>
                       <table>
                           <thead>
                               <tr>
                                   <th>ID</th>
                                   <th>Email</th>
                                   <th>Branch</th>
                                   <th>Status</th>
                                   <th>Created At</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php
                               $recent_users = array_slice($users, 0, 5); // Get only first 5 users
                               foreach ($recent_users as $user): 
                               ?>
                                   <tr>
                                       <td><?php echo htmlspecialchars($user['id']); ?></td>
                                       <td><?php echo htmlspecialchars($user['email']); ?></td>
                                       <td><?php echo htmlspecialchars($user['branch_name']); ?></td>
                                       <td>
                                           <span class="status status-<?php echo $user['status']; ?>">
                                               <?php echo ucfirst($user['status']); ?>
                                           </span>
                                       </td>
                                       <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-users-slash"></i>
                           <h3>No Users Found</h3>
                           <p>There are no users assigned to your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
           
           <!-- Recent Logs -->
           <div class="content-card">
               <div class="content-header">
                   <h2><i class="fas fa-list-alt"></i> Recent Activity Logs</h2>
                   <a href="?view=logs" class="upload-btn">
                       <i class="fas fa-arrow-right"></i> View All
                   </a>
               </div>
               
               <div class="table-wrapper">
                   <?php if (count($logs) > 0): ?>
                       <table>
                           <thead>
                               <tr>
                                   <th>Log ID</th>
                                   <th>Sender</th>
                                   <th>Filename</th>
                                   <th>File Size</th>
                                   <th>Timestamp</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php
                               $recent_logs = array_slice($logs, 0, 5); // Get only first 5 logs
                               foreach ($recent_logs as $log): 
                               ?>
                                   <tr>
                                       <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                       <td><?php echo htmlspecialchars($log['sender_email']); ?></td>
                                       <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                       <td class="file-size">
                                           <i class="fas fa-file"></i> 
                                           <?php echo formatFileSize($log['file_size']); ?>
                                       </td>
                                       <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-clipboard-list"></i>
                           <h3>No Logs Found</h3>
                           <p>There are no activity logs for your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
           
       <?php elseif ($view === 'users'): ?>
           <!-- Users Table -->
           <div class="content-card">
               <div class="content-header">
                   <h2><i class="fas fa-users"></i> User Management</h2>
               </div>
               
               <div class="table-wrapper">
                   <?php if (count($users) > 0): ?>
                       <table>
                           <thead>
                               <tr>
                                   <th>ID</th>
                                   <th>Phone</th>
                                   <th>Email</th>
                                   <th>Branch</th>
                                   <th>Aadhar</th>
                                   <th>Address</th>
                                   <th>Status</th>
                                   <th>Created At</th>
                                   <th>Actions</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($users as $user): ?>
                                   <tr>
                                       <td><?php echo htmlspecialchars($user['id']); ?></td>
                                       <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                       <td><?php echo htmlspecialchars($user['email']); ?></td>
                                       <td><?php echo htmlspecialchars($user['branch_name']); ?></td>
                                       <td><?php echo htmlspecialchars($user['aadhar']); ?></td>
                                       <td><?php echo htmlspecialchars($user['address']); ?></td>
                                       <td>
                                           <span class="status status-<?php echo $user['status']; ?>">
                                               <?php echo ucfirst($user['status']); ?>
                                           </span>
                                       </td>
                                       <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                       <td>
                                           <div class="actions">
                                               <?php if ($user['status'] === 'pending'): ?>
                                                   <form method="POST" action="">
                                                       <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                       <input type="hidden" name="status" value="approved">
                                                       <button type="submit" class="action-btn approve-btn">
                                                           <i class="fas fa-check"></i> Approve
                                                       </button>
                                                   </form>
                                                   
                                                   <form method="POST" action="">
                                                       <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                       <input type="hidden" name="status" value="rejected">
                                                       <button type="submit" class="action-btn reject-btn">
                                                           <i class="fas fa-times"></i> Reject
                                                       </button>
                                                   </form>
                                               <?php endif; ?>
                                           </div>
                                       </td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-users-slash"></i>
                           <h3>No Users Found</h3>
                           <p>There are no users assigned to your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
           
       <?php elseif ($view === 'logs'): ?>
           <!-- Logs Table -->
           <div class="content-card">
               <div class="content-header">
                   <h2><i class="fas fa-list-alt"></i> Activity Logs</h2>
               </div>
               
               <div class="table-wrapper">
                   <?php if (count($logs) > 0): ?>
                       <table>
                           <thead>
                               <tr>
                                   <th>Log ID</th>
                                   <th>Sender</th>
                                   <th>Receiver</th>
                                   <th>Filename</th>
                                   <th>File Size</th>
                                   <th>Timestamp</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($logs as $log): ?>
                                   <tr>
                                       <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                       <td><?php echo htmlspecialchars($log['sender_email']); ?></td>
                                       <td><?php echo htmlspecialchars($log['receiver_email'] ?? 'N/A'); ?></td>
                                       <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                       <td class="file-size">
                                           <i class="fas fa-file"></i> 
                                           <?php echo formatFileSize($log['file_size']); ?>
                                       </td>
                                       <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php else: ?>
                       <div class="empty-state">
                           <i class="fas fa-clipboard-list"></i>
                           <h3>No Logs Found</h3>
                           <p>There are no activity logs for your branch at this time.</p>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
           
       <?php elseif ($view === 'profile'): ?>
           <!-- Profile Section -->
           <div class="profile-section">
               <div class="profile-header">
                   <div class="profile-pic-container">
                       <img src="uploads/<?php echo $manager['profile_pic'] ?? 'default.png'; ?>" alt="Profile Picture" class="profile-pic">
                       <form method="POST" enctype="multipart/form-data" class="upload-form">
                           <div class="file-input-wrapper">
                               <label class="file-input-trigger">
                                   <i class="fas fa-upload"></i> Select Image
                                   <input type="file" name="profile_pic" accept="image/*">
                               </label>
                           </div>
                           <button type="submit" class="upload-btn">
                               <i class="fas fa-save"></i> Update
                           </button>
                       </form>
                   </div>
                   
                   <div class="profile-info">
                       <h3><?php echo htmlspecialchars($manager['username']); ?></h3>
                       <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($manager['branch_name']); ?> Branch Manager</p>
                       <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($manager['email']); ?></p>
                       <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($manager['phone'] ?? 'Not set'); ?></p>
                       <p><i class="fas fa-calendar-alt"></i> Joined on <?php echo date('F j, Y', strtotime($manager['created_at'])); ?></p>
                   </div>
               </div>
           </div>
       <?php endif; ?>
   </div>
   
   <script>
       // Simple animation for the stats cards
       document.addEventListener('DOMContentLoaded', function() {
           const statCards = document.querySelectorAll('.stat-card');
           
           setTimeout(() => {
               statCards.forEach((card, index) => {
                   setTimeout(() => {
                       card.style.opacity = '1';
                       card.style.transform = 'translateY(0)';
                   }, index * 100);
               });
           }, 300);
       });
   </script>
</body>
</html>