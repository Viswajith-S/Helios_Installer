<?php
require 'includes/db.php';
try {
    $db_crm->exec("UPDATE projects SET status='Completed' WHERE id IN (1, 2, 3)");
    $db_crm->exec("
        INSERT IGNORE INTO completed_jobs (crm_project_id, lead_name, technician_name, install_date) 
        SELECT id, customer_name, 'Ravi Kumar', '2026-07-01' FROM projects WHERE id IN (1, 2, 3)
    ");
    echo "Successfully inserted mock completed jobs for July 1.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
