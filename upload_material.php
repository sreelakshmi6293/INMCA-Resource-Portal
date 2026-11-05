
<?php
session_start();
require_once 'db.php';

$subject = $_GET['subject'] ?? '';
$semester = $_GET['semester'] ?? '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject = $_POST['subject'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $resource_type = $_POST['resource_type'] ?? 'Notes';
    if ($semester == '10') {
        $resource_type = 'Notes';
    }

    
    if (!isset($_FILES['material']) || $_FILES['material']['error'] === UPLOAD_ERR_NO_FILE) {
    $error = "Please select a file.";

    } elseif ($_FILES['material']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['material']['error'];

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        $error = "File is too large. Please upload a smaller file.";
    } else {
        $error = "File upload failed. Error code: " . $errorCode;
    }


    } else {

        $file = $_FILES['material'];

        $allowed_types = ['pdf', 'doc', 'docx'];

        $file_extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($file_extension, $allowed_types)) {

            $error = "Only PDF, DOC and DOCX files are allowed.";

        } else {

            $upload_folder = "uploads/";

            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }

            $new_file_name =
                time() . "_" .
                preg_replace("/[^a-zA-Z0-9._-]/", "_", $file['name']);

            $file_path = $upload_folder . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {

                $uploaded_by = $_SESSION['name'] ?? null;
                


                $stmt = $conn->prepare("
                    INSERT INTO resources
                    (semester, subject, resource_type, file_name, file_path, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "isssss",
                    $semester,
                    $subject,
                    $resource_type,
                    $file['name'],
                    $file_path,
                    $uploaded_by
                );

                if ($stmt->execute()) {

                    $message = "Material uploaded successfully.";

                } else {

                    $error = "Database error: " . $stmt->error;

                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }

                $stmt->close();

            } else {

                $error = "Failed to upload the file.";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Upload Material</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fb;
            min-height: 100vh;
            margin: 0;
        }
        .page-content {
            min-height: calc(100vh - 74px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
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


        .card {
            background: white;
            width: 90%;
            max-width: 550px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin: 50px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #374151;
            
        }

        .form-group {
            margin-bottom: 20px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .upload-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #1a73e8;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .upload-btn:hover {
            background: #1557b0;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }
        /* Page Header */

.page-header {
    text-align: center;
    padding: 50px 20px 35px;
}

.page-label {
    display: inline-block;
    font-size: 14px;
    font-weight: 600;
    color: #4f46e5;
    background: #eef2ff;
    padding: 8px 16px;
    border-radius: 20px;
    margin-bottom: 18px;
}

.page-header h1 {
    font-size: 38px;
    font-weight: 700;
    color: #0b1c30;
    margin-bottom: 12px;
}

.page-header h1 span {
    color: #3e34f9;
}

.page-header p {
    font-size: 16px;
    color: #64748b;
    margin: 0;
}
@media (max-width: 700px) {
    .navbar {
        height: auto;
        min-height: 74px;
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .brand-name {
        font-size: 15px;
    }

    .brand img {
        width: 36px;
        height: 36px;
    }

    .nav-links {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 4px;
    }

    .nav-links a {
        padding: 8px;
        font-size: 12px;
    }

    .page-content {
        min-height: auto;
        padding: 16px 12px;
    }

    .card {
        width: 100%;
        padding: 22px 16px;
        margin: 16px auto;
    }

    input,
    select {
        font-size: 16px;
    }
}
    </style>

</head>

<body>
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
            Dashboard
        </a>

       

        <a href="my_uploads.php">
            My Uploads
        </a>

        <a href="all_materials.php">
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
            Upload <span>Material</span>
        </h1>

        <p>
            Share your academic resources with the students.
        </p>

    </section>



    <!-- ================= FORM ================= -->

    <div class="card">

    <h1>Upload Material</h1>

    <p class="subtitle">
        Faculty Resource Upload
    </p>

    <?php if ($message): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <form method="POST"
          enctype="multipart/form-data">

       <!-- Semester -->

<div class="form-group">

    <label>Semester</label>

    <select name="semester"
            id="semester"
            onchange="loadSubjects()"
            required>

        <option value="">Select Semester</option>

        <option value="1">Semester 1</option>
        <option value="2">Semester 2</option>
        <option value="3">Semester 3</option>
        <option value="4">Semester 4</option>
        <option value="5">Semester 5</option>
        <option value="6">Semester 6</option>
        <option value="7">Semester 7</option>
        <option value="8">Semester 8</option>
        <option value="9">Semester 9</option>
        <option value="10">Semester 10</option>

    </select>

</div>


<!-- Subject -->

<div class="form-group">

    <label>Subject</label>

    <select name="subject"
            id="subject"
            required>

        <option value="">Select Semester First</option>

    </select>

</div>


<script>

function loadSubjects() {

    const semester =
        document.getElementById("semester").value;

    const subject =
        document.getElementById("subject");

    subject.innerHTML =
        '<option value="">Select Subject</option>';

    let subjects = [];

    if (semester === "1") {

         subjects = [

            "ENGLISH",
            "BASIC MATHEMATICS",
            "INTRODUCTION TO PROGRAMMING",
            "INTRODUCTION TO COMPUTERS & PC HARDWARE",
            "FUNDAMENTALS OF ACCOUNTANCY"

        ];
    } else if (semester === "2") {
         subjects = [
            "TECHNICAL COMMUNICATION", 
            "INTRODUCTION TO DISCRETE MATHEMATICS",
            "INTRODUCTION TO DIGITAL SYSTEMS & LOGIC DESIGNS",
            "PROBLEM SOLVING AND STRUCTURED PROGRAMMING",
            "PERSONALITY DEVELOPMENT AND SOFT SKILLS" ];
        }
    else if (semester === "3") { subjects = [ 
        "COMPUTER ORGANIZATION", 
        "PROBABILITY AND STATISTICS", 
        "INTRODUCTION TO OBJECT ORIENTED PROGRAMMING", 
        "ACCOUNTING AND FINANCIAL MANAGEMENT",
        "DATA STRUCTURES" ];
    }
    else if (semester === "4") {  subjects = [ 
        "LINUX/UNIX FUNDAMENTALS",
        "STATISTICAL APPLICATIONS",
        "OPERATING SYSTEMS",
        "ELEMENTS OF BUSINESS MANAGEMENT",
        "INTERNET CONCEPTS AND WEB TECHNOLOGY"];
    }
    else if (semester === "5") {  subjects = [ 
        "NUMERICAL METHODS", 
        "USER INTERFACE DESIGN", 
        "INTRODUCTION TO RDBMS AND SQL", 
        "FUNDAMENTALS OF INFORMATION SYSTEMS SECURITY", 
        "INTRODUCTION TO OPERATIONS RESEARCH" ];
    }
    else if (semester === "6") {  subjects = [ 
        "INTRODUCTION TO SOFTWARE ENGINEERING", 
        "FUNDAMENTALS OF DATA SCIENCE", 
        "VISUAL PROGRAMMING",
        "DESIGN AND ANALYSIS OF ALGORITHMS", 
        "MINI PROJECT 1" ];
    }
    else if (semester === "7") {  subjects = [
        "M-COMMERCE", 
        "JAVA PROGRAMMING", 
        "ADVANCED WEB PROGRAMMING", 
        "ADVANCED SOFTWARE ENGINEERING", 
        "DISTRIBUTED COMPUTING" ];
    }
    else if (semester === "8") {  subjects = [
        "ADVANCED DATABASE MANAGEMENT SYSTEMS", 
        "ADVANCED COMPUTER NETWORKS", 
        "RESEARCH METHODOLOGY", 
        "ORGANIZATIONAL BEHAVIOUR", 
        "ARTIFICIAL INTELLIGENCE" ];
    }
    else if (semester === "9") {  subjects = [
        "DATA SCIENCE & MACHINE LEARNING", 
        "MOBILE COMPUTING", 
        "CYBER FORENSICS", 
        "SOCIAL NETWORK ANALYSIS", 
        "MINI PROJECT 2" ];
    }
    else if (semester === "10") {  subjects = [
        "COMPREHENSIVE VIVA", 
        "SEMINAR", 
        "MAIN PROJECT", 
        ];
    }
    subjects.forEach(function(subjectName) {

            const option =
                document.createElement("option");

            option.value = subjectName;
            option.textContent = subjectName;

            subject.appendChild(option);

        });

       const resourceType = document.getElementById("resource_type");

       if (semester === "10") {
    resourceType.value = "Notes";
    resourceType.disabled = true;
} else {
    resourceType.value = "";
    resourceType.disabled = false;
} 
    }


</script>



        

        <div class="form-group">

            <label>Resource Type</label>

            <select name="resource_type" required>

                <option value="">
                    Select Resource Type
                </option>

                <option value="Notes">
                    Notes
                </option>

                <option value="Syllabus">
                    Syllabus
                </option>

                <option value="Question Papers">
                    Question Papers
                </option>

                

            </select>

        </div>

        <div class="form-group">

            <label>Select File</label>

            <input type="file"
                   name="material"
                   accept=".pdf,.doc,.docx"
                   required>

        </div>

        <button type="submit"
                class="upload-btn">

            Upload Material

        </button>

    </form>

</div>

</body>

</html>


