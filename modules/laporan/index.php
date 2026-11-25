<?php
require_once '../../includes/auth.php';
requireLogin();

$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$id_sp  = (int)($_GET['id_sparepart'] ?? 0);

$where = "WHERE DATE(h.post_date) BETWEEN '$dari' AND '$sampai'";
if ($id_sp) $where .= " AND h.id_sparepart=$id_sp";

// Ambil data transaksi
$data = $conn->query("
    SELECT h.*, s.part_number, s.nama_sparepart 
    FROM history_transaksi h
    JOIN data_sparepart s ON s.id_sparepart=h.id_sparepart
    $where
    ORDER BY h.id_transaksi DESC
");

// Ambil list sparepart untuk dropdown
$sparepart = $conn->query("SELECT * FROM data_sparepart WHERE deleted_at IS NULL ORDER BY part_number");

$pageTitle = 'Laporan Transaksi';
include '../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">📊 Laporan Transaksi</h2>
        <a href="export_excel.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>&id_sparepart=<?= $id_sp ?>" class="btn btn-success fw-bold">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
    </div>

    <!-- Card Filter -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                    <input type="date" name="dari" value="<?= $dari ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" name="sampai" value="<?= $sampai ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Pilih Sparepart</label>
                    <select name="id_sparepart" class="form-select">
                        <option value="">-- Semua Sparepart --</option>
                        <?php while ($s=$sparepart->fetch_assoc()): ?>
                            <option value="<?= $s['id_sparepart'] ?>" <?= $id_sp==$s['id_sparepart']?'selected':'' ?>>
                                <?= htmlspecialchars($s['part_number'].' - '.$s['nama_sparepart']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary fw-bold w-100"><i class="bi bi-funnel"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card Tabel -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th scope="col" class="fw-semibold">Tanggal</th>
                            <th scope="col" class="fw-semibold">Part Number</th>
                            <th scope="col" class="fw-semibold">Nama Sparepart</th>
                            <th scope="col" class="fw-semibold text-center text-success">Masuk</th>
                            <th scope="col" class="fw-semibold text-center text-danger">Keluar</th>
                            <th scope="col" class="fw-semibold text-center">Saldo</th>
                            <th scope="col" class="fw-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $totalMasuk=0; $totalKeluar=0;
                    if ($data->num_rows > 0):
                        while ($r=$data->fetch_assoc()): 
                            $totalMasuk += $r['masuk']; $totalKeluar += $r['keluar'];
                    ?>
                        <tr>
                            <td class="text-nowrap text-muted small"><?= date('d/m/Y H:i', strtotime($r['post_date'])) ?></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($r['part_number']) ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($r['nama_sparepart']) ?></td>
                            <td class="text-center fw-bold text-success"><?= $r['masuk'] ? '+'.$r['masuk'] : '' ?></td>
                            <td class="text-center fw-bold text-danger"><?= $r['keluar'] ? '-'.$r['keluar'] : '' ?></td>
                            <td class="text-center fw-bold bg-light"><?= $r['balance'] ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada transaksi pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                    <?php if ($data->num_rows > 0): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                            <td class="text-center text-success">+<?= $totalMasuk ?></td>
                            <td class="text-center text-danger">-<?= $totalKeluar ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>