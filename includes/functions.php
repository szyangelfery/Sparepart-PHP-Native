<?php
if (!function_exists('redirect')) {

    function redirect($url) { header("Location: $url"); exit; }

    function flash($type, $msg) { $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }

    function showFlash() {
        if (!empty($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            echo "<div class='alert alert-{$f['type']}'>{$f['msg']}</div>";
            unset($_SESSION['flash']);
        }
    }

    function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

    function isLogin() { return isset($_SESSION['user_id']); }

    function requireLogin() {
        if (!isLogin()) {
            $base = defined('BASE_URL') ? BASE_URL : '/sparepart/';
            redirect($base . 'auth/login.php');
        }
    }

    // Alias biar kompatibel dengan kode lama
    function cekLogin() { requireLogin(); }

    function currentUserId() { return $_SESSION['user_id'] ?? null; }
    function currentUser()   { return $_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'Guest'; }
    function isAdmin()       { return ($_SESSION['user_role'] ?? '') === 'admin'; }

    function logActivity($conn, $aksi, $tabel, $id_record, $keterangan = '') {
        $id_user = currentUserId();
        $stmt = $conn->prepare("INSERT INTO activity_log (id_user, aksi, tabel, id_record, keterangan) VALUES (?,?,?,?,?)");
        if (!$stmt) return; // safety kalau tabel belum ada
        $stmt->bind_param("issis", $id_user, $aksi, $tabel, $id_record, $keterangan);
        $stmt->execute();
        $stmt->close();
    }

    function getLastBalance($conn, $id_sparepart) {
        $stmt = $conn->prepare("SELECT balance FROM history_transaksi WHERE id_sparepart=? ORDER BY id_transaksi DESC LIMIT 1");
        $stmt->bind_param("i", $id_sparepart);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['balance'] : 0;
    }

    function insertTransaksi($conn, $data) {
        $id_sparepart = (int)$data['id_sparepart'];
        $masuk  = (int)($data['masuk']  ?? 0);
        $keluar = (int)($data['keluar'] ?? 0);
        
        $harga        = (float)($data['harga'] ?? 0);
        $ppn_persen   = (int)($data['ppn_persen'] ?? 0);
        $ppn_nominal  = (float)($data['ppn_nominal'] ?? 0);
        $gambar       = $data['gambar'] ?? null;
        
        // PARAMETER BARU
        $id_mesin     = $data['id_mesin'] ?? null;
        $id_area      = $data['id_area'] ?? null;
        $user_peminta = $data['user_peminta'] ?? null;

        $balanceLama = getLastBalance($conn, $id_sparepart);
        $balanceBaru = $balanceLama + $masuk - $keluar;

        if ($balanceBaru < 0) {
            return ['ok'=>false, 'msg'=>"Stok tidak cukup! Balance sekarang: $balanceLama, keluar: $keluar"];
        }

        $id_supplier = $data['id_supplier'] ?? null;
        $no_po       = $data['no_po'] ?? null;
        $notes       = $data['notes'] ?? '';
        $editor      = currentUser();

        $stmt = $conn->prepare("INSERT INTO history_transaksi 
            (id_sparepart, id_supplier, no_po, masuk, keluar, harga, ppn_persen, ppn_nominal, balance, notes, gambar, id_mesin, id_area, user_peminta, editor) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        
        // Tipe bind_param: i (int), s (string), d (double)
        $stmt->bind_param("iisiiidssssssss", 
            $id_sparepart, $id_supplier, $no_po, $masuk, $keluar, 
            $harga, $ppn_persen, $ppn_nominal, $balanceBaru, $notes, $gambar, 
            $id_mesin, $id_area, $user_peminta, $editor
        );
        
        $ok = $stmt->execute();
        $id_transaksi = $conn->insert_id;
        $stmt->close();

        if ($ok) {
            $conn->query("UPDATE data_sparepart SET stok=$balanceBaru WHERE id_sparepart=$id_sparepart");
            logActivity($conn, 'CREATE', 'history_transaksi', $id_transaksi, 
                "Transaksi id=$id_transaksi, masuk=$masuk, keluar=$keluar, balance=$balanceBaru");
            return ['ok'=>true, 'id'=>$id_transaksi, 'balance'=>$balanceBaru];
        }
        return ['ok'=>false, 'msg'=>'Gagal menyimpan transaksi'];
    }
    
    function updateStatusPO($conn, $id_order) {
    $q_detail = $conn->query("SELECT status FROM order_detail WHERE id_order = $id_order");
    
    $total = $q_detail->num_rows;
    $draft = 0; $ordered = 0; $received_partial = 0; $received = 0;
    
    while($d = $q_detail->fetch_assoc()) {
        if ($d['status'] === 'draft') $draft++;
        elseif ($d['status'] === 'ordered') $ordered++;
        elseif ($d['status'] === 'received_partial') $received_partial++;
        elseif ($d['status'] === 'received') $received++;
    }

    $new_status = 'perlu_order'; // Default

    if ($total > 0) {
        if ($received == $total) {
            $new_status = 'selesai';
        } elseif ($draft == 0) {
            // Kalau semua item udah di-order, cek apakah ada yang diterima
            if ($received_partial > 0 || $received > 0) {
                $new_status = 'terima_partial';
            } else {
                $new_status = 'order_partial'; // Semua diorder, belum ada yang datang
            }
        } else {
            // Kalau masih ada draft, cek apakah ada yang udah diorder
            if ($ordered > 0 || $received > 0 || $received_partial > 0) {
                $new_status = 'order_partial';
            } else {
                $new_status = 'perlu_order';
            }
        }
    }

    $conn->query("UPDATE orders SET status = '$new_status' WHERE id_order = $id_order");
}
    /**
     * Fungsi untuk mencatat aktivitas user ke tabel log_aktivitas
     * 
     * @param mysqli $conn Koneksi database
     * @param string $aksi 'TAMBAH', 'EDIT', 'HAPUS', 'LOGIN', 'LOGOUT'
     * @param string $modul 'Sparepart', 'Kategori', 'Transaksi', 'Order'
     * @param string $keterangan Detail aktivitas
     */
    if (!function_exists('catatLog')) {
        function catatLog($conn, $aksi, $modul, $keterangan) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $id_user   = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0;
            $nama_user = $_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'Sistem';
            
            // Deteksi IP
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($ip_list[0]);
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            }
            if ($ip == '::1') $ip = '127.0.0.1 (Localhost)';
            
            // Deteksi User Agent (Browser/Device)
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            // Batasi panjang UA biar ga error (TEXT max 65535 char)
            $ua = substr($ua, 0, 500);

            $stmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, nama_user, aksi, modul, keterangan, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $id_user, $nama_user, $aksi, $modul, $keterangan, $ip, $ua);
                $stmt->execute();
            }
        }
    }

} // end guard
?>