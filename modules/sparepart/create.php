<?php
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Tambah Sparepart';

$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part_number = trim($_POST['part_number']);
    $nama        = trim($_POST['nama_sparepart']);
    $id_kategori = (int)$_POST['id_kategori'];
    $harga       = (float)$_POST['harga'];
    $min_stok    = (int)$_POST['min_stok'];
    $max_stok    = (int)$_POST['max_stok'];
    $stok_awal   = (int)$_POST['stok_awal'];

    $stmt = $conn->prepare("INSERT INTO data_sparepart (part_number, nama_sparepart, stok, id_kategori, harga, min_stok, max_stok) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssiidii", $part_number, $nama, $stok_awal, $id_kategori, $harga, $min_stok, $max_stok);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        if ($stok_awal > 0) {
            insertTransaksi($conn, ['id_sparepart' => $id, 'masuk' => $stok_awal, 'notes' => 'Stok awal']);
        }
        logActivity($conn, 'CREATE', 'data_sparepart', $id, "Tambah sparepart: $part_number");
        flash('success', 'Sparepart berhasil ditambahkan!');
        redirect('index.php');
    } else {
        flash('danger', 'Gagal: ' . $conn->error);
    }
}

include '../../includes/header.php';
?>

<div class="form-card mt-4 mb-5">
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
        <h3 class="mb-0 fw-bold">➕ Tambah Sparepart</h3>
        <a href="index.php" class="btn-app sm ms-auto">Kembali</a>
    </div>

    <form method="post">
        <div class="row g-3">
            <!-- Part Number & Nama -->
            <div class="col-md-6">
                <label class="form-label">Part Number <span class="text-danger">*</span></label>
                <input type="text" name="part_number" class="form-control" required placeholder="Cth: YMH-001">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama Sparepart <span class="text-danger">*</span></label>
                <input type="text" name="nama_sparepart" class="form-control" required placeholder="Cth: Kampas Rem">
            </div>

            <!-- Kategori (Select2) -->
            <div class="col-md-12">
                <label class="form-label">Kategori</label>
                <select name="id_kategori" class="form-select select2">
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($k = $kategori->fetch_assoc()): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Harga -->
            <div class="col-md-12">
                <label class="form-label">Harga (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">Rp</span>
                    <input type="number" step="0.01" name="harga" class="form-control" value="">
                </div>
            </div>

            <!-- Stok -->
            <div class="col-md-4">
                <label class="form-label">Stok Awal</label>
                <input type="number" name="stok_awal" class="form-control" value="0">
                <small class="text-muted">Akan dicatat sebagai transaksi masuk</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Stok Minimal</label>
                <input type="number" name="min_stok" class="form-control" value="5">
            </div>
            <div class="col-md-4">
                <label class="form-label">Stok Maksimal</label>
                <input type="number" name="max_stok" class="form-control" value="100">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="index.php" class="btn-app">Batal</a>
            <button type="submit" class="btn-app primary px-4">💾 Simpan Data</button>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>