<?php 
session_start();
include 'config.php';

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
    <title>Resources | URS S.O.R.S</title>
    <link rel="icon" type="image/ico" href="/img/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="home_sections_style.css">

    <style>
        .container{
            margin-top: 170px;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar with Search Form -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="welcome.php"><img src="/img/sors_logo.png" alt=""></a>
            </div>
                
                <div class="nav-icons">
                    <a href="welcome.php" class="nav-item">
                        <span class="material-icons" alt="Home">home</span>
                    </a>
                    <a href="/Admin/BackEnd/ranking_board.php" class="nav-item">
                        <span class="material-icons">leaderboard</span>
                    </a>
                    <a href="/Admin/BackEnd/profile.php" class="nav-item">
                        <span class="material-icons">person</span>
                    </a>
                </div>

                
                <a href="/Admin/BackEnd/notifications.php" class="notification-icon">
                    <span class="material-icons" >notifications</span> 
                </a>
                <p class="notif-count"><strong><?php echo $notification_count; ?></strong></p>
                
                
                <span class="material-icons menu-button" id="menu-button">menu</span>
        </div> 
            
            
        <div class="dropdown-menu" id="dropdown-menu">
            <ul>
                <li><a href="/Admin/FrontEnd/home_sections/course_page.php"><i class=""></i> Home </a></li>
                <li><a href="/Admin/BackEnd/profile.php"><i class=""></i> Profile </a></li>
                <li><a href="/Admin/BackEnd/ranking_board.php"><i class=""></i> Rank Board </a></li>
                <li><a href="/Admin/BackEnd/logout.php"><i class=""></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="flashcard-container">
            <a href="ce_subject_class_page.html" class="flashcard">
                <div class="flashcard-content">
                    <span class="material-icons flashcard-icon">engineering</span>
                    <h3>Civil Engineering</h3>
                    <p>Bachelor of Science in Civil Engineering</p>
                </div>
            </a>
            <a href="cpe_subject_class_page.html" class="flashcard">
                <div class="flashcard-content">
                    <span class="material-icons flashcard-icon">computer</span>
                    <h3>Computer Engineering</h3>
                    <p>Bachelor of Science in Computer Engineering</p>
                </div>
            </a>
        </div>

    </div>
    
    
    

    <!-- <a href="news_feed.php?section=Sports&sub_section=Football&nested_sub_section=Teams&deep_sub_section=PlayerProfiles">Player Profiles</a> -->
        
  

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