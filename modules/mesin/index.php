<?php
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Data Mesin';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $stmt = $conn->prepare("INSERT INTO mesin (nama_mesin, keterangan) VALUES (?,?)");
    $stmt->bind_param("ss", $_POST['nama_mesin'], $_POST['keterangan']);
    $stmt->execute();
    flash('success','Mesin ditambahkan'); redirect('index.php');
}
if (isset($_GET['hapus'])) {
    $id=(int)$_GET['hapus'];
    $conn->query("DELETE FROM mesin WHERE id_mesin=$id");
    flash('success','Mesin dihapus'); redirect('index.php');
}
$data = $conn->query("SELECT * FROM mesin ORDER BY nama_mesin");
include '../../includes/header.php';
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-1">⚙️ Data Mesin</h2>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="post" class="row g-3 mb-4">
                <div class="col-md-5"><input type="text" name="nama_mesin" class="form-control" placeholder="Nama Mesin" required></div>
                <div class="col-md-5"><input type="text" name="keterangan" class="form-control" placeholder="Keterangan"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100 fw-bold">Tambah</button></div>
            </form>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>#</th><th>Nama Mesin</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $i=1; while($r=$data->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['nama_mesin']) ?></td>
                        <td><?= htmlspecialchars($r['keterangan']) ?></td>
                        <td><a href="?hapus=<?= $r['id_mesin'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>