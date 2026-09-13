
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
    $resource_type = $_POST['resource_type'] ?? '';

    if (!isset($_FILES['material']) || $_FILES['material']['error'] !== UPLOAD_ERR_OK) {

        $error = "Please select a file.";

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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: white;
            width: 90%;
            max-width: 550px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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

    </style>

</head>

<body>

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


    if (semester === "1") {

        const subjects = [

            "ENGLISH",
            "BASIC MATHEMATICS",
            "INTRODUCTION TO PROGRAMMING",
            "INTRODUCTION TO COMPUTERS & PC HARDWARE",
            "FUNDAMENTALS OF ACCOUNTANCY"

        ];

        subjects.forEach(function(subjectName) {

            const option =
                document.createElement("option");

            option.value = subjectName;
            option.textContent = subjectName;

            subject.appendChild(option);

        });

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

                <option value="Study Materials">
                    Study Materials
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


