<?php
require_once '../../includes/auth.php';
requireLogin();

$id_detail = (int)($_GET['id'] ?? 0);

// Ambil data detail + info PO + info Supplier + info Sparepart
$data = $conn->query("
    SELECT d.*, o.no_po, o.no_invoice, o.tanggal, s.nama_supplier, sp.part_number, sp.nama_sparepart
    FROM order_detail d 
    JOIN orders o ON o.id_order = d.id_order 
    JOIN data_sparepart sp ON sp.id_sparepart = d.id_sparepart
    LEFT JOIN supplier s ON s.id_supplier = d.id_supplier
    WHERE d.id_detail = $id_detail
")->fetch_assoc();

if (!$data) { die('Data item tidak ditemukan'); }

$subtotal = $data['qty_diterima'] * $data['harga'];
$ppn = $subtotal * (($data['ppn_persen'] ?? 0) / 100);
$total = $subtotal + $ppn;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Terima - <?= $data['part_number'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; }
        .box { border: 2px solid #000; padding: 20px; max-width: 600px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; }
        table { width: 100%; margin-bottom: 15px; }
        td { padding: 4px 0; vertical-align: top; }
        .label { width: 120px; font-weight: bold; }
        .total-box { text-align: right; border-top: 1px solid #000; padding-top: 10px; font-weight: bold; font-size: 14px; }
        .ttd { margin-top: 40px; display: flex; justify-content: space-between; text-align: center; }
        .ttd div { width: 40%; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body onload="window.print()">
    <div class="box">
        <div class="header">
            <h2>BUKTI TERIMA BARANG</h2>
            <div>PT. MODERN PLASINDO MUTIARA</div>
        </div>

        <table>
            <tr><td class="label">No. PO</td><td>: <?= $data['no_po'] ?></td></tr>
            <tr><td class="label">No. Invoice</td><td>: <?= $data['no_invoice'] ?? '-' ?></td></tr>
            <tr><td class="label">Tanggal</td><td>: <?= date('d/m/Y', strtotime($data['tanggal'])) ?></td></tr>
            <tr><td class="label">Supplier</td><td>: <?= $data['nama_supplier'] ?? '-' ?></td></tr>
        </table>

        <table style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0;">
            <tr>
                <td>
                    <b><?= $data['part_number'] ?></b><br>
                    <?= $data['nama_sparepart'] ?>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <h3 style="margin:0;"><?= $data['qty_diterima'] ?> pcs</h3>
                </td>
            </tr>
        </table>

        <table>
            <tr><td class="label">Harga Satuan</td><td style="text-align:right;">Rp <?= number_format($data['harga'], 0, ',', '.') ?></td></tr>
            <tr><td class="label">PPN (<?= $data['ppn_persen'] ?>%)</td><td style="text-align:right;">Rp <?= number_format($ppn, 0, ',', '.') ?></td></tr>
            <tr>
                <td colspan="2">
                    <div class="total-box">
                        Total: Rp <?= number_format($total, 0, ',', '.') ?>
                    </div>
                </td>
            </tr>
        </table>

        <div class="ttd">
            <div>Penerima</div>
            <div>Pengirim / Supplier</div>
        </div>
    </div>
</body>
</html>