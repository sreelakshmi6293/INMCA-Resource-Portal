<?php
session_start();

// 1. Identify user role before clearing session data
$redirect_url = 'login.php'; // Default fallback for students

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $redirect_url = 'admin_login.php';
    } elseif ($_SESSION['role'] === 'faculty') {
        $redirect_url = 'faculty_login.php';
    }
}

// 2. Unset all session variables
session_unset();

// 3. Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy session
session_destroy();

// 5. Redirect user to their respective login page
header("Location: " . $redirect_url);
exit;
?>