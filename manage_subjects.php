<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Resource Deletion Logic (Optional: removes DB record and file from server)
if (isset($_GET['delete_resource_id']) && is_numeric($_GET['delete_resource_id'])) {
    $delete_id = intval($_GET['delete_resource_id']);
    
    // First, fetch file path to remove physical file from server
    $file_stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ?");
    $file_stmt->bind_param("i", $delete_id);
    $file_stmt->execute();
    $file_res = $file_stmt->get_result()->fetch_assoc();
    
    if ($file_res && file_exists($file_res['file_path'])) {
        unlink($file_res['file_path']); // Delete physical file
    }

    // Delete database entry
    $delete_stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        header("Location: manage_subjects.php?msg=resource_deleted");
        exit;
    }
}

// 3. Filter / Search Logic
$search_query = trim($_GET['search'] ?? '');
$semester_filter = trim($_GET['semester'] ?? '');

$sql = "SELECT id, semester, subject, resource_type, file_name, file_path, uploaded_by, uploaded_at FROM resources WHERE 1=1";

if (!empty($search_query)) {
    $sql .= " AND (subject LIKE ? OR file_name LIKE ? OR uploaded_by LIKE ? OR resource_type LIKE ?)";
}

if (!empty($semester_filter)) {
    $sql .= " AND semester = " . intval($semester_filter);
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $conn->prepare($sql);

if (!empty($search_query)) {
    $param_search = "%{$search_query}%";
    $stmt->bind_param("ssss", $param_search, $param_search, $param_search, $param_search);
}

$stmt->execute();
$resources = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Resources - Admin Portal</title>
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
        .search-box { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-box input, .search-box select { padding: 8px 14px; border: 1px solid #d0d5dd; border-radius: 6px; outline: none; font-size: 14px; }
        .search-box input { width: 250px; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; border: none; cursor: pointer; display: inline-block; }
        .btn-sm { padding: 5px 10px; font-size: 13px; }
        .primary-btn { background-color: #004ac6; color: white; }
        .secondary-btn { background-color: #e4e7ec; color: #344054; }
        .logout-btn { background-color: #d92d20; color: white; }
        .btn-delete { background-color: #d92d20; color: white; }
        .btn-view { background-color: #027a48; color: white; margin-right: 5px; }
        .alert-success { background-color: #ecfdf3; color: #027a48; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #abefc6; }
        .badge-type { background: #e6f0ff; color: #004ac6; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-sem { background: #f2f4f7; color: #344054; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
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
                <li><a href="students.php">Students</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_subjects.php" class="active">Subjects</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="dashboard-container">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'resource_deleted'): ?>
            <div class="alert-success">Resource file and record were successfully deleted.</div>
        <?php endif; ?>

        <!-- Subject Resources Table Card -->
        <div class="content-card">
            <div class="card-header">
                <h2>Subject Resource Materials</h2>
                
                <!-- Search & Filter Form -->
                <form method="GET" action="" class="search-box">
                    <select name="semester">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <input type="text" name="search" placeholder="Search subject, file, or uploader..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn primary-btn">Search</button>
                    <?php if (!empty($search_query) || !empty($semester_filter)): ?>
                        <a href="manage_subjects.php" class="btn secondary-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Subject</th>
                            <th>Resource Type</th>
                            <th>File Name</th>
                            <th>Uploaded By</th>
                            <th>Uploaded Date & Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resources->num_rows > 0): ?>
                            <?php while ($row = $resources->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge-sem">Sem <?php echo htmlspecialchars($row['semester']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                    <td><span class="badge-type"><?php echo htmlspecialchars($row['resource_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['uploaded_by'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y - h:i A', strtotime($row['uploaded_at'])); ?></td>
                                    <td>
                                        <?php if (!empty($row['file_path']) && file_exists($row['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-view">View</a>
                                        <?php endif; ?>
                                        <a href="manage_subjects.php?delete_resource_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this resource file?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No resource records found.</td>
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