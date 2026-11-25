<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Data Transaksi';
$filter = $_GET['filter'] ?? 'all'; // all|masuk|keluar

$where = "";
if ($filter === 'masuk')  $where = "WHERE h.masuk > 0";
if ($filter === 'keluar') $where = "WHERE h.keluar > 0";

// Ambil semua data (biar JS bisa filter & paginate)
$data = $conn->query("
    SELECT h.*, s.part_number, s.nama_sparepart, sp.nama_supplier 
    FROM history_transaksi h
    JOIN data_sparepart s ON s.id_sparepart = h.id_sparepart
    LEFT JOIN supplier sp ON sp.id_supplier = h.id_supplier
    $where
    ORDER BY h.id_transaksi ASC
");

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">🔄 Data Transaksi</h2>
        <div class="d-flex gap-2">
            <a href="masuk.php" class="btn btn-success fw-bold"><i class="bi bi-box-arrow-in-down"></i> Barang Masuk</a>
            <a href="keluar.php" class="btn btn-danger fw-bold"><i class="bi bi-box-arrow-up"></i> Barang Keluar</a>
        </div>
    </div>

    <!-- Filter Tab (Semua/Masuk/Keluar) -->
    <div class="mb-3">
        <div class="btn-group w-100 w-md-auto" role="group">
            <a href="?filter=all" class="btn btn-outline-primary <?= $filter==='all'?'active':'' ?>">Semua Transaksi</a>
            <a href="?filter=masuk" class="btn btn-outline-success <?= $filter==='masuk'?'active':'' ?>">Hanya Masuk</a>
            <a href="?filter=keluar" class="btn btn-outline-danger <?= $filter==='keluar'?'active':'' ?>">Hanya Keluar</a>
        </div>
    </div>

    <!-- Card Tabel -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            
            <!-- Toolbar: Search & Filter -->
            <div class="row mb-4 g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari part number, nama, supplier, PO...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="perPageSelect" class="form-select">
                        <option value="10" selected>Tampilkan 10 baris</option>
                        <option value="25">Tampilkan 25 baris</option>
                        <option value="50">Tampilkan 50 baris</option>
                        <option value="100">Tampilkan 100 baris</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end text-muted d-flex align-items-center justify-content-md-end">
                    <small id="infoText">-</small>
                </div>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th scope="col" class="fw-semibold">Tanggal</th>
                            <th scope="col" class="fw-semibold">Sparepart</th>
                            <th scope="col" class="fw-semibold">Supplier</th>
                            <th scope="col" class="fw-semibold">PO</th>
                            <th scope="col" class="fw-semibold text-center text-success">Masuk</th>
                            <th scope="col" class="fw-semibold text-center text-danger">Keluar</th>
                            <th scope="col" class="fw-semibold text-center">Sisa Stok</th>
                            <th scope="col" class="fw-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data di-render via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Tabel: Pagination -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                <div class="text-muted small" id="pageInfo"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPT -->
<script>
// Ambil data dari PHP ke JS
const allData = <?= json_encode($data->fetch_all(MYSQLI_ASSOC)) ?>;

let filteredData = [...allData];
let currentPage = 1;
let perPage = 10;

const searchInput = document.getElementById('searchInput');
const perPageSelect = document.getElementById('perPageSelect');
const tableBody = document.getElementById('tableBody');
const pagination = document.getElementById('pagination');
const pageInfo = document.getElementById('pageInfo');
const infoText = document.getElementById('infoText');

// ============ RENDER TABLE ============
function renderTable() {
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    const pageData = filteredData.slice(start, end);

    tableBody.innerHTML = '';

    if (pageData.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Data transaksi tidak ditemukan
        </td></tr>`;
    } else {
        pageData.forEach(r => {
            // Format tanggal
            const tgl = new Date(r.post_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            
            // Badge Masuk / Keluar
            const masuk = r.masuk > 0 ? `<span class="badge bg-success">+${r.masuk}</span>` : '-';
            const keluar = r.keluar > 0 ? `<span class="badge bg-danger">-${r.keluar}</span>` : '-';
            
            // Supplier & PO
            let supplierPo = escapeHtml(r.nama_supplier ?? '-');
            let namaSupplier = escapeHtml(r.nama_supplier ?? '-');
            if (r.no_po) supplierPo += `<br><small class="text-muted">PO: ${escapeHtml(r.no_po)}</small>`;

            tableBody.innerHTML += `
                <tr>
                    <td class="text-nowrap">${tgl}</td>
                    <td>${escapeHtml(r.nama_sparepart)}</td>
                    <td>${namaSupplier}</td>
                    <td>${r.no_po}</td>
                    <td class="text-center fw-bold">${masuk}</td>
                    <td class="text-center fw-bold">${keluar}</td>
                    <td class="text-center"><span class="badge bg-secondary rounded-pill">${r.balance}</span></td>
                    <td><small class="text-muted">${escapeHtml(r.notes ?? '-')}</small></td>
                </tr>`;
        });
    }

    // Info text
    const total = filteredData.length;
    const showing = total === 0 ? 0 : `${start + 1}-${Math.min(end, total)}`;
    infoText.textContent = `Menampilkan ${showing} dari ${total} data`;
    pageInfo.textContent = `Halaman ${currentPage} dari ${Math.max(1, Math.ceil(total / perPage))}`;

    renderPagination();
}

// ============ RENDER PAGINATION ============
function renderPagination() {
    const totalPages = Math.max(1, Math.ceil(filteredData.length / perPage));
    pagination.innerHTML = '';

    const makeItem = (iconOrText, page, disabled = false, active = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.innerHTML = iconOrText;
        a.addEventListener('click', e => {
            e.preventDefault();
            if (disabled || active) return;
            currentPage = page;
            renderTable();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
        li.appendChild(a);
        return li;
    };

    pagination.appendChild(makeItem('<i class="bi bi-chevron-left"></i>', currentPage - 1, currentPage === 1));

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    if (startPage > 1) {
        pagination.appendChild(makeItem('1', 1, false, currentPage === 1));
        if (startPage > 2) pagination.appendChild(makeItem('...', 0, true));
    }

    for (let i = startPage; i <= endPage; i++) {
        pagination.appendChild(makeItem(i, i, false, i === currentPage));
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) pagination.appendChild(makeItem('...', 0, true));
        pagination.appendChild(makeItem(totalPages, totalPages, false, currentPage === totalPages));
    }

    pagination.appendChild(makeItem('<i class="bi bi-chevron-right"></i>', currentPage + 1, currentPage === totalPages));
}

// ============ LIVE SEARCH ============
let searchTimer;
searchInput.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = e.target.value.toLowerCase().trim();
        filteredData = allData.filter(r => {
            const haystack = [
                r.part_number, r.nama_sparepart, r.nama_supplier, 
                r.no_po, r.notes, r.balance
            ].join(' ').toLowerCase();
            return haystack.includes(q);
        });
        currentPage = 1;
        renderTable();
    }, 150);
});

// ============ FILTER JUMLAH BARIS ============
perPageSelect.addEventListener('change', e => {
    perPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
});

// ============ UTILITY: escape HTML ============
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// ============ INIT ============
renderTable();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>