<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Please log in first.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid resource ID.');
}

$stmt = $conn->prepare(
    "SELECT file_name, file_path FROM resources WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$resource = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resource) {
    http_response_code(404);
    exit('Resource not found.');
}

$sourceFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . $resource['file_path']);
$uploadsFolder = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');

if (!$sourceFile || !$uploadsFolder ||
    strncasecmp($sourceFile, $uploadsFolder . DIRECTORY_SEPARATOR, strlen($uploadsFolder . DIRECTORY_SEPARATOR)) !== 0) {
    http_response_code(404);
    exit('File not found.');
}

$extension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
if (!in_array($extension, ['doc', 'docx'], true)) {
    http_response_code(400);
    exit('This preview is only for DOC and DOCX files.');
}

$soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
$outputFolder = __DIR__ . DIRECTORY_SEPARATOR . 'converted_pdfs';
$pdfFile = $outputFolder . DIRECTORY_SEPARATOR .
           pathinfo($sourceFile, PATHINFO_FILENAME) . '.pdf';

if (!is_file($soffice)) {
    http_response_code(500);
    exit('LibreOffice was not found at the expected location.');
}

if (!is_dir($outputFolder)) {
    mkdir($outputFolder, 0775, true);
}

if (!is_file($pdfFile)) {
    if (!function_exists('exec')) {
        http_response_code(500);
        exit('PHP exec is disabled. Ask your administrator to enable it.');
    }

    $command = '"' . $soffice . '" --headless --convert-to pdf --outdir ' .
               escapeshellarg($outputFolder) . ' ' . escapeshellarg($sourceFile);

    exec($command . ' 2>&1', $output, $status);

    if ($status !== 0 || !is_file($pdfFile)) {
        http_response_code(500);
        exit('Could not convert this Word file to PDF.');
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="resource-preview.pdf"');
readfile($pdfFile);
exit;