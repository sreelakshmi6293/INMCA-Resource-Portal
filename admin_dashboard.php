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
        .navbar {
            height: 74px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 45px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
        }

        .brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 8px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .admin-badge {
            margin-left: 8px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 15px;
            border-radius: 9px;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #2563eb;
            background: #f1f5f9;
        }

        .nav-links a.active {
            color: #2563eb;
            background: #eff6ff;
            font-weight: 600;
        }

        .logout {
            margin-left: 8px;
            color: #dc2626 !important;
            background: #fef2f2;
            border: 1px solid #fee2e2;
        }

        .logout:hover {
            background: #fee2e2 !important;
            color: #b91c1c !important;
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
        @media (max-width: 600px) {
    .navbar {
        height: auto;
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .brand {
        gap: 8px;
    }

    .brand img {
        width: 36px;
        height: 36px;
    }

    .brand-name {
        font-size: 15px;
    }

    .nav-links {
        width: 100%;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 4px;
    }

    .nav-links a {
        padding: 7px 9px;
        font-size: 12px;
    }

    .logout {
        margin-left: 0;
    }

    .dashboard-container {
        margin: 18px auto;
        padding: 0 14px;
    }

    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
        padding: 18px;
    }

    .dashboard-header > div:last-child {
        text-align: left !important;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    footer {
        padding: 16px;
        font-size: 10px;
    }
}
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar">
        
            <a href="admin_dashboard.php" class="brand">
             <img src="images/logo.svg" alt="INMCA Resources Logo" >   
             <div>
                <span class="brand-name">INMCA Resource Portal</span>  
                <span class="admin-badge">Admin</span>
             </div>
            </a>
            <nav  class="nav-links">
                <a href="admin_dashboard.php" class="active">Dashboard</a>
                <a href="admin_students.php">Students</a>
                <a href="manage_faculty.php">Faculty</a>
                <a href="manage_subjects.php">Subjects</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
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
       <p>
         &copy; <?php echo date('Y'); ?> INMCA Resource Portal. Empowering Academic Excellence.
        </p>
    </footer>

</body>
</html>