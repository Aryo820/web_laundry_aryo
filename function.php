<?php
// Mengimpor file koneksi ke database
require_once 'config/koneksi.php';

// Membuat hash password baru (bcrypt/argon2 mengikuti PASSWORD_DEFAULT)
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Mengecek password dengan dukungan data lama:
// 1. Hash modern (bcrypt/argon2) dari password_hash()
// 2. Hash SHA1 (40 karakter hex) dari versi lama aplikasi
// 3. Password plaintext dari data seed lama
function verifyPassword($input, $stored)
{
    if ($stored === '') {
        return false;
    }

    if (password_verify($input, $stored)) {
        return true;
    }

    if (strlen($stored) === 40 && ctype_xdigit($stored)) {
        return hash_equals($stored, sha1($input));
    }

    return hash_equals($stored, $input);
}

// Mengecek apakah hash password perlu di-upgrade ke algoritma saat ini
function passwordNeedsRehash($stored)
{
    return password_needs_rehash($stored, PASSWORD_DEFAULT);
}

// Membuat atau mengambil token CSRF milik session saat ini
function csrfToken()
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Hidden input token untuk ditempel di dalam form POST
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// Memvalidasi token CSRF dari POST; false jika kosong atau tidak cocok
function csrfValid()
{
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// Normalisasi email/IP sebelum dipakai di query percobaan login
function loginAttemptIdentity($email, $ip)
{
    return [
        'email' => mb_substr((string) $email, 0, 100),
        'ip'    => substr((string) $ip, 0, 45),
    ];
}

// Menghitung percobaan login gagal dalam jendela waktu tertentu
function loginAttemptCount($config, $email, $ip, $minutes = 15)
{
    $minutes = max(1, (int) $minutes);
    $data    = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    $query = mysqli_query($config, "SELECT COUNT(*) AS total FROM login_attempt WHERE email='$emailSafe' AND ip_address='$ipSafe' AND attempted_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE)");
    $row   = $query ? mysqli_fetch_assoc($query) : null;

    return (int) ($row['total'] ?? 0);
}

// Mencatat satu percobaan login gagal
function loginAttemptAdd($config, $email, $ip)
{
    $data      = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    mysqli_query($config, "INSERT INTO login_attempt (email, ip_address, attempted_at) VALUES ('$emailSafe', '$ipSafe', NOW())");
}

// Menghapus riwayat percobaan setelah login berhasil
function loginAttemptClear($config, $email, $ip)
{
    $data      = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    mysqli_query($config, "DELETE FROM login_attempt WHERE email='$emailSafe' AND ip_address='$ipSafe'");
}

// Membersihkan catatan lama agar tabel tidak membengkak
function loginAttemptCleanup($config, $days = 1)
{
    $days = max(1, (int) $days);

    mysqli_query($config, "DELETE FROM login_attempt WHERE attempted_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
}
