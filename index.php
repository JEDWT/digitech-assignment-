<?php
session_start();
require_once 'php/db_connect.php'; 
require_once 'php/auth_check.php';

// If logged in, redirect to timetable
if (isset($_SESSION['user_id'])) {

    $created = $conn->prepare("SELECT timetable_created FROM users WHERE id = ?");
    $created->bind_param("i",$_SESSION['user_id']);
    $created->execute();
    $result = $created->get_result();
    $TimeTable_Created = $result->fetch_assoc();
    
    if ($TimeTable_Created['timetable_created'] == 1) {
        header("Location: Homepage.php");
    } else {
        header("Location: create_timetable.php");
    }
    exit;
}

// Handle POSTed form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Start session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['First_Name']; // Make sure column name matches

        // Handle "Remember Me"
        if (isset($_POST['remember_me'])) {
            $token = bin2hex(random_bytes(16));
            // Set secure to false if not using HTTPS, true if you are
            setcookie('remember_me', $token, time() + (30*24*60*60), "/", "", false, true);

            // Store token in database
            $stmt = $conn->prepare("UPDATE users SET Remember_token = ? WHERE id = ?");
            $stmt->bind_param("si", $token, $user['id']);
            $stmt->execute();
            $stmt->close();
        }

        $query->close();
        header("Location: create_timetable.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
    $query->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - My Timetable</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="School Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <label>
            <input type="checkbox" name="remember_me"> Remember Me
        </label><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>