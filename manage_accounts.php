<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Admins only.");
}


// Fetch unread notification count
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$notification_count = $result->fetch_assoc()['unread_count'];


// Fetch all users
$sql = "SELECT id, first_name, middle_name, last_name, username, role, can_post FROM users WHERE role IN ('student', 'teacher')";
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $can_post = $_POST['can_post'];

    // Update the user's posting permissions
    $update_sql = "UPDATE users SET can_post = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $can_post, $user_id);

    if ($stmt->execute()) {
        header("Location: manage_accounts.php");
        exit;
    } else {
        echo "Error updating permissions: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts | Admin</title>
    <link rel="icon" type="image/ico" href="/img/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/Admin/FrontEnd/admin.css">
    <style>
        body {
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
    </style>
</head>
<body>

    <!-- Navigation Bar with Search Form -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="admin_home.php"><img src="/img/sors_logo.png" alt=""></a>
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

    <h1>Manage Accounts</h1>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Username</th>
                <th>Account Type</th>
                <th>Can Post</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Inside the table row -->
            <?php
                while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['middle_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo ucfirst($row['role']); ?></td>
                    <td><?php echo $row['can_post'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <form action="manage_accounts.php" method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="can_post" value="<?php echo $row['can_post'] ? 0 : 1; ?>">
                            <button type="submit">
                                <?php echo $row['can_post'] ? 'Restrict Posting' : 'Allow Posting'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
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
