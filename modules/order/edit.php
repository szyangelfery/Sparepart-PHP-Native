<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Order PO';
$id = (int)($_GET['id'] ?? 0);

$order = $conn->query("SELECT * FROM orders WHERE id_order = $id")->fetch_assoc();
if (!$order) { flash('danger', 'Order tidak ditemukan'); redirect('index.php'); }
if ($order['status'] !== 'pending') { flash('danger', 'PO yang sudah diproses tidak bisa diedit!'); redirect('index.php'); }

// Ambil master sparepart untuk dropdown barang BARU
$masterSparepart = $conn->query("SELECT * FROM data_sparepart WHERE deleted_at IS NULL ORDER BY part_number");

// Ambil detail order yang LAMA
$details = $conn->query("
    SELECT d.*, s.part_number, s.nama_sparepart 
    FROM order_detail d 
    JOIN data_sparepart s ON s.id_sparepart = d.id_sparepart 
    WHERE d.id_order = $id
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_po   = trim($_POST['no_po']);
    $tanggal = $_POST['tanggal'];
    
    // 1. Update Header PO
    $stmt = $conn->prepare("UPDATE orders SET no_po=?, tanggal=? WHERE id_order=?");
    $stmt->bind_param("ssi", $no_po, $tanggal, $id);
    $stmt->execute();

    // 2. Update Item LAMA (Qty & Harga)
    if (isset($_POST['id_detail_lama'])) {
        foreach ($_POST['id_detail_lama'] as $id_detail) {
            $qty   = (int)$_POST['qty_lama'][$id_detail];
            $harga = (float)$_POST['harga_lama'][$id_detail];
            $conn->query("UPDATE order_detail SET qty=$qty, harga=$harga WHERE id_detail=$id_detail AND id_order=$id");
        }
    }

    // 3. Hapus Item Lama yang dicentang "Hapus"
    if (isset($_POST['hapus_item'])) {
        foreach ($_POST['hapus_item'] as $id_detail_hapus) {
            $conn->query("DELETE FROM order_detail WHERE id_detail=$id_detail_hapus AND id_order=$id");
        }
    }

    // 4. Insert Item BARU
    if (isset($_POST['sparepart_baru']) && is_array($_POST['sparepart_baru'])) {
        foreach ($_POST['sparepart_baru'] as $index => $id_sp) {
            $qty   = (int)$_POST['qty_baru'][$index];
            $harga = (float)$_POST['harga_baru'][$index];
            if (!empty($id_sp) && $qty > 0) {
                $conn->query("INSERT INTO order_detail (id_order, id_sparepart, qty, harga) VALUES ($id, $id_sp, $qty, $harga)");
            }
        }
    }

    flash('success', 'PO berhasil diperbarui.');
    redirect('index.php');
}

include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">✏️ Edit PO: <?= htmlspecialchars($order['no_po']) ?></h2>
        <a href="index.php" class="btn btn-light fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <form method="post">
        <!-- Header PO -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">No PO Order</label>
                        <input type="text" name="no_po" class="form-control" value="<?= htmlspecialchars($order['no_po']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= $order['tanggal'] ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Item LAMA (Hanya Qty & Harga yang bisa diedit, Nama dikunci) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-4">
                <h5 class="mb-0 fw-bold">Barang Dipesan (Sebelumnya)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sparepart</th>
                            <th width="120">Qty</th>
                            <th width="180">Harga Satuan</th>
                            <th width="80" class="text-center">Hapus?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = $details->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <!-- TAMPILKAN PART NUMBER & NAMA (Hanya Teks, Nggak Bisa Diedit) -->
                                <div class="fw-bold text-primary"><?= htmlspecialchars($d['part_number']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($d['nama_sparepart']) ?></small>
                                
                                <!-- Hidden ID -->
                                <input type="hidden" name="id_detail_lama[]" value="<?= $d['id_detail'] ?>">
                            </td>
                            <td>
                                <input type="number" name="qty_lama[<?= $d['id_detail'] ?>]" class="form-control" value="<?= $d['qty'] ?>" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="harga_lama[<?= $d['id_detail'] ?>]" class="form-control" value="<?= $d['harga'] ?>" min="0" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="hapus_item[]" value="<?= $d['id_detail'] ?>" class="form-check-input border-danger" style="transform: scale(1.5); cursor: pointer;">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($details->num_rows == 0): ?>
                        <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada item lama.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Item BARU (Bisa Tambah & Hapus Baris) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Tambah Barang Baru</h5>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnTambahBaris">
                    <i class="bi bi-plus-lg"></i> Tambah Baris
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sparepart</th>
                            <th width="120">Qty</th>
                            <th width="180">Harga Satuan</th>
                            <th width="80" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBaru">
                        <!-- Baris Baru akan di-generate via JS di sini -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="bi bi-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<!-- Script Hanya untuk Tabel Baru -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbodyBaru = document.getElementById('tbodyBaru');
    const btnTambah = document.getElementById('btnTambahBaris');
    
    // Opsi dari PHP
    const opsiSparepart = `
        <option value="">-- Cari Sparepart --</option>
        <?php 
        $masterSparepart->data_seek(0); 
        while ($s = $masterSparepart->fetch_assoc()): 
        ?>
            <option value="<?= $s['id_sparepart'] ?>" data-harga="<?= $s['harga'] ?>">
                <?= htmlspecialchars($s['part_number'].' - '.$s['nama_sparepart']) ?>
            </option>
        <?php endwhile; ?>
    `;

    function tambahBarisBaru() {
        const tr = document.createElement('tr');
        // Gunakan name array (tanpa index spesifik, biar PHP yang atur)
        tr.innerHTML = `
            <td class="ps-4">
                <select name="sparepart_baru[]" class="form-select select2-barang-baru" required>
                    ${opsiSparepart}
                </select>
            </td>
            <td><input type="number" name="qty_baru[]" class="form-control" value="1" min="1" required></td>
            <td><input type="number" name="harga_baru[]" class="form-control harga-baru-input" value="0" min="0" required></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tbodyBaru.appendChild(tr);
        
        // Panggil Select2 KHUSUS untuk elemen yang baru dibuat ini
        const selectEl = $(tr).find('.select2-barang-baru');
        selectEl.select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Cari Sparepart --' });
        
        // Autofill Harga
        selectEl.on('change', function() {
            const harga = $(this).find(':selected').data('harga') || 0;
            $(tr).find('.harga-baru-input').val(harga);
        });
    }

    // Hapus baris baru
    tbodyBaru.addEventListener('click', function(e) {
        if (e.target.closest('.btn-hapus-baris')) {
            e.target.closest('tr').remove();
        }
    });

    // Klik tambah
    btnTambah.addEventListener('click', tambahBarisBaru);
});
</script>
<?php include '../../includes/footer.php'; ?>