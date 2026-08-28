<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "student_portal";
$port = 3307; // Standard MySQL port in XAMPP

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>