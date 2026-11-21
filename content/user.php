<?php
$pesanGagal = [
    'tidak-ditemukan' => 'User tidak ditemukan.',
    'sendiri'         => 'Anda tidak bisa menghapus akun yang sedang digunakan.',
    'simpan'          => 'User gagal disimpan. Silakan coba lagi.',
];

// Hapus user memakai POST supaya tidak terpicu oleh link/prefetch.
if (isset($_POST['delete_user'])) {
    $id_user = (int) $_POST['delete_user'];
    $gagalCode = 'tidak-ditemukan';

    if ($id_user > 0) {
        if ($id_user === (int) ($_SESSION['id'] ?? 0)) {
            $gagalCode = 'sendiri';
        } else {
            $delete = mysqli_query($config, "DELETE FROM user WHERE id='$id_user'");

            if ($delete && mysqli_affected_rows($config) > 0) {
                header('location:?page=user&hapus=berhasil');
                exit;
            }
        }
    }

    header('location:?page=user&gagal=' . $gagalCode);
    exit;
}

$queryUser = mysqli_query($config, "SELECT user.*, level.level_name FROM user LEFT JOIN level ON user.id_level = level.id ORDER BY user.id DESC");
$rowUser = $queryUser ? mysqli_fetch_all($queryUser, MYSQLI_ASSOC) : [];

$tambah = is_string($_GET['tambah'] ?? null) ? $_GET['tambah'] : '';
$ubah   = is_string($_GET['ubah'] ?? null) ? $_GET['ubah'] : '';
$hapus  = is_string($_GET['hapus'] ?? null) ? $_GET['hapus'] : '';
$gagal  = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';

$notifSukses = '';
if ($tambah === 'berhasil') {
    $notifSukses = 'User berhasil ditambahkan.';
} elseif ($ubah === 'berhasil') {
    $notifSukses = 'User berhasil diubah.';
} elseif ($hapus === 'berhasil') {
    $notifSukses = 'User berhasil dihapus.';
}

$notifGagal = $pesanGagal[$gagal] ?? '';
$idSession = (int) ($_SESSION['id'] ?? 0);
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data User</h5>
                    <?php if ($notifSukses !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($notifGagal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <div class="table-responsive">
                        <div class="mb-3" align="right">
                            <a href="?page=tambah-user" class="btn btn-primary">Add User</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rowUser as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['level_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="?page=tambah-user&edit=<?php echo (int) $user['id']; ?>" class="btn btn-warning">Edit</a>
                                            <?php if ((int) $user['id'] === $idSession): ?>
                                                <span class="badge bg-secondary">Akun Anda</span>
                                            <?php else: ?>
                                                <form action="?page=user" method="post" class="d-inline" onsubmit="return confirm('Hapus user ini secara permanen?');">
                                                    <input type="hidden" name="delete_user" value="<?php echo (int) $user['id']; ?>">
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
