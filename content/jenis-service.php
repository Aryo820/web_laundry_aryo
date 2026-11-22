<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Jenis service tidak ditemukan atau sudah dihapus.',
    'simpan'          => 'Jenis service gagal disimpan. Silakan coba lagi.',
    'csrf'            => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
];

// Hapus jenis service memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_service'])) {
    if (!csrfValid()) {
        header('location:?page=jenis-service&gagal=csrf');
        exit;
    }

    $id_service = (int) $_POST['delete_service'];
    $dihapus = false;

    if ($id_service > 0) {
        $now = date('Y-m-d H:i:s');
        $delete = mysqli_query($config, "UPDATE type_of_service SET deleted_at='$now' WHERE id='$id_service' AND deleted_at IS NULL");
        $dihapus = $delete && mysqli_affected_rows($config) > 0;
    }

    header('location:?page=jenis-service&' . ($dihapus ? 'hapus=berhasil' : 'gagal=tidak-ditemukan'));
    exit;
}

$queryService = mysqli_query($config, "SELECT * FROM type_of_service WHERE deleted_at IS NULL ORDER BY id DESC");
$rowService = $queryService ? mysqli_fetch_all($queryService, MYSQLI_ASSOC) : [];

$tambah = is_string($_GET['tambah'] ?? null) ? $_GET['tambah'] : '';
$ubah   = is_string($_GET['ubah'] ?? null) ? $_GET['ubah'] : '';
$hapus  = is_string($_GET['hapus'] ?? null) ? $_GET['hapus'] : '';
$gagal  = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';

$notifSukses = '';
if ($tambah === 'berhasil') {
    $notifSukses = 'Jenis service berhasil ditambahkan.';
} elseif ($ubah === 'berhasil') {
    $notifSukses = 'Jenis service berhasil diubah.';
} elseif ($hapus === 'berhasil') {
    $notifSukses = 'Jenis service berhasil dihapus.';
}

$notifGagal = $pesanGagal[$gagal] ?? '';
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Type Service</h5>
                    <?php if ($notifSukses !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($notifGagal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <div class="table-responsive">
                        <div class="mb-3" align="right">
                            <a href="?page=tambah-jenis-service" class="btn btn-primary">Add Type Service</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rowService as $index => $service): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int) $service['price']; ?></td>
                                        <td><?php echo htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="?page=tambah-jenis-service&edit=<?php echo (int) $service['id']; ?>" class="btn btn-warning">Edit</a>
                                            <form action="?page=jenis-service" method="post" class="d-inline" onsubmit="return confirm('Hapus jenis service ini? Order lama tetap menyimpan harga saat transaksi.');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="delete_service" value="<?php echo (int) $service['id']; ?>">
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
