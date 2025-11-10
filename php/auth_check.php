<?php
// php/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for remember_me cookie if not logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    include_once 'db_connect.php';
    
    $token = $_COOKIE['remember_me'];

    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Restore the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
    } else {
        // Invalid token, clear cookie
        setcookie('remember_me', '', time() - 3600, "/");
    }
    
    $stmt->close();
}

// If not logged in, redirect to login

?>