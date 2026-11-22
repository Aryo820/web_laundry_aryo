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
