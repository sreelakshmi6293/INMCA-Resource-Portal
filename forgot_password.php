<?php
session_start();
require_once 'db.php';

// Load Composer Autoloader
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Set local timezone to match database expiration calculations
date_default_timezone_set('Asia/Kolkata');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and normalize email input to lowercase
    $raw_email = trim($_POST['email'] ?? '');
    $email     = filter_var(strtolower($raw_email), FILTER_VALIDATE_EMAIL);

    if ($email) {
        // 2. Check if user exists in your student table (case-insensitive check)
        $stmt = $conn->prepare("SELECT id FROM student_register WHERE LOWER(email) = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user_exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        if ($user_exists) {
            // 3. Generate secure raw token and its sha256 hash
            $raw_token  = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $raw_token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // 4. Delete existing reset requests for this email
            $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE LOWER(email) = ?");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();
            $del_stmt->close();

            // 5. Insert token hash into database
            $ins_stmt = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
            $ins_stmt->bind_param("sss", $email, $token_hash, $expires_at);
            $ins_stmt->execute();
            $ins_stmt->close();

            // 6. Construct link preserving host port and directory path
            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $domain     = $_SERVER['HTTP_HOST']; // Includes localhost:8080
            $path       = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $reset_link = "{$protocol}://{$domain}{$path}/reset_password.php?token=" . $raw_token;

            // 7. Dispatch Email via PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server Settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'inmca.student.portal@gmail.com'; // <--- REPLACE WITH YOUR GMAIL
                $mail->Password   = 'mroonaiwlvoitdze';   // <--- REPLACE WITH YOUR 16-CHAR APP PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Bypass SSL certificate check for local XAMPP environments
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    )
                );

                // Sender & Recipient
                $mail->setFrom('inmca.student.portal@gmail.com', 'Student Portal Support');
                $mail->addAddress($email);

                // HTML Email Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; line-height: 1.6;'>
                        <h2 style='color: #1a73e8;'>Password Reset Request</h2>
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
                        <p style='margin: 25px 0;'>
                            <a href='{$reset_link}' style='background: #1a73e8; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Reset Password</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p><a href='{$reset_link}'>{$reset_link}</a></p>
                        <br>
                        <p><strong>Note:</strong> This link will expire in 15 minutes.</p>
                        <p>If you did not request a password reset, you can safely ignore this message.</p>
                    </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                // Quietly log error details for administrative debugging
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }
    }

    // Generic response prevents account enumeration attacks
    $message = "If an account exists with that email address, a reset link has been sent.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { text-align: center; margin-bottom: 20px; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
        .btn { width: 100%; padding: 10px; background: #1a73e8; border: none; color: #fff; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 5px; }
        .btn:hover { background: #1557b0; }
        .alert-info { background: #e8f0fe; color: #1a73e8; border: 1px solid #aecbfa; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .link { text-align: center; margin-top: 15px; font-size: 14px; }
        .link a { color: #1a73e8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <h2>Forgot Password</h2>

    <?php if (!empty($message)): ?>
        <div class="alert-info">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <div class="form-group">
            <label>Registered Email</label>
            <input type="email" name="email" placeholder="example@gmail.com" required autocomplete="off">
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
    </form>

    <div class="link">
        Remembered your password? <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>