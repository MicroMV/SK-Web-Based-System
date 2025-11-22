<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../public/signin.php");
    exit;
}

// Get file ID
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($file_id > 0) {
    // Get file info from database
    $stmt = $pdo->prepare("SELECT file_path, original_name, file_type FROM files WHERE file_id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        $file_path = '../' . $file['file_path'];
        
        // Check if file exists
        if (file_exists($file_path)) {
            // Set headers to force download with original filename
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $file['file_type']);
            header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            // Clear output buffer
            ob_clean();
            flush();
            
            // Read file and output
            readfile($file_path);
            exit;
        } else {
            die('Error: File not found on server.');
        }
    } else {
        die('Error: File not found in database.');
    }
} else {
    die('Error: Invalid file ID.');
}
?>
