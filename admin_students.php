<?php
session_start();
require_once 'db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Student Deletion Logic
if (isset($_GET['delete_student_id']) && is_numeric($_GET['delete_student_id'])) {
    $delete_id = intval($_GET['delete_student_id']);
    $delete_stmt = $conn->prepare("DELETE FROM student_register WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        header("Location: admin_students.php?msg=student_deleted");
        exit;
    }
}

// 3. Search & Fetch Students Logic
$search_query = trim($_GET['search'] ?? '');
$student_sql = "SELECT id, name, username, email, `phone no` AS phone FROM student_register WHERE 1=1";

if (!empty($search_query)) {
    $student_sql .= " AND (name LIKE ? OR username LIKE ? OR email LIKE ?)";
}
$student_sql .= " ORDER BY id DESC LIMIT 100";

$stmt_students = $conn->prepare($student_sql);
if (!empty($search_query)) {
    $param_search = "%{$search_query}%";
    $stmt_students->bind_param("sss", $param_search, $param_search, $param_search);
}
$stmt_students->execute();
$students = $stmt_students->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Students - Admin Portal</title>
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
        .dashboard-container { max-width: 1200px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }
        .content-card { background: #ffffff; border-radius: 8px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        .search-box { display: flex; align-items: center; gap: 10px; }
        .search-box input { padding: 8px 14px; border: 1px solid #d0d5dd; border-radius: 6px; outline: none; width: 280px; font-size: 14px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; border: none; cursor: pointer; display: inline-block; }
        .btn-sm { padding: 5px 10px; font-size: 13px; }
        .primary-btn { background-color: #004ac6; color: white; }
        .secondary-btn { background-color: #e4e7ec; color: #344054; }
        .logout-btn { background-color: #d92d20; color: white; }
        .btn-delete { background-color: #d92d20; color: white; }
        .alert-success { background-color: #ecfdf3; color: #027a48; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #abefc6; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        table th { background-color: #f9fafb; color: #475467; font-weight: 600; }
        .no-data { text-align: center; color: #667085; padding: 20px; }
        footer { background-color: #ffffff; border-top: 1px solid #e1e4e8; padding: 20px; text-align: center; margin-top: auto; }
        .footer-logo { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: bold; color: #004ac6; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="navbar">
            <a href="admin_dashboard.php" class="logo">
                <div class="logo-icon">A</div>
                <span>AdminPortal</span>
            </a>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_students.php" class="active">Students</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_subjects.php">Subjects</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Container -->
    <div class="dashboard-container">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'student_deleted'): ?>
            <div class="alert-success">Student record successfully deleted from database.</div>
        <?php endif; ?>

        <!-- Registered Students Table Card -->
        <div class="content-card">
            <div class="card-header">
                <h2>Registered Students</h2>
                
                <!-- Student Search Form -->
                <form method="GET" action="" class="search-box">
                    <input type="text" name="search" placeholder="Search student name, username, email..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn primary-btn">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="students.php" class="btn secondary-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
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
                                    <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="students.php?delete_student_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this student permanently?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">No student records found matching your search.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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