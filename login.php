<?php
session_start();
require_once 'db.php';

// Redirect if user is already logged in based on their role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'faculty') {
        header("Location: faculty_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: dashboard.php");
        exit;
    }
}

$errors = [];
$success_message = '';

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = trim($_POST['user_login_identity'] ?? '');
    $password       = $_POST['user_login_secret'] ?? '';

    if (empty($username_email) || empty($password)) {
        $errors[] = "Please fill in all fields.";
    } else {
        $user_found = false;

        // 1. CHECK ADMINS TABLE
        $stmt = $conn->prepare("SELECT id, name, username, email, password, 'admin' AS role FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();

            if (password_verify($password, $user['password'])) {
                $user_found = true;

                // Rehash password if needed
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $up_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $up_stmt->bind_param("si", $new_hash, $user['id']);
                    $up_stmt->execute();
                    $up_stmt->close();
                }

                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['name']     = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = 'admin';

                header("Location: admin_dashboard.php");
                exit;
            } else {
                $errors[] = "Invalid password. Please try again.";
                $user_found = true;
            }
        } else {
            $stmt->close();
        }

        // 2. CHECK FACULTY TABLE (If not found in admins)
        if (!$user_found) {
            $stmt = $conn->prepare("SELECT id, name, username, email, password, 'faculty' AS role FROM faculty WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $stmt->close();

                if (password_verify($password, $user['password'])) {
                    $user_found = true;

                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['name']      = $user['name'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['role']      = 'faculty';

                    header("Location: faculty_dashboard.php");
                    exit;
                } else {
                    $errors[] = "Invalid password. Please try again.";
                    $user_found = true;
                }
            } else {
                $stmt->close();
            }
        }

        // 3. CHECK STUDENTS TABLE (If not found in admins or faculty)
        if (!$user_found) {
            $stmt = $conn->prepare("SELECT id, name, username, password, COALESCE(role, 'student') AS role FROM student_register WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $stmt->close();

                if (password_verify($password, $user['password'])) {
                    $user_found = true;

                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role']      = 'student';

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $errors[] = "Invalid password. Please try again.";
                    $user_found = true;
                }
            } else {
                $stmt->close();
            }
        }

        // If user wasn't found in any of the 3 tables
        if (!$user_found && empty($errors)) {
            $errors[] = "No account found with that username or email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { text-align: center; margin-bottom: 20px; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; font-size: 14px; }
        .form-group input:focus { border-color: #1a73e8; }
        .btn { width: 100%; padding: 10px; background: #1a73e8; border: none; color: #fff; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #1557b0; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; }
        .options { display: flex; justify-content: space-between; margin-top: 12px; font-size: 13px; }
        .options a { color: #1a73e8; text-decoration: none; font-weight: 600; }
        .options a:hover { text-decoration: underline; }

        /* Password Field Styling & Disabling Native Icons */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { padding-right: 42px !important; }
        
        /* Hide browser-native eye/password buttons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none !important; }
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button { visibility: hidden !important; display: none !important; pointer-events: none !important; }

        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #718096; padding: 4px; }
        .toggle-password:hover { color: #1a73e8; }
    </style>
</head>
<body>

<div class="card">
    <h2>Portal Login</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error) { echo "• " . htmlspecialchars($error) . "<br>"; } ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="" autocomplete="off">
        <input type="text" style="display:none" tabIndex="-1">
        <input type="password" style="display:none" tabIndex="-1">

        <div class="form-group">
            <label>Username or Email</label>
            <input type="text" name="user_login_identity" id="user_login_identity" value="<?php echo htmlspecialchars($_POST['user_login_identity'] ?? ''); ?>" autocomplete="off" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="user_login_secret" id="user_login_secret" autocomplete="new-password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('user_login_secret', this)" tabindex="-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="options">
        <a href="forgot_password.php">Forgot Password?</a>
        <a href="register.php">Create Student Account</a>
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