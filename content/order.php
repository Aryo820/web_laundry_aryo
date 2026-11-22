<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Order tidak ditemukan atau sudah dihapus.',
    'lunas'           => 'Order yang sudah lunas tidak bisa dihapus.',
    'csrf'            => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
];

// Hapus order memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_order'])) {
    if (!csrfValid()) {
        header('location:?page=order&gagal=csrf');
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
                    header('location:?page=order&hapus=berhasil');
                    exit;
                }
            }
        }
    }

    header('location:?page=order&gagal=' . $gagalCode);
    exit;
}

// Pencarian dari header: kode order, nama customer, atau nomor telepon
$cari = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$cari = mb_substr($cari, 0, 50);

$kondisiCari = '';
if ($cari !== '') {
    // Escape wildcard agar '%' dan '_' dicari sebagai karakter biasa
    $cariLike = addcslashes($cari, '%_');
    $cariSafe = mysqli_real_escape_string($config, $cariLike);
    $kondisiCari = " AND (trans_order.order_code LIKE '%$cariSafe%' OR customer.customer_name LIKE '%$cariSafe%' OR customer.phone LIKE '%$cariSafe%')";
}

$queryOrder = mysqli_query($config, "SELECT trans_order.id, trans_order.order_code, trans_order.order_date, trans_order.order_end_date, trans_order.order_status, trans_order.laundry_status, customer.customer_name
FROM trans_order
LEFT JOIN customer ON trans_order.id_customer = customer.id
WHERE trans_order.deleted_at IS NULL$kondisiCari
ORDER BY trans_order.id DESC");
$rowOrder = $queryOrder ? mysqli_fetch_all($queryOrder, MYSQLI_ASSOC) : [];

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
                            <?php echo count($rowOrder); ?> order.
                            <a href="?page=order" class="alert-link">Tampilkan semua</a>
                        </div>
                    <?php endif ?>
                    <div class="table-responsive">
                        <div class="mb-3" align="right">
                            <a href="?page=tambah-order" class="btn btn-primary">Add Order</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Tanggal</th>
                                    <th>Estimasi</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowOrder)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <?php echo $cari !== '' ? 'Tidak ada order yang cocok.' : 'Belum ada order.'; ?>
                                        </td>
                                    </tr>
                                <?php endif ?>
                                <?php foreach ($rowOrder as $order): ?>
                                    <?php $isLunas = (int) $order['order_status'] === 1; ?>
                                    <tr>
                                        <td><a href="?page=tambah-order&detail=<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
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
                                            <a href="print.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-warning">Print</a>
                                            <?php if (!$isLunas): ?>
                                                <form action="?page=order" method="post" class="d-inline" onsubmit="return confirm('Hapus order ini?');">
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
                </div>
            </div>
        </div>
    </div>
</section>
