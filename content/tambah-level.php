<?php
$isEdit = isset($_GET['edit']);
$editId = $isEdit ? (int) $_GET['edit'] : 0;
$row = null;
$error = '';

if ($isEdit) {
    $query = mysqli_query($config, "SELECT * FROM level WHERE id='$editId' AND deleted_at IS NULL");
    $row = $query ? mysqli_fetch_assoc($query) : null;

    if (!$row) {
        header('location:?page=level&gagal=tidak-ditemukan');
        exit;
    }
}

$form = [
    'level_name' => $row['level_name'] ?? '',
];

if (isset($_POST['save'])) {
    $form['level_name'] = is_string($_POST['level_name'] ?? null) ? trim($_POST['level_name']) : '';

    if ($form['level_name'] === '') {
        $error = 'Nama level wajib diisi.';
    } elseif (mb_strlen($form['level_name']) > 50) {
        $error = 'Nama level maksimal 50 karakter.';
    } else {
        $name = mysqli_real_escape_string($config, $form['level_name']);
        $kondisiId = $isEdit ? " AND id != '$editId'" : '';
        $queryDuplikat = mysqli_query($config, "SELECT id FROM level WHERE deleted_at IS NULL AND level_name='$name'$kondisiId LIMIT 1");

        if ($queryDuplikat && mysqli_num_rows($queryDuplikat) > 0) {
            $error = 'Nama level sudah ada. Gunakan nama lain.';
        } else {
            if ($isEdit) {
                $simpan = mysqli_query($config, "UPDATE level SET level_name='$name' WHERE id='$editId' AND deleted_at IS NULL");
                $tujuan = '?page=level&ubah=berhasil';
            } else {
                $simpan = mysqli_query($config, "INSERT INTO level (level_name) VALUES ('$name')");
                $tujuan = '?page=level&tambah=berhasil';
            }

            if ($simpan) {
                header('location:' . $tujuan);
                exit;
            }

            $error = 'Level gagal disimpan. Silakan coba lagi.';
        }
    }
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $isEdit ? 'Edit' : 'Add'; ?> Level</h5>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="" class="form-label">Name Level</label>
                            <input name="level_name" type="text" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($form['level_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your name level" required>
                        </div>
                        <div class="mb-3">
                            <button name="save" type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
