<?php
session_start();
include 'db.php';

// Allow only logged-in faculty
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'faculty') {
    header("Location: login.php");
    exit();
}

// Get faculty name
$faculty_name = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? 'Faculty Member';

// Get filter values
$semester = $_GET['semester'] ?? '';
$subject = $_GET['subject'] ?? '';
$resource_type = $_GET['resource_type'] ?? '';
$search = $_GET['search'] ?? '';

// Get subjects for dropdown
$subjects = [];

$subject_query = "SELECT DISTINCT subject FROM resources 
                  WHERE uploaded_by = ? 
                  AND subject IS NOT NULL 
                  AND subject != ''
                  ORDER BY subject ASC";

$stmt = $conn->prepare($subject_query);
$stmt->bind_param("s", $faculty_name);
$stmt->execute();
$result_subjects = $stmt->get_result();

while ($row = $result_subjects->fetch_assoc()) {
    $subjects[] = $row['subject'];
}

$stmt->close();


// Build My Uploads query
$sql = "SELECT * FROM resources WHERE uploaded_by = ?";
$params = [$faculty_name];
$types = "s";

if ($semester !== '') {
    $sql .= " AND semester = ?";
    $params[] = $semester;
    $types .= "s";
}

if ($subject !== '') {
    $sql .= " AND subject = ?";
    $params[] = $subject;
    $types .= "s";
}

if ($resource_type !== '') {
    $sql .= " AND resource_type = ?";
    $params[] = $resource_type;
    $types .= "s";
}

if ($search !== '') {
    $sql .= " AND (subject LIKE ? OR file_name LIKE ?)";
    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ss";
}

$sql .= " ORDER BY uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$uploads = $stmt->get_result();


// Resource types
$resource_types = [
    "Notes",
    "Syllabus",
    "Question Papers",
    "Study Materials"
];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    

    <title>My Uploads - INMCA Resources Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f6f8fc;
            color: #1e293b;
            
            min-height: 100vh;
        }

        /* ================= NAVBAR ================= */

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

        .faculty-badge {
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


        /* ================= MAIN ================= */

        .main {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 55px 30px 70px;
        }


        /* ================= PAGE HEADER ================= */

        .page-header {
            text-align: center;
            margin-bottom: 38px;
        }

        .page-label {
            display: inline-block;
            padding: 6px 14px;
            margin-bottom: 13px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .page-header h1 {
            font-size: 34px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .page-header h1 span {
            color: #2563eb;
        }

        .page-header p {
            color: #64748b;
            font-size: 14px;
        }


        /* ================= FILTER BOX ================= */

        .filter-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 28px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        .filter-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .filters {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1.2fr 2fr auto;
            gap: 12px;
            align-items: end;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 7px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #ffffff;
            color: #334155;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px #eff6ff;
        }

        .filter-btn {
            height: 42px;
            padding: 0 20px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .filter-btn:hover {
            background: #1d4ed8;
        }

        .clear-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0 18px;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .clear-btn:hover {
            background: #f1f5f9;
        }


        /* ================= RESULTS ================= */

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .results-header h2 {
            font-size: 18px;
            color: #0f172a;
        }

        .result-count {
            font-size: 12px;
            color: #64748b;
            background: #ffffff;
            padding: 6px 11px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
        }


        /* ================= TABLE ================= */

        .table-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 17px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #475569;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fafcff;
        }

        .file-name {
            font-weight: 600;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #eff6ff;
            color: #2563eb;
        }

        .view-btn {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 7px;
            text-decoration: none;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
        }

        .view-btn:hover {
            background: #dbeafe;
        }

        .delete-btn {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 7px;
            text-decoration: none;
            background: #fef2f2;
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }


        /* ================= EMPTY ================= */

        .empty {
            text-align: center;
            padding: 65px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .empty h3 {
            color: #334155;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .empty p {
            font-size: 13px;
        }


        /* ================= FOOTER ================= */

        .footer {
            text-align: center;
            padding: 22px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
            color: #94a3b8;
            font-size: 12px;
        }
        @media (max-width: 600px) {
    .navbar {
        height: auto;
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .brand-name {
        font-size: 14px;
    }

    .nav-links {
        width: 100%;
        justify-content: flex-end;
        gap: 4px;
    }

    .nav-links a {
        padding: 7px 9px;
        font-size: 12px;
    }

    .main {
        padding: 32px 14px 48px;
    }

    .page-header h1 {
        font-size: 28px;
    }

    .filter-box {
        padding: 16px;
    }

    .filters {
        grid-template-columns: 1fr;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 700px;
    }
}
    
.action-buttons {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
}

.action-buttons a {
    min-width: 58px;
    text-align: center;
}

.action-buttons .delete-btn {
    margin-left: 0;
}
    </style>

</head>


<body>


<!-- ================= NAVBAR ================= -->

<header class="navbar">

    <a href="faculty_dashboard.php" class="brand">

        <img src="images/logo.svg" alt="INMCA Logo">

        <div>

            <span class="brand-name">
                INMCA Resources Portal
            </span>

            <span class="faculty-badge">
                Faculty
            </span>

        </div>

    </a>


    <nav class="nav-links">

        <a href="faculty_dashboard.php">
            <- Back
        </a>

        

        <a href="all_materials.php" class="active">
            All Materials
        </a>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </nav>

</header>



<!-- ================= MAIN ================= -->

<main class="main">


    <!-- PAGE HEADER -->

    <section class="page-header">

        <div class="page-label">
            Faculty Workspace
        </div>

        <h1>
            My <span>Uploads</span>
        </h1>

        <p>
            View and manage the academic resources you have uploaded.
        </p>

    </section>



    <!-- ================= FILTERS ================= -->

    <form method="GET" class="filter-box">

        <div class="filter-title">
            Search & Filter
        </div>

        <div class="filters">


            <!-- Semester -->

            <div class="filter-group">

                <label>Semester</label>

                <select name="semester">

                    <option value="">
                        All Semesters
                    </option>

                    <?php for ($i = 1; $i <= 10; $i++): ?>

                        <option value="<?php echo $i; ?>"
                            <?php echo ($semester == $i) ? 'selected' : ''; ?>>

                            Semester <?php echo $i; ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>



            <!-- Subject -->

            <div class="filter-group">

                <label>Subject</label>

                <select name="subject">

                    <option value="">
                        All Subjects
                    </option>

                    <?php foreach ($subjects as $sub): ?>

                        <option value="<?php echo htmlspecialchars($sub); ?>"
                            <?php echo ($subject === $sub) ? 'selected' : ''; ?>>

                            <?php echo htmlspecialchars($sub); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- Resource Type -->

            <div class="filter-group">

                <label>Resource Type</label>

                <select name="resource_type">

                    <option value="">
                        All Types
                    </option>

                    <?php foreach ($resource_types as $type): ?>

                        <option value="<?php echo htmlspecialchars($type); ?>"
                            <?php echo ($resource_type === $type) ? 'selected' : ''; ?>>

                            <?php echo htmlspecialchars($type); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- Search -->

            <div class="filter-group">

                <label>Search</label>

                <input
                    type="text"
                    name="search"
                    placeholder="Search subject or file name..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

            </div>



            <!-- Buttons -->

            <div>

                <button type="submit" class="filter-btn">
                    Filter
                </button>

            </div>

        </div>


        <?php if ($semester !== '' || $subject !== '' || $resource_type !== '' || $search !== ''): ?>

            <div style="margin-top:12px;">

                <a href="my_uploads.php" class="clear-btn">
                    Clear Filters
                </a>

            </div>

        <?php endif; ?>

    </form>



    <!-- ================= RESULTS ================= -->

    <div class="results-header">

        <h2>
            Uploaded Materials
        </h2>

        <span class="result-count">

            <?php echo $uploads->num_rows; ?>

            result<?php echo ($uploads->num_rows != 1) ? 's' : ''; ?>

        </span>

    </div>



    <!-- ================= TABLE ================= -->

    <div class="table-container">

        <?php if ($uploads->num_rows > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            File Name
                        </th>

                        <th>
                            Semester
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Resource Type
                        </th>

                        <th>
                            Uploaded Date
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while ($row = $uploads->fetch_assoc()): ?>

                    <tr>

                        <td class="file-name">

                            <?php echo htmlspecialchars($row['file_name']); ?>

                        </td>


                        <td>

                            Semester <?php echo htmlspecialchars($row['semester']); ?>

                        </td>


                        <td>

                            <?php echo htmlspecialchars($row['subject']); ?>

                        </td>


                        <td>

                            <span class="badge">

                                <?php echo htmlspecialchars($row['resource_type']); ?>

                            </span>

                        </td>


                        <td>

                            <?php
                            echo !empty($row['uploaded_at'])
                                ? date("d M Y", strtotime($row['uploaded_at']))
                                : '-';
                            ?>

                        </td>

                         <?php
                         $extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));

                           if (in_array($extension, ['doc', 'docx'], true)) {
                         $viewUrl = 'preview_word.php?id=' . (int)$row['id'];
                         } else {
                         $viewUrl = $row['file_path'];
                         }
                         ?>
                        <td>
                         <div class="action-buttons">
                            <a
                               href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                class="view-btn">
                            
                                View
                            </a>

                            <a
                                href="delete_upload.php?id=<?php echo $row['id']; ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this material?');"
                            >
                                Delete
                            </a>
                         </div>
                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    📂
                </div>

                <h3>
                    No uploads found
                </h3>

                <p>
                    No materials match the selected filters.
                </p>

            </div>

        <?php endif; ?>

    </div>


</main>



<!-- ================= FOOTER ================= -->

<footer class="footer">

    © <?php echo date('Y'); ?>
    INMCA Resources Portal. All rights reserved.

</footer>


</body>

</html>