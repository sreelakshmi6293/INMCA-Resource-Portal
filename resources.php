<?php
require_once 'db.php';

$semester = $_GET['semester'] ?? '';
$subject = $_GET['subject'] ?? '';
$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? '';

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0px;
        }

        .navbar {
          background: white;
          min-height: 60px;
          padding: 10px 24px;
          display: flex;
          align-items: center;
          justify-content: space-between;
           gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
       }


       .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0f172a;
    font-weight: bold;
    text-decoration: none;
    
    
}

.brand img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.back-link {
    color: #004ac6;
    text-decoration: none;
    font-weight: bold;
}

.container {
    max-width: 900px;
    margin: auto;
    padding: 24px 16px;
}

@media (max-width: 600px) {
    .navbar {
        padding: 10px 14px;
    }

    .brand {
        font-size: 14px;
    }
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
        .resource-card {
    width: 100%;
max-width: 320px;
    margin: 18px auto;
    padding: 22px;
    border-left: 5px solid #3b5ccc;
    border-radius: 14px;
    background: white;
    box-shadow: 0 6px 18px rgba(20, 40, 75, 0.1);
}

.file-name {
    font-size: 20px;
    margin-bottom: 10px;
}

.uploaded-info {
    color: #687386;
    line-height: 1.6;
}

.btn {
    min-width: 100px;
    margin-top: 8px;
    text-align: center;
    transition: 0.2s;
}

.view-btn {
    background: #eef2ff;
    color: #2949a5;
}

.download-btn {
    background: #2949a5;
    color: white;
}

.btn:hover {
    opacity: 0.85;
}

@media (max-width: 560px) {
    .btn {
        display: block;
        width: 100%;
        margin: 10px 0;
    }
}
.container {
    max-width: 1400px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.container h1,
.container .no-resource {
    grid-column: 1 / -1;
}

.resource-card {
    width: auto;
    max-width: none;
    margin: 0;
}

@media (max-width: 1000px) {
    .container {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .container {
        grid-template-columns: 1fr;
    }
}
#doc-loading {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.4);
    align-items: center;
    justify-content: center;
}

.doc-loading-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 24px;
    border-radius: 12px;
    background: white;
    font-weight: 600;
}

.doc-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #ddd;
    border-top-color: #2949a5;
    border-radius: 50%;
    animation: doc-spin 0.8s linear infinite;
}

@keyframes doc-spin {
    to { transform: rotate(360deg); }
}
    </style>
</head>

<body>
<header class="navbar">
    <a href="index.html" class="brand">
        <img src="images/logo.svg" alt="INMCA logo">
        <span>INMCA Resource Portal</span>
    </a>

   <a class="back-link"
   href="semester<?php echo (int)$semester; ?>.html<?php echo in_array($from, ['all', 'student'], true) ? '?from=' . urlencode($from) : ''; ?>">
    ← Back
</a> 
</header>
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

                
                <?php
               $extension = strtolower(
               pathinfo($resource['file_name'], PATHINFO_EXTENSION)
               );

                if (in_array($extension, ['doc', 'docx'], true)) {
                  $viewUrl = 'preview_word.php?id=' . (int)$resource['id'];
                 } else {
                  $viewUrl = $resource['file_path'];
                  }
                ?>

               
<div class="resource-actions">
    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
       class="btn view-btn">👁 &nbsp; View</a>

    <a href="<?php echo htmlspecialchars($resource['file_path'], ENT_QUOTES, 'UTF-8'); ?>"
       download
       class="btn download-btn">⬇ &nbsp; Download</a>
</div>
            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="no-resource">
            <h3>No resources available.</h3>
            <p>No <?php echo htmlspecialchars($type); ?> uploaded for this subject yet.</p>
        </div>

    <?php endif; ?>

</div>
<div id="doc-loading" role="status" aria-live="polite">
    <div class="doc-loading-box">
        <span class="doc-spinner"></span>
        <span>Please wait a moment. This may take a little while to load.</span>
    </div>
</div>

<script>
document.querySelectorAll('.view-btn').forEach(function (link) {
    if (link.href.includes('preview_word.php')) {
        link.addEventListener('click', function () {
            document.getElementById('doc-loading').style.display = 'flex';
        });
    }
});
window.addEventListener('pageshow', function () {
    document.getElementById('doc-loading').style.display = 'none';
});
</script>
</body>
</html>