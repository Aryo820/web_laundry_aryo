<?php
$isEdit = isset($_GET['edit']);
$editId = $isEdit ? (int) $_GET['edit'] : 0;
$row = null;
$error = '';

if ($isEdit) {
    $query = mysqli_query($config, "SELECT * FROM type_of_service WHERE id='$editId' AND deleted_at IS NULL");
    $row = $query ? mysqli_fetch_assoc($query) : null;

    if (!$row) {
        header('location:?page=jenis-service&gagal=tidak-ditemukan');
        exit;
    }
}

$form = [
    'service_name' => $row['service_name'] ?? '',
    'price'        => $row['price'] ?? '',
    'description'  => $row['description'] ?? '',
];

if (isset($_POST['save'])) {
    $form['service_name'] = is_string($_POST['service_name'] ?? null) ? trim($_POST['service_name']) : '';
    $form['price']        = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
    $form['description']  = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';

    if ($form['service_name'] === '') {
        $error = 'Nama service wajib diisi.';
    } elseif (mb_strlen($form['service_name']) > 50) {
        $error = 'Nama service maksimal 50 karakter.';
    } elseif (!preg_match('/^\d{1,9}$/', $form['price'])) {
        $error = 'Harga wajib berupa angka bulat 0-999.999.999 tanpa titik atau koma.';
    } elseif ($form['description'] === '') {
        $error = 'Deskripsi wajib diisi.';
    } else {
        $name        = mysqli_real_escape_string($config, $form['service_name']);
        $price       = (int) $form['price'];
        $description = mysqli_real_escape_string($config, $form['description']);

        if ($isEdit) {
            $simpan = mysqli_query($config, "UPDATE type_of_service SET service_name='$name', price='$price', description='$description' WHERE id='$editId' AND deleted_at IS NULL");
            $tujuan = '?page=jenis-service&ubah=berhasil';
        } else {
            $simpan = mysqli_query($config, "INSERT INTO type_of_service (service_name, price, description) VALUES ('$name', '$price', '$description')");
            $tujuan = '?page=jenis-service&tambah=berhasil';
        }

        if ($simpan) {
            header('location:' . $tujuan);
            exit;
        }

        $error = 'Jenis service gagal disimpan. Silakan coba lagi.';
    }
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $isEdit ? 'Edit' : 'Add'; ?> Jenis Service</h5>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="" class="form-label">Name Service</label>
                            <input name="service_name" type="text" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($form['service_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your name service" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Price</label>
                            <input name="price" type="number" min="0" max="999999999" step="1" class="form-control" value="<?php echo htmlspecialchars($form['price'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your price" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Description</label>
                            <textarea name="description" id="" cols="30" rows="10" class="form-control" required><?php echo htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
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
