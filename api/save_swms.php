<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data in swms']);
    exit;
}

$project_id = (int)$data['project_id'];
$contractor_name = $data['contractor_name'] ?? '';
$signature_date = $data['signature_date'] ?? '';
$signature_data = $data['signature_data'] ?? '';

if (!$db_installs) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $insert = $db_installs->prepare("
        INSERT INTO swms_signatures (crm_project_id, contractor_name, signature_date, signature_data) 
        VALUES (?, ?, ?, ?)
    ");
    $insert->execute([$project_id, $contractor_name, $signature_date, $signature_data]);
    $new_id = $db_installs->lastInsertId();

    echo json_encode(['success' => true, 'message' => 'Signature saved successfully', 'new_id' => $new_id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
