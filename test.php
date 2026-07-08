<?php
require 'includes/db.php';
$stmt = $db_crm->query("SELECT p.id, p.customer_name, p.job_sheet_url, pai.address FROM projects p LEFT JOIN project_additional_info pai ON p.id = pai.project_id WHERE p.id IN (257, 235, 258)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
