<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Customer tidak ditemukan atau sudah dihapus.',
    'simpan'          => 'Customer gagal disimpan. Silakan coba lagi.',
];

// Hapus customer memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_customer'])) {
    $id_customer = (int) $_POST['delete_customer'];
    $dihapus = false;

    if ($id_customer > 0) {
        $now = date('Y-m-d H:i:s');
        $delete = mysqli_query($config, "UPDATE customer SET deleted_at='$now' WHERE id='$id_customer' AND deleted_at IS NULL");
        $dihapus = $delete && mysqli_affected_rows($config) > 0;
    }

    header('location:?page=customer&' . ($dihapus ? 'hapus=berhasil' : 'gagal=tidak-ditemukan'));
    exit;
}

$queryCustomer = mysqli_query($config, "SELECT * FROM customer WHERE deleted_at IS NULL ORDER BY id DESC");
$rowCustomer = $queryCustomer ? mysqli_fetch_all($queryCustomer, MYSQLI_ASSOC) : [];

$tambah = is_string($_GET['tambah'] ?? null) ? $_GET['tambah'] : '';
$ubah   = is_string($_GET['ubah'] ?? null) ? $_GET['ubah'] : '';
$hapus  = is_string($_GET['hapus'] ?? null) ? $_GET['hapus'] : '';
$gagal  = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';

$notifSukses = '';
if ($tambah === 'berhasil') {
    $notifSukses = 'Customer berhasil ditambahkan.';
} elseif ($ubah === 'berhasil') {
    $notifSukses = 'Customer berhasil diubah.';
} elseif ($hapus === 'berhasil') {
    $notifSukses = 'Customer berhasil dihapus.';
}

$notifGagal = $pesanGagal[$gagal] ?? '';
?>
<section class="section">
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Data Customer</h5>
                <?php if ($notifSukses !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif ?>
                <?php if ($notifGagal !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif ?>
                <div class="table-responsive">
                    <div class="mb-3" align="right">
                        <a href="?page=tambah-customer" class="btn btn-primary">Add Customer</a>
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rowCustomer as $index => $customer): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="?page=tambah-order&id_customer=<?php echo (int) $customer['id']; ?>" class="btn btn-secondary">Order</a>
                                    <a href="?page=tambah-customer&edit=<?php echo (int) $customer['id']; ?>" class="btn btn-warning">Edit</a>
                                    <form action="?page=customer" method="post" class="d-inline" onsubmit="return confirm('Hapus customer ini? Data order lama tetap tersimpan.');">
                                        <input type="hidden" name="delete_customer" value="<?php echo (int) $customer['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
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
