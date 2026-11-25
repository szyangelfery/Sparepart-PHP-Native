<?php
require_once '../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger', 'ID tidak valid'); redirect('index.php'); }

// Cek status order
$order = $conn->query("SELECT * FROM orders WHERE id_order = $id")->fetch_assoc();
if (!$order) { flash('danger', 'Order tidak ditemukan'); redirect('index.php'); }

if ($order['status'] !== 'pending') {
    flash('danger', 'PO yang sudah diproses tidak bisa dihapus!');
    redirect('index.php');
}

// Hapus (foreign key ON DELETE CASCADE akan otomatis hapus order_detail)
$conn->query("DELETE FROM orders WHERE id_order = $id");

logActivity($conn, 'DELETE', 'orders', $id, "Hapus PO: {$order['no_po']}");
flash('success', "PO {$order['no_po']} berhasil dihapus permanen.");
redirect('index.php');
?>