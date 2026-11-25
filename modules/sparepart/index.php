<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Data Sparepart';

$data = $conn->query("
    SELECT s.*, k.nama_kategori 
    FROM data_sparepart s
    LEFT JOIN kategori k ON k.id_kategori = s.id_kategori
    WHERE s.deleted_at IS NULL
    ORDER BY s.id_sparepart DESC
");

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">📦 Data Sparepart</h2>
        <a href="create.php" class="btn btn-primary fw-bold">
            <i class="bi bi-plus-lg"></i> Tambah Sparepart
        </a>
    </div>

    <!-- Card Tabel -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            
            <!-- Toolbar: Search & Filter -->
            <div class="row mb-4 g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari part number, nama, kategori, atau stok...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="perPageSelect" class="form-select">
                        <option value="5">Tampilkan 5 baris</option>
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
                            <th scope="col" class="fw-semibold">Part Number</th>
                            <th scope="col" class="fw-semibold">Nama Sparepart</th>
                            <th scope="col" class="fw-semibold">Kategori</th>
                            <th scope="col" class="fw-semibold text-center">Stok</th>
                            <th scope="col" class="fw-semibold text-center">Aksi</th>
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

function renderTable() {
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    const pageData = filteredData.slice(start, end);

    tableBody.innerHTML = '';

    if (pageData.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Data tidak ditemukan
        </td></tr>`;
    } else {
        pageData.forEach(r => {
            const stokBadge = r.stok <= r.min_stok ? 'bg-danger' : 'bg-success';
            // Format harga ke rupiah
            const harga = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(r.harga || 0);
            
            tableBody.innerHTML += `
                <tr>
                    <td class="fw-bold text-primary">${escapeHtml(r.part_number)}</td>
                    <td>
                        <div class="fw-semibold text-dark">${escapeHtml(r.nama_sparepart)}</div>
                    </td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-dark border">${escapeHtml(r.nama_kategori ?? '-')}</span></td>
                    <td class="text-center">
                        <span class="badge rounded-pill ${stokBadge}">${r.stok}</span>
                    </td>
                    <td class="text-center">
                         <a href="stock_card.php?id=${r.id_sparepart}" class="btn btn-sm btn-outline-info" title="Kartu Stok">
                            <i class="bi bi-journal-text"></i>
                        </a>
                        <a href="edit.php?id=${r.id_sparepart}" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="delete.php?id=${r.id_sparepart}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus sparepart ini?')" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>`;
        });
    }

    const total = filteredData.length;
    const showing = total === 0 ? 0 : `${start + 1}-${Math.min(end, total)}`;
    infoText.textContent = `Menampilkan ${showing} dari ${total} data`;
    pageInfo.textContent = `Halaman ${currentPage} dari ${Math.max(1, Math.ceil(total / perPage))}`;

    renderPagination();
}

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

    // Prev
    pagination.appendChild(makeItem('<i class="bi bi-chevron-left"></i>', currentPage - 1, currentPage === 1));

    // Angka
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

    // Next
    pagination.appendChild(makeItem('<i class="bi bi-chevron-right"></i>', currentPage + 1, currentPage === totalPages));
}

let searchTimer;
searchInput.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = e.target.value.toLowerCase().trim();
        filteredData = allData.filter(r => {
            const haystack = [r.part_number, r.nama_sparepart, r.nama_kategori, r.stok].join(' ').toLowerCase();
            return haystack.includes(q);
        });
        currentPage = 1;
        renderTable();
    }, 150);
});

perPageSelect.addEventListener('change', e => {
    perPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

renderTable();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>