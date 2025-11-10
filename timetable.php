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

$subjects = $conn->prepare("SELECT id,Subject_name,teacher_name FROM classes WHERE user_id = ?");
$subjects->bind_param("i",$userid);
$subjects->execute();
$subect_Results = $subjects->get_result();
$subjectsTable = [];
while ($data = $subect_Results->fetch_assoc()) {
    $Subject_ID = $data['id'];
    $Subject_Name = $data['Subject_name'];
    $subjectsTable[$Subject_ID] = $Subject_Name;
}

$day_Names = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
$Week_Types = ["A","B"];
$periods = 5;
$days = 5;
$weeks = 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <title>Timetable - </title>
</head>
<body>
    <table border="1">
    <tr>
        <th>Week</th>
        <th>Day</th>
        <?php for ($p = 1; $p <= $periods; $p++): ?>
            <th>Period <?= $p ?></th>
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
            <td><?= $week_type ?></td>
            <td><?= $day_name ?></td>
            <?php for ($p = 1; $p <= $periods; $p++): ?>
                <td><?= isset($timetable[$p]) ? htmlspecialchars($subjectsTable[$timetable[$p]]) : "-" ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; 
    endfor; ?>
</table>

</body>





