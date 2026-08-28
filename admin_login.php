<?php
session_start();
require_once 'db.php';

// Redirect if already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input)) {
        $errors[] = "Username or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        // Search the dedicated admins table
        $stmt = $conn->prepare("SELECT id, name, username, email, password FROM admins WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify Bcrypt password
            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']  = $admin['id'];
                $_SESSION['name']     = $admin['name'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role']     = 'admin';

                header("Location: admin_dashboard.php");
                exit;
            } else {
                $errors[] = "Invalid credentials. Password verification failed.";
            }
        } else {
            $errors[] = "Invalid credentials. Username or email not found.";
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
    <title>Admin Portal - Login</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #1a202c; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 35px 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 100%; max-width: 400px; }
        .badge { background: #e53e3e; color: #fff; text-transform: uppercase; font-size: 11px; font-weight: bold; letter-spacing: 1px; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; font-size: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #4a5568; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 6px; outline: none; font-size: 14px; }
        .form-group input:focus { border-color: #e53e3e; }
        .btn { width: 100%; padding: 12px; background: #e53e3e; border: none; color: #ffffff; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #c53030; }
        .alert-danger { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }

        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { padding-right: 42px !important; }
        
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none !important; }

        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #a0aec0; padding: 4px; }
        .toggle-password:hover { color: #e53e3e; }
        .link { text-align: center; margin-top: 20px; font-size: 13px; }
        .link a { color: #718096; text-decoration: none; }
        .link a:hover { color: #e53e3e; }
    </style>
</head>
<body>

<div class="card">
    <span class="badge">Restricted Access</span>
    <h2>Admin Login</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $error) { echo "• " . htmlspecialchars($error) . "<br>"; } ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <div class="form-group">
            <label>Admin Username or Email</label>
            <input type="text" name="login_input" placeholder="Enter admin credentials" autocomplete="off" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" id="admin_password" name="password" placeholder="••••••••" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('admin_password', this)" tabindex="-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn">Authenticate Admin</button>
    </form>

    <div class="link">
        <a href="login.php">← Back to Student Portal</a>
    </div>
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