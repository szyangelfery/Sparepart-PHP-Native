<?php
require_once '../../includes/auth.php';
requireLogin();

$id_detail = (int)($_GET['id'] ?? 0);
$id_order  = (int)($_GET['id_order'] ?? 0);

if ($id_detail && $id_order) {
    // Pastikan belum ada barang yang diterima
    $cek = $conn->query("SELECT qty_diterima FROM order_detail WHERE id_detail = $id_detail")->fetch_assoc();
    
    if ($cek && $cek['qty_diterima'] == 0) {
        $conn->query("DELETE FROM order_detail WHERE id_detail = $id_detail");
        flash('success', 'Barang berhasil dihapus dari PO.');
    } else {
        flash('danger', 'Tidak bisa dihapus karena barang sudah mulai diterima.');
    }
}
redirect('index.php');
?>