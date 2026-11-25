<?php
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $parts = explode('/', trim($scriptDir, '/'));
    $rootFolder = $parts[0] ?? 'sparepart';
    define('BASE_URL', '/' . $rootFolder . '/');
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
function navActive($needle) {
    global $currentPath;
    return strpos($currentPath, $needle) !== false ? ' active' : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#1e293b">
    <title><?= $pageTitle ?? 'Sparepart App' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 Bootstrap 5 Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Custom Style untuk Form & Select2 -->
    <style>
        /* Styling Form Card */
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        
        /* Override Select2 biar tinggi & rounded */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 46px !important;
            border-radius: 10px !important;
            border: 1px solid #e2e8f0 !important;
            padding: 4px 8px;
            display: flex;
            align-items: center;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 8px;
            color: #1e293b;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            top: 0;
        }
        .select2-container--bootstrap-5 .select2-selection--single:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1) !important;
        }
        .select2-dropdown {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .select2-search__field {
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
            padding: 8px 12px !important;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span>🔧</span>
        <span>SPAREPART PT MPM</span>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>index.php" class="<?= navActive('/index.php') && !navActive('/modules/') ? 'active' : '' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>modules/transaksi/index.php" class="<?= navActive('/modules/transaksi') ?>">
            <span class="icon">🔄</span> Data Transaksi
        </a>
        <a href="<?= BASE_URL ?>modules/sparepart/index.php" class="<?= navActive('/modules/sparepart') ?>">
            <span class="icon">📦</span> Data Sparepart
        </a>
        <a href="<?= BASE_URL ?>modules/kategori/index.php" class="<?= navActive('/modules/kategori') ?>">
            <span class="icon">🏷️</span> Data Kategori
        </a>
        <a href="<?= BASE_URL ?>modules/supplier/index.php" class="<?= navActive('/modules/supplier') ?>">
            <span class="icon">🏭</span> Data Supplier
        </a>
        <a href="<?= BASE_URL ?>modules/mesin/index.php">
            <span class="icon">⚙️</span> Data Mesin
        </a>
        <a href="<?= BASE_URL ?>modules/area/index.php">
            <span class="icon">🏢</span> Data Gudang/Area</a>
        <a href="<?= BASE_URL ?>modules/order/index.php" class="<?= navActive('/modules/order') ?>">
            <span class="icon">📋</span> Data Order
        </a>
        <a href="<?= BASE_URL ?>modules/laporan/index.php" class="<?= navActive('/modules/laporan') ?>">
            <span class="icon">📈</span> Laporan
        </a>
        <a href="<?= BASE_URL ?>modules/backup/index.php" class="<?= navActive('/modules/backup') ?>">
            <span class="icon">💾</span> Backup
        </a>
    </nav>
    <div class="sidebar-footer">
        <div>Versi 1.0</div>
        <div>© <?= date('Y') ?></div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- MAIN -->
<div class="main-wrapper">

    <!-- TOPBAR -->
    <header class="topbar">
        <button class="topbar-toggle" id="sidebarToggle" aria-label="Menu">☰</button>
        <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
        <div class="topbar-user">
            <div class="user-info-desktop">
                <span class="name"><?= htmlspecialchars(currentUser()) ?></span>
                <span class="role"><?= htmlspecialchars($_SESSION['user_role'] ?? 'user') ?></span>
            </div>
            <div class="user-avatar">
                <?= strtoupper(substr(currentUser(), 0, 1)) ?>
            </div>
            <a href="<?= BASE_URL ?>logout.php" class="btn-logout" onclick="return confirm('Logout?')">
                🚪 <span class="d-none d-sm-inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="content">
        <?php showFlash(); ?>