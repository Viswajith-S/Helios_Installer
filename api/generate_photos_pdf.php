<?php
require_once '../includes/db.php';
require_once '../includes/fpdf/fpdf.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$project_id = $data['project_id'] ?? 0;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

try {
    // Fetch photos
    $stmt = $db_crm->prepare("SELECT file_path, file_type FROM project_files WHERE crm_project_id = ? AND (file_type = 'pre_install_photo' OR file_type = 'post_install_photo') ORDER BY file_type DESC");
    $stmt->execute([$project_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($photos)) {
        echo json_encode(['success' => false, 'message' => 'No photos found for this project.']);
        exit;
    }

    $pdf = new FPDF();
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Installation Photos Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Project ID: ' . $project_id, 0, 1, 'C');
    $pdf->Ln(10);

    $x = 10;
    $y = $pdf->GetY();
    $maxWidth = 90;
    $maxHeight = 90;
    $count = 0;

    foreach ($photos as $photo) {
        $filePath = '../' . $photo['file_path']; // e.g. ../uploads/pre/123/file.jpg
        
        if (file_exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                // If we need a new row or page
                if ($count > 0 && $count % 2 == 0) {
                    $x = 10;
                    $y += $maxHeight + 15;
                    
                    if ($y + $maxHeight > 280) {
                        $pdf->AddPage();
                        $y = 20;
                    }
                }

                $pdf->SetFont('Arial', 'B', 10);
                $label = ($photo['file_type'] === 'pre_install_photo') ? 'Pre-Install Photo' : 'Post-Install Photo';
                $pdf->Text($x, $y - 2, $label);
                
                // Try to embed image
                try {
                    $pdf->Image($filePath, $x, $y, $maxWidth, $maxHeight, $ext);
                } catch(Exception $e) {
                    // Skip unsupported images silently
                }
                
                $x += $maxWidth + 10;
                $count++;
            }
        }
    }

    // Ensure output directory exists
    $outDir = '../uploads/reports';
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }
    
    $filename = 'Photos_Project_' . $project_id . '.pdf';
    $outPath = $outDir . '/' . $filename;
    $pdf->Output('F', $outPath);

    echo json_encode([
        'success' => true,
        'url' => 'uploads/reports/' . $filename
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
}
