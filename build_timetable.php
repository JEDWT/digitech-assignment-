<?php
session_start();
require_once 'php/db_connect.php';

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

$user_id = $_SESSION['user_id'];
$message = "";

// Get current day and week from URL (no action parameter needed)
$day = $_GET['day'] ?? 'Monday';
$CurrentWeek = $_GET['week'] ?? 'A';

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
$currentIndex = array_search($day, $days);

// Calculate what the NEXT day/week should be
$nextDay = $day;
$nextWeek = $CurrentWeek;

if ($day == "Friday") {
    $nextDay = "Monday";
    $nextWeek = ($CurrentWeek === "A") ? "B" : "A";
} else {
    $nextDay = $days[$currentIndex + 1];
}

// Calculate what the PREVIOUS day/week should be
$previousday = $day;
$previousweek = $CurrentWeek;

if ($day == "Monday") {
    $previousday = "Friday";
    $previousweek = ($CurrentWeek === "B") ? "A" : "B";
} else {
    $previousday = $days[$currentIndex - 1];
}

// Load all classes for this user
$stmt = $conn->prepare("SELECT id, subject_name FROM classes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['period'] as $period_number => $subject_id) {
        if (!empty($subject_id)) {
            // Check if entry exists
            
            $check = $conn->prepare("SELECT id FROM timetable_entries WHERE user_id = ? AND day = ? AND period_number = ? AND Week = ?");
            $check->bind_param("isis", $user_id, $day, $period_number,$CurrentWeek);
            $check->execute();
            $checkResult = $check->get_result();
            if ($checkResult->num_rows > 0) {
                // Update existing entry
                $update = $conn->prepare("UPDATE timetable_entries SET subject_id = ?, Week = ? WHERE user_id = ? AND day = ? AND period_number = ? AND Week = ?");
                $update->bind_param("isisis", $subject_id,$CurrentWeek, $user_id, $day, $period_number,$CurrentWeek);
                $update->execute();
                $update->close();
            } else {
                // Insert new entry
                $insert = $conn->prepare("INSERT INTO timetable_entries (user_id, day, period_number, subject_id,Week) VALUES (?, ?, ?, ?,?)");
                $insert->bind_param("isiis", $user_id, $day, $period_number, $subject_id,$CurrentWeek);
                $insert->execute();
                $insert->close();
            }
            $check->close();
        }
    }

    $message = "$day saved successfully!";
    // Redirect if next/back button pressed
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'next') {
            header("Location: build_timetable.php?day=" . urlencode($nextDay) . "&week=" . urlencode($nextWeek));
            exit;
        } elseif ($_POST['action'] === 'back') {
            header("Location: build_timetable.php?day=" . urlencode($previousday) . "&week=" . urlencode($previousweek));
            exit;
        }
    }
}

// Load current timetable for this day
$stmt = $conn->prepare("SELECT period_number, subject_id FROM timetable_entries WHERE user_id = ? AND day = ? AND Week = ?");
$stmt->bind_param("iss", $user_id, $day,$CurrentWeek);
$stmt->execute();
$result = $stmt->get_result();
$timetable = [];
while ($row = $result->fetch_assoc()) {
    $timetable[$row['period_number']] = $row['subject_id'];
}
$stmt->close();

// Define periods 
$periods = [
    1 => "8:40 - 9:40",
    2 => "9:40 - 10:40",
    3 => "11:00 - 12:00",
    4 => "12:00 - 1:00",
    5 => "1:40 - 2:40"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Build Timetable - <?= htmlspecialchars($day) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center; /* center children horizontally */
            width: 60%;          /* optional max width */
        }
        table {
            border-collapse: collapse;
            background: white;
            width: 100%; /* fill container width */
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-bottom: 20px; /* space between table and buttons */
        }
        th, td {
            padding: 25px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        select {
            padding: 6px;
            border-radius: 6px;
        }
        button {
            margin-top: 5px;
            padding: 10px 20px;
            background: #0078ff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            flex-direction: column; /* stack buttons vertically */
        }
     
        .message {
            margin: 10px;
            color: green;
        }
        
       .button-group {
            display: flex;
            flex-direction: column; /* vertical stack */
            align-items: center;
            gap: 10px;              /* spacing between buttons */
            width: 100%;            /* same as container width */
        }

        .button-group button {
            width: 60%; /* slightly smaller than table */
            padding: 10px 0;
            background: #0078ff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .button-group button:hover {
            background: #005fc7;
        }
    </style>
</head>
<body>

    <h1> Week <?= htmlspecialchars($CurrentWeek)?> , <?= htmlspecialchars($day) ?> Timetable</h1>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
<div class="container">
    <form method="POST">
        <table>
            <tr>
                <th>Period</th>
                <th>Time</th>
                <th>Class</th>
            </tr>

            <?php foreach ($periods as $period_number => $time): ?>
                <tr>
                    <td><?= $period_number ?></td>
                    <td><?= htmlspecialchars($time) ?></td>
                    <td>
                        <select name="period[<?= $period_number ?>]">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" 
                                    <?= (isset($timetable[$period_number]) && $timetable[$period_number] == $class['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="button-group">
            <button type="submit">💾 Save <?= htmlspecialchars($day) ?></button>


            <!-- Next Button -->
            <?php if (!($day == "Friday" && $CurrentWeek === "B")): ?>
                <button type="submit" name="action" value="next">➡️ Next: <?= htmlspecialchars($nextDay) ?></button>
            <?php else: ?>
                <p>🎉 All days completed!</p>
            <?php endif; ?>

            <!-- Back Button -->
            <?php if (!($day == "Monday" && $CurrentWeek === "A")): ?>
                <button type="submit" name="action" value="back">⬅️ Back: <?= htmlspecialchars($previousday) ?></button>
            <?php elseif ($day == "Monday" && $CurrentWeek === "A"):?>
                <button type="submit" formaction="create_timetable.php">⬅️ Back: <?= htmlspecialchars("Class creator") ?></button>
            <?php endif; ?>

        </div>
    </form>
</div>
       
    

   

    

</body>
</html>