<?php
session_start();
require_once 'db.php';

$raw_token   = $_GET['token'] ?? '';
$errors      = [];
$token_valid = false;
$email       = null;

// Validate incoming token
if (!empty($raw_token)) {
    $token_hash = hash('sha256', $raw_token);

    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $token_valid = true;
        $email       = $row['email'];
    } else {
        $errors[] = "Invalid or expired password reset link.";
    }
    $stmt->close();
} else {
    $errors[] = "No reset token provided.";
}

// Process new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_pwd  = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_pwd) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password in database
        $update_stmt = $conn->prepare("UPDATE student_register SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        $update_stmt->execute();
        $update_stmt->close();

        // Invalidate token so it cannot be reused
        $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del_stmt->bind_param("s", $email);
        $del_stmt->execute();
        $del_stmt->close();

        $_SESSION['success'] = "Password updated successfully! Please log in.";
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { text-align: center; margin-bottom: 20px; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        .btn { width: 100%; padding: 10px; background: #1a73e8; border: none; color: #fff; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:disabled { background: #a0c3ff; cursor: not-allowed; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; }

        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { width: 100%; padding: 10px; padding-right: 42px !important; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
        
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none !important; }

        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #718096; padding: 4px; }
        .toggle-password:hover { color: #1a73e8; }
    </style>
</head>
<body>

<div class="card">
    <h2>Set New Password</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error) { echo "• " . htmlspecialchars($error) . "<br>"; } ?>
        </div>
    <?php endif; ?>

    <?php if ($token_valid): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password', this)" tabindex="-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" tabindex="-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
    <?php else: ?>
        <p style="text-align: center; font-size: 14px;"><a href="forgot_password.php" style="color: #1a73e8;">Request a new reset link</a></p>
    <?php endif; ?>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
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