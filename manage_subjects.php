<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Fetch all resources for real-time client-side search
$sql = "SELECT id, semester, subject, resource_type, file_name, file_path, uploaded_by, uploaded_at FROM resources ORDER BY id DESC";
$resources = $conn->query($sql);
$total_resources = $resources ? $resources->num_rows : 0;
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
        .navbar { max-width: 1200px; margin: 0 auto; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav-left { display: flex; align-items: center; gap: 15px; }
        .back-btn { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background-color: #f2f4f7; color: #344054; text-decoration: none; transition: all 0.2s ease; }
        .back-btn:hover { background-color: #004ac6; color: #ffffff; }
        .logo { display: flex; align-items: center; }
        .logo-img { height: 48px; width: auto; object-fit: contain; }
        .nav-links { list-style: none; display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #596579; font-weight: 500; }
        .nav-links a.active, .nav-links a:hover { color: #004ac6; }
        .logout-btn { background-color: #f2f4f7; color: #344054; padding: 8px 14px; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .logout-btn:hover { background-color: #fee4e2; color: #d92d20; }
        .dashboard-container { max-width: 1200px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }
        .content-card { background: #ffffff; border-radius: 8px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        .header-title-group { display: flex; align-items: center; gap: 12px; }
        .resource-count-badge { background-color: #e6f0ff; color: #004ac6; font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .search-box { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-box input, .search-box select { padding: 9px 14px; border: 1px solid #d0d5dd; border-radius: 6px; outline: none; font-size: 14px; transition: border-color 0.2s; }
        .search-box input:focus, .search-box select:focus { border-color: #004ac6; }
        .search-box input { width: 300px; }
        .badge-type { background: #e6f0ff; color: #004ac6; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-sem { background: #f2f4f7; color: #344054; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        table th { background-color: #f9fafb; color: #475467; font-weight: 600; }
        .no-data { text-align: center; color: #667085; padding: 20px; display: none; }
        footer { background-color: #ffffff; border-top: 1px solid #e1e4e8; padding: 20px; text-align: center; margin-top: auto; }
        .footer-logo-img { height: 36px; width: auto; margin-bottom: 8px; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="navbar">
            <div class="nav-left">
                <a href="admin_dashboard.php" class="back-btn" title="Back to Admin Dashboard">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <a href="admin_dashboard.php" class="logo">
                    <img src="logo (3).png" alt="INMCA Resource Portal Logo" class="logo-img">
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_subjects.php" class="active">Subjects</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="dashboard-container">

        <!-- Subject Resources Table Card -->
        <div class="content-card">
            <div class="card-header">
                <div class="header-title-group">
                    <h2>Subject Resource Materials</h2>
                    <span class="resource-count-badge" id="resourceCount">Total: <?php echo $total_resources; ?></span>
                </div>
                
                <!-- Real-time Filter & Search -->
                <div class="search-box">
                    <select id="semesterSelect">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>

                    <input type="text" id="searchInput" placeholder="Type sem, subject, resource type..." autocomplete="off">
                </div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_resources > 0): ?>
                            <?php while ($row = $resources->fetch_assoc()): ?>
                                <tr class="resource-row" data-semester="<?php echo htmlspecialchars($row['semester']); ?>">
                                    <td><span class="badge-sem">Sem <?php echo htmlspecialchars($row['semester']); ?></span></td>
                                    <td class="resource-subject"><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                    <td class="resource-type"><span class="badge-type"><?php echo htmlspecialchars($row['resource_type']); ?></span></td>
                                    <td class="resource-filename"><?php echo htmlspecialchars($row['file_name']); ?></td>
                                    <td class="resource-uploader"><?php echo htmlspecialchars($row['uploaded_by'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y - h:i A', strtotime($row['uploaded_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <tr id="noResultsRow" class="no-data">
                            <td colspan="6">No resource records found matching your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <img src="logo (3).png" alt="INMCA Resource Portal Logo" class="footer-logo-img">
        <p>&copy; <?php echo date('Y'); ?> INMCA Resource Portal. All rights reserved.</p>
    </footer>

    <!-- Real-time Search and Filter Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const semesterSelect = document.getElementById('semesterSelect');
        const rows = document.querySelectorAll('.resource-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const resourceCountBadge = document.getElementById('resourceCount');
        const totalCount = rows.length;

        function filterResources() {
            let query = searchInput.value.toLowerCase().trim();
            let selectedSemester = semesterSelect.value;

            // Real-time semester detection from text input (e.g., "sem 1", "sem2", "s3")
            const semRegex = /(?:sem(?:ester)?|s)\s*([1-9]|10)\b/i;
            const match = query.match(semRegex);

            if (match) {
                selectedSemester = match[1];
                query = query.replace(semRegex, '').trim();
            }

            let visibleCount = 0;

            rows.forEach(row => {
                const rowSem = row.getAttribute('data-semester');
                const subject = row.querySelector('.resource-subject').textContent.toLowerCase();
                const type = row.querySelector('.resource-type').textContent.toLowerCase();
                const filename = row.querySelector('.resource-filename').textContent.toLowerCase();
                const uploader = row.querySelector('.resource-uploader').textContent.toLowerCase();

                const matchesSem = (selectedSemester === '' || rowSem === selectedSemester);
                const matchesText = (query === '' || 
                    subject.includes(query) || 
                    type.includes(query) || 
                    filename.includes(query) || 
                    uploader.includes(query)
                );

                if (matchesSem && matchesText) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update badge text
            if (query === '' && selectedSemester === '') {
                resourceCountBadge.textContent = `Total: ${totalCount}`;
            } else {
                resourceCountBadge.textContent = `Found: ${visibleCount}`;
            }

            // Toggle "No records found" row
            if (visibleCount === 0 && totalCount > 0) {
                noResultsRow.style.display = 'table-row';
            } else {
                noResultsRow.style.display = 'none';
            }
        }

        searchInput.addEventListener('input', filterResources);
        semesterSelect.addEventListener('change', filterResources);
    });
    </script>

</body>
</html>