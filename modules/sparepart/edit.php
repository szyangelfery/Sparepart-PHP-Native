<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Edit Sparepart';
$id = (int)($_GET['id'] ?? 0);
$data = $conn->query("SELECT * FROM data_sparepart WHERE id_sparepart=$id AND deleted_at IS NULL")->fetch_assoc();
if (!$data) { flash('danger','Data tidak ditemukan'); redirect('index.php'); }
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part_number = trim($_POST['part_number']);
    $nama        = trim($_POST['nama_sparepart']);
    $id_kategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
    $harga       = !empty($_POST['harga']) ? (float)$_POST['harga'] : 0;
    $min_stok    = (int)$_POST['min_stok'];
    $max_stok    = (int)$_POST['max_stok'];

    // PERBAIKAN: bind_param pakai "ssiidii" tapi valuenya dipastikan tidak null/kosong
    $stmt = $conn->prepare("UPDATE data_sparepart SET 
        part_number=?, nama_sparepart=?, id_kategori=?, harga=?, min_stok=?, max_stok=?
        WHERE id_sparepart=?");
    
    // Gunakan i untuk int, s untuk string, d untuk double/float
    $stmt->bind_param("ssiidii", $part_number, $nama, $id_kategori, $harga, $min_stok, $max_stok, $id);
    
    if ($stmt->execute()) {
        logActivity($conn, 'UPDATE', 'data_sparepart', $id, "Edit sparepart: $part_number");
        flash('success','Sparepart berhasil diupdate.');
        redirect('index.php');
    } else {
        flash('danger','Gagal update: ' . $conn->error);
    }
}

include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-1">✏️ Edit Sparepart</h2>
        <a href="index.php" class="btn btn-light fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <form method="post">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Part Number -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Part Number <span class="text-danger">*</span></label>
                        <input type="text" name="part_number" class="form-control" value="<?= htmlspecialchars($data['part_number']) ?>" required>
                    </div>
                    <!-- Nama Sparepart -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Nama Sparepart <span class="text-danger">*</span></label>
                        <input type="text" name="nama_sparepart" class="form-control" value="<?= htmlspecialchars($data['nama_sparepart']) ?>" required>
                    </div>

                    <!-- Kategori (Select2) -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">Kategori</label>
                        <select name="id_kategori" class="form-select select2">
                            <option value="">- Pilih Kategori -</option>
                            <?php while ($k = $kategori->fetch_assoc()): ?>
                                <option value="<?= $k['id_kategori'] ?>" <?= $k['id_kategori']==$data['id_kategori']?'selected':'' ?>>
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Harga -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-dark">Harga Jual (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" step="100" name="harga" class="form-control" value="<?= (int)$data['harga'] ?>">
                        </div>
                        <small class="text-muted">Gunakan angka tanpa titik/koma.</small>
                    </div>

                    <!-- Stok Minimal & Maksimal -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Stok Minimal</label>
                        <input type="number" name="min_stok" class="form-control" value="<?= $data['min_stok'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Stok Maksimal</label>
                        <input type="number" name="max_stok" class="form-control" value="<?= $data['max_stok'] ?>">
                    </div>
                </div>

                <div class="alert alert-info border-0 mt-4 mb-0 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-5 me-3"></i>
                    <div>
                        Stok saat ini: <b><?= $data['stok'] ?> pcs</b>. Stok tidak bisa diubah di sini, harus melalui transaksi <b>Barang Masuk</b> atau <b>Barang Keluar</b> agar tercatat di Kartu Stok.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-light btn-lg fw-bold px-4">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg fw-bold px-4"><i class="bi bi-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>