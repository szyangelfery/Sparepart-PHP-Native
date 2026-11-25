<div class="modal fade" id="modalPO<?= $r['id_order'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Detail PO: <?= htmlspecialchars($r['no_po']) ?></h5>
                    <small class="text-muted">Tanggal: <?= date('d/m/Y', strtotime($r['tanggal'])) ?></small>
                </div>
                
                <div class="ms-auto me-3 d-flex gap-2">
                    <!-- TOMBOL CETAK REKAP (Hanya muncul jika udah ada barang masuk) -->
                    <?php if ($r['status'] === 'terima_partial' || $r['status'] === 'selesai' || $r['status'] === 'partial_selesai'): ?>
                        <a href="cetak_po.php?id=<?= $r['id_order'] ?>" target="_blank" class="btn btn-sm btn-outline-dark fw-bold">
                            <i class="bi bi-printer"></i> Cetak Rekap
                        </a>
                    <?php endif; ?>

                    <!-- TOMBOL SELESAIKAN -->
                    <?php if($r['status'] !== 'selesai' && $r['status'] !== 'partial_selesai'): ?>
                        <a href="selesaikan.php?id=<?= $r['id_order'] ?>" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('Selesaikan paksa PO ini?')">
                            Selesaikan
                        </a>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sparepart</th>
                            <th class="text-center">Dipesan</th>
                            <th class="text-center text-success">Datang</th>
                            <th class="text-end">Harga Per Item</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $id_ord = $r['id_order'];
                        $det = $conn->query("SELECT d.*, sp.part_number, sp.nama_sparepart FROM order_detail d JOIN data_sparepart sp ON sp.id_sparepart = d.id_sparepart WHERE d.id_order = $id_ord");
                        while($item = $det->fetch_assoc()): 
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-primary"><?= htmlspecialchars($item['part_number']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($item['nama_sparepart']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= $item['qty'] ?></td>
                            <td class="text-center fw-bold text-success"><?= $item['qty_diterima'] ?></td>
                            <td class="text-end">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td class="text-center pe-4 text-nowrap">
                                <?php if ($item['status'] === 'draft'): ?>
                                    <a href="proses_order.php?id=<?= $r['id_order'] ?>" class="btn btn-sm btn-warning fw-bold text-dark">
                                        <i class="bi bi-send"></i> Order
                                    </a>
                                <?php elseif ($item['status'] === 'received'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                    <!-- TOMBOL CETAK PER ITEM -->
                                    <a href="cetak_item.php?id=<?= $item['id_detail'] ?>" target="_blank" class="btn btn-sm btn-outline-dark ms-1" title="Cetak Bukti Item">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Tombol Terima -->
                                    <a href="terima_item.php?id_detail=<?= $item['id_detail'] ?>" class="btn btn-sm btn-success fw-bold">
                                        <i class="bi bi-box-arrow-in-down"></i> Terima
                                    </a>
                                    <a href="hapus_item.php?id=<?= $item['id_detail'] ?>&id_order=<?= $r['id_order'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Hapus item?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>