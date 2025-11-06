<?php
session_start();
require_once 'php/db_connect.php';

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
        table {
            border-collapse: collapse;
            background: white;
            width: 60%;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        select {
            padding: 6px;
            border-radius: 6px;
        }
        button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #0078ff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #005fc7;
        }
        .message {
            margin: 10px;
            color: green;
        }
    </style>
</head>
<body>

    <h1> Week <?= htmlspecialchars($CurrentWeek)?> , <?= htmlspecialchars($day) ?> Timetable</h1>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

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

        <button type="submit">💾 Save <?= htmlspecialchars($day) ?></button>
    </form>

    <br>

   

    <!-- Next Button -->
    <?php if (!($day == "Friday" && $CurrentWeek === "B")): ?>
        <a href="?day=<?= urlencode($nextDay) ?>&week=<?= urlencode($nextWeek) ?>">
            <button type="button">➡️ Next: <?= htmlspecialchars($nextDay) ?></button>
        </a>
    <?php else: ?>
        <p>🎉 All days completed!</p>
    <?php endif; ?>

     <!-- Back Button -->
    <?php if (!($day == "Monday" && $CurrentWeek === "A")): ?>
        <a href="?day=<?= urlencode($previousday) ?>&week=<?= urlencode($previousweek) ?>">
            <button type="button">⬅️ Back: <?= htmlspecialchars($previousday) ?></button>
        </a>
    <?php elseif ($day == "Monday" && $CurrentWeek === "A"): ?>
        <form action="create_timetable.php" method="POST">
             <button type="submit">⬅️ Back: <?= htmlspecialchars("Class creator") ?></button>
        </form>
    <?php endif; ?>

</body>
</html>