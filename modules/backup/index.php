<?php
require_once '../../includes/auth.php';
requireLogin();

// Jalankan backup manual
if (isset($_POST['backup_now'])) {
    exec('php ' . escapeshellarg(__DIR__ . '/auto_backup.php') . ' 2>&1', $out, $ret);
    flash($ret===0?'success':'danger', 'Backup selesai. ' . implode(' ', $out));
    redirect('index.php');
}

$logs = $conn->query("SELECT * FROM backup_log ORDER BY id DESC LIMIT 50");
$files = glob(__DIR__ . '/../../backups/*.sql');
usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
include '../../includes/header.php';
?>
<div class="page-header">
    <h1>💾 Backup</h1>
    <form method="post"><button name="backup_now" class="btn btn-primary">🔄 Backup Sekarang</button></form>
</div>

<h2>File Backup (<?= count($files) ?>)</h2>
<table class="table">
    <thead><tr><th>File</th><th>Ukuran</th><th>Tanggal</th><th>Download</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($files,0,20) as $f): ?>
        <tr>
            <td><?= basename($f) ?></td>
            <td><?= round(filesize($f)/1024, 1) ?> KB</td>
            <td><?= date('d/m/Y H:i', filemtime($f)) ?></td>
            <td><a href="../../backups/<?= basename($f) ?>" class="btn btn-sm">⬇ Download</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Log Backup</h2>
<table class="table">
    <thead><tr><th>Waktu</th><th>File</th><th>Ukuran</th><th>Status</th></tr></thead>
    <tbody>
    <?php while ($l=$logs->fetch_assoc()): ?>
        <tr>
            <td><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
            <td><?= htmlspecialchars($l['filename']) ?></td>
            <td><?= round($l['ukuran']/1024, 1) ?> KB</td>
            <td><span class="badge <?= $l['status']==='success'?'badge-success':'badge-danger' ?>"><?= $l['status'] ?></span></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php include '../../includes/footer.php'; ?>