<?php
session_start();
require_once 'db.php';

// Load PHPMailer Autoloader
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Set timezone for email timestamps
date_default_timezone_set('Asia/Kolkata');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side Validations
    if (empty($name) || empty($username) || empty($phone_no) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = "Full Name can only contain alphabetic letters and spaces.";
    }
    if (strlen($username) < 5) {
        $errors[] = "Username must be at least 5 characters long.";
    }
    if (!preg_match('/^[0-9]{10}$/', $phone_no)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check Duplicate Records
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id, username, email, `phone no` FROM student_register WHERE username = ? OR email = ? OR `phone no` = ?");
        $check_stmt->bind_param("sss", $username, $email, $phone_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            if ($existing_user['email'] === $email) {
                $errors[] = "You have already registered with this email address.";
            } else if ($existing_user['username'] === $username) {
                $errors[] = "This username is already taken. Please choose another.";
            } else if ($existing_user['phone no'] === $phone_no) {
                $errors[] = "This phone number is already registered.";
            }
        }
        $check_stmt->close();
    }

    // Insert User & Send Email
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = 'student'; 

        $stmt = $conn->prepare("INSERT INTO student_register (name, username, `phone no`, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $username, $phone_no, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $stmt->close();

            // Dispatch Confirmation Email via PHPMailer
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'inmca.student.portal@gmail.com'; // <--- Your Gmail Address
                $mail->Password   = 'mroo naiw lvoi tdze';   // <--- Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // SSL Certificate Fix for Local Hosts (XAMPP/WAMP)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    )
                );

                // Recipients
                $mail->setFrom('inmca.student.portal@gmail.com', 'Student Portal Team');
                $mail->addAddress($email, $name);

                // Build Login URL dynamically
                $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $domain    = $_SERVER['HTTP_HOST'];
                $path      = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $login_url = "{$protocol}://{$domain}{$path}/login.php";

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to INMCA Resource Portal - Account Registered';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                        <h2 style='color: #1a73e8; text-align: center;'>Registration Successful!</h2>
                        <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                        <p>Thank you for registering on our platform. Your account details are set up as follows:</p>
                        <ul style='background: #f8f9fa; padding: 15px 25px; border-radius: 5px; list-style-type: none;'>
                            <li><strong>Username:</strong> " . htmlspecialchars($username) . "</li>
                            <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                            <li><strong>Phone:</strong> " . htmlspecialchars($phone_no) . "</li>
                        </ul>
                        <p style='margin-top: 20px; text-align: center;'>
                            <a href='{$login_url}' style='background: #1a73e8; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Login to Your Account</a>
                        </p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                        <p style='font-size: 12px; color: #777; text-align: center;'>If you did not initiate this request, please ignore this email.</p>
                    </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Welcome Email sending failed. Error: " . $mail->ErrorInfo);
            }

            $_SESSION['success'] = "Registration successful! A welcome confirmation email has been sent to your email address.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed due to a database error. Please try again.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { text-align: center; margin-bottom: 20px; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; font-size: 14px; }
        .form-group input:focus { border-color: #1a73e8; }
        .form-group input.invalid-input { border-color: #dc3545; background-color: #fff8f8; }
        .error-msg { color: #dc3545; font-size: 12px; margin-top: 4px; display: none; }
        .btn { width: 100%; padding: 10px; background: #1a73e8; border: none; color: #fff; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #1557b0; }
        .alert-server { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; display: block; }
        .link { text-align: center; margin-top: 15px; font-size: 14px; }
        .link a { color: #1a73e8; text-decoration: none; font-weight: 600; }

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
    <h2>Student Registration</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-server">
            <?php foreach ($errors as $error) { echo "• " . htmlspecialchars($error) . "<br>"; } ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off" onsubmit="return validateRegisterForm()">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="name" name="name" placeholder="John Doe" autocomplete="off">
            <div class="error-msg" id="msg-name"></div>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" id="username" name="username" placeholder="At least 5 characters" autocomplete="off">
            <div class="error-msg" id="msg-username"></div>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" id="phone_no" name="phone_no" maxlength="10" placeholder="10-digit number" autocomplete="off">
            <div class="error-msg" id="msg-phone_no"></div>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="email" name="email" placeholder="example@gmail.com" autocomplete="off">
            <div class="error-msg" id="msg-email"></div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="At least 6 characters" autocomplete="new-password">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)" tabindex="-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <div class="error-msg" id="msg-password"></div>
        </div>
        <button type="submit" class="btn">Register Account</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
const fields = [
    document.getElementById('name'),
    document.getElementById('username'),
    document.getElementById('phone_no'),
    document.getElementById('email'),
    document.getElementById('password')
];

document.getElementById('name').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
});
document.getElementById('phone_no').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

function validateSingleField(field, isSubmitting = false) {
    const val = field.value.trim();
    const msgBox = document.getElementById(`msg-${field.id}`);
    let isValid = true;
    let message = '';

    if (val === '') {
        if (isSubmitting) {
            isValid = false;
            message = 'This field is required.';
        } else {
            field.classList.remove('invalid-input');
            msgBox.style.display = 'none';
            return true;
        }
    } else if (field.id === 'name' && !/^[a-zA-Z\s]+$/.test(val)) {
        isValid = false;
        message = 'Full Name must contain letters and spaces only.';
    } else if (field.id === 'username' && val.length < 5) {
        isValid = false;
        message = 'Username must be at least 5 characters long.';
    } else if (field.id === 'phone_no' && !/^\d{10}$/.test(val)) {
        isValid = false;
        message = 'Phone number must be exactly 10 digits.';
    } else if (field.id === 'email') {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|org|net|edu|gov|in|co|io)$/i;
        if (!emailRegex.test(val)) {
            isValid = false;
            message = 'Please enter a valid email address.';
        }
    } else if (field.id === 'password' && val.length < 6) {
        isValid = false;
        message = 'Password must be at least 6 characters long.';
    }

    if (!isValid) {
        field.classList.add('invalid-input');
        msgBox.innerText = message;
        msgBox.style.display = 'block';
    } else {
        field.classList.remove('invalid-input');
        msgBox.style.display = 'none';
    }

    return isValid;
}

fields.forEach(field => {
    field.addEventListener('blur', function() {
        validateSingleField(this, false);
    });
    field.addEventListener('input', function() {
        if (this.classList.contains('invalid-input')) {
            const msgBox = document.getElementById(`msg-${this.id}`);
            this.classList.remove('invalid-input');
            msgBox.style.display = 'none';
        }
    });
});

function validateRegisterForm() {
    let isFormValid = true;
    let firstInvalidField = null;

    fields.forEach(field => {
        const valid = validateSingleField(field, true);
        if (!valid && !firstInvalidField) {
            firstInvalidField = field;
            isFormValid = false;
        }
    });

    if (!isFormValid && firstInvalidField) {
        firstInvalidField.focus();
    }

    return isFormValid;
}

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