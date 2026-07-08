<?php
$fpdf_url = 'https://github.com/Setasign/FPDF/archive/refs/heads/master.zip';
$fpdi_url = 'https://github.com/Setasign/FPDI/archive/refs/heads/master.zip';

function downloadAndExtract($url, $destDir, $subFolderToKeep) {
    $zipFile = 'temp.zip';
    file_put_contents($zipFile, file_get_contents($url));
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo(__DIR__ . '/temp_extract');
        $zip->close();
        
        // Find the extracted folder
        $dirs = glob(__DIR__ . '/temp_extract/*', GLOB_ONLYDIR);
        if (!empty($dirs)) {
            $extracted = $dirs[0];
            if ($subFolderToKeep) {
                rename($extracted . '/' . $subFolderToKeep, __DIR__ . '/' . $destDir);
            } else {
                rename($extracted, __DIR__ . '/' . $destDir);
            }
        }
        
        // Cleanup
        $it = new RecursiveDirectoryIterator(__DIR__ . '/temp_extract', RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir(__DIR__ . '/temp_extract');
    }
    unlink($zipFile);
}

if (!is_dir('includes/fpdf')) {
    echo "Downloading FPDF...\n";
    downloadAndExtract($fpdf_url, 'includes/fpdf', '');
}
if (!is_dir('includes/fpdi')) {
    echo "Downloading FPDI...\n";
    downloadAndExtract($fpdi_url, 'includes/fpdi', 'src');
}
echo "Libraries installed successfully!\n";
