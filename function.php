<?php
// Mengimpor file koneksi ke database
require_once 'config/koneksi.php';

// Fungsi untuk meng-handle proses login
function loginController($email, $password)
{
    global $connection; // Menggunakan variabel koneksi global

    // Query untuk mencari data user berdasarkan email yang tidak dihapus (deleted_at = 0)
    $queryLogin = mysqli_query($connection, "SELECT * FROM user WHERE email='$email' AND deleted_at=0");

    // Mengecek apakah ada data user yang ditemukan
    if (mysqli_num_rows($queryLogin) > 0) {
        // Mengambil data user dalam bentuk array asosiatif
        $rowLogin = mysqli_fetch_assoc($queryLogin);

        // Mengecek apakah password yang dimasukkan sama dengan password di database
        if ($password == $rowLogin['password']) {
            // Menyimpan data user ke dalam session
            $_SESSION['name'] = $rowLogin['name'];
            $_SESSION['id'] = $rowLogin['id'];

            return true; // Login berhasil
        } else {
            return false; // Password salah
        }
    } else {
        return false; // User tidak ditemukan
    }
}

// Fungsi untuk mengecek apakah user sudah login
function loginValidation()
{
    // Jika session id belum diset, berarti belum login
    if (!isset($_SESSION['id'])) {
        return false;
    } else {
        return true; // Sudah login
    }
}

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

// Fungsi untuk mengubah nilai status order menjadi label HTML
// Letakkan fungsi ini di file koneksi.php atau file fungsi utama Anda


// ... ini adalah kode koneksi database Anda yang sudah ada ...
// ... mysqli_connect(...) ...


// ===============================================================
// PASTE FUNGSI LENGKAP INI DI DALAM FILE koneksi.php ANDA
// ===============================================================
function getOrderStatus($status_code)
{
    switch ($status_code) {
        case 0:
            return '<span class="badge bg-primary">Belum Bayar</span>';
        case 1:
            return '<span class="badge bg-info">Lunas</span>';
        case 2:
            return '<span class="badge bg-info">Siap Diambil</span>';
        case 3:
            return '<span class="badge bg-info">Selesai</span>';
        default:
            return '<span class="badge bg-secondary">Tidak Diketahui</span>';
    }
}
