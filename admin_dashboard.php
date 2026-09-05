<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Fetch Dashboard Statistics
// Total Registered Students
$total_students_query = $conn->query("SELECT COUNT(*) AS total FROM student_register");
$total_students = $total_students_query->fetch_assoc()['total'] ?? 0;

// Total Faculty Accounts
$total_faculty_query = $conn->query("SELECT COUNT(*) AS total FROM faculty");
$total_faculty = $total_faculty_query->fetch_assoc()['total'] ?? 0;

// Total Admin Accounts
$total_admins_query = $conn->query("SELECT COUNT(*) AS total FROM admins");
$total_admins = $total_admins_query->fetch_assoc()['total'] ?? 0;

// 3. Search and Filtering Logic for Students
$search = trim($_GET['search'] ?? '');
$sql = "SELECT id, name, username, email, `phone no` AS phone FROM student_register WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR username LIKE ? OR email LIKE ?)";
}
$sql .= " ORDER BY id DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $param = "%{$search}%";
    $stmt->bind_param("sss", $param, $param, $param);
}
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Management Portal</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling */
        .sidebar { width: 250px; background: #1a202c; color: #fff; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { font-size: 20px; color: #e53e3e; margin-bottom: 30px; border-bottom: 1px solid #2d3748; padding-bottom: 10px; }
        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 10px; }
        .nav-links a { color: #cbd5e0; text-decoration: none; padding: 10px 12px; display: block; border-radius: 6px; font-weight: 500; transition: 0.2s; }
        .nav-links a.active, .nav-links a:hover { background: #e53e3e; color: #fff; }
        .logout-btn { background: #e53e3e; color: #fff; text-decoration: none; padding: 10px; text-align: center; border-radius: 6px; font-weight: bold; display: block; transition: background 0.2s; }
        .logout-btn:hover { background: #c53030; }

        /* Main Content Styling */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header h1 { font-size: 24px; color: #2d3748; }
        .user-info { font-size: 14px; color: #718096; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #e53e3e; }
        .stat-card h3 { font-size: 13px; color: #718096; text-transform: uppercase; margin-bottom: 8px; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #2d3748; }

        /* Table & Controls */
        .table-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 14px; outline: none; }
        .search-box button { padding: 8px 15px; background: #2b6cb0; border: none; color: #fff; border-radius: 5px; cursor: pointer; font-weight: 500; }
        .search-box button:hover { background: #2c5282; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; color: #4a5568; font-weight: 600; }
        tr:hover { background: #f8fafc; }
        .no-data { text-align: center; padding: 20px; color: #a0aec0; }
        
        /* Action Buttons */
        .action-btn { padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 5px; display: inline-block; }
        .btn-edit { background: #ebf4ff; color: #2b6cb0; border: 1px solid #bee3f8; }
        .btn-edit:hover { background: #bee3f8; }
        .btn-delete { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .btn-delete:hover { background: #fed7d7; }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div>
            <h2>Admin Control</h2>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php" class="active">Manage Students</a></li>
                <li><a href="manage_faculty.php">Manage Faculty</a></li>
                <li><a href="manage_subjects.php">Manage Subjects</a></li>
            </ul>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <h1>System Overview</h1>
            <div class="user-info">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></div>
        </div>

        <!-- Metric Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #3182ce;">
                <h3>Total Faculty</h3>
                <div class="number"><?php echo $total_faculty; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #38a169;">
                <h3>System Administrators</h3>
                <div class="number"><?php echo $total_admins; ?></div>
            </div>
        </div>

        <!-- Student Management Table -->
        <div class="table-card">
            <div class="table-header">
                <h2>Registered Students Management</h2>
                <form method="GET" action="" class="search-box">
                    <input type="text" name="search" placeholder="Search name, username, email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="admin_dashboard.php" style="padding: 8px 12px; background: #e2e8f0; color: #4a5568; text-decoration: none; border-radius: 5px; font-size: 14px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students->num_rows > 0): ?>
                        <?php while ($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="action-btn btn-edit">Edit</a>
                                    <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No students found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>