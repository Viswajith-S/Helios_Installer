<?php
require 'includes/db.php';
$stmt = $db_crm->query("DESCRIBE projects");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
