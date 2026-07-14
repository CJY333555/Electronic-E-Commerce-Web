<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$file     = $_FILES['profile_pic'];
$mimeType = mime_content_type($file['tmp_name']);

// Only allow PNG
if ($mimeType !== 'image/png') {
    echo json_encode(['success' => false, 'message' => 'Only PNG images are allowed.']);
    exit();
}

// Save to assets/images/profile_pics/
$uploadDir = __DIR__ . '/assets/images/profile_pics/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename  = 'user_' . $_SESSION['user_id'] . '_' . time() . '.png';
$destPath  = $uploadDir . $filename;
$relPath   = 'assets/images/profile_pics/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
    exit();
}

// Update DB
$stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "si", $relPath, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true, 'path' => $relPath]);
exit();
