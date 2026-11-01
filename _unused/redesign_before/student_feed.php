<?php

require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/config.php';
require_login(); // Database connection file

// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /login.html");
    exit;
}


$user_id = $_SESSION['user_id'];

// Check if the user is allowed to post
$sql = "SELECT can_post FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
}


// Fetch unread notification count
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notification_result = $stmt->get_result();
$notification_count = $notification_result->fetch_assoc()['unread_count'];


// Fetch posts based on the selected section

$sections = [
    

    "Bachelor of Science in Computer Engineering" => [
        "subsections" => [

            "General Education Subjects" => [
                "subsections" => [
                    "Understanding the Self" => ["General", "Professor Only"], 
                    "Readings in Philippine History" => ["General", "Professor Only"], 
                    "Mathematics in the Modern World" => ["General", "Professor Only"],
                    "The Contemporary World" => ["General", "Professor Only"],
                    "Purposive Communication" => ["General", "Professor Only"],
                    "Art Appreciation" => ["General", "Professor Only"],
                    "Science, Technology, and Society" => ["General", "Professor Only"],
                    "Ethics" => ["General", "Professor Only"],
                    "Life and Works of Rizal" => ["General", "Professor Only"],
                    "Komunikasyon at Pananaliksik sa Wika at Kulturang Filipino" => ["General", "Professor Only"],
                    "Pagbasa at Pagsusuri ng Iba't-Ibang Teksto Tungo sa Pananaliksik" => ["General", "Professor Only"]
                    ]
                
            ],

            "Core Engineering Subjects" => [
                "subsections" => [
                    "Engineering Mathematics 1 (Algebra and Trigonometry)" => ["General", "Professor Only"],
                    "Engineering Mathematics 2 (Analytic Geometry and Calculus)" => ["General", "Professor Only"],
                    "Differential Equations" => ["General", "Professor Only"],
                    "Probability and Statistics" => ["General", "Professor Only"],
                    "Engineering Mechanics (Statics and Dynamics)" => ["General", "Professor Only"],
                    "Strength of Materials" => ["General", "Professor Only"],
                    "Engineering Economy" => ["General", "Professor Only"],
                    "Basic Thermodynamics" => ["General", "Professor Only"],
                    "Engineering Drawing and Design" => ["General", "Professor Only"],
                    "Environmental Science and Engineering" => ["General", "Professor Only"]
                    ]
                
            ],

            "Professional Computer Engineering Subjects" => [
                "subsections" => [
                    "Introduction to Computer Engineering" => ["General", "Professor Only"],
                    "Object-Oriented Programming" => ["General", "Professor Only"],
                    "Computer-Aided Drafting and Design" => ["General", "Professor Only"],
                    "Microprocessor and Microcontroller Systems" => ["General", "Professor Only"],
                    "Data Structures and Algorithms" => ["General", "Professor Only"],
                    "Computer Architecture and Organization" => ["General", "Professor Only"],
                    "Data Communications and Networking" => ["General", "Professor Only"],
                    "Operating Systems" => ["General", "Professor Only"],
                    "Software Engineering" => ["General", "Professor Only"],
                    "Database Management Systems" => ["General", "Professor Only"],
                    "Embedded Systems" => ["General", "Professor Only"],
                    "Computer Systems Engineering" => ["General", "Professor Only"],
                    "Signals, Systems, and Transforms" => ["General", "Professor Only"],
                    "Discrete Mathematics" => ["General", "Professor Only"],
                    "Capstone Design Project" => ["General", "Professor Only"],
                    "Internship / On-the-Job Training (OJT)" => ["General", "Professor Only"]
                    ]
                
                ],

            "Computer Engineering Electives" => [
                "subsections" => [
                    "Robotics and Automation" => ["General", "Professor Only"],
                    "Artificial Intelligence and Machine Learning" => ["General", "Professor Only"],
                    "Internet of Things (IoT)" => ["General", "Professor Only"],
                    "Cloud Computing" => ["General", "Professor Only"],
                    "Blockchain Technology" => ["General", "Professor Only"],
                    "Mobile Application Development" => ["General", "Professor Only"],
                    "Advanced Web Development" => ["General", "Professor Only"],
                    "Digital Signal Processing" => ["General", "Professor Only"],
                    "Cybersecurity" => ["General", "Professor Only"]
                    ]
                
            ]
        ]
    ],

    "Bachelor of Science in Civil Engineering" => [
        "subsections" => [

            "Core Engineering Subjects" => [
                "subsections" => [
                    "Fluid Mechanics" => ["General", "Professor Only"]
                ]
                
            ],

            "Professional Civil Engineering Subjects" => [
                "subsections" => [
                    "Surveying" => ["General", "Professor Only"], 
                    "Geology and Geotechnical Engineering" => ["General", "Professor Only"], 
                    "Structural Theory and Analysis" => ["General", "Professor Only"], 
                    "Hydraulics and Hydrology" => ["General", "Professor Only"], 
                    "Reinforced Concrete Design" => ["General", "Professor Only"], 
                    "Steel and Timber Design" => ["General", "Professor Only"], 
                    "Soil Mechanics and Foundations" => ["General", "Professor Only"], 
                    "Transportation Engineering" => ["General", "Professor Only"], 
                    "Highway Engineering" => ["General", "Professor Only"], 
                    "Water Resources Engineering" => ["General", "Professor Only"],
                    "Construction Project Management" => ["General", "Professor Only"],
                    "Environmental Engineering" => ["General", "Professor Only"],
                    "Capstone Design Project" => ["General", "Professor Only"],
                    "Internship / On-the-Job Training (OJT)" => ["General", "Professor Only"]
                ]
                
                ],

            "Civil Engineering Electives" => [
                "subsections" => [

                    "Earthquake Engineering" => ["General", "Professor Only"],
                    "Green Building Design" => ["General", "Professor Only"], 
                    "Advanced Structural Design" => ["General", "Professor Only"], 
                    "Urban Planning and Development" => ["General", "Professor Only"], 
                    "Coastal and Port Engineering" => ["General", "Professor Only"], 
                    "Construction Safety and Health Management" => ["General", "Professor Only"],
                    "Building Information Modeling (BIM)" => ["General", "Professor Only"]
                ]
                
            ],
        ]
    ],
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


$sql = "SELECT posts.*, users.first_name, users.middle_name, users.last_name, users.username 
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
    <title>Resources | URS S.O.R.S</title>
    <link rel="icon" type="image/ico" href="/assets/images/title_icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/home_sections_style.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        
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
                <li><a href="/logout.php"><i class=""></i> Logout</a></li>
            </ul>
        </div>
    </nav>


    
    <!-- News Feed -->
    <div class="container">

        <h2>General Channel</h2>
        
        

        <!-- Post Form -->
            <?php if ($user['can_post'] == 0 || ($selected_deep_sub_section === 'Professor Only' && $_SESSION['role'] === 'student')): ?>
            <div class="post-form-container">
                <h2>Create New Post</h2>
                <p style="color: red;">You are currently restricted from posting. Please contact an admin for assistance.</p>
            </div>
            <?php else: ?>
                <div class="post-form-container">
                    <h2>Create New Post</h2>
                    <form action="post_handler.php" method="POST" enctype="multipart/form-data">
                        <textarea name="content" placeholder="Write a description..." required></textarea><br>
                        
                        <!-- Automatically set section, sub_section, nested_sub_section, and deep_sub_section -->
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($selected_section); ?>">
                        <input type="hidden" name="sub_section" value="<?php echo htmlspecialchars($selected_sub_section ?? ''); ?>">
                        <input type="hidden" name="nested_sub_section" value="<?php echo htmlspecialchars($selected_nested_sub_section ?? ''); ?>">
                        <input type="hidden" name="deep_sub_section" value="<?php echo htmlspecialchars($selected_deep_sub_section ?? ''); ?>">

                        
                        <div class="icon-container">
                        <span class="material-icons">image</span>
                        <input type="file" name="image" id="image" accept="image/*">
                        

                        <span class="material-icons">attach_file</span>
                        <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                        </div>

                        <button type="submit">Post</button>
                    </form>
                </div>
            <?php endif; ?>
            
        


        <!-- Display Posts -->
        <div class="news-feed-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post">
                    <p><strong><?php echo htmlspecialchars($post['first_name'] . " " . $post['middle_name'] . " " . $post['last_name']); ?> (<?php echo htmlspecialchars($post['username']); ?>):</strong></p>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <p><small>Posted on <?php echo $post['created_at']; ?></small></p>



                    <!-- Display Uploaded Image -->
                    <?php if (!empty($post['image_path'])): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Uploaded Image" style="max-width: 100%; height: auto;">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['file_path'])): ?>
                        <div>
                            <a href="<?php echo htmlspecialchars($post['file_path'] ?? ''); ?>" download>Download attachment</a>
                        </div>
                    <?php endif; ?>
                    
                    
                    <!-- Show delete button if the user is a teacher of admin -->
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher'): ?>
                        <form action="post_actions.php" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <?php if ($post['is_archived'] == 0): ?>
                                <button type="submit" name="action" value="archive">Archive</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="restore">Restore</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    

                        <div>
                            <!-- Show delete button if the user is an admin -->
                            <?php if (($_SESSION['role'] == 'admin')): ?>
                                <form action="delete_post.php" method="POST" class="delete-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="delete-button">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>


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
