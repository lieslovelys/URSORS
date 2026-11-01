<?php
session_start();
include 'config.php'; // Database connection file

// Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Check if search query is provided
if (isset($_GET['query'])) {
    $query = $_GET['query'];

    // Search for users by username
    $sql = "SELECT id, username, profile_picture FROM users WHERE username LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_query = "%" . $query . "%";
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Redirect if no query is provided
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <link rel="stylesheet" href="/Admin/FrontEnd/style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <form action="search.php" method="GET" class="search-form">
            <input type="text" name="query" placeholder="Search for users..." required>
            <button type="submit">Search</button>
        </form>
        <a href="news_feed.php">Home</a>
        <a href="profile.php">Profile</a>
        
        <a href="logout.php">Logout</a>
        
    </nav>

    <!-- Search Results -->
    <div class="container">
        <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>

        <?php if ($result->num_rows > 0): ?>
            <ul class="user-list">
                <?php while ($user = $result->fetch_assoc()): ?>
                    <li class="user-item">
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="user-profile-pic">
                        <a href="profile.php?user_id=<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No users found matching your search.</p>
        <?php endif; ?>

        <?php $stmt->close(); ?>
    </div>

</body>
</html>
