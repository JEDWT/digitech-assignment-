<?php
session_start();
require_once 'php/db_connect.php'; // <- your database connection

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// -------------------------
// Add a new class
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_name = trim($_POST['subject_name']);
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $color = trim($_POST['color'] ?? '');

    if (!empty($subject_name)) {
        $stmt = $conn->prepare("INSERT INTO classes (user_id, subject_name, teacher_name, color) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $subject_name, $teacher_name, $color);
        $stmt->execute();
        $stmt->close();

        header("Location: create_timetable.php?success=1");
        exit;
    } else {
        $message = "Please enter a subject name.";
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

    if (!empty($subject_name)) {
        $stmt = $conn->prepare("UPDATE classes SET subject_name = ?, teacher_name = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $subject_name, $teacher_name, $color, $edit_id, $user_id);
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

            <label>Teacher Name (optional):</label>
            <input type="text" name="teacher_name">

            <label>Color (optional):</label>
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
                            <input type="text" name="teacher_name" value="<?= htmlspecialchars($class['teacher_name']) ?>">
                            <input type="color" name="color" value="<?= htmlspecialchars($class['color'] ?: '#e9f2ff') ?>">
                            <button class="edit-btn" type="submit">Save</button>
                        </form>

                        <a href="create_timetable.php?delete_id=<?= $class['id'] ?>" class="delete-btn" onclick="return confirm('Delete this class?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

  <div class="panel">
    <h2>Timetable Builder (Next Step)</h2>
    <p>Once you’ve added all your subjects, you can begin assigning them to days and periods.</p>

    <form action="index.php" method="GET">
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


</body>
</html>
