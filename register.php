<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $role = $_POST['role'];

    if (!in_array($role, ['student', 'teacher'])) {
        die("Invalid user type.");
    }

    // Insert user into the database
    $sql = "INSERT INTO users (first_name, middle_name, last_name, email, username, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // All users require admin approval except admins
    $is_approved = $role == 'admin' ? 1 : 0;

    $stmt->bind_param("sssssssi", $first_name, $middle_name, $last_name, $email, $username, $password, $role, $is_approved);

    if ($stmt->execute()) {
        echo "Registration successful! Wait for admin approval.";
        
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
