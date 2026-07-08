<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$crm_project_id = isset($_POST['crm_project_id']) ? (int)$_POST['crm_project_id'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : ''; // 'pre' or 'post'

if ($crm_project_id <= 0 || !in_array($type, ['pre', 'post'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID or type']);
    exit;
}

$upload_dir = __DIR__ . '/../uploads/' . $type . '/' . $crm_project_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$saved_files = [];

if (!empty($_FILES['photos'])) {
    $files = $_FILES['photos'];
    $count = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $count; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        
        // Generate unique filename
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'];
        if (!in_array($ext, $allowed)) continue;
        
        $new_name = $type . '_' . date('Ymd_His') . '_' . $i . '.' . $ext;
        $dest = $upload_dir . $new_name;
        
        if (move_uploaded_file($tmp, $dest)) {
            $saved_files[] = $new_name;
            
            // Insert into project_files schema
            if (isset($db_crm)) {
                $file_type = ($type === 'pre') ? 'pre_install_photo' : 'post_install_photo';
                // Path relative to CRM2/Engineering where it is accessed
                $db_path = 'uploads/' . $type . '/' . $crm_project_id . '/' . $new_name;
                try {
                    $insert_file = $db_crm->prepare("INSERT INTO project_files (crm_project_id, file_type, file_path) VALUES (?, ?, ?)");
                    $insert_file->execute([$crm_project_id, $file_type, $db_path]);
                } catch (Exception $e) {
                    error_log("Failed to insert project file: " . $e->getMessage());
                }
            }
        }
    }
}

if (count($saved_files) > 0) {
    echo json_encode([
        'success' => true, 
        'message' => count($saved_files) . ' photo(s) saved.',
        'files' => $saved_files
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No photos were saved.']);
}
