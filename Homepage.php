<?php
require_once 'php/db_connect.php';
require_once 'php/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $conn->prepare("UPDATE users SET timetable_created = ? WHERE id = ?");
    $confirm->bind_param("ii",$timetable_created,$userid);
    $confirm->execute();
    $confirm->close();
};



?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title> Home Page </title>
        
        <style>
            button {
                margin-top: 5px;
                padding: 10px 20px;
                background: #0078ff;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                
            }
        </style>

    </head>
    <body>
        <form method="POST">
            <div>
                <button type="submit" formaction="timetable.php"> TimeTable</button>
                <button type="submit" formaction="search.php"> search </button>
            </div>
        </form>
    </body>
</html>