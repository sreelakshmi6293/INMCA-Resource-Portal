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
    header("Location: login.php");
    exit;
}

// --- SMTP CONFIGURATION ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'inmca.student.portal@gmail.com');      // Replace with your SMTP email address
define('SMTP_PASS', 'mroo naiw lvoi tdze');        // Replace with your 16-character App Password
define('SMTP_PORT', 587);

$message = '';$has_alert = false;
$assigned_credentials = null;

// --- HANDLE FACULTY DELETION ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);

    $delete_stmt =$conn->prepare("DELETE FROM faculty WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);

    if ($delete_stmt->execute()) {$_SESSION['msg'] = "Faculty account deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete faculty account.";
    }
    $delete_stmt->close();

    header("Location: manage_faculty.php");
    exit;
}

// Handle Session Messages from redirects
if (isset($_SESSION['msg'])) {$message = "<div id='alert-banner' class='alert alert-success'>" . htmlspecialchars($_SESSION['msg']) . "</div>";
    unset($_SESSION['msg']);
}
if (isset($_SESSION['error'])) {$message = "<div id='alert-banner' class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
    $has_alert = true;
    unset($_SESSION['error']);
}

// --- HANDLE FACULTY CREATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) &&$_POST['action'] === 'add_faculty') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!empty($name) && !empty($email)) {
        
        // 1. CROSS-TABLE DUPLICATE CHECK: Query both 'faculty' AND 'student_register' tables
        $check_stmt =$conn->prepare("
            SELECT email, name, 'faculty' AS account_type FROM faculty WHERE email = ? OR name = ?
            UNION
            SELECT email, name, 'student' AS account_type FROM student_register WHERE email = ? OR name = ?
        ");
        $check_stmt->bind_param("ssss", $email, $name,$email, $name);$check_stmt->execute();
        $check_result =$check_stmt->get_result();

        if ($check_result->num_rows > 0) {$existing_user = $check_result->fetch_assoc();$has_alert = true;
            
            if (strtolower($existing_user['email']) === strtolower($email)) {$role_label = ucfirst($existing_user['account_type']);$message = "<div id='alert-banner' class='alert alert-danger'><strong>Duplicate Email Alert:</strong> The email <u>" . htmlspecialchars($email) . "</u> is already registered as a <strong>{$role_label}</strong>. Please enter a different email address.</div>";
            } else {
                $message = "<div id='alert-banner' class='alert alert-warning'><strong>Warning:</strong> A user named <u>" . htmlspecialchars($name) . "</u> is already registered in the system. Please verify the name or modify it to avoid confusion.</div>";
            }
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // 2. Generate Unique Username and Password
            $clean_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '',$name));
            $generated_username =$clean_name . rand(100, 999);
            $generated_password = "Fac#" . rand(1000, 9999);
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

            // 3. Insert into Faculty Table
            $stmt =$conn->prepare("INSERT INTO faculty (name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name,$generated_username, $email,$hashed_password);

            if ($stmt->execute()) {$assigned_credentials = [
                    'name' => $name,
                    'username' => $generated_username,
                    'password' => $generated_password,
                    'email' => $email
                ];

                // 4. Send Email Notification via PHPMailer
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();$mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;

                    $mail->setFrom(SMTP_USER, 'Admin Portal');$mail->addAddress($email,$name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Faculty Account Credentials';$mail->Body    = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                      <h2>Welcome, " . htmlspecialchars($name) . "!</h2>
                      <p>Your faculty account has been created successfully.</p>
                      <p>You can now log in using either your username or email address:</p>
                      <div style='background: #f4f6f9; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        <p style='margin: 5px 0;'><strong>Username:</strong> " . htmlspecialchars($generated_username) . "</p>
                        <p style='margin: 5px 0;'><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p style='margin: 5px 0;'><strong>Temporary Password:</strong> " . htmlspecialchars($generated_password) . "</p>
                      </div>
                      <p>Please log in and change your password as soon as possible.</p>
                    </body>
                    </html>";

                    $mail->send();$message = "<div id='alert-banner' class='alert alert-success'>Faculty account created successfully and email sent to <strong>" . htmlspecialchars($email) . "</strong>!</div>";
                } catch (Exception $e) {$message = "<div id='alert-banner' class='alert alert-warning'>Account created, but email notification failed. PHPMailer Error: " . htmlspecialchars($mail->ErrorInfo) . "</div>";
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

// Fetch all registered faculty members for real-time client-side search
$faculty_list =$conn->query("SELECT id, name, username, email, created_at FROM faculty ORDER BY id DESC");
$total_faculty = $faculty_list ? $faculty_list->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Admin Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Navbar Styles */
        header { background-color: #ffffff; border-bottom: 1px solid #e1e4e8; width: 100%; }
        .navbar { max-width: 1200px; margin: 0 auto; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #004ac6; font-weight: bold; font-size: 18px; }
        .logo-icon { background-color: #004ac6; color: #fff; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
        .nav-links { list-style: none; display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #596579; font-weight: 500; }
        .nav-links a.active, .nav-links a:hover { color: #004ac6; }
        
        /* Logout Button Style */
        /* Updated Logout Button styling to match other nav links */
        .logout-btn { 
            background-color: #f2f4f7; 
            color: #344054; 
            padding: 8px 14px; 
            border-radius: 6px; 
            font-size: 14px; 
            font-weight: 500; 
            transition: all 0.2s ease;
        }
        .logout-btn:hover { 
            background-color: #e4e7ec; 
            color: #004ac6; 
        }

        /* Dashboard Container & Content Cards */
        .dashboard-container { max-width: 1200px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }
        .content-card { background: #ffffff; border-radius: 8px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        .header-title-group { display: flex; align-items: center; gap: 12px; }
        .faculty-count-badge { background-color: #e6f0ff; color: #004ac6; font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        h2 { color: #2d3748; font-size: 20px; }

        /* Form Components */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end; }
        input { width: 100%; padding: 10px 14px; border: 1px solid #d0d5dd; border-radius: 6px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #004ac6; }
        button.btn-primary { padding: 10px 20px; background: #004ac6; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        button.btn-primary:hover { background: #00379b; }

        /* Alerts & Credential Boxes */
        .alert { padding: 14px; border-radius: 6px; margin-top: 20px; font-size: 14px; line-height: 1.4; transition: opacity 0.5s ease; }
        .alert-success { background-color: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .alert-warning { background-color: #feebc8; color: #742a2a; border: 1px solid #fbd38d; }
        .alert-danger { background-color: #fed7d7; color: #9b2c2c; border: 1px solid #feb2b2; }
        .cred-box { background: #e6f0ff; border-left: 4px solid #004ac6; padding: 15px; margin-bottom: 20px; border-radius: 4px; }

        /* Data Tables & Buttons */
        .search-box { display: flex; align-items: center; gap: 10px; }
        .search-box input { width: 300px; padding: 9px 14px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle; }
        table th { background-color: #f9fafb; color: #475467; font-weight: 600; }
        .no-data { text-align: center; color: #667085; padding: 20px; display: none; }
        
        /* Delete Button Style */
        .btn-delete { background-color: #d92d20; color: #ffffff; border: none; padding: 6px 14px; border-radius: 5px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; }
        .btn-delete:hover { background-color: #b42318; }

        /* Centered Modal Box Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); display: flex; justify-content: center; align-items: center; z-index: 1000; opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-box { background: #ffffff; padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); text-align: center; transform: scale(0.9); transition: transform 0.2s ease; }
        .modal-overlay.active .modal-box { transform: scale(1); }
        .modal-title { font-size: 18px; font-weight: 600; color: #101828; margin-bottom: 10px; }
        .modal-text { font-size: 14px; color: #475467; margin-bottom: 20px; line-height: 1.5; }
        .modal-actions { display: flex; justify-content: center; gap: 12px; }
        .btn-cancel { background-color: #f2f4f7; color: #344054; border: 1px solid #d0d5dd; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-cancel:hover { background-color: #e4e7ec; }
        .btn-confirm-delete { background-color: #f2f4f7; color: #344054; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-confirm-delete:hover { background-color: #f2f4f7; }

        /* Footer */
        footer { background-color: #ffffff; border-top: 1px solid #e1e4e8; padding: 20px; text-align: center; margin-top: auto; }
        .footer-logo { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: bold; color: #004ac6; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="navbar">
            <a href="admin_dashboard.php" class="logo">
                <img src="images/logo (3).png" alt="INMCA Resources Logo" class="logo-img">   
                <span>INMCA Resource Portal</span> 
            </a>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_students.php">Students</a></li>
                <li><a href="manage_faculty.php" class="active">Faculty</a></li>
                <li><a href="manage_subjects.php">Subjects</a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Dashboard Container -->
    <div class="dashboard-container">

        <!-- Form Card for Assigning Credentials -->
        <div class="content-card">
            <div class="card-header">
                <h2>Assign Credentials & Notify Faculty</h2>
            </div>

            <?php if ($assigned_credentials): ?>
                <div class="cred-box">
                    <h4 style="color:#004ac6; margin-bottom: 8px;">Generated Account Information:</h4>
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
                        <input type="text" id="faculty_name" name="name" placeholder="Full Name" autocomplete="off" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 5px; font-size:13px; font-weight:600;">Faculty Email Address</label>
                        <input type="email" id="faculty_email" name="email" placeholder="Email Address" autocomplete="off" required>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">Assign & Send Email</button>
                    </div>
                </div>
            </form>

            <!-- Alert message placed directly below faculty name and email input fields -->
            <?php echo $message; ?>
        </div>

        <!-- Registered Faculty Table Card -->
        <div class="content-card">
            <div class="card-header">
                <div class="header-title-group">
                    <h2>Registered Faculty Accounts</h2>
                    <span class="faculty-count-badge" id="facultyCount">Total: <?php echo $total_faculty; ?></span>
                </div>
                
                <!-- Real-time Faculty Search Input -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Type faculty name, username, email..." autocomplete="off">
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_faculty > 0): ?>
                            <?php while ($f =$faculty_list->fetch_assoc()): ?>
                                <tr class="faculty-row">
                                    <td class="faculty-id">#<?php echo $f['id']; ?></td>
                                    <td class="faculty-name"><strong><?php echo htmlspecialchars($f['name']); ?></strong></td>
                                    <td class="faculty-username"><code><?php echo htmlspecialchars($f['username']); ?></code></td>
                                    <td class="faculty-email"><?php echo htmlspecialchars($f['email']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($f['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn-delete" onclick="openDeleteModal(<?php echo $f['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        
                        <tr id="noResultsRow" class="no-data">
                            <td colspan="6">No faculty accounts found matching your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Centered Custom Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-title">Delete Faculty Account</div>
            <div class="modal-text">Are you sure you want to delete this faculty account? This action cannot be undone.</div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn-confirm-delete">OK</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-logo">
            <div class="logo-icon">A</div>
            <span>AdminPortal</span>
        </div>
        <p>&copy; <?php echo date('Y'); ?> Portal Management System. All rights reserved.</p>
    </footer>

    <!-- Client-Side Search and Script Logic -->
    <script>
    let targetDeleteId = null;

    function openDeleteModal(id) {
        targetDeleteId = id;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
        targetDeleteId = null;
        document.getElementById('deleteModal').classList.remove('active');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (targetDeleteId) {
            window.location.href = `manage_faculty.php?action=delete&id=${targetDeleteId}`;
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('faculty_name');
        const emailInput = document.getElementById('faculty_email');
        const alertBanner = document.getElementById('alert-banner');

        // Automatically hide alert message after 4 seconds (4000ms)
        if (alertBanner) {
            setTimeout(function () {
                alertBanner.style.opacity = '0';
                setTimeout(function () {
                    alertBanner.style.display = 'none';
                }, 500); // smooth fade out duration
            }, 4000);
        }

        // 1. Clear input fields if an alert is currently displayed
        <?php if ($has_alert): ?>
            if (nameInput) nameInput.value = '';
            if (emailInput) emailInput.value = '';
        <?php endif; ?>

        // 2. Hide alert message immediately when user modifies form inputs
        const hideAlert = function () {
            if (alertBanner) {
                alertBanner.style.display = 'none';
            }
        };

        if (nameInput) nameInput.addEventListener('input', hideAlert);
        if (emailInput) emailInput.addEventListener('input', hideAlert);

        // 3. Real-time Search and Counter Logic
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('.faculty-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const facultyCountBadge = document.getElementById('facultyCount');
        const totalCount = rows.length;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                let visibleCount = 0;

                rows.forEach(row => {
                    const id = row.querySelector('.faculty-id').textContent.toLowerCase();
                    const name = row.querySelector('.faculty-name').textContent.toLowerCase();
                    const username = row.querySelector('.faculty-username').textContent.toLowerCase();
                    const email = row.querySelector('.faculty-email').textContent.toLowerCase();

                    if (id.includes(query) || name.includes(query) || username.includes(query) || email.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update badge text based on active filter
                if (query === '') {
                    facultyCountBadge.textContent = `Total: ${totalCount}`;
                } else {
                    facultyCountBadge.textContent = `Found: ${visibleCount}`;
                }

                // Toggle "No records found" row visibility
                if (visibleCount === 0 && totalCount > 0) {
                    noResultsRow.style.display = 'table-row';
                } else {
                    noResultsRow.style.display = 'none';
                }
            });
        }
    });
    </script>

</body>
</html>