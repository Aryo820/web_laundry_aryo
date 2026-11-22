<?php
$pesanGagal = [
    'nama'    => 'Nama toko wajib diisi, maksimal 50 karakter.',
    'alamat'  => 'Alamat wajib diisi, maksimal 100 karakter.',
    'telepon' => 'Nomor telepon tidak valid. Gunakan 6-20 digit, spasi, tanda hubung, atau tanda kurung.',
    'email'   => 'Alamat email tidak valid, maksimal 50 karakter.',
    'simpan'  => 'Pengaturan gagal disimpan. Silakan coba lagi.',
    'csrf'    => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
];

$querySetting = mysqli_query($config, "SELECT * FROM setting ORDER BY id ASC LIMIT 1");
$rowSetting = $querySetting ? mysqli_fetch_assoc($querySetting) : null;

if (isset($_POST['save_setting'])) {
    if (!csrfValid()) {
        header('location:?page=setting&gagal=csrf');
        exit;
    }

    $name    = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $address = trim(is_string($_POST['address'] ?? null) ? $_POST['address'] : '');
    $phone   = trim(is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '');
    $email   = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');

    $errorCode = '';

    if ($name === '' || mb_strlen($name) > 50) {
        $errorCode = 'nama';
    } elseif ($address === '' || mb_strlen($address) > 100) {
        $errorCode = 'alamat';
    } elseif (!preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
        $errorCode = 'telepon';
    } elseif ($email !== '' && (mb_strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errorCode = 'email';
    }

    if ($errorCode !== '') {
        header('location:?page=setting&gagal=' . $errorCode);
        exit;
    }

    $nameSafe    = mysqli_real_escape_string($config, $name);
    $addressSafe = mysqli_real_escape_string($config, $address);
    $phoneSafe   = mysqli_real_escape_string($config, $phone);
    $emailSafe   = mysqli_real_escape_string($config, $email);

    if ($rowSetting) {
        $simpan = mysqli_query($config, "UPDATE setting SET name='$nameSafe', address='$addressSafe', phone='$phoneSafe', email='$emailSafe', update_at=NOW() WHERE id='" . (int) $rowSetting['id'] . "'");
    } else {
        $simpan = mysqli_query($config, "INSERT INTO setting (name, address, phone, email, create_at, update_at) VALUES ('$nameSafe', '$addressSafe', '$phoneSafe', '$emailSafe', NOW(), NOW())");
    }

    if (!$simpan) {
        header('location:?page=setting&gagal=simpan');
        exit;
    }

    header('location:?page=setting&simpan=berhasil');
    exit;
}

$simpanStatus = is_string($_GET['simpan'] ?? null) ? $_GET['simpan'] : '';
$gagal        = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';
$notifSukses  = $simpanStatus === 'berhasil' ? 'Pengaturan toko berhasil disimpan.' : '';
$notifGagal   = $pesanGagal[$gagal] ?? '';

$namaToko    = (string) ($rowSetting['name'] ?? '');
$alamatToko  = (string) ($rowSetting['address'] ?? '');
$teleponToko = (string) ($rowSetting['phone'] ?? '');
$emailToko   = (string) ($rowSetting['email'] ?? '');
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pengaturan Toko</h5>
                    <?php if ($notifSukses !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($notifSukses, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <?php if ($notifGagal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($notifGagal, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif ?>
                    <form action="" method="post">
                        <?php echo csrfField(); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Toko</label>
                                    <input type="text" name="name" class="form-control" maxlength="50" required value="<?php echo htmlspecialchars($namaToko, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="phone" class="form-control" maxlength="20" required value="<?php echo htmlspecialchars($teleponToko, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($emailToko, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2" maxlength="100" required><?php echo htmlspecialchars($alamatToko, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <button type="submit" name="save_setting" class="btn btn-primary">Simpan</button>
                    </form>
                    <p class="text-muted small mt-3 mb-0">Identitas ini dipakai pada struk pembayaran.</p>
                </div>
            </div>
        </div>
    </div>
</section>
