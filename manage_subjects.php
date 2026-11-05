<?php
session_start();
require_once 'db.php';

// 1. Strict Security Check: Verify user is logged in AND has 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. Fetch all resources for real-time client-side search
$sql = "SELECT id, semester, subject, resource_type, file_name, file_path, uploaded_by, uploaded_at FROM resources ORDER BY id DESC";
$resources =$conn->query($sql);$total_resources = $resources ? $resources->num_rows : 0;
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
        
        /* Navbar Styles */
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


        /* Dashboard Container & Cards */
        .dashboard-container { max-width: 1200px; width: 100%; margin: 30px auto; padding: 0 20px; flex: 1; }
        .content-card { background: #ffffff; border-radius: 8px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        .header-title-group { display: flex; align-items: center; gap: 12px; }
        .resource-count-badge { background-color: #e6f0ff; color: #004ac6; font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        h2 { color: #2d3748; font-size: 20px; }

        /* Search & Filter Controls */
        .search-box { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-box input, .search-box select { padding: 9px 14px; border: 1px solid #d0d5dd; border-radius: 6px; outline: none; font-size: 14px; transition: border-color 0.2s; }
        .search-box input:focus, .search-box select:focus { border-color: #004ac6; }
        .search-box input { width: 300px; }

        /* Badges & Actions */
        .badge-type { background: #e6f0ff; color: #004ac6; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-sem { background: #f2f4f7; color: #344054; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .action-link { color: #004ac6; font-weight: 600; text-decoration: none; font-size: 13px; }
        .action-link:hover { text-decoration: underline; }

        /* Data Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        table th { background-color: #f9fafb; color: #475467; font-weight: 600; }
        .no-data { text-align: center; color: #667085; padding: 20px; display: none; }

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
        padding: 0 12px;
    }

    .content-card {
        padding: 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .form-grid button {
        width: 100%;
    }

    .card-header {
        align-items: flex-start;
    }

    .header-title-group {
        flex-wrap: wrap;
    }

    .search-box {
        width: 100%;
    }

    .search-box input {
        width: 100%;
    }

    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }

    table {
        min-width: 720px;
    }

    table th,
    table td {
        padding: 9px 10px;
        font-size: 12px;
    }

    .modal-box {
        width: calc(100% - 28px);
        padding: 20px;
    }
}
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar">
       
            
                <a href="admin_dashboard.php" class="brand">
                    <img src="images/logo.svg" alt="INMCA Resource Portal Logo" >
                    <div>
                    <span class="brand-name">INMCA Resource Portal</span>
                    <span class="admin-badge">Admin</span>
                    </div>
                </a>
            
            <nav class="nav-links">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="admin_students.php">Students</a>
                <a href="manage_faculty.php">Faculty</a>
                <a href="manage_subjects.php" class="active">Subjects</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
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
                            <?php while ($row =$resources->fetch_assoc()): ?>
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
                            <td colspan="7">No resource records found matching your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
       
        <p>&copy; <?php echo date('Y'); ?> INMCA Resource Portal.Empowering Academic Excellence.</p>
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