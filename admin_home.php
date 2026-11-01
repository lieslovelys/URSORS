<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied. Admins only.");
}

// Fetch posts from the database
// Filter the archived posts
$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT first_name, middle_name, last_name, username, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

// Fetch unread notification count
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$notification_count = $result->fetch_assoc()['unread_count'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Admin</title>
    <link rel="icon" type="image/ico" href="/img/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/Admin/FrontEnd/admin.css">

    <style>
        body {
            margin-top: 200px;
        }
        .welcome-container {
            text-align: center;
            animation: fadeIn 2s ease-in-out;
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: slideInFromTop 1.5s ease-out;
            color: blue;
        }
        .welcome-text {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0;
            animation: fadeIn 2.5s ease-in-out forwards;
            animation-delay: 1s;
            color: black;
        }

        .welcome-container .btn-container .btn {
            position: relative;
            padding: 10px;
            background-color: blue;
            border-radius: 5px;
            color: azure;
            text-decoration: none;
        }

        .welcome-container .btn-container .btn:hover{
            background-color: lightskyblue;
        }

        
        

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        @keyframes slideInFromTop {
            0% { transform: translateY(-50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
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

    <div class="welcome-container">
        <h1>WELCOME, <?php echo htmlspecialchars(strtoupper($user['first_name'])); ?>!</h1>
        <p class="welcome-text">We’re glad to have you back. Let's explore what's new today!</p>
        <div class="btn-container">
            <a href="admin_course_page.php" class="btn">Resources</a>
        </div>
    </div>
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
