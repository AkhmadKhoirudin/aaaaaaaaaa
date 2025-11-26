<?php
// Skrip untuk memperbaiki semua file admin yang menggunakan include lama

$files_to_fix = [
    'admin/ujian.php',
    'admin/peserta.php',
    'admin/monitoring_ujian.php',
    'admin/import_soal.php',
    'admin/export_soal.php',
    'admin/cetak_kartu.php',
    'admin/analisis_nilai.php'
];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace header and sidebar includes
        $content = preg_replace(
            "/include\s+'includes/header\.php';\s*\r?\n\s*include\s+'includes/sidebar\.php';/",
            "// Set page title\n\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));\n\ninclude 'includes/header-modern.php';",
            $content
        );
        
        // Replace footer include
        $content = str_replace(
            "<?php include 'includes/footer.php'; ?>",
            "<?php include 'includes/footer-modern.php'; ?>",
            $content
        );
        
        // Set more appropriate page titles based on file name
        if (strpos($file, 'ujian.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Manajemen Ujian';",
                $content
            );
        } elseif (strpos($file, 'peserta.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Manajemen Peserta Ujian';",
                $content
            );
        } elseif (strpos($file, 'monitoring_ujian.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Monitoring Ujian';",
                $content
            );
        } elseif (strpos($file, 'import_soal.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Import Soal';",
                $content
            );
        } elseif (strpos($file, 'export_soal.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Export Soal';",
                $content
            );
        } elseif (strpos($file, 'cetak_kartu.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Cetak Kartu Ujian';",
                $content
            );
        } elseif (strpos($file, 'analisis_nilai.php') !== false) {
            $content = str_replace(
                "\$page_title = 'Manajemen ' . ucfirst(str_replace(['admin/', '.php'], '', basename(\$file)));",
                "\$page_title = 'Analisis Nilai Ujian';",
                $content
            );
        }
        
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    }
}

echo "All files have been fixed!\n";