<?php
session_start();
require_once 'db.php';

// Redirect to dashboard if admin is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Query admins table by username OR email
        $stmt = $conn->prepare("SELECT id, name, username, email, password FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $admin['password'])) {
                
                // Automatically upgrade hash if algorithm or cost parameters have changed
                if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_hash, $admin['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                // Prevent Session Fixation attacks and clear stale session IDs
                session_regenerate_id(true);

                // Set session variables matching admin_dashboard.php
                $_SESSION['user_id']  = $admin['id'];
                $_SESSION['name']     = $admin['name'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email']    = $admin['email'];
                $_SESSION['role']     = 'admin';

                // Redirect to dashboard
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials. Password verification failed.";
            }
        } else {
            $error = "Invalid credentials. Username or email not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Management Portal</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #fff; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-card h2 { font-size: 22px; color: #2d3748; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 14px; color: #4a5568; margin-bottom: 6px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #e53e3e; box-shadow: 0 0 0 2px rgba(229,62,62,0.2); }
        .btn-login { width: 100%; padding: 11px; background: #e53e3e; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; transition: background 0.2s; }
        .btn-login:hover { background: #c53030; }
        .error-msg { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; text-align: center; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>Admin Portal Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php" autocomplete="off">
            <div class="form-group">
                <label for="login_input">Username or Email</label>
                <input type="text" id="login_input" name="login_input" placeholder="Enter username or email" value="<?php echo htmlspecialchars($_POST['login_input'] ?? ''); ?>" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="off" required>
            </div>

            <button type="submit" class="btn-login">Log In</button>
        </form>
    </div>

</body>
</html>