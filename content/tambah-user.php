<?php
if (isset($_GET['edit'])) {
    $edit = $_GET['edit'];
    $query = mysqli_query($config, "SELECT * FROM user WHERE id='$edit'");
    $row = mysqli_fetch_assoc($query);

    if (isset($_POST['save'])) {
        $id_level = $_POST['id_level'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = empty($_POST['password']) ? $row['password'] : hashPassword($_POST['password']);

        mysqli_query($config, "UPDATE user SET id_level='$id_level', name='$name', email='$email', password='$password' WHERE id='$edit'");
        header("location:?page=user&ubah=berhasil");
        exit;
    }
} else {
    if (isset($_POST['save'])) {
        $id_level = $_POST['id_level'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        // Password wajib diisi untuk user baru; password_hash('') akan membuat akun tanpa password
        if ($_POST['password'] === '') {
            header("location:?page=tambah-user&password=kosong");
            exit;
        }

        $password = hashPassword($_POST['password']);

        mysqli_query($config, "INSERT INTO user (id_level, name, email, password) VALUES ('$id_level', '$name', '$email', '$password')");
        header("location:?page=user&tambah=berhasil");
        exit;
    }
}

$queryLevel = mysqli_query($config, "SELECT * FROM level WHERE deleted_at IS NULL ORDER BY id DESC");
$rowLevel = mysqli_fetch_all($queryLevel, MYSQLI_ASSOC);

?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add' ?> User</h5>
                    <?php if (isset($_GET['password']) && $_GET['password'] === 'kosong'): ?>
                        <div class="alert alert-danger">Password wajib diisi untuk user baru.</div>
                    <?php endif ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="" class="form-label">Level *</label>
                            <select name="id_level" id="" class="form-control">
                                <option value="">Select One</option>
                                <?php foreach ($rowLevel as $level): ?>
                                    <option <?php echo isset($_GET['edit']) ? ($level['id'] == $row['id_level'] ? 'selected' : '') : '' ?> value="<?php echo (int) $level['id'] ?>"><?php echo htmlspecialchars($level['level_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Nama *</label>
                            <input name="name" type="text" class="form-control" value="<?php echo isset($_GET['edit']) ? $row['name'] : '' ?>" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Email *</label>
                            <input name="email" type="text" class="form-control" value="<?php echo isset($_GET['edit']) ? $row['email'] : '' ?>" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Password *</label>
                            <input name="password" type="password" class="form-control" placeholder="Enter your password">
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