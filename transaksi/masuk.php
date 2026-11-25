<?php
require '../config/database.php';
require '../includes/auth.php';
require '../includes/functions.php';
cekLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sparepart = intval($_POST['id_sparepart']);
    $masuk        = intval($_POST['masuk']);
    $id_supplier  = intval($_POST['id_supplier']) ?: null;
    $no_po        = sanitize($conn, $_POST['no_po'] ?? '');
    $notes        = sanitize($conn, $_POST['notes'] ?? '');
    $editor       = $_SESSION['username'];

    $conn->begin_transaction();
    try {
        // Ambil stok sekarang
        $r = $conn->query("SELECT stok FROM data_sparepart WHERE id_sparepart=$id_sparepart FOR UPDATE")->fetch_assoc();
        $balance = $r['stok'] + $masuk;

        // Insert history
        $stmt = $conn->prepare("INSERT INTO history_transaksi 
            (id_sparepart, id_supplier, no_po, masuk, keluar, balance, notes, editor)
            VALUES (?,?,?,?,0,?,?,?)");
        $stmt->bind_param("iisiiss", $id_sparepart, $id_supplier, $no_po, $masuk, $balance, $notes, $editor);
        $stmt->execute();

        // Update stok
        $conn->query("UPDATE data_sparepart SET stok=$balance WHERE id_sparepart=$id_sparepart");

        logActivity($conn, 'create', 'history_transaksi', $conn->insert_id, "Stok masuk $masuk");
        $conn->commit();
        header('Location: index.php?msg=sukses');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal: " . $e->getMessage();
    }
}

$spareparts = $conn->query("SELECT id_sparepart, part_number, nama_sparepart, stok FROM data_sparepart WHERE deleted_at IS NULL ORDER BY nama_sparepart");
$suppliers  = $conn->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transaksi Masuk - RANGKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-4" style="max-width:700px">
    <h2>Barang Masuk</h2>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Sparepart</label>
            <select name="id_sparepart" class="form-select" required>
                <option value="">-- pilih --</option>
                <?php while($s = $spareparts->fetch_assoc()): ?>
                    <option value="<?= $s['id_sparepart'] ?>">
                        <?= $s['part_number'] ?> - <?= $s['nama_sparepart'] ?> (stok: <?= $s['stok'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Jumlah Masuk</label>
            <input type="number" name="masuk" min="1" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Supplier</label>
            <select name="id_supplier" class="form-select">
                <option value="">-- opsional --</option>
                <?php while($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $s['id_supplier'] ?>"><?= $s['nama_supplier'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>No. PO</label>
            <input type="text" name="no_po" class="form-control">
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Simpan Transaksi</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>