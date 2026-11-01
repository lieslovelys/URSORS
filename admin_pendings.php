<?php

require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_admin();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Admins only.");
}

// Fetch unread notification count
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notification_result = $stmt->get_result();
$notification_count = $notification_result->fetch_assoc()['unread_count'];

// Fetch pending accounts
$sql = "SELECT id, first_name, middle_name, last_name, username, role FROM users WHERE is_approved = 0";
$result = $conn->query($sql);

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $update_sql = "UPDATE users SET is_approved = 1 WHERE id = ?";
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE users SET is_approved = 0 WHERE id = ? AND role <> 'admin'";
    } else {
        die("Invalid action.");
    }

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        notify_user($conn, $user_id, $action === 'approve' ? 'Your account has been approved.' : 'Your account remains unapproved. Contact an admin.');
        header('Location: /admin_pendings.php');
        exit;
    } else {
        echo "Error processing request: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pendings | Admin</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        body{
            margin-top: 80px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        .action-buttons form {
            display: inline;
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

                
                <a href="admin_notif.php" class="notification-icon">
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

    <h1>Pending Account Approvals</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Username</th>
                    <th>Account Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['middle_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo ucfirst($row['role']); ?></td>
                        <td class="action-buttons">
                            <form action="admin_pendings.php" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit">Approve</button>
                            </form>
                            <form action="admin_pendings.php" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" style="color: red;">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending accounts at the moment.</p>
    <?php endif; ?>
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
