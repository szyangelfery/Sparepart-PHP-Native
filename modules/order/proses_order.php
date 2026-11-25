<?php
require_once '../../includes/auth.php';
requireLogin();

$id_order = (int)($_GET['id'] ?? 0);
$order = $conn->query("SELECT * FROM orders WHERE id_order = $id_order")->fetch_assoc();
if (!$order) { flash('danger', 'PO tidak ditemukan'); redirect('index.php'); }

// PROSES SIMPAN NOMOR INVOICE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_invoice = trim($_POST['no_invoice']);
    
    if (empty($no_invoice)) {
        flash('danger', 'Nomor Invoice/PO Wajib diisi!');
    } else {
        // 1. Update No Invoice di tabel orders
        $stmt = $conn->prepare("UPDATE orders SET no_invoice = ? WHERE id_order = ?");
        $stmt->bind_param("si", $no_invoice, $id_order);
        $stmt->execute();

        // 2. Ubah status semua item yang masih draft jadi ordered
        $conn->query("UPDATE order_detail SET status = 'ordered' WHERE id_order = $id_order AND status = 'draft'");
        
        // 3. Update status PO
        updateStatusPO($conn, $id_order);
        
        flash('success', "PO berhasil diproses dengan No Invoice: $no_invoice");
        redirect('index.php');
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="mb-0 fw-bold">🛒 Proses Order ke Supplier</h5>
                    <p class="text-muted mb-0 mt-1">PO: <b><?= htmlspecialchars($order['no_po']) ?></b></p>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nomor PO / Invoice Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="no_invoice" class="form-control" placeholder="Cth: INV/2023/001" required autofocus>
                            <small class="text-muted">Nomor ini didapat dari supplier saat barang dipesan.</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary fw-bold">Simpan & Lanjutkan</button>
                            <a href="index.php" class="btn btn-light fw-bold">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>