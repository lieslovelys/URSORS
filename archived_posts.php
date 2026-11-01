<?php

require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_login();

$sql = "SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.is_archived = 1 
        ORDER BY posts.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Posts</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/home_sections_style.css">

    <style>
        .container{
            margin-top: 60px;
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


    <div class="container">
        <h1>Archived Posts</h1>
        
        <?php while ($post = $result->fetch_assoc()): ?>
            <div class="post">
                <p><strong><?php echo htmlspecialchars($post['username']); ?>:</strong></p>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <p><small>Posted on <?php echo $post['created_at']; ?></small></p>

                <!-- Display Section Context -->
                <p><strong>Section:</strong> <?php echo htmlspecialchars($post['section']); ?></p>
                <?php if (!empty($post['sub_section'])): ?>
                    <p><strong>Subsection:</strong> <?php echo htmlspecialchars($post['sub_section']); ?></p>
                <?php endif; ?>
                <?php if (!empty($post['nested_sub_section'])): ?>
                    <p><strong>Nested Subsection:</strong> <?php echo htmlspecialchars($post['nested_sub_section']); ?></p>
                <?php endif; ?>
                <?php if (!empty($post['deep_sub_section'])): ?>
                    <p><strong>Deep Subsection:</strong> <?php echo htmlspecialchars($post['deep_sub_section']); ?></p>
                <?php endif; ?>

                <?php if (!empty($post['image_path'])): ?>
                    <div>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Uploaded Image" style="max-width: 100%; height: auto;">
                    </div>
                <?php endif; ?>

                <?php if (in_array($_SESSION['role'], ['teacher', 'admin'], true)): ?><form action="post_actions.php" method="POST">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="action" value="restore">Restore</button>
                </form><?php endif; ?>
            </div>
        <?php endwhile; ?>
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
