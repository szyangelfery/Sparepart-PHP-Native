<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (!function_exists('attemptLogin')) {
    function attemptLogin($conn, $username, $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // ⚠️ KONSISTEN: pakai key yang sama seperti index.php lama
            $_SESSION['user_id']   = $user['id_user'];
            $_SESSION['username']  = $user['username'];        // ← dipakai index.php lama
            $_SESSION['user_nama'] = $user['nama_lengkap'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['role']      = $user['role'];            // ← alias biar kompatibel
            logActivity($conn, 'LOGIN', 'users', $user['id_user'], 'Login berhasil');
            return true;
        }
        return false;
    }
}
?>