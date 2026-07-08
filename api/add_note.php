<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $crm_project_id = isset($data['crm_project_id']) ? (int)$data['crm_project_id'] : 0;
    $note = isset($data['note']) ? trim($data['note']) : '';

    if ($crm_project_id > 0 && !empty($note) && $db_installs) {
        try {
            $stmt = $db_installs->prepare("INSERT INTO job_notes (crm_project_id, note) VALUES (:id, :note)");
            $stmt->execute(['id' => $crm_project_id, 'note' => $note]);
            
            // Return the created note so the UI can append it instantly
            $created_at = date('Y-m-d H:i:s');
            echo json_encode([
                'success' => true, 
                'note' => [
                    'text' => $note,
                    'time' => date('h:i A', strtotime($created_at)),
                    'date' => date('d M Y', strtotime($created_at))
                ]
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);
