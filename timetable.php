<?php
session_start();
require_once 'php/db_connect.php';
require_once 'php/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userid = $_SESSION['user_id'];
$timetable_created = 1;

$subjects = $conn->prepare("SELECT id,Subject_name,teacher_name,location FROM classes WHERE user_id = ?");
$subjects->bind_param("i",$userid);
$subjects->execute();
$subect_Results = $subjects->get_result();
$subjectsTable = [];
$teacherNames = [];
$roomlocations = [];
while ($data = $subect_Results->fetch_assoc()) {
    $Subject_ID = $data['id'];
    $Subject_Name = $data['Subject_name'];
    $teacherName = $data['teacher_name'];
    $location = $data['location'];
    $subjectsTable[$Subject_ID] = $Subject_Name;
    $teacherNames[$Subject_ID] = $teacherName;
    $roomlocations[$Subject_ID] = $location;
}

$periodsTimes = [
    0 => "8:30 - 8:40", // fratelli
    1 => "8:40 - 9:40",
    2 => "9:40 - 10:40",
    3 => "11:10 - 12:08",
    4 => "12:12 - 1:10",
    5 => "1:40 - 2:40"
];

$day_Names = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
$Week_Types = ["A","B"];
$periods = 5;
$days = 5;
$weeks = 2;

// im to lazy to do css
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <title>Timetable - </title> 
</head>
<body>
    <form method="POST">
        <div>
            <button type="submit" formaction="Homepage.php"> Homepage</button>
        </div>
    </form>
    <table border="1">
    <tr>
       
        <th></th>
        <?php for ($p = 0; $p <= $periods; $p++): ?>
            <?php if ($p == 0):?>
                <th>Fratelli <br> <?= $periodsTimes[$p] ?></th>
            <?php else: ?>
                <th>Period <?= $p ?> <br> <?= $periodsTimes[$p] ?></th>
            <?php endif; ?>
        <?php endfor; ?>
    </tr>

    <?php for ($Current_Week = 1; $Current_Week <= $weeks; $Current_Week++): 
        $week_type = $Week_Types[$Current_Week-1];

        for ($Current_Day = 1; $Current_Day <= $days; $Current_Day++):
            $day_name = $day_Names[$Current_Day-1];

            // Fetch timetable for this day
            $classes = $conn->prepare("SELECT period_number, subject_id FROM timetable_entries WHERE user_id = ? AND day = ? AND Week = ?");
            $classes->bind_param("iss", $userid, $day_name, $week_type);
            $classes->execute();
            $result = $classes->get_result();

            $timetable = [];
            while ($row = $result->fetch_assoc()) {
                $timetable[$row['period_number']] = $row['subject_id'];
            }
            $classes->close();
    ?>
        <tr>
            <td><?= $day_name ?> <?= $week_type ?></td>
            <?php for ($p = 0; $p <= $periods; $p++): ?>
                <td><?= isset($timetable[$p]) ? htmlspecialchars($subjectsTable[$timetable[$p]]) : "-" ?>
            <br>   <?= isset($timetable[$p]) ? htmlspecialchars($teacherNames[$timetable[$p]]) : "-" ?>
            <br>   <?= isset($timetable[$p]) ? htmlspecialchars($roomlocations[$timetable[$p]]) : "-" ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; 
    endfor; ?>
</table>

</body>





