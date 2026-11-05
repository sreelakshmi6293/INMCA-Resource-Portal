<?php
session_start();
require_once 'db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// 2. Student Deletion Logic


// 3. Fetch All Students (Real-time filtering will be handled on the client side)
$student_sql = "SELECT id, name, username, email, `phone no` AS phone FROM student_register ORDER BY id DESC";
$students = $conn->query($student_sql);

// Total count from database
$total_students = $students ? $students->num_rows : 0;
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
        .content-card { background: #ffffff; border-radius: 8px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        .header-title-group { display: flex; align-items: center; gap: 12px; }
        .student-count-badge { background-color: #e6f0ff; color: #004ac6; font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .search-box { display: flex; align-items: center; gap: 10px; }
        .search-box input { padding: 9px 14px; border: 1px solid #d0d5dd; border-radius: 6px; outline: none; width: 300px; font-size: 14px; transition: border-color 0.2s; }
        .search-box input:focus { border-color: #004ac6; }
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; border: none; cursor: pointer; display: inline-block; }
        .btn-sm { padding: 5px 10px; font-size: 13px; }

                /* Updated Logout Button styling to match other nav links */
        
        
        .alert-success { background-color: #ecfdf3; color: #027a48; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #abefc6; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
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
        padding: 14px;
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
        min-width: 650px;
    }

    table th,
    table td {
        padding: 9px 10px;
        font-size: 12px;
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
            <nav class="nav-links">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="students.php" class="active">Students</a>
                <a href="manage_faculty.php">Faculty</a>
                <a href="manage_subjects.php">Subjects</a>
                <a href="logout.php" class="logout">Logout</a>
</nav>
        </div>
    </header>

    <div class="dashboard-container">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'student_deleted'): ?>
            <div class="alert-success">Student record successfully deleted from database.</div>
        <?php endif; ?>

        <!-- Registered Students Table Card -->
        <div class="content-card">
            <div class="card-header">
                <div class="header-title-group">
                    <h2>Registered Students</h2>
                    <span class="student-count-badge" id="studentCount">Total: <?php echo $total_students; ?></span>
                </div>
                
                <!-- Real-time Student Search -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Type student name, username, email..." autocomplete="off">
                </div>
            </div>

            <div class="table-responsive">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_students > 0): ?>
                            <?php while ($row = $students->fetch_assoc()): ?>
                                <tr class="student-row">
                                    <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="student-name"><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td class="student-username"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="student-email"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        
                        <!-- Row displayed when live search yields no matching rows -->
                        <tr id="noResultsRow" class="no-data">
                            <td colspan="6">No student records found matching your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> INMCA Resource Portal. Empowering Academic Excellence.</p>
           
    </footer>

    <!-- Real-time Filter & Counter Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('.student-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const studentCountBadge = document.getElementById('studentCount');
        const totalCount = rows.length;

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.querySelector('.student-name').textContent.toLowerCase();
                const username = row.querySelector('.student-username').textContent.toLowerCase();
                const email = row.querySelector('.student-email').textContent.toLowerCase();

                if (name.includes(query) || username.includes(query) || email.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update visible count badge
            if (query === '') {
                studentCountBadge.textContent = `Total: ${totalCount}`;
            } else {
                studentCountBadge.textContent = `Found: ${visibleCount}`;
            }

            // Show or hide the "No results found" row
            if (visibleCount === 0 && totalCount > 0) {
                noResultsRow.style.display = 'table-row';
            } else {
                noResultsRow.style.display = 'none';
            }
        });
    });
    </script>

</body>
</html>