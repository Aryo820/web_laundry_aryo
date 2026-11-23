<?php
$pesanGagal = [
    'nama'    => 'Nama toko wajib diisi, maksimal 50 karakter.',
    'alamat'  => 'Alamat wajib diisi, maksimal 100 karakter.',
    'telepon' => 'Nomor telepon tidak valid. Gunakan 6-20 digit, spasi, tanda hubung, atau tanda kurung.',
    'email'   => 'Alamat email tidak valid, maksimal 50 karakter.',
    'footer'  => 'Footer struk maksimal 100 karakter.',
    'kertas'  => 'Ukuran kertas struk harus 58 atau 80 mm.',
    'logo'    => 'Logo harus berupa gambar PNG, JPEG, atau WebP maksimal 2 MB.',
    'logo-upload' => 'Logo gagal diunggah. Silakan coba lagi.',
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
    $footer  = trim(is_string($_POST['receipt_footer'] ?? null) ? $_POST['receipt_footer'] : '');
    $paper   = is_string($_POST['receipt_paper'] ?? null) ? $_POST['receipt_paper'] : '80';

    $errorCode = '';

    if ($name === '' || mb_strlen($name) > 50) {
        $errorCode = 'nama';
    } elseif ($address === '' || mb_strlen($address) > 100) {
        $errorCode = 'alamat';
    } elseif (!preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
        $errorCode = 'telepon';
    } elseif ($email !== '' && (mb_strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errorCode = 'email';
    } elseif (mb_strlen($footer) > 100) {
        $errorCode = 'footer';
    } elseif (!ukuranKertasValid($paper)) {
        $errorCode = 'kertas';
    }

    if ($errorCode !== '') {
        header('location:?page=setting&gagal=' . $errorCode);
        exit;
    }

    $folderLogo = dirname(__DIR__) . '/assets/uploads';
    $logoLama   = (string) ($rowSetting['receipt_logo'] ?? '');
    $logoBaru   = $logoLama;

    // Hanya file di dalam assets/uploads yang boleh dihapus
    $hapusLogo = function ($path) use ($folderLogo) {
        if ($path === '') {
            return;
        }

        $folder = realpath($folderLogo);
        $file   = realpath(dirname(__DIR__) . '/' . $path);

        if ($folder !== false && $file !== false && strpos($file, $folder . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
            @unlink($file);
        }
    };

    if (isset($_POST['hapus_logo']) && $logoLama !== '') {
        $logoBaru = '';
    }

    $unggah = $_FILES['logo'] ?? null;

    if (is_array($unggah) && ($unggah['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($unggah['error'] !== UPLOAD_ERR_OK || (int) ($unggah['size'] ?? 0) > 2 * 1024 * 1024) {
            header('location:?page=setting&gagal=logo');
            exit;
        }

        $info     = @getimagesize($unggah['tmp_name']);
        $mime     = $info['mime'] ?? '';
        $ekstensi = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? '';

        if ($ekstensi === '' || !is_uploaded_file($unggah['tmp_name'])) {
            header('location:?page=setting&gagal=logo');
            exit;
        }

        if (!is_dir($folderLogo) && !mkdir($folderLogo, 0777, true) && !is_dir($folderLogo)) {
            header('location:?page=setting&gagal=logo-upload');
            exit;
        }

        $namaFile = 'logo-' . bin2hex(random_bytes(8)) . '.' . $ekstensi;

        if (!move_uploaded_file($unggah['tmp_name'], $folderLogo . '/' . $namaFile)) {
            header('location:?page=setting&gagal=logo-upload');
            exit;
        }

        $logoBaru = 'assets/uploads/' . $namaFile;
    }

    $nameSafe    = mysqli_real_escape_string($config, $name);
    $addressSafe = mysqli_real_escape_string($config, $address);
    $phoneSafe   = mysqli_real_escape_string($config, $phone);
    $emailSafe   = mysqli_real_escape_string($config, $email);
    $footerSafe  = mysqli_real_escape_string($config, $footer);
    $logoSafe    = $logoBaru === '' ? 'NULL' : "'" . mysqli_real_escape_string($config, $logoBaru) . "'";
    $paperInt    = (int) $paper;

    if ($rowSetting) {
        $simpan = mysqli_query($config, "UPDATE setting SET name='$nameSafe', address='$addressSafe', phone='$phoneSafe', email='$emailSafe', receipt_logo=$logoSafe, receipt_footer='$footerSafe', receipt_paper=$paperInt, update_at=NOW() WHERE id='" . (int) $rowSetting['id'] . "'");
    } else {
        $simpan = mysqli_query($config, "INSERT INTO setting (name, address, phone, email, receipt_logo, receipt_footer, receipt_paper, create_at, update_at) VALUES ('$nameSafe', '$addressSafe', '$phoneSafe', '$emailSafe', $logoSafe, '$footerSafe', $paperInt, NOW(), NOW())");
    }

    if (!$simpan) {
        // Jangan tinggalkan file logo baru kalau database gagal menyimpan
        if ($logoBaru !== '' && $logoBaru !== $logoLama) {
            $hapusLogo($logoBaru);
        }
        header('location:?page=setting&gagal=simpan');
        exit;
    }

    // File lama baru dihapus setelah database pasti tersimpan
    if ($logoLama !== '' && $logoLama !== $logoBaru) {
        $hapusLogo($logoLama);
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
$footerToko  = (string) ($rowSetting['receipt_footer'] ?? '');
$paperToko   = ukuranKertasValid($rowSetting['receipt_paper'] ?? null) ? (string) $rowSetting['receipt_paper'] : '80';
$logoToko    = logoStruk($rowSetting ?? []);
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
                    <form action="" method="post" enctype="multipart/form-data">
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ukuran Kertas Struk</label>
                                    <select name="receipt_paper" class="form-select">
                                        <?php foreach ([58, 80] as $ukuran): ?>
                                            <option value="<?php echo $ukuran; ?>" <?php echo $paperToko === (string) $ukuran ? 'selected' : ''; ?>><?php echo labelUkuranKertas($ukuran); ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <div class="form-text">Pilih 58 mm untuk printer thermal kecil, 80 mm untuk printer standar.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Footer Struk</label>
                                    <input type="text" name="receipt_footer" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($footerToko, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Terima Kasih!!">
                                    <div class="form-text">Kosongkan untuk memakai teks bawaan "Terima Kasih!!".</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo Struk</label>
                            <?php if ($logoToko !== ''): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($logoToko, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo toko" class="img-thumbnail" style="max-height: 80px;">
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hapus_logo" id="hapus_logo" value="1">
                                    <label class="form-check-label" for="hapus_logo">Hapus logo saat menyimpan</label>
                                </div>
                            <?php endif ?>
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">PNG, JPEG, atau WebP maksimal 2 MB. Logo tampil di bagian atas struk.</div>
                        </div>
                        <button type="submit" name="save_setting" class="btn btn-primary">Simpan</button>
                    </form>
                    <p class="text-muted small mt-3 mb-0">Identitas dan tampilan ini dipakai pada struk pembayaran.</p>
                </div>
            </div>
        </div>
    </div>
</section>
