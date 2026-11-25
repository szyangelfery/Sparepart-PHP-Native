<?php
require_once 'includes/auth.php';
requireLogin();

$totalSparepart = $conn->query("SELECT COUNT(*) c FROM data_sparepart WHERE deleted_at IS NULL")->fetch_assoc()['c'];
$totalSupplier  = $conn->query("SELECT COUNT(*) c FROM supplier")->fetch_assoc()['c'];
$masukHariIni   = $conn->query("SELECT COALESCE(SUM(masuk),0) c FROM history_transaksi WHERE DATE(post_date)=CURDATE()")->fetch_assoc()['c'];
$keluarHariIni  = $conn->query("SELECT COALESCE(SUM(keluar),0) c FROM history_transaksi WHERE DATE(post_date)=CURDATE()")->fetch_assoc()['c'];

$stokMinimal = $conn->query("
  SELECT s.*, k.nama_kategori FROM data_sparepart s 
  LEFT JOIN kategori k ON k.id_kategori=s.id_kategori
  WHERE s.deleted_at IS NULL AND s.stok <= s.min_stok 
  ORDER BY s.stok ASC LIMIT 10
");

$transaksiTerbaru = $conn->query("
  SELECT h.*, s.nama_sparepart, s.part_number FROM history_transaksi h
  JOIN data_sparepart s ON s.id_sparepart = h.id_sparepart
  ORDER BY h.id_transaksi DESC LIMIT 8
");

include 'includes/header.php';
?>
<h1>📊 Dashboard</h1>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total Sparepart</div><div class="stat-value"><?= $totalSparepart ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Supplier</div><div class="stat-value"><?= $totalSupplier ?></div></div>
    <div class="stat-card green"><div class="stat-label">Masuk Hari Ini</div><div class="stat-value"><?= $masukHariIni ?></div></div>
    <div class="stat-card red"><div class="stat-label">Keluar Hari Ini</div><div class="stat-value"><?= $keluarHariIni ?></div></div>
</div>

<div class="row">
    <div class="col">
        <h2>⚠️ Stok Minimal</h2>
        <table class="table">
            <thead><tr><th>Part No</th><th>Nama</th><th>Stok</th><th>Min</th></tr></thead>
            <tbody>
            <?php while ($r = $stokMinimal->fetch_assoc()): ?>
                <tr class="row-warning">
                    <td><?= htmlspecialchars($r['part_number']) ?></td>
                    <td><?= htmlspecialchars($r['nama_sparepart']) ?></td>
                    <td><span class="badge badge-danger"><?= $r['stok'] ?></span></td>
                    <td><?= $r['min_stok'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="col">
        <h2>🔄 Transaksi Terbaru</h2>
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Sparepart</th><th>Masuk</th><th>Keluar</th><th>Balance</th></tr></thead>
            <tbody>
            <?php while ($r = $transaksiTerbaru->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m H:i', strtotime($r['post_date'])) ?></td>
                    <td><?= htmlspecialchars($r['part_number']) ?></td>
                    <td class="text-green"><?= $r['masuk'] ?: '' ?></td>
                    <td class="text-red"><?= $r['keluar'] ?: '' ?></td>
                    <td><b><?= $r['balance'] ?></b></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>