<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Daftar Pre-Order (PO)';

// Query Utama: Ambil data PO, Supplier (gabungan), dan Total Biaya Aktual (yang udah diterima)
$data = $conn->query("
    SELECT o.*, 
           GROUP_CONCAT(DISTINCT s.nama_supplier SEPARATOR ', ') as list_supplier,
           COALESCE(SUM(d.qty_diterima * d.harga), 0) as total_biaya_aktual
    FROM orders o 
    LEFT JOIN order_detail d ON d.id_order = o.id_order
    LEFT JOIN supplier s ON s.id_supplier = d.id_supplier
    GROUP BY o.id_order
    ORDER BY o.id_order DESC
");

$orders = [];
while ($r = $data->fetch_assoc()) {
    $orders[] = $r;
}

include '../../includes/header.php';
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📋 Daftar Pre-Order (PO)</h2>
            <p class="text-muted mb-0">Kelola pemesanan dan penerimaan barang</p>
        </div>
        <a href="create.php" class="btn btn-primary fw-bold">
            <i class="bi bi-plus-lg"></i> Buat PO Baru
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            
            <div class="row mb-4 g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari No PO, Supplier, atau Status...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-4">No PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th class="text-center">Status PO</th>
                            <th class="text-end">Total Biaya</th> <!-- KOLOM BARU -->
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach($orders as $r): 
                            $st = $r['status'];
                            $badge = 'bg-secondary'; $label = 'PERLU ORDER';
                            if ($st === 'order_partial') { $badge = 'bg-warning text-dark'; $label = 'ORDER PARTIAL'; }
                            if ($st === 'terima_partial') { $badge = 'bg-info text-dark'; $label = 'TERIMA PARTIAL'; }
                            if ($st === 'selesai') { $badge = 'bg-success'; $label = 'SELESAI'; }
                            if ($st === 'partial_selesai') { $badge = 'bg-dark'; $label = '[PARTIAL] SELESAI'; }

                            $suppliers = $r['list_supplier'] ? $r['list_supplier'] : '-';
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($r['no_po']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($suppliers) ?></td>
                            <td class="text-center"><span class="badge rounded-pill <?= $badge ?>"><?= $label ?></span></td>
                            <td class="text-end fw-bold text-success">
                                Rp <?= number_format($r['total_biaya_aktual'], 0, ',', '.') ?>
                            </td>
                            <td class="text-center pe-4 text-nowrap">
                                <button class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalPO<?= $r['id_order'] ?>">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                                
                                <!-- TOMBOL CETAK (Hanya jika ada barang masuk) -->
                                <?php if ($st === 'terima_partial' || $st === 'selesai' || $st === 'partial_selesai'): ?>
                                    <a href="cetak_po.php?id=<?= $r['id_order'] ?>" target="_blank" class="btn btn-sm btn-outline-dark ms-1" title="Cetak Rekap PO">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($st !== 'selesai' && $st !== 'partial_selesai'): ?>
                                    <a href="edit.php?id=<?= $r['id_order'] ?>" class="btn btn-sm btn-outline-warning ms-1" title="Edit PO">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $r['id_order'] ?>" class="btn btn-sm btn-outline-danger ms-1" title="Hapus PO" onclick="return confirm('Yakin ingin menghapus PO ini secara permanen?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DIPANGGIL DI LUAR TABEL -->
<?php foreach($orders as $r): ?>
    <?php include 'detail_modal.php'; ?>
<?php endforeach; ?>

<?php include '../../includes/footer.php'; ?>