<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Order tidak ditemukan atau sudah dihapus.',
    'lunas'           => 'Order yang sudah lunas tidak bisa dihapus.',
];

// Hapus order memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_order'])) {
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

$queryOrder = mysqli_query($config, "SELECT trans_order.id, trans_order.order_code, trans_order.order_date, trans_order.order_status, customer.customer_name
FROM trans_order
LEFT JOIN customer ON trans_order.id_customer = customer.id
WHERE trans_order.deleted_at IS NULL
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
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rowOrder as $order): ?>
                                    <?php $isLunas = (int) $order['order_status'] === 1; ?>
                                    <tr>
                                        <td><a href="?page=tambah-order&detail=<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if ($isLunas): ?>
                                                <span class="badge bg-success">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Baru</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <a href="print.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-warning">Print</a>
                                            <?php if (!$isLunas): ?>
                                                <form action="?page=order" method="post" class="d-inline" onsubmit="return confirm('Hapus order ini?');">
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
