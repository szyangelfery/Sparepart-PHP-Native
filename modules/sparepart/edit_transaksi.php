<?php
require_once '../../includes/auth.php';
requireLogin();

$id_transaksi = (int)($_GET['id'] ?? 0);
$id_sparepart = (int)($_GET['id_sparepart'] ?? 0);

$trx = $conn->query("SELECT * FROM history_transaksi WHERE id_transaksi = $id_transaksi")->fetch_assoc();
if (!$trx) { flash('danger', 'Data tidak ditemukan'); redirect("stock_card.php?id=$id_sparepart"); }

// Tentukan tipe transaksi dari data yang ada
$isMasuk = ($trx['masuk'] > 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil nilai dari form, pastikan hanya mengambil yang diizinkan
    $qty_masuk  = $isMasuk ? (int)$_POST['masuk'] : 0;
    $qty_keluar = !$isMasuk ? (int)$_POST['keluar'] : 0;
    
    // Harga hanya relevan untuk transaksi masuk (harga beli)
    $harga      = $isMasuk ? (float)$_POST['harga'] : 0;
    $notes      = trim($_POST['notes']);

    if ($qty_masuk < 0 || $qty_keluar < 0) {
        flash('danger', 'Qty tidak boleh negatif!');
    } else {
        // Update data
        $stmt = $conn->prepare("UPDATE history_transaksi SET masuk=?, keluar=?, harga=?, notes=? WHERE id_transaksi=?");
        $stmt->bind_param("iidsi", $qty_masuk, $qty_keluar, $harga, $notes, $id_transaksi);
        
        if ($stmt->execute()) {
            // RE-CALCULATE BALANCE (Hitung ulang saldo stok)
            $sisa_trx = $conn->query("SELECT id_transaksi, masuk, keluar FROM history_transaksi WHERE id_sparepart = $id_sparepart ORDER BY post_date ASC, id_transaksi ASC");
            $balance = 0;
            while ($r = $sisa_trx->fetch_assoc()) {
                $balance = $balance + $r['masuk'] - $r['keluar'];
                $conn->query("UPDATE history_transaksi SET balance = $balance WHERE id_transaksi = {$r['id_transaksi']}");
            }
            // Update master stok
            $conn->query("UPDATE data_sparepart SET stok = $balance WHERE id_sparepart = $id_sparepart");

            logActivity($conn, 'UPDATE', 'history_transaksi', $id_transaksi, "Edit transaksi: " . ($isMasuk ? "Masuk" : "Keluar"));
            flash('success', 'Transaksi berhasil diperbarui.');
            redirect("stock_card.php?id=$id_sparepart");
        }
    }
}

$pageTitle = 'Edit Transaksi';
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">✏️ Edit Transaksi</h5>
                        <span class="badge <?= $isMasuk ? 'bg-success' : 'bg-danger' ?>">
                            <?= $isMasuk ? 'Barang Masuk' : 'Barang Keluar' ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        
                        <?php if ($isMasuk): ?>
                            <!-- FORM UNTUK BARANG MASUK -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Qty Masuk</label>
                                <input type="number" name="masuk" class="form-control" value="<?= $trx['masuk'] ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Harga Satuan (Rp)</label>
                                <input type="number" name="harga" class="form-control" value="<?= $trx['harga'] ?>" min="0" step="100">
                            </div>
                            <!-- Hidden untuk Keluar -->
                            <input type="hidden" name="keluar" value="0">
                        <?php else: ?>
                            <!-- FORM UNTUK BARANG KELUAR -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Qty Keluar</label>
                                <input type="number" name="keluar" class="form-control" value="<?= $trx['keluar'] ?>" min="1" required>
                            </div>
                            <!-- Hidden untuk Masuk & Harga -->
                            <input type="hidden" name="masuk" value="0">
                            <input type="hidden" name="harga" value="0">
                        <?php endif; ?>

                        <div class="mb-4 mt-3">
                            <label class="form-label fw-bold">Keterangan / Catatan</label>
                            <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($trx['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary fw-bold">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                            <a href="stock_card.php?id=<?= $id_sparepart ?>" class="btn btn-light fw-bold">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>