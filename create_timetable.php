<?php

session_start();
require_once 'php/db_connect.php'; 
require_once 'php/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// If logged in, redirect to timetable
if (isset($_SESSION['user_id'])) {

    $created = $conn->prepare("SELECT timetable_created FROM users WHERE id = ?");
    $created->bind_param("i",$_SESSION['user_id']);
    $created->execute();
    $result = $created->get_result();
    $TimeTable_Created = $result->fetch_assoc();
    
    if ($TimeTable_Created['timetable_created'] == 1) {
        header("Location: Homepage.php");
        exit;
    }

}

$user_id = $_SESSION['user_id'];
$message = "";
$Week_Types = ["A","B"];
$day_Names = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
$periods = 5;
$days = 5;
$weeks = 2;


// -------------------------
// Add a new class
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_name = trim($_POST['subject_name']);
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $location = $_POST['location'] ?? '';

    if (!empty($subject_name)) {
    if ($subject_name === "Fratelli") {
        // Check if Fratelli already exists
        $check = $conn->prepare("SELECT id FROM classes WHERE user_id = ? AND subject_name = ?");
        $check->bind_param("is", $user_id, $subject_name);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // ✅ Update existing Fratelli
            $stmt = $conn->prepare("UPDATE classes SET teacher_name = ?, color = ?, location = ? WHERE user_id = ? AND subject_name = ?");
            $stmt->bind_param("sssds", $teacher_name, $color, $location, $user_id, $subject_name);
            $stmt->execute();
            $stmt->close();
        } else {
            // ✅ Create new Fratelli and apply your existing "add to every week/day" logic
            for ($week = 1; $week <= $total_weeks; $week++) {
                foreach ($days as $day) {
                    $day_name = $day_Names[$day-1];
                    $week_type = $Week_Types[$week-1];
                    $period = 0;
                    $stmt = $conn->prepare("INSERT INTO timetable_entries (user_id, day, period_number, subject_id,Week) VALUES (?, ?, ?, ?,?)");
                    $stmt->bind_param("isiis", $user_id, $day, $period, $subject_id,$week_type);
                    $stmt->execute();
                    $stmt->close();
                    
                }
            }
        }

        $check->close();
    } else {
        // ✨ Your normal add-subject logic (for non-Fratelli subjects)
        $stmt = $conn->prepare("INSERT INTO classes (user_id, subject_name, teacher_name, color, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $subject_name, $teacher_name, $color, $location);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: create_timetable.php");
    exit;
}


}

// -------------------------
// Delete a class
// -------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Delete related timetable entries first
    $stmt = $conn->prepare("DELETE FROM timetable_entries WHERE subject_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Then delete the class itself
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: create_timetable.php?deleted=1");
    exit;
}

// -------------------------
// Edit / Update a class
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = intval($_POST['class_id']);
    $subject_name = trim($_POST['subject_name']);
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $location = $_POST['location'] ?? '';

    if (!empty($subject_name)) {
        $stmt = $conn->prepare("UPDATE classes SET subject_name = ?, teacher_name = ?, color = ?, location = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssii", $subject_name, $teacher_name, $color,$location, $edit_id, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: create_timetable.php?updated=1");
        exit;
    } else {
        $message = "Please enter a subject name.";
    }
}

// -------------------------
// Fetch all classes for user
// -------------------------
$stmt = $conn->prepare("SELECT * FROM classes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_GET['success'])) $message = "Class added successfully!";
if (isset($_GET['deleted'])) $message = "Class deleted successfully!";
if (isset($_GET['updated'])) $message = "Class updated successfully!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Timetable</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 50px;
            padding: 50px;
        }
        .panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 400px;
        }
        .under-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 400px;
        }
        .panel-group {
            display: flex;
            flex-direction: column; /* stacks the second panel and under-panel vertically */
            gap: 15px;
        }
        input, button {
            margin: 5px 0;
            padding: 8px;
            width: 100%;
        }
        .class-list {
            margin-top: 10px;
        }
        .class-item {
            background: #e9f2ff;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        .edit-btn, .delete-btn {
            flex: 1;
            border: none;
            padding: 6px;
            cursor: pointer;
            border-radius: 4px;
        }
        .edit-btn {
            background: #ffce54;
        }
        .delete-btn {
            background: #ed5565;
            color: white;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="panel">
        <h2>Create Periods (Subjects)</h2>

        <?php if ($message): ?>
            <p style="color: green;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add">

            <label>Subject Name:</label>
            <input type="text" name="subject_name" required>

            <label>Teacher Name:</label>
            <input type="text" name="teacher_name" required>

            <label>Room:</label>
            <input type="text" name="location" required>

            <label>Colour (optional):</label>
            <input type="color" name="color">

            <button type="submit">Add Subject</button>
        </form>

        <h3>Your Subjects:</h3>
        <div class="class-list">
            <?php foreach ($classes as $class): ?>
                <div class="class-item" style="background-color: <?= htmlspecialchars($class['color'] ?: '#e9f2ff') ?>">
                    <strong><?= htmlspecialchars($class['subject_name']) ?></strong>
                    <?php if ($class['teacher_name']): ?>
                        <br><small><?= htmlspecialchars($class['teacher_name']) ?></small>
                    <?php endif; ?>

                    <div class="actions">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <input type="text" name="subject_name" value="<?= htmlspecialchars($class['subject_name']) ?>" required>
                            <input type="text" name="teacher_name" value="<?= htmlspecialchars($class['teacher_name']) ?>" required>
                            <input type="text" name="location" value="<?= htmlspecialchars($class['location']) ?>" required>
                            <input type="color" name="color" value="<?= htmlspecialchars($class['color'] ?: '#e9f2ff') ?>">
                            <button class="edit-btn" type="submit">Save</button>
                        </form>

                        <a href="create_timetable.php?delete_id=<?= $class['id'] ?>" class="delete-btn" onclick="return confirm('Delete this class?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

      <div class="panel-group">
        <div class="panel">
            <h2>Timetable Builder (Next Step)</h2>
            <p>Once you’ve added all your subjects, you can begin assigning them to days and periods.</p>

            <form action="build_timetable.php" method="GET">
                <input type="hidden" name="day" value="Monday">
                <button type="submit" style="
                    background: #0078ff;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    cursor: pointer;
                ">
                    ➡️ Start Building Timetable
                </button>
            </form>
        </div>

        <div class="under-panel">
            <h2> Fratelli </h2>
            <form  method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="class" value="Fratelli">

                <input type="hidden" name="subject_name" value="Fratelli">


                <label>Teacher Name:</label>
                <input type="text" name="teacher_name" required>

                <label>Room:</label>
                <input type="text" name="location" required>

                <label>Colour (optional):</label>
                <input type="color" name="color">

               <button type="submit" style="
                    background: #0078ff;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    cursor: pointer;
                ">
                   Save Frattelli
                </button>
            </form>


        </div>
    </div>

</body>
</html>
