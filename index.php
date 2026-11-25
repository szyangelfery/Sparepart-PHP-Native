<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';

$total_part     = $conn->query("SELECT COUNT(*) c FROM data_sparepart WHERE deleted_at IS NULL")->fetch_assoc()['c'];
$total_stok     = $conn->query("SELECT COALESCE(SUM(stok),0) s FROM data_sparepart WHERE deleted_at IS NULL")->fetch_assoc()['s'];
$stok_minim     = $conn->query("SELECT COUNT(*) c FROM data_sparepart WHERE stok <= min_stok AND deleted_at IS NULL")->fetch_assoc()['c'];
$total_kategori = $conn->query("SELECT COUNT(*) c FROM kategori")->fetch_assoc()['c'];

$stokMenipis = $conn->query("
    SELECT s.*, k.nama_kategori FROM data_sparepart s
    LEFT JOIN kategori k ON k.id_kategori = s.id_kategori
    WHERE s.stok <= s.min_stok AND s.deleted_at IS NULL
    ORDER BY s.stok ASC LIMIT 10
");

$trxTerbaru = $conn->query("
    SELECT h.*, s.part_number, s.nama_sparepart
    FROM history_transaksi h
    JOIN data_sparepart s ON s.id_sparepart = h.id_sparepart
    ORDER BY h.id_transaksi DESC LIMIT 8
");

include __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">👋 Selamat datang, <?= htmlspecialchars(currentUser()) ?></h1>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Sparepart</div>
        <div class="stat-value"><?= number_format($total_part) ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Stok</div>
        <div class="stat-value"><?= number_format($total_stok) ?></div>
    </div>
    <div class="stat-card warn">
        <div class="stat-label">Stok Menipis</div>
        <div class="stat-value"><?= number_format($stok_minim) ?></div>
    </div>
    <div class="stat-card info">
        <div class="stat-label">Kategori</div>
        <div class="stat-value"><?= number_format($total_kategori) ?></div>
    </div>
</div>

<!-- STOK MENIPIS -->
<div class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title">⚠️ Stok Menipis</h2>
        <a href="<?= BASE_URL ?>modules/sparepart/index.php" class="btn-app sm primary">Lihat Semua</a>
    </div>
    <div class="card-panel-body p-0">
        <?php if ($stokMenipis->num_rows === 0): ?>
            <div class="empty-state">
                <div class="icon">✅</div>
                <div>Semua stok aman</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table stack-mobile">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Min</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($r = $stokMenipis->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Part No"><b><?= htmlspecialchars($r['part_number']) ?></b></td>
                            <td data-label="Nama"><?= htmlspecialchars($r['nama_sparepart']) ?></td>
                            <td data-label="Kategori"><?= htmlspecialchars($r['nama_kategori'] ?? '-') ?></td>
                            <td data-label="Stok"><span class="badge-pill danger"><?= $r['stok'] ?></span></td>
                            <td data-label="Min"><?= $r['min_stok'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TRANSAKSI TERBARU -->
<div class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title">🔄 Transaksi Terbaru</h2>
        <a href="<?= BASE_URL ?>modules/transaksi/index.php" class="btn-app sm primary">Lihat Semua</a>
    </div>
    <div class="card-panel-body p-0">
        <?php if ($trxTerbaru->num_rows === 0): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <div>Belum ada transaksi</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table stack-mobile">
                    <thead>
                        <tr><th>Tanggal</th><th>Part No</th><th>Nama</th><th>Masuk</th><th>Keluar</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($t = $trxTerbaru->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Tanggal"><?= date('d/m H:i', strtotime($t['post_date'])) ?></td>
                            <td data-label="Part No"><b><?= htmlspecialchars($t['part_number']) ?></b></td>
                            <td data-label="Nama"><?= htmlspecialchars($t['nama_sparepart']) ?></td>
                            <td data-label="Masuk"><?= $t['masuk'] ? '<span class="badge-pill success">+' . $t['masuk'] . '</span>' : '-' ?></td>
                            <td data-label="Keluar"><?= $t['keluar'] ? '<span class="badge-pill danger">-' . $t['keluar'] . '</span>' : '-' ?></td>
                            <td data-label="Balance"><b><?= $t['balance'] ?></b></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FAB mobile untuk tambah cepat -->
<a href="<?= BASE_URL ?>modules/transaksi/masuk.php" class="fab mobile-only" title="Tambah Barang Masuk">＋</a>

<?php include __DIR__ . '/includes/footer.php'; ?>