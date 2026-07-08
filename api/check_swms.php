<?php
require 'D:/Helios/other/Install manager/includes/db.php';
$stmt = $db_installs->query('DESCRIBE swms_signatures');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
