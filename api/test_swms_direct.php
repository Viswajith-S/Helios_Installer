<?php
require_once '../includes/db.php';
require_once '../includes/fpdf/fpdf.php';
require_once '../includes/fpdi/autoload.php';

use setasign\Fpdi\Fpdi;

$project_id = 257;

try {
    // Fetch signatures
    $stmt = $db_installs->prepare("SELECT contractor_name, signature_date, signature_data FROM swms_signatures WHERE crm_project_id = ? ORDER BY created_at ASC");
    $stmt->execute([$project_id]);
    $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($signatures)) {
        echo json_encode(['success' => false, 'message' => 'No signatures found']);
        exit;
    }

    $pdf = new Fpdi();
    
    // Import all pages of the existing template
    $templatePath = '../swms_template.pdf';
    if (file_exists($templatePath)) {
        $pageCount = $pdf->setSourceFile($templatePath);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
    }

    echo "OK: Fpdi loaded and template parsed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
