<?php
require_once '../../includes/auth.php';
requireLogin();

$id_transaksi = (int)($_POST['id_transaksi'] ?? 0);
$password     = $_POST['password'] ?? '';
$id_sparepart = (int)($_POST['id_sparepart'] ?? 0);

if (!$id_transaksi || !$password) {
    flash('danger', 'Data tidak valid.');
    redirect("stock_card.php?id=$id_sparepart");
}

// Verifikasi password user yang login
$user_id = currentUserId();
$stmt = $conn->prepare("SELECT password FROM users WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    flash('danger', 'Password salah! Transaksi gagal dihapus.');
    redirect("stock_card.php?id=$id_sparepart");
}

// Ambil data transaksi sebelum dihapus
$trx = $conn->query("SELECT * FROM history_transaksi WHERE id_transaksi = $id_transaksi")->fetch_assoc();
if (!$trx) {
    flash('danger', 'Transaksi tidak ditemukan.');
    redirect("stock_card.php?id=$id_sparepart");
}

// 1. Hapus transaksinya
$conn->query("DELETE FROM history_transaksi WHERE id_transaksi = $id_transaksi");

// 2. RE-CALCULATE BALANCE (Sangat penting agar saldo di bawahnya tidak kacau)
// Ambil semua transaksi setelah yang dihapus, urutkan dari terlama
$sisa_trx = $conn->query("SELECT id_transaksi, masuk, keluar FROM history_transaksi WHERE id_sparepart = $id_sparepart ORDER BY post_date ASC, id_transaksi ASC");
$balance = 0;
while ($r = $sisa_trx->fetch_assoc()) {
    $balance = $balance + $r['masuk'] - $r['keluar'];
    $conn->query("UPDATE history_transaksi SET balance = $balance WHERE id_transaksi = {$r['id_transaksi']}");
}

// 3. Update master stok di data_sparepart
$conn->query("UPDATE data_sparepart SET stok = $balance WHERE id_sparepart = $id_sparepart");

logActivity($conn, 'DELETE', 'history_transaksi', $id_transaksi, "Hapus transaksi & recalculate stok");
flash('success', 'Transaksi berhasil dihapus dan saldo stok telah disesuaikan.');
redirect("stock_card.php?id=$id_sparepart");
?>