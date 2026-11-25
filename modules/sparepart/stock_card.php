<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger', 'ID tidak valid.'); redirect('index.php'); }

$sparepart = $conn->query("
    SELECT s.*, k.nama_kategori 
    FROM data_sparepart s
    LEFT JOIN kategori k ON k.id_kategori = s.id_kategori
    WHERE s.id_sparepart = $id AND s.deleted_at IS NULL
")->fetch_assoc();

if (!$sparepart) { flash('danger', 'Sparepart tidak ditemukan.'); redirect('index.php'); }

$pageTitle = 'Kartu Stok: ' . $sparepart['nama_sparepart'];

$history = $conn->query("
    SELECT h.*, sup.nama_supplier 
    FROM history_transaksi h
    LEFT JOIN supplier sup ON sup.id_supplier = h.id_supplier
    WHERE h.id_sparepart = $id
    ORDER BY h.post_date ASC, h.id_transaksi ASC
");

$history_data = [];
while ($h = $history->fetch_assoc()) {
    $history_data[] = $h;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">📋 Kartu Stok</h2>
            <p class="text-muted mb-0">Riwayat keluar masuk: <b><?= htmlspecialchars($sparepart['nama_sparepart']) ?></b></p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary fw-bold"><i class="bi bi-printer"></i> Cetak / PDF</button>
            <a href="index.php" class="btn btn-secondary fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Card Info Sparepart -->
    <div class="card border-0 shadow-sm rounded-4 mb-4"> 
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-1"><strong>Part Number:</strong> <?= htmlspecialchars($sparepart['part_number']) ?></p>
                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($sparepart['nama_sparepart']) ?></p>
                    <p class="mb-1"><strong>Kategori:</strong> <?= htmlspecialchars($sparepart['nama_kategori'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Stok Saat Ini:</strong> <?= $sparepart['stok'] ?> pcs</p>
                    <p class="mb-1"><strong>Minimal Stok:</strong> <?= $sparepart['min_stok'] ?></p>
                    <p class="mb-1"><strong>Harga Jual:</strong> Rp <?= number_format($sparepart['harga'], 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Tabel -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            
            <!-- Toolbar: Filter & Search -->
            <div class="row mb-4 g-2 align-items-end  d-print-none">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                    <input type="date" id="filterDari" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" id="filterSampai" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Cari Keterangan/Supplier</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Ketik sesuatu...">
                </div>
                <div class="col-md-3">
                    <button id="btnReset" class="btn btn-outline-secondary w-100 fw-bold">Reset Filter</button>
                </div>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tabelStok">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="10">No</th>
                            <th width="10">Tanggal</th>
                            <th>Supplier / No PO</th>
                            <th>Keterangan</th>
                            <th width="10" class="text-success">Masuk</th>
                            <th width="10" class="text-danger">Keluar</th>
                            <th width="120">Harga Beli</th>
                            <th width="10">Saldo</th>
                            <th width="15" class="d-print-none">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyStok">
                        <!-- Di-render via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL PEMBELIAN (FILTERED):</td>
                            <td class="text-center text-success" id="totalMasuk">0</td>
                            <td class="text-center text-danger" id="totalKeluar">0</td>
                            <td class="text-end text-primary fs-6" id="totalHarga">Rp 0</td>
                            <td colspan="2" class="d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="text-muted small mt-2" id="infoFilter">Menampilkan semua data.</div>
        </div>
    </div>
</div>

<!-- MODAL HAPUS (Verifikasi Password) -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="hapus_transaksi.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_transaksi" id="hapusIdTransaksi">
                    <input type="hidden" name="id_sparepart" value="<?= $id ?>">
                    <p class="mb-3">Apakah Anda yakin ingin menghapus transaksi ini? Stok akan otomatis disesuaikan ulang.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Masukkan Password Anda <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Password login Anda" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold">Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@media print {
    /* 1. Sembunyikan navigasi & tombol */
    .sidebar, .topbar, .main-wrapper > header, 
    .no-print, .d-print-none, #infoFilter, .modal, 
    a.btn, button.btn { 
        display: none !important; 
        
        printWindow.document.write;
    }

    /* 2. Paksa semua container jadi putih & transparan */
    body, .main-wrapper, .container-fluid, .card, .card-body, .row, .col-md-6 { 
        background: transparent !important; 
        box-shadow: none !important; 
        border-radius: 0 !important; /* BUANG ROUNDED YANG BIKIN KEPOTONG */
        overflow: visible !important; /* Paksa tampilkan isi */
        margin: 0 !important; 
        padding: 0 !important;
        color: #000 !important;
    }

    /* 3. Tampilkan garis kotak pembatas antar card */
    .card { 
        border: 1px solid #000 !important; 
        margin-bottom: 15px !important; 
        padding: 10px !important;
    }

    /* 4. Paksa semua teks jadi hitam pekat */
    * { 
        color: #000 !important; 
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important; 
    }

    /* 5. Rapatkan tabel info sparepart */
    .table-borderless td {
        padding: 3px !important;
        font-size: 12px !important;
    }

    /* 6. Pengaturan Tabel Riwayat */
    .table { 
        width: 100% !important; 
        border-collapse: collapse !important; 
    }
    .table th, .table td { 
        border: 1px solid #000 !important; 
        padding: 5px !important; 
        font-size: 10px !important; 
    }
    .table-dark th { 
        background-color: #000 !important; 
        color: #fff !important; 
    }

    /* 7. Sembunyikan kolom aksi (paling kanan) */
    th:last-child, td:last-child { 
        display: none !important; 
    }
    
    
}
</style>

<script>
// Data dari PHP
const allData = <?= json_encode($history_data) ?>;
const tableBody = document.getElementById('tbodyStok');
const filterDari = document.getElementById('filterDari');
const filterSampai = document.getElementById('filterSampai');
const searchInput = document.getElementById('searchInput');
const btnReset = document.getElementById('btnReset');
const infoFilter = document.getElementById('infoFilter');

const totalMasukEl = document.getElementById('totalMasuk');
const totalKeluarEl = document.getElementById('totalKeluar');
const totalHargaEl = document.getElementById('totalHarga');

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function renderTable() {
    const q = searchInput.value.toLowerCase().trim();
    const tglDari = filterDari.value ? new Date(filterDari.value) : null;
    const tglSampai = filterSampai.value ? new Date(filterSampai.value) : null;

    let filtered = allData.filter(r => {
        // Search text
        const haystack = [r.nama_supplier, r.no_po, r.notes, r.editor].join(' ').toLowerCase();
        if (q && !haystack.includes(q)) return false;

        // Filter Tanggal
        const tglTrx = new Date(r.post_date.replace(' ', 'T'));
        if (tglDari) {
            tglDari.setHours(0,0,0,0);
            if (tglTrx < tglDari) return false;
        }
        if (tglSampai) {
            tglSampai.setHours(23,59,59,999);
            if (tglTrx > tglSampai) return false;
        }
        return true;
    });

    tableBody.innerHTML = '';
    
    let sumMasuk = 0;
    let sumKeluar = 0;
    let sumHarga = 0;

    if (filtered.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data yang cocok dengan filter.</td></tr>`;
        infoFilter.textContent = "Menampilkan 0 data.";
    } else {
        filtered.forEach((r, index) => {
            const tgl = new Date(r.post_date.replace(' ', 'T')).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            
            // Logika Harga Beli (Hanya dihitung kalau ada barang masuk)
            let hargaBeli = '-';
            if (r.masuk > 0) {
                const nominal = r.harga > 0 ? r.harga : 0; // Ambil dari kolom harga (kalau ada)
                sumHarga += (nominal * r.masuk);
                hargaBeli = formatRupiah(nominal);
            }

            sumMasuk += parseInt(r.masuk);
            sumKeluar += parseInt(r.keluar);

            const supplier = r.nama_supplier ? `<div class="fw-bold">${escapeHtml(r.nama_supplier)}</div>` : '';
            const po = r.no_po ? `<small class="text-muted">PO: ${escapeHtml(r.no_po)}</small>` : '';

            tableBody.innerHTML += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-nowrap">${tgl}</td>
                    <td>${supplier}${po}</td>
                    <td>${escapeHtml(r.notes ?? '-')}</td>
                    <td class="text-center text-success fw-bold">${r.masuk > 0 ? '+'+r.masuk : ''}</td>
                    <td class="text-center text-danger fw-bold">${r.keluar > 0 ? '-'+r.keluar : ''}</td>
                    <td class="text-end">${hargaBeli}</td>
                    <td class="text-center fw-bold bg-light">${r.balance}</td>
                    <td class="text-center d-print-none text-nowrap">
                        <a href="edit_transaksi.php?id=${r.id_transaksi}&id_sparepart=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <button class="btn btn-sm btn-outline-danger btnHapus" data-id="${r.id_transaksi}" title="Hapus"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `;
        });

        infoFilter.textContent = `Menampilkan ${filtered.length} transaksi.`;
    }

    // Update Footer Total
    totalMasukEl.textContent = '+' + sumMasuk;
    totalKeluarEl.textContent = '-' + sumKeluar;
    totalHargaEl.textContent = formatRupiah(sumHarga);

    // Bind event listener untuk tombol hapus yang baru di-render
    document.querySelectorAll('.btnHapus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('hapusIdTransaksi').value = id;
            new bootstrap.Modal(document.getElementById('modalHapus')).show();
        });
    });
}

// Event Listener untuk Filter
searchInput.addEventListener('input', renderTable);
filterDari.addEventListener('change', renderTable);
filterSampai.addEventListener('change', renderTable);
btnReset.addEventListener('click', () => {
    searchInput.value = '';
    filterDari.value = '';
    filterSampai.value = '';
    renderTable();
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Render Pertama Kali
renderTable();
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>