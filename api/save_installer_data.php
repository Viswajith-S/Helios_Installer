<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON POST body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $crm_project_id = isset($data['crm_project_id']) ? (int)$data['crm_project_id'] : 0;
    $swms = isset($data['swms']) ? $data['swms'] : '';
    $photos = isset($data['photos']) ? json_encode($data['photos']) : '{}';

    if ($crm_project_id > 0 && $db_installs) {
        try {
            $db_installs->beginTransaction();

            // Ensure job_compliance record exists
            $stmt = $db_installs->prepare("INSERT IGNORE INTO job_compliance (crm_project_id) VALUES (:id)");
            $stmt->execute(['id' => $crm_project_id]);
            
            // Update SWMS and Photos
            $swms_val = !empty($swms) ? 1 : 0;
            $stmt = $db_installs->prepare("UPDATE job_compliance SET swms_completed = :swms, photo_data = :photos WHERE crm_project_id = :id");
            $stmt->execute([
                'swms' => $swms_val,
                'photos' => $photos,
                'id' => $crm_project_id
            ]);

            $db_installs->commit();
            echo json_encode(['success' => true, 'message' => 'Job data saved successfully.']);
            exit;

        } catch (Exception $e) {
            if ($db_installs->inTransaction()) {
                $db_installs->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID or database connection failed.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
