<?php
session_start();
include 'config.php'; // Database connection file

// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /Admin/FrontEnd/login.html");
    exit;
}

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

if (!empty($selected_section)) {
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
    <title>Feed | URS S.O.R.S</title>
    <link rel="stylesheet" href="/Admin/FrontEnd/style.css">
</head>
<body>

     <!-- Navigation Bar with Search Form -->
    <nav class="navbar">

        <!-- Search Form -->
        <form action="search.php" method="GET" class="search-form">
            <input type="text" name="query" placeholder="Search for users..." required>
            <button type="submit">Search</button>
        </form> <br>

        <!-- Sections -->
        <form action="" method="GET">
            <!-- Section Filter -->
            <select name="section" onchange="this.form.submit()">
                <option value="All" <?php if ($selected_section == 'All') echo 'selected'; ?>>All</option>
                <?php foreach ($sections as $sec => $details): ?>
                    <option value="<?php echo htmlspecialchars($sec); ?>" <?php if ($selected_section == $sec) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($sec); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Subsection Filter -->
            <?php if (!empty($sections[$selected_section]['subsections'])): ?>
                <select name="sub_section" onchange="this.form.submit()">
                    <option value="">All Subsections</option>
                    <?php 
                    foreach ($sections[$selected_section]['subsections'] as $sub => $nested): ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php if ($selected_sub_section == $sub) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sub); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Nested Subsection Filter -->
            <?php if (!empty($sections[$selected_section]['subsections'][$selected_sub_section])): ?>
                <select name="nested_sub_section" onchange="this.form.submit()">
                    <option value="">All Nested Subsections</option>
                    <?php 
                    foreach ($sections[$selected_section]['subsections'][$selected_sub_section]['subsections'] as $nested => $deep): ?>
                        <option value="<?php echo htmlspecialchars($nested); ?>" <?php if ($selected_nested_sub_section == $nested) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($nested); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Deep Subsection Filter -->
            <?php if (!empty($sections[$selected_section]['subsections'][$selected_sub_section]['subsections'][$selected_nested_sub_section])): ?>
                <select name="deep_sub_section" onchange="this.form.submit()">
                    <option value="">All Deep Subsections</option>
                    <?php 
                    foreach ($sections[$selected_section]['subsections'][$selected_sub_section]['subsections'][$selected_nested_sub_section] as $deep): ?>
                        <option value="<?php echo htmlspecialchars($deep); ?>" <?php if ($selected_deep_sub_section == $deep) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($deep); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>




        <a href="home.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="ranking_board.php">Ranking Board</a>
        <a href="archived_posts.php">Archived Posts</a>
        <a href="logout.php">Logout</a>
        
        
    </nav>


    
    <!-- News Feed -->
    <div class="container">

        <!-- Section Header -->
        <h2>News Feed</h2>
        <p>
            <strong>Current Location:</strong>
            Section: <?php echo htmlspecialchars($selected_section); ?> 
            <?php if (!empty($selected_sub_section)): ?>
                > Subsection: <?php echo htmlspecialchars($selected_sub_section); ?>
            <?php endif; ?>
            <?php if (!empty($selected_nested_sub_section)): ?>
                > Nested Subsection: <?php echo htmlspecialchars($selected_nested_sub_section); ?>
            <?php endif; ?>
            <?php if (!empty($selected_deep_sub_section)): ?>
                > Deep Subsection: <?php echo htmlspecialchars($selected_deep_sub_section); ?>
            <?php endif; ?>
        </p>
        

        <!-- Post Form -->
        <div class="post-form-container">
            <h2>Create New Post</h2>
            <form action="post_handler.php" method="POST" enctype="multipart/form-data">
                <textarea name="content" placeholder="What's on your mind?" required></textarea><br>
                
                <!-- Automatically set section, sub_section, nested_sub_section, and deep_sub_section -->
                <input type="hidden" name="section" value="<?php echo htmlspecialchars($selected_section); ?>">
                <input type="hidden" name="sub_section" value="<?php echo htmlspecialchars($selected_sub_section); ?>">
                <input type="hidden" name="nested_sub_section" value="<?php echo htmlspecialchars($selected_nested_sub_section); ?>">
                <input type="hidden" name="deep_sub_section" value="<?php echo htmlspecialchars($selected_deep_sub_section); ?>">

                <label for="image">Upload an Image:</label>
                <input type="file" name="image" id="image" accept="image/*"><br>
                <label for="file">Upload a File:</label>
                <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"><br>

                <button type="submit">Post</button>
            </form>

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
                                        WHERE comments.post_id = ? 
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
                                                <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['is_admin']): ?>
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

                        <!-- Comments Section -->
                        <div class="comments-section">
                            <!-- Comments display and form go here -->
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts to display.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
