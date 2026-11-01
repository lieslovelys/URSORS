<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_admin(); // Database connection file


// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

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

// Fetch posts from the database
// Filter the archived posts
$sql = "SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.is_archived = 0 
        ORDER BY posts.created_at DESC";
$result = $conn->query($sql);


// Fetch posts based on the selected section

$sections = [
    "General" => [
        "subsections" => []
    ],

    "Sports" => [
        "subsections" => [
            "Football" => [
                "subsections" => [
                    "Players" => ["Players 1", "Players 2"], 
                    "Clubs" => [], 
                    "Tournaments" => []
                    ]
                
            ],

            "Basketball" => [
                "subsections" => [
                    "NBA" => [], 
                    "EuroLeague" => []
                    ]
                
            ],

            "Tennis" => [
                "subsections" => [
                    "Grand Slams" => [], 
                    "Rankings" => []
                    ]
                
            ]
        ]
    ],

    "Technology" => [
        "subsections" => [
            "AI" => [
                "subsections" => [
                    "Machine Learning" => [], 
                    "Neural Networks" => []
                    ]
                
            ],

            "Software" => [
                "subsections" => [
                    "Programming" => [], 
                    "Applications" => []
                ]
                
            ],

            "Hardware" => [
                "subsections" => [
                    "Processors" => [], 
                    "Gadgets" => []
                ]
                
                ]
        ]
    ],

    "Entertainment" => [
        "subsections" => [
            "Movies" => [
                "subsections" => [
                    "Hollywood" => [], 
                    "Bollywood" => []
                    ]
                
            ],

            "Music" => [
                "subsections" => [
                    "Pop" => [], 
                    "Classical" => []
                    ]
                
            ],

            "TV Shows" => [
                "subsections" => [
                    "Sitcoms" => [], 
                    "Drama" => []
                    ]
                
                ]
        ]
    ]
];


$selected_section = isset($_GET['section']) ? $_GET['section'] : 'All';
$selected_sub_section = isset($_GET['sub_section']) ? $_GET['sub_section'] : null;
$selected_nested_sub_section = isset($_GET['nested_sub_section']) ? $_GET['nested_sub_section'] : null;
$selected_deep_sub_section = isset($_GET['deep_sub_section']) ? $_GET['deep_sub_section'] : null;

// Fetch posts based on selected section, subsection, and nested subsection
$where_clause = "posts.is_archived = 0"; // Exclude archived posts

if (!empty($selected_section) && $selected_section !== 'All') {
    $where_clause .= " AND posts.section = '" . $conn->real_escape_string($selected_section) . "'";
}

if (!empty($selected_sub_section)) {
    $where_clause .= " AND posts.sub_section = '" . $conn->real_escape_string($selected_sub_section) . "'";
}

if (!empty($selected_nested_sub_section)) {
    $where_clause .= " AND posts.nested_sub_section = '" . $conn->real_escape_string($selected_nested_sub_section) . "'";
}

if (!empty($selected_deep_sub_section)) {
    $where_clause .= " AND posts.deep_sub_section = '" . $conn->real_escape_string($selected_deep_sub_section) . "'";
}


$sql = "SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE $where_clause 
        ORDER BY posts.created_at DESC";

$result = $conn->query($sql);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($_SESSION['username']); ?>'s Profile</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        body {
            margin-top: 80px;
        }
        
        .news-feed-container{
            display: none;
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


    <!-- Profile Header with Cover Photo, Profile Picture, and Username -->
    <div class="profile-header">
        <div class="cover-photo">
            <img src="<?php echo htmlspecialchars(profile_image($user['cover_photo'], 'cover')); ?>" alt="Cover Photo" id="coverPhotoImg">
            <form action="upload_cover.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="cover_photo" accept="image/*" required>
                <button type="submit">Update Cover Photo</button>
            </form>
        </div>
        <div class="profile-photo">
            <img src="<?php echo htmlspecialchars(profile_image($user['profile_picture'], 'profile')); ?>" alt="Profile Picture" id="profilePictureImg">
            <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" accept="image/*" required>
                <button type="submit">Update Profile Picture</button>
            </form>
        </div>
        <div class="username">
            <h2><?php echo htmlspecialchars(ucfirst($user['first_name']) . " " . ucfirst($user['middle_name'] ?? '') . " " . ucfirst($user['last_name'])); ?></h2>
            <p><strong><?php echo ucfirst($_SESSION['username']); ?></strong></p>
            <p> <?php echo ucfirst($_SESSION['role']); ?></p>
        </div>
    </div>

    <!-- Display Posts -->
    <div class="news-feed-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post">
                    <p><strong><?php echo htmlspecialchars($post['username']); ?>:</strong></p>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>

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

                    <!-- Display Uploaded Image -->
                    <?php if (!empty($post['image_path'])): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Uploaded Image" style="max-width: 100%; height: auto;">
                        </div>
                    <?php endif; ?>

                    <form action="post_actions.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <?php if ($post['is_archived'] == 0): ?>
                            <button type="submit" name="action" value="archive">Archive</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="restore">Restore</button>
                        <?php endif; ?>
                    </form>
                    

                            <!-- Show delete button if the post belongs to the logged-in user -->
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                <form action="delete_post.php" method="POST" class="delete-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="delete-button">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <!-- <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p> -->

                        <!-- Comments Section -->
                        <div class="comments-section">
                            <!-- Display Comments -->
                            <?php
                                $sql = "SELECT comments.*, users.username 
                                        FROM comments 
                                        JOIN users ON comments.user_id = users.id 
                                        WHERE comments.post_id = ? AND comments.is_deleted = 0
                                        ORDER BY comments.created_at ASC";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $post['id']);
                                $stmt->execute();
                                $result_comments = $stmt->get_result();

                                if ($result_comments->num_rows > 0): ?>
                                    <div class="comments-section">
                                        <?php while ($comment = $result_comments->fetch_assoc()): ?>
                                            <div class="comment">
                                                <p><strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo htmlspecialchars($comment['content']); ?></p>
                                                <?php if (!empty($comment['file_path'])): ?>
                                                    <p>Attachment: 
                                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $comment['file_path'])): ?>
                                                            <img src="<?php echo htmlspecialchars($comment['file_path']); ?>" alt="Attachment" style="max-width: 200px; max-height: 200px;">
                                                        <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($comment['file_path']); ?>" download>Download File</a>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                                <small>Posted on <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></small>
                                                <?php if ($_SESSION['user_id'] == $comment['user_id'] || ($_SESSION['role'] === 'admin')): ?>
                                                    <form action="delete_comment.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" onclick="return confirm('Delete this comment?');">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; ?>

                            <!-- Add Comment Form -->
                            <form action="add_comment.php" method="POST" enctype="multipart/form-data">
                                <textarea name="comment_content" placeholder="Write a comment..." required></textarea><br>
                                <label for="comment_file">Attach a file:</label><br>
                                <input type="file" name="comment_file" id="comment_file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"><br><br>
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit">Add Comment</button>
                            </form>
                        </div>

                        <!-- Like Button -->
                        <div class="like-section">
                            <?php
                            // Check if the user has liked this post
                            $like_sql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
                            $stmt = $conn->prepare($like_sql);
                            $stmt->bind_param("ii", $post['id'], $_SESSION['user_id']);
                            $stmt->execute();
                            $liked_result = $stmt->get_result();
                            $is_liked = $liked_result->num_rows > 0;
                            $stmt->close();

                            // Count total likes
                            $count_sql = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
                            $stmt = $conn->prepare($count_sql);
                            $stmt->bind_param("i", $post['id']);
                            $stmt->execute();
                            $count_result = $stmt->get_result();
                            $like_count = $count_result->fetch_assoc()['like_count'];
                            $stmt->close();
                            ?>

                            <form action="like.php" method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="like-button">
                                    <?php echo $is_liked ? "Unlike" : "Like"; ?>
                                </button>
                                <span><?php echo $like_count; ?> Likes</span>
                            </form>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts to display.</p>
            <?php endif; ?>
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
