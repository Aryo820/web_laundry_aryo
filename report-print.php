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

// Cetak laporan hanya untuk level yang boleh membuka menu Report (9 dan 11)
$idUser = (int) ($_SESSION['id'] ?? 0);
$queryUser = $idUser > 0 ? mysqli_query($config, "SELECT id_level, name FROM user WHERE id='$idUser'") : null;
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

$filter     = filterLaporan();
$where      = laporanWhere($config, $filter);
$ringkasan  = laporanRingkasan($config, $where);
$rowOrder   = laporanOrder($config, $where);
$rowLayanan = laporanLayanan($config, $where);

$querySetting = mysqli_query($config, "SELECT name, address, phone, email FROM setting ORDER BY id ASC LIMIT 1");
$rowSetting   = $querySetting ? mysqli_fetch_assoc($querySetting) : null;

$namaToko    = (string) ($rowSetting['name'] ?? 'Laundry');
$alamatToko  = (string) ($rowSetting['address'] ?? '');
$teleponToko = (string) ($rowSetting['phone'] ?? '');
$emailToko   = (string) ($rowSetting['email'] ?? '');

$tautanKembali = 'home.php?page=report&' . queryLaporan($filter);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan <?php echo htmlspecialchars($namaToko, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .tombol {
            margin-bottom: 16px;
        }

        .tombol a,
        .tombol button {
            padding: 6px 12px;
            font-size: 12px;
            border: 1px solid #444;
            background: #f5f5f5;
            color: #000;
            cursor: pointer;
            text-decoration: none;
        }

        .kop {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .kop h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .kop p {
            margin: 2px 0;
        }

        .judul {
            text-align: center;
            margin: 16px 0 2px;
            font-size: 15px;
            font-weight: bold;
        }

        .periode {
            text-align: center;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 5px 6px;
            vertical-align: top;
        }

        th {
            background: #eee;
            text-align: left;
        }

        .angka {
            text-align: right;
        }

        tfoot td {
            font-weight: bold;
            background: #f5f5f5;
        }

        .ringkasan {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .ringkasan div {
            border: 1px solid #444;
            padding: 6px 10px;
        }

        .catatan {
            margin-top: 10px;
            font-size: 11px;
            color: #333;
        }

        .footer {
            margin-top: 24px;
            font-size: 11px;
            text-align: right;
        }

        @media print {
            body {
                padding: 0;
            }

            .tombol {
                display: none;
            }

            @page {
                size: A4;
                margin: 14mm;
            }
        }
    </style>
</head>

<body>
    <div class="tombol">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="<?php echo htmlspecialchars($tautanKembali, ENT_QUOTES, 'UTF-8'); ?>">Kembali ke laporan</a>
    </div>

    <div class="kop">
        <h1><?php echo htmlspecialchars($namaToko, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($alamatToko !== ''): ?>
            <p><?php echo htmlspecialchars($alamatToko, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif ?>
        <?php if ($teleponToko !== '' || $emailToko !== ''): ?>
            <p>
                <?php echo htmlspecialchars($teleponToko, ENT_QUOTES, 'UTF-8'); ?>
                <?php echo ($teleponToko !== '' && $emailToko !== '') ? ' | ' : ''; ?>
                <?php echo htmlspecialchars($emailToko, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif ?>
    </div>

    <div class="judul">Laporan Transaksi Laundry</div>
    <div class="periode"><?php echo htmlspecialchars(keteranganLaporan($filter), ENT_QUOTES, 'UTF-8'); ?></div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Estimasi</th>
                <th class="angka">Qty (kg)</th>
                <th class="angka">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rowOrder)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada order pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $totalGram = 0; $totalRupiah = 0; ?>
                <?php foreach ($rowOrder as $index => $order): ?>
                    <?php
                    $totalGram   += (int) $order['gram'];
                    $totalRupiah += (float) $order['total'];
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo formatTanggalSingkat($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo formatTanggalSingkat($order['order_end_date']); ?></td>
                        <td class="angka"><?php echo formatKg($order['gram']); ?></td>
                        <td class="angka"><?php echo rupiah($order['total']); ?></td>
                        <td><?php echo labelStatusBayar($order['order_status']); ?> / <?php echo labelStatusCucian($order['laundry_status']); ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
        <?php if (!empty($rowOrder)): ?>
            <tfoot>
                <tr>
                    <td colspan="5" class="angka">Total</td>
                    <td class="angka"><?php echo formatKg($totalGram); ?></td>
                    <td class="angka"><?php echo rupiah($totalRupiah); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif ?>
    </table>

    <div class="ringkasan">
        <div>Jumlah order: <strong><?php echo number_format($ringkasan['total_order'], 0, ',', '.'); ?></strong></div>
        <div>Total cucian: <strong><?php echo formatKg($ringkasan['gram']); ?> kg</strong></div>
        <div>Pendapatan lunas: <strong><?php echo rupiah($ringkasan['pendapatan']); ?></strong></div>
        <div>Belum dibayar: <strong><?php echo rupiah($ringkasan['piutang']); ?></strong></div>
    </div>

    <h3 style="font-size: 13px; margin: 20px 0 0;">Rekap per Layanan</h3>
    <table>
        <thead>
            <tr>
                <th>Layanan</th>
                <th class="angka">Qty (kg)</th>
                <th class="angka">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rowLayanan)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">Belum ada layanan pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $totalLayananGram = 0; $totalLayananSubtotal = 0; ?>
                <?php foreach ($rowLayanan as $layanan): ?>
                    <?php
                    $totalLayananGram     += (int) $layanan['gram'];
                    $totalLayananSubtotal += (float) $layanan['subtotal'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($layanan['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="angka"><?php echo formatKg($layanan['gram']); ?></td>
                        <td class="angka"><?php echo rupiah($layanan['subtotal']); ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
        <?php if (!empty($rowLayanan)): ?>
            <tfoot>
                <tr>
                    <td class="angka">Total</td>
                    <td class="angka"><?php echo formatKg($totalLayananGram); ?></td>
                    <td class="angka"><?php echo rupiah($totalLayananSubtotal); ?></td>
                </tr>
            </tfoot>
        <?php endif ?>
    </table>

    <p class="catatan">Subtotal memakai harga snapshot saat order dibuat, bukan harga layanan saat ini.</p>

    <div class="footer">
        Dicetak oleh <?php echo htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8'); ?>
        pada <?php echo date('d/m/Y H:i'); ?>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>
