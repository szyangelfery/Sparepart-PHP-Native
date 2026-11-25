<?php
require_once '../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $conn->query("UPDATE orders SET status = 'partial_selesai' WHERE id_order = $id");
    flash('success', 'PO berhasil diselesaikan paksa.');
}

redirect('index.php');
?>