<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Query student details safely
$stmt = $conn->prepare("SELECT id, name, username, email, `phone no`, role FROM student_register WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $current_user = $result->fetch_assoc();
} else {
    // Session user not found in DB
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - INMCA Resource Portal</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; color: #333; }

        /* Sidebar Styling */
        .sidebar { width: 250px; background: #1a202c; color: #fff; display: flex; flex-direction: column; position: fixed; height: 100vh; }
        .sidebar .brand { padding: 25px 20px; font-size: 20px; font-weight: bold; background: #141923; color: #3182ce; border-bottom: 1px solid #2d3748; }
        .sidebar ul { list-style: none; padding-top: 15px; }
        .sidebar ul li a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #a0aec0; text-decoration: none; font-size: 15px; transition: 0.2s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: #2d3748; color: #ffffff; border-left: 4px solid #3182ce; }
        .sidebar .logout-btn { margin-top: auto; padding: 20px; border-top: 1px solid #2d3748; }
        .sidebar .logout-btn a { display: block; width: 100%; text-align: center; background: #e53e3e; color: #fff; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .sidebar .logout-btn a:hover { background: #c53030; }

        /* Main Area */
        .main-content { margin-left: 250px; flex: 1; padding: 30px; }

        /* Top Header Navigation */
        .top-header { display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .user-greeting h1 { font-size: 22px; color: #2d3748; }
        .user-greeting p { font-size: 13px; color: #718096; margin-top: 2px; }
        .profile-badge { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 42px; height: 42px; background: #3182ce; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; }

        /* Content Grid Cards */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #3182ce; }
        .card.role-card { border-left-color: #38a169; }
        .card.email-card { border-left-color: #d69e2e; }
        .card.phone-card { border-left-color: #805ad5; }
        .card-title { font-size: 13px; font-weight: 600; text-transform: uppercase; color: #718096; margin-bottom: 8px; }
        .card-value { font-size: 16px; font-weight: bold; color: #2d3748; word-break: break-all; }

        /* Information Section Box */
        .details-section { background: #ffffff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .details-section h3 { margin-bottom: 15px; color: #2d3748; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 12px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .info-table td.label { font-weight: 600; color: #4a5568; width: 30%; }
        .info-table td.value { color: #2d3748; }

        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 15px; }
            body { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="brand">INMCA Portal</div>
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="#">Resources</a></li>
            <li><a href="#">My Downloads</a></li>
            <li><a href="#">Profile Settings</a></li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php">Logout Account</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Header -->
        <div class="top-header">
            <div class="user-greeting">
                <h1>Welcome back, <?php echo htmlspecialchars($current_user['name'] ?? 'Student'); ?>!</h1>
                <p>Access your study resources and account details</p>
            </div>
            <div class="profile-badge">
                <div class="avatar">
                    <?php echo strtoupper(substr($current_user['name'] ?? 'S', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- Quick Info Cards -->
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-title">Username</div>
                <div class="card-value"><?php echo htmlspecialchars($current_user['username'] ?? 'N/A'); ?></div>
            </div>
            <div class="card role-card">
                <div class="card-title">Account Role</div>
                <div class="card-value"><?php echo htmlspecialchars(ucfirst($current_user['role'] ?? 'Student')); ?></div>
            </div>
            <div class="card email-card">
                <div class="card-title">Email Address</div>
                <div class="card-value"><?php echo htmlspecialchars($current_user['email'] ?? 'N/A'); ?></div>
            </div>
            <div class="card phone-card">
                <div class="card-title">Phone Number</div>
                <div class="card-value"><?php echo htmlspecialchars($current_user['phone no'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- Student Account Details -->
        <div class="details-section">
            <h3>Student Profile Summary</h3>
            <table class="info-table">
                <tr>
                    <td class="label">Full Name</td>
                    <td class="value"><?php echo htmlspecialchars($current_user['name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">System ID</td>
                    <td class="value">#<?php echo htmlspecialchars($current_user['id']); ?></td>
                </tr>
                <tr>
                    <td class="label">Username</td>
                    <td class="value"><?php echo htmlspecialchars($current_user['username'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Email Address</td>
                    <td class="value"><?php echo htmlspecialchars($current_user['email'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Contact Number</td>
                    <td class="value"><?php echo htmlspecialchars($current_user['phone no'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>