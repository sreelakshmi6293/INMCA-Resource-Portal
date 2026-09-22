<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Fetch Dashboard Statistics
$total_students_query = $conn->query("SELECT COUNT(*) AS total FROM student_register");
$total_students = $total_students_query->fetch_assoc()['total'] ?? 0;

$total_faculty_query = $conn->query("SELECT COUNT(*) AS total FROM faculty");
$total_faculty = $total_faculty_query->fetch_assoc()['total'] ?? 0;

$total_admins_query = $conn->query("SELECT COUNT(*) AS total FROM admins");
$total_admins = $total_admins_query->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Management Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        header { background-color: #ffffff; border-bottom: 1px solid #e1e4e8; width: 100%; }
        .navbar { max-width: 1200px; margin: 0 auto; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #004ac6; font-weight: bold; font-size: 18px; }
        .logo-icon { background-color: #004ac6; color: #fff; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
        .nav-links { list-style: none; display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #596579; font-weight: 500; }
        .nav-links a.active, .nav-links a:hover { color: #004ac6; }
        
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

        .dashboard-container { max-width: 1200px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 20px 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .badge { background: #e6f0ff; color: #004ac6; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); border-left: 4px solid #004ac6; }
        .stat-label { color: #596579; font-size: 14px; font-weight: 500; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #1a1a1a; margin-top: 5px; }

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
            <ul class="nav-links">
                <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="admin_students.php">Students</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_subjects.php">Subjects</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </header>

    <div class="dashboard-container">
        
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <div>
                <span class="badge">System Administrator</span>
                <h1 style="margin-top: 8px;">Dashboard Overview</h1>
            </div>
            <div style="text-align: right;">
                <span style="color: #596579; font-size: 14px;">Logged in as</span>
                <div style="font-weight: bold; color: #004ac6; font-size: 16px;"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Students</div>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Faculty</div>
                <div class="number"><?php echo $total_faculty; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Administrators</div>
                <div class="number"><?php echo $total_admins; ?></div>
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

</body>
</html>