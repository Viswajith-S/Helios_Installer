<?php
require 'includes/db.php';
try {
    $db_installs->exec("CREATE TABLE IF NOT EXISTS `swms_signatures` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `crm_project_id` int(11) NOT NULL,
        `contractor_name` varchar(255) NOT NULL,
        `signature_date` date NOT NULL,
        `signature_data` longtext NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table swms_signatures created.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
