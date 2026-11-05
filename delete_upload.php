<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'faculty') {
    header("Location: login.php");
    exit();
}

$faculty_name = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? '';

$id = $_GET['id'] ?? '';

if ($id == '') {
    header("Location: my_uploads.php");
    exit();
}

/* Get the file */
$stmt = $conn->prepare("
    SELECT file_path
    FROM resources
    WHERE id = ? AND uploaded_by = ?
");

$stmt->bind_param("is", $id, $faculty_name);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: my_uploads.php");
    exit();
}

$row = $result->fetch_assoc();
$file_path = $row['file_path'];

$stmt->close();

/* Delete the actual file */
if (file_exists($file_path)) {
    unlink($file_path);
}

/* Delete from database */
$stmt = $conn->prepare("
    DELETE FROM resources
    WHERE id = ? AND uploaded_by = ?
");

$stmt->bind_param("is", $id, $faculty_name);
$stmt->execute();

$stmt->close();

header("Location: my_uploads.php");
exit();

?>