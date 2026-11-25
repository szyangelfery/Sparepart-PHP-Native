<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tambah'])) {
    $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori, keterangan) VALUES (?,?)");
    $stmt->bind_param("ss", $_POST['nama_kategori'], $_POST['keterangan']);
    $stmt->execute();
    
    // ✅ INJEKSI LOG
    catatLog($conn, 'TAMBAH', 'Kategori', 'Menambahkan kategori baru: ' . $_POST['nama_kategori']);
    
    flash('success','Kategori ditambahkan');
    redirect('index.php');
}

if (isset($_GET['hapus'])) {
    $id=(int)$_GET['hapus'];
    
    // ✅ AMBIL DATA SEBELUM HAPUS
    $q_old = $conn->query("SELECT * FROM kategori WHERE id_kategori=$id");
    $old = $q_old->fetch_assoc();
    
    if ($old) {
        catatLog($conn, 'HAPUS', 'Kategori', 'Menghapus kategori: ' . $old['nama_kategori'] . ' (Keterangan: ' . ($old['keterangan'] ?: '-') . ')');
    }
    
    $conn->query("DELETE FROM kategori WHERE id_kategori=$id");
    flash('success','Kategori dihapus'); 
    redirect('index.php');
}

$pageTitle = 'Data Kategori';

// Ambil semua data (tanpa limit) karena filtering dilakukan di JS
$data = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

include '../../includes/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">🏷️ Data Kategori</h2>
    </div>

    <!-- Form Tambah -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="post" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Elektronik" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Keterangan opsional...">
                </div>
                <div class="col-md-3">
                    <button name="tambah" class="btn btn-primary fw-bold w-100">
                        <i class="bi bi-plus-lg"></i> Tambah Kategori
                    </button>
                </div>
            </form>
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
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama kategori atau keterangan...">
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
                            <th scope="col" width="50" class="fw-semibold">#</th>
                            <th scope="col" class="fw-semibold">Nama Kategori</th>
                            <th scope="col" class="fw-semibold">Keterangan</th>
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
// Ambil semua data dari PHP
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
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Data tidak ditemukan
        </td></tr>`;
    } else {
        pageData.forEach((r, index) => {
            // Nomor urut global berdasarkan halaman
            const globalIndex = start + index + 1;
            
            tableBody.innerHTML += `
                <tr>
                    <td class="text-muted">${globalIndex}</td>
                    <td class="fw-semibold text-dark">${escapeHtml(r.nama_kategori)}</td>
                    <td>${escapeHtml(r.keterangan || '-')}</td>
                    <td class="text-center">
                        <a href="?hapus=${r.id_kategori}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')" title="Hapus">
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

// SEARCH (Debounce 150ms)
let searchTimer;
searchInput.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = e.target.value.toLowerCase().trim();
        filteredData = allData.filter(r => {
            const haystack = [r.nama_kategori, r.keterangan].join(' ').toLowerCase();
            return haystack.includes(q);
        });
        currentPage = 1;
        renderTable();
    }, 150);
});

// PER PAGE
perPageSelect.addEventListener('change', e => {
    perPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Render awal
renderTable();
</script>

<?php include '../../includes/footer.php'; ?>