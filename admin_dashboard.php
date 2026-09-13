<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Database Deletion Logic
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_stmt = $conn->prepare("DELETE FROM student_register WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        header("Location: admin_dashboard.php?msg=deleted");
        exit;
    }
}

// 3. Fetch Dashboard Statistics
$total_students_query = $conn->query("SELECT COUNT(*) AS total FROM student_register");
$total_students = $total_students_query->fetch_assoc()['total'] ?? 0;

$total_faculty_query = $conn->query("SELECT COUNT(*) AS total FROM faculty");
$total_faculty = $total_faculty_query->fetch_assoc()['total'] ?? 0;

$total_admins_query = $conn->query("SELECT COUNT(*) AS total FROM admins");
$total_admins = $total_admins_query->fetch_assoc()['total'] ?? 0;

// 4. Search Logic for Students
$student_search = trim($_GET['student_search'] ?? '');
$student_sql = "SELECT id, name, username, email, `phone no` AS phone FROM student_register WHERE 1=1";

if (!empty($student_search)) {
    $student_sql .= " AND (name LIKE ? OR username LIKE ? OR email LIKE ?)";
}
$student_sql .= " ORDER BY id DESC LIMIT 50";

$stmt_students = $conn->prepare($student_sql);
if (!empty($student_search)) {
    $param_s = "%{$student_search}%";
    $stmt_students->bind_param("sss", $param_s, $param_s, $param_s);
}
$stmt_students->execute();
$students = $stmt_students->get_result();

// 5. Search Logic for Faculty
$faculty_search = trim($_GET['faculty_search'] ?? '');
$faculty_sql = "SELECT id, name, email FROM faculty WHERE 1=1";

if (!empty($faculty_search)) {
    $faculty_sql .= " AND (name LIKE ? OR email LIKE ?)";
}
$faculty_sql .= " ORDER BY id DESC LIMIT 50";

$stmt_faculty = $conn->prepare($faculty_sql);
if (!empty($faculty_search)) {
    $param_f = "%{$faculty_search}%";
    $stmt_faculty->bind_param("ss", $param_f, $param_f);
}
$stmt_faculty->execute();
$faculty_members = $stmt_faculty->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Management Portal</title>
    <link rel="stylesheet" href="style.css">
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
                <li><a href="admin_dashboard.php" class="active">Student</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_subjects.php">Subjects</a></li>
                <li><a href="logout.php" class="btn logout-btn">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="dashboard-container">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert-success">Student record successfully deleted from database.</div>
        <?php endif; ?>

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

        <!-- Registered Students Table Card -->
        <div class="content-card">
            <div class="card-header">
                <h2>Registered Students</h2>
                
                <!-- Student Search Form -->
                <form method="GET" action="" class="search-box">
                    <input type="text" name="student_search" placeholder="Search student name, username, email..." value="<?php echo htmlspecialchars($student_search); ?>">
                    <button type="submit" class="btn primary-btn">Search</button>
                    <?php if (!empty($student_search)): ?>
                        <a href="admin_dashboard.php" class="btn secondary-btn">Clear</a>
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
                                        <a href="admin_dashboard.php?delete_id=<?php echo $row['id']; ?>" class="action-link btn-delete" onclick="return confirm('Are you sure you want to delete this student permanently from database?');">Delete</a>
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

        <!-- Faculty Members Table Card -->
        <div class="content-card">
            <div class="card-header">
                <h2>Faculty Directory</h2>
                
                <!-- Faculty Search Form -->
                <form method="GET" action="" class="search-box">
                    <input type="text" name="faculty_search" placeholder="Search faculty name, email..." value="<?php echo htmlspecialchars($faculty_search); ?>">
                    <button type="submit" class="btn primary-btn">Search</button>
                    <?php if (!empty($faculty_search)): ?>
                        <a href="admin_dashboard.php" class="btn secondary-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($faculty_members->num_rows > 0): ?>
                            <?php while ($row = $faculty_members->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-data">No faculty members found matching your criteria.</td>
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