<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Order tidak ditemukan atau sudah dihapus.',
    'lunas'           => 'Order yang sudah lunas tidak bisa dihapus.',
    'csrf'            => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
];

$filter = filterOrder($_GET);

// Hapus order memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_order'])) {
    if (!csrfValid()) {
        header('location:?' . queryOrder($filter) . '&gagal=csrf');
        exit;
    }

    $id_order = (int) $_POST['delete_order'];
    $gagalCode = 'tidak-ditemukan';

    if ($id_order > 0) {
        $queryCek = mysqli_query($config, "SELECT order_status FROM trans_order WHERE id='$id_order' AND deleted_at IS NULL LIMIT 1");
        $orderCek = $queryCek ? mysqli_fetch_assoc($queryCek) : null;

        if ($orderCek) {
            if ((int) $orderCek['order_status'] === 1) {
                $gagalCode = 'lunas';
            } else {
                $now = date('Y-m-d H:i:s');
                $delete = mysqli_query($config, "UPDATE trans_order SET deleted_at='$now' WHERE id='$id_order' AND deleted_at IS NULL");

                if ($delete && mysqli_affected_rows($config) > 0) {
                    header('location:?' . queryOrder($filter) . '&hapus=berhasil');
                    exit;
                }
            }
        }
    }

    header('location:?' . queryOrder($filter) . '&gagal=' . $gagalCode);
    exit;
}

$where = orderWhere($config, $filter);

$queryTotal = mysqli_query($config, "SELECT COUNT(*) AS total FROM trans_order LEFT JOIN customer ON trans_order.id_customer = customer.id WHERE $where");
$totalOrder = $queryTotal ? (int) (mysqli_fetch_assoc($queryTotal)['total'] ?? 0) : 0;

$perHalaman    = 10;
$info          = infoHalaman($totalOrder, $perHalaman, $filter['hal']);
$filter['hal'] = $info['hal'];

$queryOrder = mysqli_query($config, "SELECT trans_order.id, trans_order.order_code, trans_order.order_date, trans_order.order_end_date, trans_order.order_status, trans_order.laundry_status, trans_order.total, customer.customer_name, customer.phone
FROM trans_order
LEFT JOIN customer ON trans_order.id_customer = customer.id
WHERE $where
ORDER BY trans_order.id DESC
LIMIT {$info['offset']}, $perHalaman");
$rowOrder = $queryOrder ? mysqli_fetch_all($queryOrder, MYSQLI_ASSOC) : [];

$querySetting = mysqli_query($config, "SELECT name FROM setting ORDER BY id ASC LIMIT 1");
$setting      = $querySetting ? mysqli_fetch_assoc($querySetting) : null;
$namaToko     = $setting['name'] ?? 'Laundry';

$cari = $filter['q'];

$hapus = is_string($_GET['hapus'] ?? null) ? $_GET['hapus'] : '';
$gagal = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';

$notifSukses = $hapus === 'berhasil' ? 'Order berhasil dihapus.' : '';
$notifGagal  = $pesanGagal[$gagal] ?? '';
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data Order</h5>
                    <?php if ($notifSukses !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($notifGagal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($cari !== ''): ?>
                        <div class="alert alert-info">
                            Hasil pencarian untuk <strong><?php echo htmlspecialchars($cari, ENT_QUOTES, 'UTF-8'); ?></strong>:
                            <?php echo $info['total']; ?> order.
                            <a href="?page=order" class="alert-link">Tampilkan semua</a>
                        </div>
                    <?php endif ?>
                    <div class="mb-3" align="right">
                        <a href="?page=tambah-order" class="btn btn-primary">Add Order</a>
                    </div>
                    <form method="GET" action="home.php" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="page" value="order">
                        <?php if ($cari !== ''): ?>
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($cari, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif ?>
                        <div class="col-12 col-md-4">
                            <label for="filter-status" class="form-label">Status Bayar</label>
                            <select name="status" id="filter-status" class="form-select">
                                <option value="">Semua</option>
                                <option value="0" <?php echo $filter['status'] === '0' ? 'selected' : ''; ?>>Belum Bayar</option>
                                <option value="1" <?php echo $filter['status'] === '1' ? 'selected' : ''; ?>>Lunas</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="filter-cucian" class="form-label">Status Cucian</label>
                            <select name="cucian" id="filter-cucian" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ([0, 1, 2, 3] as $kodeCucian): ?>
                                    <option value="<?php echo $kodeCucian; ?>" <?php echo $filter['cucian'] === (string) $kodeCucian ? 'selected' : ''; ?>>
                                        <?php echo labelStatusCucian($kodeCucian); ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary">Terapkan</button>
                            <a href="?page=order" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                    <?php if ($info['total'] > 0): ?>
                        <p class="text-muted mb-2">
                            Menampilkan <?php echo $info['dari']; ?>&ndash;<?php echo $info['sampai']; ?>
                            dari <?php echo $info['total']; ?> order.
                        </p>
                    <?php endif ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>No. WA</th>
                                    <th>Tanggal</th>
                                    <th>Estimasi</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowOrder)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <?php echo $cari !== '' || $filter['status'] !== '' || $filter['cucian'] !== '' ? 'Tidak ada order yang cocok dengan filter.' : 'Belum ada order.'; ?>
                                        </td>
                                    </tr>
                                <?php endif ?>
                                <?php foreach ($rowOrder as $order): ?>
                                    <?php
                                    $isLunas = (int) $order['order_status'] === 1;
                                    $waLink  = linkWa($order['phone'] ?? '', pesanWaOrder($order, $namaToko));
                                    ?>
                                    <tr>
                                        <td class="text-nowrap"><a href="?page=tambah-order&detail=<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars(($order['phone'] ?? '') !== '' ? $order['phone'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-nowrap">
                                            <?php echo formatTanggalSingkat($order['order_end_date']); ?>
                                            <?php if (cucianLewatTenggat($order['order_end_date'], $order['laundry_status'])): ?>
                                                <span class="badge bg-danger">Lewat</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <?php echo badgeStatusBayar($order['order_status']); ?>
                                            <?php echo badgeStatusCucian($order['laundry_status']); ?>
                                        </td>
                                        <td>
                                            <?php if ($waLink !== ''): ?>
                                                <a href="<?php echo htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success">WA</a>
                                            <?php endif ?>
                                            <a href="print.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-warning">Print</a>
                                            <?php if (!$isLunas): ?>
                                                <form action="?<?php echo htmlspecialchars(queryOrder($filter), ENT_QUOTES, 'UTF-8'); ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus order ini?');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="delete_order" value="<?php echo (int) $order['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            <?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($info['total_halaman'] > 1): ?>
                        <nav aria-label="Navigasi halaman order" class="mt-3">
                            <ul class="pagination justify-content-end mb-0">
                                <li class="page-item <?php echo $info['hal'] <= 1 ? 'disabled' : ''; ?>">
                                    <?php if ($info['hal'] > 1): ?>
                                        <a class="page-link" href="?<?php echo htmlspecialchars(queryOrder($filter, $info['hal'] - 1), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                                    <?php else: ?>
                                        <span class="page-link">Previous</span>
                                    <?php endif ?>
                                </li>
                                <?php foreach (daftarHalaman($info['hal'], $info['total_halaman']) as $nomor): ?>
                                    <li class="page-item <?php echo $nomor === $info['hal'] ? 'active' : ''; ?>">
                                        <?php if ($nomor === $info['hal']): ?>
                                            <span class="page-link"><?php echo $nomor; ?></span>
                                        <?php else: ?>
                                            <a class="page-link" href="?<?php echo htmlspecialchars(queryOrder($filter, $nomor), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $nomor; ?></a>
                                        <?php endif ?>
                                    </li>
                                <?php endforeach ?>
                                <li class="page-item <?php echo $info['hal'] >= $info['total_halaman'] ? 'disabled' : ''; ?>">
                                    <?php if ($info['hal'] < $info['total_halaman']): ?>
                                        <a class="page-link" href="?<?php echo htmlspecialchars(queryOrder($filter, $info['hal'] + 1), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                                    <?php else: ?>
                                        <span class="page-link">Next</span>
                                    <?php endif ?>
                                </li>
                            </ul>
                        </nav>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</section>
