<?php
// Memulai session agar bisa menggunakan $_SESSION
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

// Mengaktifkan output buffering (penyimpanan output sementara sebelum dikirim ke browser)
ob_start();

// Membersihkan output buffer, menghapus semua isi buffer (jika ada output sebelumnya)
ob_clean();

// Memanggil file koneksi ke database
require_once 'config/koneksi.php';

// Memanggil file yang berisi fungsi-fungsi tambahan
require_once 'function.php';

// Mengecek apakah session 'id' kosong (belum login atau session habis)
if (empty($_SESSION['id'])) {
  // Jika belum login, arahkan pengguna ke halaman logout (biasanya akan diarahkan ke login page lagi)
  header('Location: logout.php');
  exit;
}

// Pastikan user di session masih ada; sinkronkan nama dan level terbaru
$idSession = (int) ($_SESSION['id'] ?? 0);
$querySession = mysqli_query($config, "SELECT id, name, id_level FROM user WHERE id='$idSession'");
$rowSession = $querySession ? mysqli_fetch_assoc($querySession) : null;

if (!$rowSession) {
  session_unset();
  session_destroy();
  header('Location: index.php');
  exit;
}

$_SESSION['name'] = $rowSession['name'];
$_SESSION['id_level'] = $rowSession['id_level'];

// Daftar putih halaman per level: 9 Administrator, 10 Operator, 11 Leader
$halamanPerLevel = [
  9  => ['dashboard', 'customer', 'tambah-customer', 'jenis-service', 'tambah-jenis-service', 'level', 'tambah-level', 'user', 'tambah-user', 'order', 'tambah-order', 'report'],
  10 => ['order', 'tambah-order'],
  11 => ['report'],
];
$halamanAwal = [9 => 'dashboard', 10 => 'order', 11 => 'report'];
$levelSession = (int) $_SESSION['id_level'];

// Level tidak dikenal: tutup session, jangan beri akses apa pun
if (!isset($halamanPerLevel[$levelSession])) {
  session_unset();
  session_destroy();
  header('Location: index.php');
  exit;
}

$halaman = is_string($_GET['page'] ?? null) ? $_GET['page'] : $halamanAwal[$levelSession];

// Halaman di luar hak akses dialihkan ke halaman awal sesuai level
if (!in_array($halaman, $halamanPerLevel[$levelSession], true)) {
  header('Location: ?page=' . $halamanAwal[$levelSession]);
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Laundry Aryo</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <!-- ======= Header ======= -->
  <?php include 'inc/header.php' ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include 'inc/sidebar.php' ?>
  <!-- End Sidebar-->

  <main id="main" class="main">

    <?php
    $fileHalaman = 'content/' . $halaman . '.php';

    if (file_exists($fileHalaman)) {
      include $fileHalaman;
    } else {
      include 'content/notfound.php';
    }
    ?>

  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>