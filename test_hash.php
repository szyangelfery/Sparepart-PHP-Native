<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$coba = ['admin123', 'admin', 'password', '123456', 'admin1234', 'sparepart', '12345'];
foreach ($coba as $p) {
    echo str_pad($p, 15) . " → " . (password_verify($p, $hash) ? "✅ COCOK" : "❌ salah") . "<br>";
}