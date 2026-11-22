<?php
// Pastikan session dan koneksi database sudah ada dari file utama
$navbarID = (int) ($_SESSION['id'] ?? 0);
$queryNavbar = mysqli_query($config, "SELECT id_level FROM user WHERE id='$navbarID'");
$dataNavbar = $queryNavbar ? mysqli_fetch_assoc($queryNavbar) : null;
$levelNavbar = (int) ($dataNavbar['id_level'] ?? ($_SESSION['id_level'] ?? 0));
$halamanAktif = is_string($_GET['page'] ?? null) ? $_GET['page'] : '';
?>
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">
    <?php if ($levelNavbar === 9) : ?>
      <li class="nav-item<?= ($halamanAktif === '' || $halamanAktif === 'dashboard') ? ' active' : '' ?>">
        <a class="nav-link collapsed" href="?page=dashboard">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Master Data</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li<?= in_array($halamanAktif, ['customer', 'tambah-customer'], true) ? ' class="active"' : '' ?>>
            <a href="?page=customer">
              <i class="bi bi-circle"></i><span>Customer</span>
            </a>
          </li>
          <li<?= in_array($halamanAktif, ['jenis-service', 'tambah-jenis-service'], true) ? ' class="active"' : '' ?>>
            <a href="?page=jenis-service">
              <i class="bi bi-circle"></i><span>Jenis Service</span>
            </a>
          </li>
          <li<?= in_array($halamanAktif, ['level', 'tambah-level'], true) ? ' class="active"' : '' ?>>
            <a href="?page=level">
              <i class="bi bi-circle"></i><span>Level</span>
            </a>
          </li>
          <li<?= in_array($halamanAktif, ['user', 'tambah-user'], true) ? ' class="active"' : '' ?>>
            <a href="?page=user">
              <i class="bi bi-circle"></i><span>User</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-heading">Transaksi</li>
      <li class="nav-item<?= in_array($halamanAktif, ['order', 'tambah-order'], true) ? ' active' : '' ?>">
        <a class="nav-link collapsed" href="?page=order">
          <i class="bi bi-person"></i>
          <span>Order</span>
        </a>
      </li>
      <li class="nav-item<?= $halamanAktif === 'report' ? ' active' : '' ?>">
        <a class="nav-link collapsed" href="?page=report">
          <i class="bi bi-bar-chart"></i>
          <span>Report</span>
        </a>
      </li>

    <?php elseif ($levelNavbar === 10) : ?>
      <li class="nav-item<?= in_array($halamanAktif, ['order', 'tambah-order'], true) ? ' active' : '' ?>">
        <a class="nav-link collapsed" href="?page=order">
          <i class="bi bi-person"></i>
          <span>Order</span>
        </a>
      </li><!-- End Order Nav -->

    <?php elseif ($levelNavbar === 11) : ?>
      <li class="nav-item<?= $halamanAktif === 'report' ? ' active' : '' ?>">
        <a class="nav-link collapsed" href="?page=report">
          <i class="bi bi-bar-chart"></i>
          <span>Report</span>
        </a>
      </li><!-- End Report Nav -->
    <?php endif ?>
  </ul>
</aside>
