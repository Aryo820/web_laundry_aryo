<?php
$isEdit = isset($_GET['edit']);
$editId = $isEdit ? (int) $_GET['edit'] : 0;
$row = null;
$error = '';

if ($isEdit) {
    $query = mysqli_query($config, "SELECT * FROM customer WHERE id='$editId' AND deleted_at IS NULL");
    $row = $query ? mysqli_fetch_assoc($query) : null;

    if (!$row) {
        header('location:?page=customer&gagal=tidak-ditemukan');
        exit;
    }
}

$form = [
    'customer_name' => $row['customer_name'] ?? '',
    'phone'         => $row['phone'] ?? '',
    'address'       => $row['address'] ?? '',
];

if (isset($_POST['save'])) {
    $form['customer_name'] = is_string($_POST['customer_name'] ?? null) ? trim($_POST['customer_name']) : '';
    $form['phone']         = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
    $form['address']       = is_string($_POST['address'] ?? null) ? trim($_POST['address']) : '';

    if ($form['customer_name'] === '') {
        $error = 'Nama customer wajib diisi.';
    } elseif (mb_strlen($form['customer_name']) > 50) {
        $error = 'Nama customer maksimal 50 karakter.';
    } elseif (!preg_match('/^[0-9+\-\s]{6,13}$/', $form['phone'])) {
        $error = 'Nomor telepon harus 6-13 karakter, hanya angka dan simbol + - .';
    } elseif ($form['address'] === '') {
        $error = 'Alamat wajib diisi.';
    } else {
        $name    = mysqli_real_escape_string($config, $form['customer_name']);
        $phone   = mysqli_real_escape_string($config, $form['phone']);
        $address = mysqli_real_escape_string($config, $form['address']);

        if ($isEdit) {
            $simpan = mysqli_query($config, "UPDATE customer SET customer_name='$name', phone='$phone', address='$address' WHERE id='$editId' AND deleted_at IS NULL");
            $tujuan = '?page=customer&ubah=berhasil';
        } else {
            $simpan = mysqli_query($config, "INSERT INTO customer (customer_name, phone, address) VALUES ('$name', '$phone', '$address')");
            $tujuan = '?page=customer&tambah=berhasil';
        }

        if ($simpan) {
            header('location:' . $tujuan);
            exit;
        }

        $error = 'Customer gagal disimpan. Silakan coba lagi.';
    }
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $isEdit ? 'Edit' : 'Add'; ?> Customer</h5>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="" class="form-label">Name</label>
                            <input name="customer_name" type="text" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($form['customer_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Phone</label>
                            <input name="phone" type="text" inputmode="tel" class="form-control" maxlength="13" pattern="[0-9+\-\s]{6,13}" value="<?php echo htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your phone number" required>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Address</label>
                            <textarea name="address" id="" cols="30" rows="10" class="form-control" required><?php echo htmlspecialchars($form['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
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
