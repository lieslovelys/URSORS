<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_login(); // Database connection file


// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /login.html");
    exit;
}

$user_id = (int) ($_GET['user_id'] ?? $_SESSION['user_id']);
$own_profile = $user_id === (int) $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

$stmt->close();

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
    <title><?php echo htmlspecialchars($_SESSION['username']); ?>'s Profile</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link rel="stylesheet" href="/assets/css/home_sections_style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/profile.css">
    <style>
        body{
            margin-top: 80px;
        }
    </style>
</head>

<body>

    <!-- Navigation Bar with Search Form -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="/course_page.php"><img src="/assets/images/sors_logo.png" alt=""></a>
            </div>
                
                <div class="nav-icons">
                    <a href="/course_page.php" class="nav-item">
                        <span class="material-icons" alt="Home">home</span>
                    </a>
                    <a href="/ranking_board.php" class="nav-item">
                        <span class="material-icons">leaderboard</span>
                    </a>
                    <a href="/profile.php" class="nav-item">
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
                <li><a href="/course_page.php"><i class=""></i> Home </a></li>
                <li><a href="/profile.php"><i class=""></i> Profile </a></li>
                <li><a href="/ranking_board.php"><i class=""></i> Rank Board </a></li>
                <li><a href="/archived_posts.php"><i class=""></i> Archived Posts</a></li>
                <li><a href="/logout.php"><i class=""></i> Logout</a></li>
            </ul>
        </div>
    </nav>


    <!-- Profile Header with Cover Photo, Profile Picture, and Username -->
    <div class="profile-header">
        <div class="cover-photo">
            <img src="<?php echo htmlspecialchars(profile_image($user['cover_photo'], 'cover')); ?>" alt="Cover Photo" id="coverPhotoImg">
            <?php if ($own_profile): ?><form action="upload_cover.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="cover_photo" accept="image/*" required>
                <button type="submit">Update Cover Photo</button>
            </form><?php endif; ?>
        </div>
        <div class="profile-photo">
            <img src="<?php echo htmlspecialchars(profile_image($user['profile_picture'], 'profile')); ?>" alt="Profile Picture" id="profilePictureImg">
            <?php if ($own_profile): ?><form action="upload_profile.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" accept="image/*" required>
                <button type="submit">Update Profile Picture</button>
            </form><?php endif; ?>
        </div>
        <div class="username">
            <h2><?php echo htmlspecialchars(ucfirst($user['first_name']) . " " . ucfirst($user['middle_name'] ?? '') . " " . ucfirst($user['last_name'])); ?></h2>
            <p><strong><?php echo htmlspecialchars(ucfirst($user['username'])); ?></strong></p>
            <p><?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
        </div>
    </div>

    


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
</body>
</html>
