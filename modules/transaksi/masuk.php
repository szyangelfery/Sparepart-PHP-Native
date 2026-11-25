<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Barang Masuk';

// Ambil data untuk dropdown
$sparepart = $conn->query("SELECT * FROM data_sparepart WHERE deleted_at IS NULL ORDER BY part_number");
$supplier  = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $res = insertTransaksi($conn, [
        'id_sparepart' => (int)$_POST['id_sparepart'],
        'masuk'        => (int)$_POST['qty'],
        'id_supplier'  => $_POST['id_supplier'] ?: null,
        'no_po'        => trim($_POST['no_po']),
        'harga'        => $detail['harga'],
        'notes'        => trim($_POST['notes']),
    ]);
    if ($res['ok']) {
        flash('success', "Barang masuk tercatat. Balance baru: {$res['balance']}");
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
            <h2 class="fw-bold mb-1">📥 Barang Masuk</h2>
            <p class="text-muted mb-0">Catat penambahan stok sparepart dari supplier</p>
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
                    <!-- Qty Masuk -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Qty Masuk <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-plus-circle"></i></span>
                            <input type="number" name="qty" class="form-control" min="1" placeholder="0" required>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Supplier</label>
                        <select class="form-select select2" name="id_supplier">
                            <option value="">-- Pilih Supplier (Opsional) --</option>
                            <?php while ($s = $supplier->fetch_assoc()): ?>
                                <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- No PO & Notes -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">No PO</label>
                        <input type="text" name="no_po" class="form-control" placeholder="Cth: PO-2023-001 (opsional)">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">Keterangan / Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika perlu..."></textarea>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="index.php" class="btn btn-light fw-bold">Batal</a>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="bi bi-check-circle"></i> Simpan Barang Masuk
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>