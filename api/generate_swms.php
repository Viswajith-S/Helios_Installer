<?php
require_once '../includes/db.php';
require_once '../includes/fpdf/fpdf.php';
require_once '../includes/fpdi/autoload.php';

use setasign\Fpdi\Fpdi;

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['project_id'] ?? 0;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

try {
    // Fetch signatures
    $stmt = $db_installs->prepare("SELECT contractor_name, signature_date, signature_data FROM swms_signatures WHERE crm_project_id = ? ORDER BY created_at ASC");
    $stmt->execute([$project_id]);
    $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configuration for Inline Signature Placement
    $targetPage = 1; // Change this to the page number where the table is located
    
    // Coordinates for the First Contractor (in mm)
    $nameX = 80; $nameY = 60;
    $dateX = 175; $dateY = 58;
    $sigX = 130; $sigY = 55; $sigW = 35; $sigH = 12;

    $pdf = new Fpdi();
    
    // Import all pages of the existing template
    $templatePath = '../swms_template.pdf';
    try {
        if (file_exists($templatePath)) {
            $pageCount = $pdf->setSourceFile($templatePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                
                // Insert Signature on Target Page
                if ($pageNo == $targetPage && !empty($signatures)) {
                    $sig = $signatures[0]; // Use the primary contractor
                    $pdf->SetFont('Arial', '', 11);
                    
                    // Name
                    $pdf->SetXY($nameX, $nameY);
                    $pdf->Cell(40, 10, $sig['contractor_name']);
                    
                    // Date
                    $pdf->SetXY($dateX, $dateY);
                    $pdf->Cell(30, 10, $sig['signature_date']);
                    
                    // Signature Image
                    if (preg_match('/^data:image\/(?<extension>(?:png|gif|jpg|jpeg));base64,(?<image>.+)$/', $sig['signature_data'], $matchings)) {
                        $imageData = base64_decode($matchings['image']);
                        $ext = $matchings['extension'];
                        $tempFile = sys_get_temp_dir() . '/sig_' . uniqid() . '.' . $ext;
                        file_put_contents($tempFile, $imageData);
                        
                        $pdf->Image($tempFile, $sigX, $sigY, $sigW, $sigH, $ext);
                        unlink($tempFile);
                    }
                }
            }
        }
    } catch (Exception $e) {
        // If the PDF uses unsupported compression (PDF 1.5+), fallback to just the signature page
        $pdf = new Fpdi();
    }

    // If there are additional signatures, append them as a new page at the end
    if (count($signatures) > 1) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Additional SWMS Acknowledgements', 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(70, 10, 'Contractor Name', 1, 0, 'L', true);
        $pdf->Cell(40, 10, 'Date', 1, 0, 'L', true);
        $pdf->Cell(80, 10, 'Signature', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);
        
        // Start from index 1 (skip the primary contractor)
        for ($i = 1; $i < count($signatures); $i++) {
            $sig = $signatures[$i];
            $pdf->Cell(70, 20, $sig['contractor_name'], 1, 0, 'L');
            $pdf->Cell(40, 20, $sig['signature_date'], 1, 0, 'L');
            
            // Handle Base64 Image
            if (preg_match('/^data:image\/(?<extension>(?:png|gif|jpg|jpeg));base64,(?<image>.+)$/', $sig['signature_data'], $matchings)) {
                $imageData = base64_decode($matchings['image']);
                $ext = $matchings['extension'];
                
                $tempFile = sys_get_temp_dir() . '/sig_' . uniqid() . '.' . $ext;
                file_put_contents($tempFile, $imageData);
                
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                
                $pdf->Cell(80, 20, '', 1, 1, 'C');
                $pdf->Image($tempFile, $x + 5, $y + 1, 70, 18, $ext);
                unlink($tempFile);
            } else {
                $pdf->Cell(80, 20, 'No Signature', 1, 1, 'C');
            }
        }
    }

    // Ensure output directory exists
    $outDir = '../uploads/swms';
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }
    
    $filename = 'SWMS_Project_' . $project_id . '.pdf';
    $outPath = $outDir . '/' . $filename;
    $pdf->Output('F', $outPath);

    echo json_encode([
        'success' => true,
        'url' => 'uploads/swms/' . $filename
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
}
