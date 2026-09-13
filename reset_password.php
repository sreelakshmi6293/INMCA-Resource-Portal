<?php
session_start();
require_once 'db.php';

date_default_timezone_set('Asia/Kolkata');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// 1. Verify token exists and is not expired
if (!empty($token)) {
    $token_hash = hash('sha256', $token);
    
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($reset = $result->fetch_assoc()) {
        if (strtotime($reset['expires_at']) <= time()) {
            $error = "This password reset link has expired. Please request a new one.";
        } else {
            $email = $reset['email'];
        }
    } else {
        $error = "Invalid or expired password reset link.";
    }
    $stmt->close();
} else {
    $error = "No reset token provided.";
}

// 2. Handle Password Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password securely
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Check which table the email exists in
        $chk_student = $conn->prepare("SELECT id FROM student_register WHERE LOWER(email) = ?");
        $chk_student->bind_param("s", $email);
        $chk_student->execute();
        $is_student = $chk_student->get_result()->num_rows > 0;
        $chk_student->close();

        $updated = false;

        if ($is_student) {
            // Update Student Password
            $upd = $conn->prepare("UPDATE student_register SET password = ? WHERE LOWER(email) = ?");
            $upd->bind_param("ss", $hashed_password, $email);
            $updated = $upd->execute();
            $upd->close();
        } else {
            // Check Faculty Table
            $chk_faculty = $conn->prepare("SELECT id FROM faculty WHERE LOWER(email) = ?");
            $chk_faculty->bind_param("s", $email);
            $chk_faculty->execute();
            $is_faculty = $chk_faculty->get_result()->num_rows > 0;
            $chk_faculty->close();

            if ($is_faculty) {
                // Update Faculty Password
                $upd = $conn->prepare("UPDATE faculty SET password = ? WHERE LOWER(email) = ?");
                $upd->bind_param("ss", $hashed_password, $email);
                $updated = $upd->execute();
                $upd->close();
            }
        }

        if ($updated) {
            // Delete used token from database
            $del = $conn->prepare("DELETE FROM password_resets WHERE LOWER(email) = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            $_SESSION['success'] = "Password reset successfully! You can now log in with your new password.";
            header("Location: login.php");
            exit;
        } else {
            $error = "Failed to update password. Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { text-align: center; margin-bottom: 20px; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
        .btn { width: 100%; padding: 10px; background: #1a73e8; border: none; color: #fff; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #1557b0; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .link { text-align: center; margin-top: 15px; font-size: 14px; }
        .link a { color: #1a73e8; text-decoration: none; font-weight: 600; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 42px !important; }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #718096; padding: 4px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Reset Password</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($error) || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>New Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                <button type="button" class="toggle-password" onclick="toggleVisibility('password', this)" tabindex="-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                <button type="button" class="toggle-password" onclick="toggleVisibility('confirm_password', this)" tabindex="-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn">Update Password</button>
    </form>
    <?php endif; ?>

    <div class="link">
        <a href="login.php">Back to Login</a>
    </div>
</div>

<script>
function toggleVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    btn.innerHTML = isPassword ? 
        `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>` : 
        `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
}
</script>

</body>
</html>