<?php
session_start();
include 'php/db_connect.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
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
    }
}

// If still not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userid = $_SESSION['user_id'];
$timetable_created = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $conn->prepare("UPDATE users SET timetable_created = ? WHERE id = ?");
    $confirm->bind_param("ii",$timetable_created,$userid);
    $confirm->execute();
    $confirm->close();
};

$day_Names = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
$periods = 5;
$days = 5;
$weeks = 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <title>Timetable - <?= htmlspecialchars($day) ?></title>
</head>
<body>
    <table>
    <?php foreach ($weeks as $Current_Week): 
            foreach ($days as $Current_Day):
                foreach ($periods as $current_Period): ?>


                
    <?php               endforeach;
                    endforeach; 
            endforeach; ?>
        
    
    </table>
</body>





