<?php
require_once '../../includes/auth.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$conn->query("UPDATE data_sparepart SET deleted_at=NOW() WHERE id_sparepart=$id");
logActivity($conn, 'DELETE', 'data_sparepart', $id, "Hapus sparepart id=$id");
flash('success','Sparepart dihapus');
redirect('index.php');