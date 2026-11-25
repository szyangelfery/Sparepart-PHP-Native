<?php
// Bisa dipanggil via: php modules/backup/auto_backup.php
// Atau via cron di server

require_once __DIR__ . '/../../config/database.php';

$backupDir = __DIR__ . '/../../backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$timestamp = date('Y-m-d_H-i-s');
$filename  = "backup_sparepart_{$timestamp}.sql";
$filepath  = $backupDir . $filename;

// Path mysqldump — sesuaikan dengan environment kamu
// Windows XAMPP: C:\\xampp\\mysql\\bin\\mysqldump.exe
// Linux: /usr/bin/mysqldump
$mysqldump = (PHP_OS_FAMILY === 'Windows') 
    ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' 
    : '/usr/bin/mysqldump';

$cmd = sprintf(
    '"%s" --user=%s --password=%s --host=%s --databases %s --routines --events > "%s" 2>&1',
    $mysqldump, DB_USER, DB_PASS, DB_HOST, DB_NAME, $filepath
);

exec($cmd, $output, $return);

if ($return === 0 && file_exists($filepath) && filesize($filepath) > 0) {
    $ukuran = filesize($filepath);
    $stmt = $conn->prepare("INSERT INTO backup_log (filename, ukuran, status, created_at) VALUES (?,?,?,NOW())");
    $status = 'success';
    $stmt->bind_param("sis", $filename, $ukuran, $status);
    $stmt->execute();
    echo "[OK] Backup berhasil: $filename (" . round($ukuran/1024, 1) . " KB)\n";
} else {
    $status = 'failed';
    $ukuran = 0;
    $stmt = $conn->prepare("INSERT INTO backup_log (filename, ukuran, status) VALUES (?,?,?)");
    $stmt->bind_param("sis", $filename, $ukuran, $status);
    $stmt->execute();
    echo "[FAIL] Backup gagal. Output: " . implode("\n", $output) . "\n";
}

// Hapus backup lebih dari 30 hari (opsional)
foreach (glob($backupDir . 'backup_sparepart_*.sql') as $f) {
    if (filemtime($f) < strtotime('-30 days')) {
        unlink($f);
        echo "[CLEANUP] Hapus backup lama: " . basename($f) . "\n";
    }
}