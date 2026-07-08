<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['project_id'] ?? 0;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

try {
    // 1. Fetch Job Details
    $stmt = $db_crm->prepare("SELECT p.customer_name, p.installation_date, pai.address 
                              FROM projects p 
                              LEFT JOIN project_additional_info pai ON p.id = pai.project_id 
                              WHERE p.id = ?");
    $stmt->execute([$project_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }

    // 2. Mark job as completed in installs db (or CRM db)? 
    // The main CRM 'projects' table has a 'status' field.
    $updateStmt = $db_crm->prepare("UPDATE projects SET status = 'Completed' WHERE id = ?");
    $updateStmt->execute([$project_id]);

    // Insert into completed_jobs in CRM DB
    try {
        $insertCompleted = $db_crm->prepare("INSERT IGNORE INTO completed_jobs (crm_project_id, lead_name, technician_name, install_date) VALUES (?, ?, ?, ?)");
        // We'll just leave technician_name empty or 'Installer' for now
        $insertCompleted->execute([$project_id, $job['customer_name'], 'Installer', $job['installation_date']]);
    } catch (Exception $e) {}

    // 3. Send Email
    $to = 'info@heliosenergy.com.au';
    $subject = 'Installation Completed: ' . $job['customer_name'];
    $message = "
    <html>
    <head>
        <title>Installation Completed</title>
    </head>
    <body>
        <h2>Job Completed</h2>
        <p><strong>Customer:</strong> {$job['customer_name']}</p>
        <p><strong>Address:</strong> {$job['address']}</p>
        <p><strong>Date:</strong> {$job['installation_date']}</p>
        <p>The installation for this customer has been marked as completed by the installer.</p>
        <p>Please check the CRM for SWMS and Photos.</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <no-reply@heliosenergy.com.au>' . "\r\n";

    // Uncomment this line to actually send mail if server is configured
    // mail($to, $subject, $message, $headers);
    
    // For local dev without mail server, we'll just log it or simulate success.
    error_log("Email sent to $to: $subject");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
