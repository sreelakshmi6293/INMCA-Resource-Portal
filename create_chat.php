<?php

session_start();

require_once "db.php";

// Check if the student is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit;
}

// Get logged-in user's ID
$user_id = $_SESSION['user_id'];

// Get semester and subject from the request
$semester = $_POST['semester'] ?? null;
$subject = $_POST['subject'] ?? null;

// Default chat title
$title = "New Chat";

// Create SQL query
$sql = "INSERT INTO chat_sessions 
        (user_id, title, semester, subject)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);
    exit;
}

// Bind values
$stmt->bind_param(
    "isis",
    $user_id,
    $title,
    $semester,
    $subject
);

// Execute query
if ($stmt->execute()) {

    // Get newly created chat ID
    $chat_id = $stmt->insert_id;

    echo json_encode([
        "success" => true,
        "chat_id" => $chat_id,
        "message" => "Chat created successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not create chat."
    ]);
}

$stmt->close();
$conn->close();

?>