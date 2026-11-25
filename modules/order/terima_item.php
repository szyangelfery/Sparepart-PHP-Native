<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Terima Item PO';
$id_detail = (int)($_GET['id_detail'] ?? 0);

$detail = $conn->query("
    SELECT d.*, o.no_po, o.no_invoice, o.id_order, s.nama_sparepart, s.part_number 
    FROM order_detail d 
    JOIN orders o ON o.id_order = d.id_order 
    JOIN data_sparepart s ON s.id_sparepart = d.id_sparepart
    WHERE d.id_detail = $id_detail
")->fetch_assoc();

if (!$detail) { flash('danger', 'Item tidak ditemukan.'); redirect('index.php'); }

$sisa = $detail['qty'] - $detail['qty_diterima'];
$supplier = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty_terima   = (int)$_POST['qty_terima'];
    $id_supplier  = (int)$_POST['id_supplier'];
    $notes        = trim($_POST['notes']);
    
    // HARGA & PPN (Bisa 0 kalau ga diisi)
    $harga_total  = !empty($_POST['harga_total']) ? (float)$_POST['harga_total'] : 0; 
    $ppn_persen   = isset($_POST['ppn_persen']) ? (int)$_POST['ppn_persen'] : 0;    
    $ppn_nominal  = (float)$_POST['ppn_nominal']; 

    // Upload Gambar (Opsional)
    $nama_file = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        // Validasi ekstensi gambar
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $nama_file = 'bukti_' . time() . '_' . rand(100,999) . '.' . $ext;
            $tujuan = '../../uploads/' . $nama_file;
            if (!is_dir('../../uploads')) mkdir('../../uploads', 0777, true);
            move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan);
        }
    }

    if ($qty_terima <= 0 || $qty_terima > $sisa) {
        flash('danger', "Qty tidak valid. Sisa: $sisa");
    } elseif (!$id_supplier) {
        flash('danger', 'Supplier wajib diisi!');
    } else {
        // 1. Insert ke Kartu Stok
        $res = insertTransaksi($conn, [
            'id_sparepart' => $detail['id_sparepart'],
            'masuk'        => $qty_terima,
            'id_supplier'  => $id_supplier,
            'no_po'        => $detail['no_invoice'] ?: $detail['no_po'], 
            'harga'        => $harga_total, 
            'ppn_persen'   => $ppn_persen,
            'ppn_nominal'  => $ppn_nominal,
            'notes'        => "Penerimaan PO {$detail['no_po']} " . ($notes ? "($notes)" : ""),
            'gambar'       => $nama_file
        ]);

        if ($res['ok']) {
            // 2. Update qty_diterima & status detail
            $new_qty_terima = $detail['qty_diterima'] + $qty_terima;
            $new_status_detail = ($new_qty_terima >= $detail['qty']) ? 'received' : 'received_partial';
            
            $conn->query("UPDATE order_detail SET qty_diterima = $new_qty_terima, status = '$new_status_detail', id_supplier = $id_supplier WHERE id_detail = $id_detail");

            // 3. Auto update status PO
            updateStatusPO($conn, $detail['id_order']);

            flash('success', "Berhasil menerima $qty_terima unit.");
            redirect('index.php');
        } else {
            flash('danger', $res['msg']);
        }
    }
}
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="mb-0 fw-bold">📦 Terima Item</h5>
                    <p class="text-muted mb-0 mt-1">PO: <b><?= htmlspecialchars($detail['no_po']) ?></b> | Invoice: <b><?= htmlspecialchars($detail['no_invoice'] ?? '-') ?></b></p>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                        <div class="fw-bold fs-6"><?= htmlspecialchars($detail['part_number']) ?> - <?= htmlspecialchars($detail['nama_sparepart']) ?></div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Qty Dipesan: <b><?= $detail['qty'] ?></b></span>
                            <span>Sudah Datang: <b><?= $detail['qty_diterima'] ?></b></span>
                            <span class="text-danger fw-bold">Sisa: <?= $sisa ?></span>
                        </div>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <!-- QTY (Wajib) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Qty Yang Datang <span class="text-danger">*</span></label>
                                <input type="number" name="qty_terima" class="form-control" min="1" max="<?= $sisa ?>" value="<?= $sisa ?>" required>
                            </div>
                            
                            <!-- SUPPLIER (Wajib) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Supplier Pengirim <span class="text-danger">*</span></label>
                                <select name="id_supplier" class="form-select select2" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    <?php while ($s = $supplier->fetch_assoc()): ?>
                                        <option value="<?= $s['id_supplier'] ?>" <?= $s['id_supplier'] == $detail['id_supplier'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_supplier']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- HARGA & PPN (Opsional) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Harga Satuan (Rp)</label>
                                <input type="number" name="harga_total" id="harga_total" class="form-control" value="<?= $detail['harga'] ?>" oninput="hitungPPN()">
                                <small class="text-muted">Kosongkan jika gratis/bonus</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PPN</label>
                                <select name="ppn_persen" id="ppn_persen" class="form-select" onchange="hitungPPN()">
                                    <option value="0">0 %</option>
                                    <option value="11">11 %</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Total (Setelah PPN)</label>
                                <input type="text" id="grand_total_text" class="form-control bg-light" readonly>
                                <input type="hidden" name="ppn_nominal" id="ppn_nominal" value="0">
                            </div>

                            <!-- KETERANGAN (Opsional) -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Keterangan (Opsional)</label>
                                <input type="text" name="notes" class="form-control" placeholder="Catatan tambahan...">
                            </div>

                            <!-- UPLOAD GAMBAR (Opsional) -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Upload Bukti Terima (Opsional)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*" capture="environment">
                                <small class="text-muted">Bisa langsung dari kamera HP atau pilih dari galeri.</small>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">Konfirmasi Terima</button>
                            <a href="index.php" class="btn btn-light btn-lg fw-bold">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function hitungPPN() {
    const harga = parseFloat(document.getElementById('harga_total').value) || 0;
    const ppnPersen = parseInt(document.getElementById('ppn_persen').value) || 0;
    
    const nominalPPN = harga * (ppnPersen / 100);
    const grandTotal = harga + nominalPPN;

    document.getElementById('ppn_nominal').value = nominalPPN;
    document.getElementById('grand_total_text').value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(grandTotal);
}
// Panggil pertama kali
hitungPPN();
</script>

<?php include '../../includes/footer.php'; ?>