<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crm_project_id = isset($_POST['crm_project_id']) ? (int)$_POST['crm_project_id'] : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    if ($crm_project_id > 0 && $db_installs) {
        
        // Ensure record exists
        try {
            $stmt = $db_installs->prepare("INSERT IGNORE INTO job_compliance (crm_project_id) VALUES (:id)");
            $stmt->execute(['id' => $crm_project_id]);
        } catch (Exception $e) {}

        if ($type === 'swms') {
            $swms = isset($_POST['swms_completed']) ? 1 : 0;
            try {
                $stmt = $db_installs->prepare("UPDATE job_compliance SET swms_completed = :swms WHERE crm_project_id = :id");
                $stmt->execute(['swms' => $swms, 'id' => $crm_project_id]);
            } catch (Exception $e) {}
            
            // Redirect back to job detail post-install tab
            header("Location: ../job_detail.php?id=" . $crm_project_id . "&tab=post-install");
            exit;
        }
        
        if ($type === 'final_lock') {
            try {
                $stmt = $db_installs->prepare("UPDATE job_compliance SET final_sign_off_status = 'Locked' WHERE crm_project_id = :id");
                $stmt->execute(['id' => $crm_project_id]);
            } catch (Exception $e) {}
            
            // Redirect back to compliance page
            header("Location: ../compliance.php?id=" . $crm_project_id . "&success=1");
            exit;
        }
    }
}

// Fallback redirect
header("Location: ../index.php");
exit;
