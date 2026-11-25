<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Barang Keluar';

// Ambil data
$sparepart = $conn->query("SELECT * FROM data_sparepart WHERE deleted_at IS NULL AND stok > 0 ORDER BY part_number");
$supplier  = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");
$mesin     = $conn->query("SELECT * FROM mesin ORDER BY nama_mesin");
$area      = $conn->query("SELECT * FROM area ORDER BY nama_area");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $res = insertTransaksi($conn, [
        'id_sparepart' => (int)$_POST['id_sparepart'],
        'keluar'       => (int)$_POST['qty'],
        'id_supplier'  => $_POST['id_supplier'] ?: null,
        'no_po'        => trim($_POST['no_po']),
        'notes'        => trim($_POST['notes']),
        'id_mesin'     => $_POST['id_mesin'] ?: null,
        'id_area'      => $_POST['id_area'] ?: null,
        'user_peminta' => trim($_POST['user_peminta']),
    ]);
    if ($res['ok']) {
        flash('success', "Barang keluar tercatat. Balance baru: {$res['balance']}");
        redirect('index.php');
    }
    flash('danger', $res['msg']);
}
include '../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-danger">📤 Barang Keluar</h2>
            <p class="text-muted mb-0">Catat pengurangan stok sparepart (pemakaian / penjualan / retur)</p>
        </div>
    </div>

    <!-- Card Form -->
    <div class="card border-0 shadow-sm rounded-4" style="max-width: 800px;">
        <div class="card-body p-4">
            <form method="post">
                
                <!-- Pilih Sparepart -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Sparepart <span class="text-danger">*</span></label>
                    <select class="form-select select2" name="id_sparepart" required>
                        <option value="">-- Cari & Pilih Sparepart --</option>
                        <?php while ($s = $sparepart->fetch_assoc()): ?>
                            <option value="<?= $s['id_sparepart'] ?>">
                                <?= htmlspecialchars($s['part_number'].' - '.$s['nama_sparepart']) ?> (Stok: <?= $s['stok'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mesin (Optional)</label>
                        <select name="id_mesin" class="form-select select2">
                            <option value="">- Pilih Mesin -</option>
                            <?php while ($m = $mesin->fetch_assoc()): ?>
                                <option value="<?= $m['id_mesin'] ?>"><?= htmlspecialchars($m['nama_mesin']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">User (Peminta) <span class="text-danger">*</span></label>
                        <input type="text" name="user_peminta" class="form-control" placeholder="Nama peminta" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Area / Gudang <span class="text-danger">*</span></label>
                        <select name="id_area" class="form-select select2" required>
                            <option value="">- Pilih Area -</option>
                            <?php while ($a = $area->fetch_assoc()): ?>
                                <option value="<?= $a['id_area'] ?>"><?= htmlspecialchars($a['nama_area']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Qty Keluar -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Qty Keluar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white"><i class="bi bi-dash-circle"></i></span>
                            <input type="number" name="qty" class="form-control" min="1" placeholder="0" required>
                        </div>
                    </div>

                    <!-- Supplier (Keperluan) -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Keperluan</label>
                        <input type="text" name="cth" class="form-control bg-light" placeholder="Barang Keluar" readonly>
                        <input type="hidden" name="id_supplier" value="3">
                    </div>
                </div>

                <!-- No PO & Notes -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">No. PO / Referensi</label>
                        <input type="text" name="no_po" class="form-control" placeholder="Cth: SJ-2023-001 / Retur-001 (opsional)">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">Tujuan / Keterangan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Cth: Dipakai untuk servis rutin, dll..."></textarea>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php" class="btn btn-light fw-bold">Batal</a>
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="bi bi-box-arrow-up"></i> Simpan Barang Keluar
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>