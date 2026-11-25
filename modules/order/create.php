<?php
require_once '../../includes/auth.php';
requireLogin();

$pageTitle = 'Buat Order Baru';
$sparepart = $conn->query("SELECT * FROM data_sparepart WHERE deleted_at IS NULL ORDER BY part_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    $editor  = currentUser();
    
    $spareparts  = $_POST['id_sparepart'];
    $qtys        = $_POST['qty'];
    $hargas      = $_POST['harga'];

    if (empty($spareparts)) {
        flash('danger', 'Minimal 1 Barang harus diisi!');
    } else {
        // 1. Simpan dulu pakai no_po sementara (uniqid) biar ga kena UNIQUE constraint
        $temp_no_po = 'TEMP-' . uniqid();
        $stmt = $conn->prepare("INSERT INTO orders (no_po, tanggal, editor) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $temp_no_po, $tanggal, $editor);
        
        if ($stmt->execute()) {
            $id_order = $conn->insert_id;
            
            // 2. Update no_po jadi format yang diinginkan berdasarkan id_order
            $final_no_po = "PO-SPAREPART-MODERN-" . $id_order;
            $conn->query("UPDATE orders SET no_po = '$final_no_po' WHERE id_order = $id_order");
            
            // 3. Insert detail barang
            $stmt_detail = $conn->prepare("INSERT INTO order_detail (id_order, id_sparepart, qty, harga) VALUES (?, ?, ?, ?)");
            
            for ($i = 0; $i < count($spareparts); $i++) {
                if (!empty($spareparts[$i]) && $qtys[$i] > 0) {
                    $id_sp = (int)$spareparts[$i];
                    $qty   = (int)$qtys[$i];
                    $harga = (float)$hargas[$i];
                    $stmt_detail->bind_param("iiid", $id_order, $id_sp, $qty, $harga);
                    $stmt_detail->execute();
                }
            }
            
            logActivity($conn, 'CREATE', 'orders', $id_order, "Buat PO Order: $final_no_po");
            flash('success', "Order $final_no_po berhasil dibuat!");
            redirect('index.php');
        } else {
            flash('danger', 'Gagal membuat order: ' . $conn->error);
        }
    }
}
include '../../includes/header.php';
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-1">📝 Buat Order Baru</h2>
        <a href="index.php" class="btn btn-light fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <form method="post">
        <!-- Info PO -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Nomor PO</label>
                        <input type="text" class="form-control bg-light" value="(Otomatis dibuat oleh sistem)" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Tanggal Order <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Barang -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Daftar Barang</h5>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnTambahBaris">
                    <i class="bi bi-plus-lg"></i> Tambah Barang
                </button>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="tabelBarang">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th>Sparepart</th>
                                <th width="150">Qty</th>
                                <th width="200">Harga Satuan (Rp)</th>
                                <th width="80" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyBarang">
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-lg fw-bold px-4"><i class="bi bi-save"></i> Simpan Order</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('tbodyBarang');
    const btnTambah = document.getElementById('btnTambahBaris');
    
    const opsiSparepart = `
        <option value="">-- Cari Sparepart --</option>
        <?php 
        $sparepart->data_seek(0); 
        while ($s = $sparepart->fetch_assoc()): 
        ?>
            <option value="<?= $s['id_sparepart'] ?>" data-harga="<?= $s['harga'] ?>">
                <?= htmlspecialchars($s['part_number'].' - '.$s['nama_sparepart']) ?>
            </option>
        <?php endwhile; ?>
    `;

    function initRow(element) {
        const selectEl = $(element).find('.select2-barang');
        selectEl.select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Cari Sparepart --' });
        selectEl.on('change', function() {
            const harga = $(this).find(':selected').data('harga') || 0;
            $(element).find('.harga-input').val(harga);
        });
    }

    initRow(tbody.querySelector('tr'));

    function updateNomor() {
        tbody.querySelectorAll('tr').forEach((row, index) => {
            row.querySelector('.no-urut').textContent = index + 1;
        });
    }

    function tambahBaris() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center fw-bold no-urut"></td>
            <td><select name="id_sparepart[]" class="form-select select2-barang" required>${opsiSparepart}</select></td>
            <td><input type="number" name="qty[]" class="form-control" min="1" value="1" required></td>
            <td><input type="number" name="harga[]" class="form-control harga-input" min="0" value="0" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-hapus"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        initRow(tbody.lastElementChild);
        updateNomor();
    }

    tbody.addEventListener('click', function(e) {
        if (e.target.closest('.btn-hapus')) {
            const row = e.target.closest('tr');
            if (tbody.querySelectorAll('tr').length > 1) {
                row.remove();
                updateNomor();
            } else {
                alert('Minimal harus ada 1 barang!');
            }
        }
    });

    btnTambah.addEventListener('click', tambahBaris);
});
</script>
<?php include '../../includes/footer.php'; ?>