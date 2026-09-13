<?php
require_once 'db.php';

$semester = $_GET['semester'] ?? '';
$subject = $_GET['subject'] ?? '';
$type = $_GET['type'] ?? '';

$resources = [];

if ($semester !== '' && $subject !== '' && $type !== '') {

    $stmt = $conn->prepare("
        SELECT id, file_name, file_path, uploaded_by, uploaded_at
        FROM resources
        WHERE semester = ?
        AND subject = ?
        AND resource_type = ?
        ORDER BY uploaded_at DESC
    ");

    $stmt->bind_param("iss", $semester, $subject, $type);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resources</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        h1 {
            text-align: center;
        }

        .resource-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .file-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .uploaded-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            margin-right: 10px;
            text-decoration: none;
            border-radius: 6px;
            color: white;
        }

        .view-btn {
            background: #3498db;
        }

        .download-btn {
            background: #27ae60;
        }

        .no-resource {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>
        <?php echo htmlspecialchars($subject); ?>
        - 
        <?php echo htmlspecialchars($type); ?>
    </h1>

    <?php if (count($resources) > 0): ?>

        <?php foreach ($resources as $resource): ?>

            <div class="resource-card">

                <div class="file-name">
                    📄 <?php echo htmlspecialchars($resource['file_name']); ?>
                </div>

                <div class="uploaded-info">
                    Uploaded by:
                    <?php echo htmlspecialchars($resource['uploaded_by']); ?>
                    <br>

                    Uploaded on:
                    <?php echo htmlspecialchars($resource['uploaded_at']); ?>
                </div>

                <a
                    href="<?php echo htmlspecialchars($resource['file_path']); ?>"
                    target="_blank"
                    class="btn view-btn">
                    View
                </a>

                <a
                    href="<?php echo htmlspecialchars($resource['file_path']); ?>"
                    download
                    class="btn download-btn">
                    Download
                </a>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="no-resource">
            <h3>No resources available.</h3>
            <p>No <?php echo htmlspecialchars($type); ?> uploaded for this subject yet.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>