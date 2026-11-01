<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password, role, is_approved FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($user['is_approved'] == 0) {
            echo "Your account is pending admin approval.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin_home.php");
            } elseif ($user['role'] == 'teacher'){
                header("Location: /Admin/FrontEnd/home_sections/welcome.php");
            }
            else {
                header("Location: /Admin/FrontEnd/home_sections/welcome.php");
            }
           exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with that username.";
    }

    $stmt->close();

}
//Redirect to login if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: /Admin/FrontEnd/login.html");
    exit;
}

?>
