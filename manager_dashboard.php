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
    <link rel="stylesheet" href="manager_style.css">
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