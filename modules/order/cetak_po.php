<?php
require_once '../../includes/auth.php';
requireLogin();

$id_order = (int)($_GET['id'] ?? 0);

// Ambil data order
$order = $conn->query("
    SELECT o.*, s.nama_supplier 
    FROM orders o 
    LEFT JOIN order_detail d ON d.id_order = o.id_order 
    LEFT JOIN supplier s ON s.id_supplier = d.id_supplier 
    WHERE o.id_order = $id_order 
    GROUP BY o.id_order
")->fetch_assoc();

if (!$order) { die('PO tidak ditemukan'); }

// Ambil detail barang
$details = $conn->query("
    SELECT d.*, sp.part_number, sp.nama_sparepart 
    FROM order_detail d 
    JOIN data_sparepart sp ON sp.id_sparepart = d.id_sparepart 
    WHERE d.id_order = $id_order
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak PO: <?= $order['no_po'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>PT. MODERN PLASINDO MUTIARA</h2>
        <p>Laporan Pembelian Barang (Purchase Order)</p>
    </div>

    <div class="info">
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 150px;"><b>No PO</b></td>
                <td style="border: none;">: <?= htmlspecialchars($order['no_po']) ?></td>
                <td style="border: none; width: 150px;"><b>Supplier</b></td>
                <td style="border: none;">: <?= htmlspecialchars($order['nama_supplier'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="border: none;"><b>Tanggal PO</b></td>
                <td style="border: none;">: <?= date('d/m/Y', strtotime($order['tanggal'])) ?></td>
                <td style="border: none;"><b>No Invoice</b></td>
                <td style="border: none;">: <?= htmlspecialchars($order['no_invoice'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="30">No</th>
                <th>Part Number</th>
                <th>Nama Sparepart</th>
                <th class="text-center">Qty Diterima</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">PPN</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $grandTotal = 0; 
            while($d = $details->fetch_assoc()): 
                // Hanya hitung yang sudah datang (qty_diterima > 0)
                if ($d['qty_diterima'] <= 0) continue;

                $subtotal = $d['qty_diterima'] * $d['harga'];
                $ppn = $subtotal * (($d['ppn_persen'] ?? 0) / 100);
                $total = $subtotal + $ppn;
                $grandTotal += $total;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['part_number']) ?></td>
                <td><?= htmlspecialchars($d['nama_sparepart']) ?></td>
                <td class="text-center"><?= $d['qty_diterima'] ?></td>
                <td class="text-end">Rp <?= number_format($d['harga'], 0, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($ppn, 0, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($total, 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-end">Grand Total</th>
                <th class="text-end">Rp <?= number_format($grandTotal, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>