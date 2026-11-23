<?php
require_once 'inc/security.php';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'config/koneksi.php';
require_once 'function.php';

// Ekspor laporan hanya untuk level yang boleh membuka menu Report (9 dan 11)
$idUser = (int) ($_SESSION['id'] ?? 0);
$queryUser = $idUser > 0 ? mysqli_query($config, "SELECT id_level FROM user WHERE id='$idUser'") : null;
$user = $queryUser ? mysqli_fetch_assoc($queryUser) : null;

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!in_array((int) $user['id_level'], [9, 11], true)) {
    header('Location: home.php');
    exit;
}

$filter    = filterLaporan();
$where     = laporanWhere($config, $filter);
$ringkasan = laporanRingkasan($config, $where);
$rowOrder  = laporanOrder($config, $where);

$querySetting = mysqli_query($config, "SELECT name FROM setting ORDER BY id ASC LIMIT 1");
$rowSetting   = $querySetting ? mysqli_fetch_assoc($querySetting) : null;
$namaToko     = (string) ($rowSetting['name'] ?? 'Laundry');

$namaFile = 'laporan-' . $filter['date'] . '_' . $filter['end_date'] . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$keluaran = fopen('php://output', 'w');

// BOM agar Excel membaca huruf beraksen dan simbol sebagai UTF-8
fwrite($keluaran, "\xEF\xBB\xBF");

fputcsv($keluaran, ['Laporan Transaksi ' . $namaToko]);
fputcsv($keluaran, ['Periode', keteranganLaporan($filter)]);
fputcsv($keluaran, []);
fputcsv($keluaran, ['No', 'Kode Order', 'Tanggal', 'Customer', 'Estimasi Selesai', 'Qty (kg)', 'Total (Rp)', 'Metode Bayar', 'Status Bayar', 'Status Cucian']);

$totalGram   = 0;
$totalRupiah = 0;

foreach ($rowOrder as $index => $order) {
    $totalGram   += (int) $order['gram'];
    $totalRupiah += (float) $order['total'];

    fputcsv($keluaran, [
        $index + 1,
        csvAman($order['order_code']),
        csvAman($order['order_date']),
        csvAman($order['customer_name'] ?? '-'),
        csvAman(formatTanggalSingkat($order['order_end_date'])),
        formatKg($order['gram']),
        (int) $order['total'],
        csvAman(labelMetodeBayar($order['payment_method'])),
        csvAman(labelStatusBayar($order['order_status'])),
        csvAman(labelStatusCucian($order['laundry_status'])),
    ]);
}

fputcsv($keluaran, []);
fputcsv($keluaran, ['', 'TOTAL', '', '', '', formatKg($totalGram), (int) $totalRupiah, '', '', '']);
fputcsv($keluaran, ['', 'Pendapatan lunas', '', '', '', '', (int) $ringkasan['pendapatan'], '', '', '']);
fputcsv($keluaran, ['', 'Belum dibayar', '', '', '', '', (int) $ringkasan['piutang'], '', '', '']);

fclose($keluaran);
exit;
