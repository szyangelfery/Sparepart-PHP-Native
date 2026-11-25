<?php
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Data Supplier';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $stmt = $conn->prepare("INSERT INTO supplier (nama_supplier, kontak, alamat) VALUES (?,?,?)");
    $stmt->bind_param("sss", $_POST['nama_supplier'], $_POST['kontak'], $_POST['alamat']);
    $stmt->execute();
    flash('success', 'Supplier berhasil ditambahkan!');
    redirect('index.php');
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM supplier WHERE id_supplier=$id");
    flash('success', 'Supplier dihapus.');
    redirect('index.php');
}

$data = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="page-title mb-0">🏭 Data Supplier</h1>
</div>

<!-- Form Tambah Supplier -->
<div class="card-panel mb-4 border-0 shadow-sm" style="border-radius: 16px;">
    <div class="card-panel-header bg-light" style="border-radius: 16px 16px 0 0;">
        <h5 class="mb-0 fw-bold">➕ Tambah Supplier Baru</h5>
    </div>
    <div class="card-panel-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                <input type="text" name="nama_supplier" class="form-control" required placeholder="Cth: PT Astra Otoparts">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kontak / Telp</label>
                <input type="text" name="kontak" class="form-control" placeholder="Cth: 021-1234567">
            </div>
            <div class="col-md-3">
                <label class="form-label">Alamat</label>
                <input type="text" name="alamat" class="form-control" placeholder="Alamat lengkap">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="tambah" class="btn-app primary w-100">➕ Tambah</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Supplier -->
<div class="card-panel border-0 shadow-sm" style="border-radius: 16px;">
    <div class="card-panel-body p-0">
        <div class="table-responsive">
            <table class="data-table stack-mobile">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Supplier</th>
                        <th>Kontak</th>
                        <th>Alamat</th>
                        <th width="100" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while ($r = $data->fetch_assoc()): ?>
                    <tr>
                        <td data-label="#"><?= $i++ ?></td>
                        <td data-label="Nama"><b><?= htmlspecialchars($r['nama_supplier']) ?></b></td>
                        <td data-label="Kontak"><?= htmlspecialchars($r['kontak'] ?: '-') ?></td>
                        <td data-label="Alamat"><?= htmlspecialchars($r['alamat'] ?: '-') ?></td>
                        <td data-label="Aksi" class="text-center">
                            <a href="?hapus=<?= $r['id_supplier'] ?>" class="btn-app sm danger" onclick="return confirm('Yakin hapus supplier ini?')">🗑 Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if($data->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data supplier.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>