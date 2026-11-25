<?php
require_once '../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$order = $conn->query("SELECT * FROM orders WHERE id_order=$id")->fetch_assoc();
if (!$order) { flash('danger','Order tidak ditemukan'); redirect('index.php'); }

$meta = json_decode($order['notes'], true) ?: [];
if (($meta['status'] ?? '') === 'diterima') {
    flash('warning','Order ini sudah diterima sebelumnya');
    redirect('index.php');
}

$qty = (int)($meta['qty'] ?? $order['balance']);

// ⭐ AUTO INSERT ke history_transaksi sebagai barang masuk
$res = insertTransaksi($conn, [
    'id_sparepart' => $order['id_sparepart'],
    'masuk'        => $qty,
    'id_supplier'  => $meta['id_supplier'] ?? null,
    'no_po'        => $meta['no_po'] ?? "ORD-{$order['id_order']}",
    'notes'        => "Penerimaan order #{$order['id_order']}",
]);

if ($res['ok']) {
    $meta['status'] = 'diterima';
    $meta['received_at'] = date('Y-m-d H:i:s');
    $notes = $conn->real_escape_string(json_encode($meta));
    $conn->query("UPDATE orders SET notes='$notes', balance={$res['balance']} WHERE id_order=$id");

    logActivity($conn, 'RECEIVE', 'orders', $id, "Terima order #$id, +$qty masuk");
    flash('success', "Order diterima! $qty unit masuk ke stok. Balance baru: {$res['balance']}");
} else {
    flash('danger', 'Gagal: '.$res['msg']);
}
redirect('index.php');