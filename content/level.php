<?php
$pesanGagal = [
    'tidak-ditemukan' => 'Level tidak ditemukan atau sudah dihapus.',
    'dipakai'         => 'Level tidak bisa dihapus karena masih dipakai oleh user.',
    'simpan'          => 'Level gagal disimpan. Silakan coba lagi.',
];

// Hapus level memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_level'])) {
    $id_level = (int) $_POST['delete_level'];
    $gagalCode = 'tidak-ditemukan';

    if ($id_level > 0) {
        $queryUser = mysqli_query($config, "SELECT id FROM user WHERE id_level='$id_level' LIMIT 1");

        if ($queryUser && mysqli_num_rows($queryUser) > 0) {
            $gagalCode = 'dipakai';
        } else {
            $now = date('Y-m-d H:i:s');
            $delete = mysqli_query($config, "UPDATE level SET deleted_at='$now' WHERE id='$id_level' AND deleted_at IS NULL");

            if ($delete && mysqli_affected_rows($config) > 0) {
                header('location:?page=level&hapus=berhasil');
                exit;
            }
        }
    }

    header('location:?page=level&gagal=' . $gagalCode);
    exit;
}

$queryLevel = mysqli_query($config, "SELECT * FROM level WHERE deleted_at IS NULL ORDER BY id DESC");
$rowLevel = $queryLevel ? mysqli_fetch_all($queryLevel, MYSQLI_ASSOC) : [];

$tambah = is_string($_GET['tambah'] ?? null) ? $_GET['tambah'] : '';
$ubah   = is_string($_GET['ubah'] ?? null) ? $_GET['ubah'] : '';
$hapus  = is_string($_GET['hapus'] ?? null) ? $_GET['hapus'] : '';
$gagal  = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';

$notifSukses = '';
if ($tambah === 'berhasil') {
    $notifSukses = 'Level berhasil ditambahkan.';
} elseif ($ubah === 'berhasil') {
    $notifSukses = 'Level berhasil diubah.';
} elseif ($hapus === 'berhasil') {
    $notifSukses = 'Level berhasil dihapus.';
}

$notifGagal = $pesanGagal[$gagal] ?? '';
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data Level</h5>
                    <?php if ($notifSukses !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($notifGagal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <div class="table-responsive">
                        <div class="mb-3" align="right">
                            <a href="?page=tambah-level" class="btn btn-primary">Add Level</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rowLevel as $index => $level): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($level['level_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="?page=tambah-level&edit=<?php echo (int) $level['id']; ?>" class="btn btn-warning">Edit</a>
                                            <form action="?page=level" method="post" class="d-inline" onsubmit="return confirm('Hapus level ini?');">
                                                <input type="hidden" name="delete_level" value="<?php echo (int) $level['id']; ?>">
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
