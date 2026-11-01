<?php
session_start();
include 'config.php';

// Fetch the ranking board data
$sql = "SELECT users.id, users.username, COUNT(posts.id) AS post_count 
        FROM users 
        LEFT JOIN posts ON users.id = posts.user_id 
        WHERE posts.is_archived = 0 
        GROUP BY users.id, users.username 
        ORDER BY post_count DESC, users.username ASC";
$result = $conn->query($sql);

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
    <title>Ranking Board</title>
    <link rel="icon" type="image/ico" href="/img/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/Admin/FrontEnd/home_sections/home_sections_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin-top: 100px;
        }
        h1 {
            text-align: center;
            color: black;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 10px;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }

        /*To Highlight the Top 3 Users*/
        tr:first-child td {
        background-color: gold;
        font-weight: bold;
        }
        tr:nth-child(2) td {
            background-color: silver;
            font-weight: bold;
        }
        tr:nth-child(3) td {
            background-color: #cd7f32; /* Bronze color */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar with Search Form -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="/Admin/FrontEnd/home_sections/course_page.php"><img src="/img/sors_logo.png" alt=""></a>
            </div>
                
                <div class="nav-icons">
                    <a href="/Admin/FrontEnd/home_sections/course_page.php" class="nav-item">
                        <span class="material-icons" alt="Home">home</span>
                    </a>
                    <a href="/Admin/BackEnd/ranking_board.php" class="nav-item">
                        <span class="material-icons">leaderboard</span>
                    </a>
                    <a href="/Admin/BackEnd/profile.php" class="nav-item">
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
                <li><a href="/Admin/FrontEnd/home_sections/course_page.php"><i class=""></i> Home </a></li>
                <li><a href="/Admin/BackEnd/profile.php"><i class=""></i> Profile </a></li>
                <li><a href="/Admin/BackEnd/ranking_board.php"><i class=""></i> Rank Board </a></li>
                <li><a href="/Admin/BackEnd/archived_posts.php"><i class=""></i> Archived Posts</a></li>
                <li><a href="/Admin/BackEnd/logout.php"><i class=""></i> Logout</a></li>
            </ul>
        </div>
    </nav>


    <h1>User Ranking Board</h1>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Username</th>
                <th>Number of Posts</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['post_count']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
