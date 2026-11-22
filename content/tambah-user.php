<?php
$isEdit = isset($_GET['edit']);
$editId = $isEdit ? (int) $_GET['edit'] : 0;
$row = null;
$error = '';

if ($isEdit) {
    $query = mysqli_query($config, "SELECT * FROM user WHERE id='$editId'");
    $row = $query ? mysqli_fetch_assoc($query) : null;

    if (!$row) {
        header('location:?page=user&gagal=tidak-ditemukan');
        exit;
    }
}

$queryLevel = mysqli_query($config, "SELECT * FROM level WHERE deleted_at IS NULL ORDER BY id ASC");
$rowLevel = $queryLevel ? mysqli_fetch_all($queryLevel, MYSQLI_ASSOC) : [];

$form = [
    'id_level' => $row['id_level'] ?? '',
    'name'     => $row['name'] ?? '',
    'email'    => $row['email'] ?? '',
];

if (isset($_POST['save'])) {
    $form['id_level'] = is_numeric($_POST['id_level'] ?? null) ? (int) $_POST['id_level'] : 0;
    $form['name']     = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $form['email']    = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password         = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $queryLevelCek = mysqli_query($config, "SELECT id FROM level WHERE id='" . (int) $form['id_level'] . "' AND deleted_at IS NULL LIMIT 1");
    $levelValid = $queryLevelCek && mysqli_num_rows($queryLevelCek) > 0;

    if (!csrfValid()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$levelValid) {
        $error = 'Pilih level yang valid dan masih aktif.';
    } elseif ($form['name'] === '') {
        $error = 'Nama user wajib diisi.';
    } elseif (mb_strlen($form['name']) > 50) {
        $error = 'Nama user maksimal 50 karakter.';
    } elseif ($form['email'] === '') {
        $error = 'Email wajib diisi.';
    } elseif (mb_strlen($form['email']) > 50) {
        $error = 'Email maksimal 50 karakter.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!$isEdit && $password === '') {
        $error = 'Password wajib diisi untuk user baru.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== '' && strlen($password) > 72) {
        $error = 'Password maksimal 72 karakter.';
    } elseif ($isEdit && $editId === (int) ($_SESSION['id'] ?? 0) && (int) $row['id_level'] !== (int) $form['id_level']) {
        $error = 'Level akun sendiri tidak bisa diubah.';
    } else {
        $name     = mysqli_real_escape_string($config, $form['name']);
        $email    = mysqli_real_escape_string($config, $form['email']);
        $kondisiId = $isEdit ? " AND id != '$editId'" : '';
        $queryEmail = mysqli_query($config, "SELECT id FROM user WHERE email='$email'$kondisiId LIMIT 1");

        if ($queryEmail && mysqli_num_rows($queryEmail) > 0) {
            $error = 'Email sudah dipakai user lain.';
        } else {
            $levelId = (int) $form['id_level'];

            if ($isEdit) {
                if ($password !== '') {
                    $hash = mysqli_real_escape_string($config, hashPassword($password));
                    $simpan = mysqli_query($config, "UPDATE user SET id_level='$levelId', name='$name', email='$email', password='$hash' WHERE id='$editId'");
                } else {
                    $simpan = mysqli_query($config, "UPDATE user SET id_level='$levelId', name='$name', email='$email' WHERE id='$editId'");
                }

                $tujuan = '?page=user&ubah=berhasil';
            } else {
                $hash = mysqli_real_escape_string($config, hashPassword($password));
                $simpan = mysqli_query($config, "INSERT INTO user (id_level, name, email, password) VALUES ('$levelId', '$name', '$email', '$hash')");
                $tujuan = '?page=user&tambah=berhasil';
            }

            if ($simpan) {
                header('location:' . $tujuan);
                exit;
            }

            $error = 'User gagal disimpan. Silakan coba lagi.';
        }
    }
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $isEdit ? 'Edit' : 'Add'; ?> User</h5>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <form action="" method="post" autocomplete="off">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label for="" class="form-label">Level *</label>
                            <select name="id_level" class="form-control" required>
                                <option value="">Select One</option>
                                <?php foreach ($rowLevel as $level): ?>
                                    <option <?php echo ((int) $form['id_level'] === (int) $level['id']) ? 'selected' : ''; ?> value="<?php echo (int) $level['id']; ?>"><?php echo htmlspecialchars($level['level_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Nama *</label>
                            <input name="name" type="text" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Email *</label>
                            <input name="email" type="email" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Password <?php echo $isEdit ? '' : '*'; ?></label>
                            <input name="password" type="password" class="form-control" minlength="6" maxlength="72" autocomplete="new-password" placeholder="<?php echo $isEdit ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 6 karakter'; ?>" <?php echo $isEdit ? '' : 'required'; ?>>
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
