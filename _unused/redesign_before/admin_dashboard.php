<?php

require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_admin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Admins only.");
}

// Fetch all users
$sql = "SELECT id, username, role FROM users";
$result = $conn->query($sql);

// Fetch unread notification count
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notification_result = $stmt->get_result();
$notification_count = $notification_result->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Admin</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">

    <style>
        body {
            margin-top: 80px;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar with Search Form -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="admin_home.php"><img src="/assets/images/sors_logo.png" alt=""></a>
            </div>
                
                <div class="nav-icons">
                    <a href="admin_home.php" class="nav-item">
                        <span class="material-icons" alt="Home">home</span>
                    </a>
                    <a href="admin_ranking.php" class="nav-item">
                        <span class="material-icons">leaderboard</span>
                    </a>
                    <a href="admin_dashboard.php" class="nav-item">
                        <span class="material-icons">dashboard</span>
                    </a>
                    <a href="manage_accounts.php" class="nav-item">
                        <span class="material-icons">manage_accounts</span>
                    </a>
                    <a href="admin_pendings.php" class="nav-item">
                        <span class="material-icons">how_to_reg</span>
                    </a>
                    <a href="admin_register.php" class="nav-item">
                        <span class="material-icons">edit_square</span>
                    </a>
                    <a href="admin_profile.php" class="nav-item">
                        <span class="material-icons">person</span>
                    </a>
                </div>

                
                <a href="notifications.php" class="notification-icon">
                    <span class="material-icons" >notifications</span> 
                </a>
                <p class="notif-count"><strong><?php echo $notification_count; ?></strong></p>
                
                
                <span class="material-icons menu-button" id="menu-button">menu</span>
        </div> 
            
            
        <div class="dropdown-menu" id="dropdown-menu">
            <ul>
                <li><a href="admin_home.php"><i class=""></i> Home </a></li>
                <li><a href="admin_profile.php"><i class=""></i> Profile </a></li>
                <li><a href="admin_dashboard.php"><i class=""></i> Dashboard </a></li>
                <li><a href="admin_ranking.php"><i class=""></i> Leaderboard </a></li>
                <li><a href="admin_archive.php"><i class=""></i> Archived Posts </a></li>
                <li><a href="manage_accounts.php"><i class=""></i> Manage Accounts</a></li>
                <li><a href="admin_pendings.php"><i class=""></i> Pendings</a></li>
                <li><a href="admin_register.php"><i class=""></i> Register </a></li>
                <li><a href="logout.php"><i class=""></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <h2>All Users</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
        </tr>
        <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo $user['role']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuButton = document.getElementById('menu-button');
        const dropdownMenu = document.getElementById('dropdown-menu');

        menuButton.addEventListener('click', () => {
            // Toggle the dropdown menu visibility
            dropdownMenu.style.display =
                dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Close the dropdown menu if clicked outside
        document.addEventListener('click', (event) => {
            if (!menuButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    });
    </script>
</html>
