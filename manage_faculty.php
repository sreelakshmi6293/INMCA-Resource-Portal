<?php
session_start();
require_once 'db.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('PHPMailer/src/Exception.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
} else {
    die("PHPMailer files not found. Please install PHPMailer via Composer or place it in the project directory.");
}

// Security Check: Admin role validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// --- SMTP CONFIGURATION ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'inmca.student.portal@gmail.com');      // Replace with your SMTP email address
define('SMTP_PASS', 'mroo naiw lvoi tdze');        // Replace with your 16-character App Password
define('SMTP_PORT', 587);

$message = '';
$has_alert = false;
$assigned_credentials = null;

// Handle Faculty Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_faculty') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!empty($name) && !empty($email)) {
        
        // 1. DUPLICATE CHECK: Query database for existing email or name
        $check_stmt = $conn->prepare("SELECT id, email, name FROM faculty WHERE email = ? OR name = ?");
        $check_stmt->bind_param("ss", $email, $name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            $has_alert = true;
            
            if ($existing_user['email'] === $email) {
                $message = "<div id='alert-banner' class='alert alert-danger'><strong>Duplicate Entry Alert:</strong> A faculty account with the email <u>" . htmlspecialchars($email) . "</u> already exists. Please enter a different email address.</div>";
            } else {
                $message = "<div id='alert-banner' class='alert alert-warning'><strong>Warning:</strong> A faculty member named <u>" . htmlspecialchars($name) . "</u> is already registered. Please check the name or modify it to avoid confusion.</div>";
            }
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // 2. Generate Credentials
            $clean_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
            $generated_username = $clean_name . rand(100, 999);
            $generated_password = "Fac#" . rand(1000, 9999);
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

            // 3. Insert into Database
            $stmt = $conn->prepare("INSERT INTO faculty (name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $generated_username, $email, $hashed_password);

            if ($stmt->execute()) {
                $assigned_credentials = [
                    'name' => $name,
                    'username' => $generated_username,
                    'password' => $generated_password,
                    'email' => $email
                ];

                // 4. Send Email via PHPMailer
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;

                    $mail->setFrom(SMTP_USER, 'Admin Portal');
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Faculty Account Credentials';
                    $mail->Body    = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                      <h2>Welcome, " . htmlspecialchars($name) . "!</h2>
                      <p>Your faculty account has been created successfully.</p>
                      <p><strong>Username:</strong> " . htmlspecialchars($generated_username) . "<br>
                      <strong>Temporary Password:</strong> " . htmlspecialchars($generated_password) . "</p>
                    </body>
                    </html>";

                    $mail->send();
                    $message = "<div id='alert-banner' class='alert alert-success'>Faculty account created successfully and email sent to <strong>" . htmlspecialchars($email) . "</strong>!</div>";
                } catch (Exception $e) {
                    $message = "<div id='alert-banner' class='alert alert-warning'>Account created, but email notification failed. PHPMailer Error: " . htmlspecialchars($mail->ErrorInfo) . "</div>";
                }
            } else {
                $has_alert = true;
                $message = "<div id='alert-banner' class='alert alert-danger'>An unexpected error occurred while adding the faculty member.</div>";
            }
            $stmt->close();
        }
    } else {
        $has_alert = true;
        $message = "<div id='alert-banner' class='alert alert-danger'>Please fill in all required fields.</div>";
    }
}

// Fetch all registered faculty members
$faculty_list = $conn->query("SELECT id, name, username, email, created_at FROM faculty ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Assign Faculty Credentials</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; padding: 30px; }
        .container { max-width: 1000px; margin: auto; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        h2 { color: #2d3748; margin-bottom: 20px; font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end; }
        input { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; }
        button { padding: 10px 20px; background: #3182ce; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #2b6cb0; }
        .alert { padding: 14px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; line-height: 1.4; }
        .alert-success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-warning { background: #feebc8; color: #742a2a; border: 1px solid #fbd38d; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; border: 1px solid #feb2b2; }
        .cred-box { background: #ebf8ff; border-left: 4px solid #3182ce; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #edf2f7; color: #4a5568; }
        a.back-btn { text-decoration: none; color: #4a5568; font-weight: 500; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>

    <div class="card">
        <h2>Assign Credentials & Notify Faculty</h2>
        
        <!-- Alert Container -->
        <?php echo $message; ?>

        <?php if ($assigned_credentials): ?>
            <div class="cred-box">
                <h4 style="color:#2b6cb0; margin-bottom: 8px;">Generated Account Information:</h4>
                <p><strong>Faculty Name:</strong> <?php echo htmlspecialchars($assigned_credentials['name']); ?></p>
                <p><strong>Assigned Username:</strong> <code><?php echo htmlspecialchars($assigned_credentials['username']); ?></code></p>
                <p><strong>Assigned Password:</strong> <code><?php echo htmlspecialchars($assigned_credentials['password']); ?></code></p>
                <p><strong>Notification Sent To:</strong> <?php echo htmlspecialchars($assigned_credentials['email']); ?></p>
            </div>
        <?php endif; ?>

        <form id="facultyForm" method="POST" action="" autocomplete="off">
            <input type="hidden" name="action" value="add_faculty">
            <div class="form-grid">
                <div>
                    <label style="display:block; margin-bottom: 5px; font-size:13px; font-weight:600;">Faculty Full Name</label>
                    <input type="text" id="faculty_name" name="name" placeholder="Dr. Sarah Connor" autocomplete="off" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 5px; font-size:13px; font-weight:600;">Faculty Email Address</label>
                    <input type="email" id="faculty_email" name="email" placeholder="sarah@college.edu" autocomplete="off" required>
                </div>
                <div>
                    <button type="submit">Assign & Send Email</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Registered Faculty Accounts</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Created On</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($faculty_list && $faculty_list->num_rows > 0): ?>
                    <?php while ($f = $faculty_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo htmlspecialchars($f['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($f['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($f['email']); ?></td>
                            <td><?php echo date('d M Y', strtotime($f['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#a0aec0;">No faculty accounts registered yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('faculty_name');
    const emailInput = document.getElementById('faculty_email');
    const alertBanner = document.getElementById('alert-banner');

    // 1. Clear input fields if an alert (duplicate/error) is currently displayed
    <?php if ($has_alert): ?>
        if (nameInput) nameInput.value = '';
        if (emailInput) emailInput.value = '';
    <?php endif; ?>

    // 2. Hide the alert message when the admin starts re-entering data into any input field
    const hideAlert = function () {
        if (alertBanner) {
            alertBanner.style.display = 'none';
        }
    };

    if (nameInput) nameInput.addEventListener('input', hideAlert);
    if (emailInput) emailInput.addEventListener('input', hideAlert);
});
</script>

</body>
</html>