<?php
require_once '../../includes/auth.php';
requireLogin();

$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$id_sp  = (int)($_GET['id_sparepart'] ?? 0);

$where = "WHERE DATE(h.post_date) BETWEEN '$dari' AND '$sampai'";
if ($id_sp) $where .= " AND h.id_sparepart=$id_sp";

$data = $conn->query("
    SELECT h.post_date, s.part_number, s.nama_sparepart, 
           h.masuk, h.keluar, h.balance, h.notes
    FROM history_transaksi h
    JOIN data_sparepart s ON s.id_sparepart=h.id_sparepart
    $where ORDER BY h.id_transaksi DESC
");

$filename = "laporan_transaksi_{$dari}_sd_{$sampai}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // BOM UTF-8 biar karakter Indonesia aman
?>
<table border="1">
    <thead>
        <tr style="background:#4CAF50;color:#fff;font-weight:bold">
            <th>Tanggal</th><th>Part Number</th><th>Nama Sparepart</th>
            <th>Masuk</th><th>Keluar</th><th>Balance</th><th>Notes</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($r=$data->fetch_assoc()): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($r['post_date'])) ?></td>
            <td><?= htmlspecialchars($r['part_number']) ?></td>
            <td><?= htmlspecialchars($r['nama_sparepart']) ?></td>
            <td><?= $r['masuk'] ?></td>
            <td><?= $r['keluar'] ?></td>
            <td><?= $r['balance'] ?></td>
            <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>