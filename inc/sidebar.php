<?php
// Pastikan session dan koneksi database sudah ada dari file utama
$navbarID = $_SESSION['id'];
$queryNavbar = mysqli_query($config, "SELECT * FROM user WHERE id = '$navbarID'");
$dataNavbar = mysqli_fetch_assoc($queryNavbar);

?>



<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">
    <?php if ($dataNavbar['id_level'] == 9) : ?>
      <li <?= !isset($_GET['page']) || ($_GET['page'] == 'dashboard') ? 'class="active"' : '' ?> class="nav-item">
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
          <li <?= (isset($_GET['page']) && in_array($_GET['page'], ['customer', 'tambah-customer'])) ? 'class="active"' : '' ?>>
            <a href="?page=customer">
              <i class="bi bi-circle"></i><span>Customer</span>
            </a>
          </li>
          <li>
            <a href="?page=jenis-service">
              <i class="bi bi-circle"></i><span>Jenis Service</span>
            </a>
          </li>
          <li>
            <a href="?page=level">
              <i class="bi bi-circle"></i><span>Level</span>
            </a>
          </li>
          <li>
            <a href="?page=user">
              <i class="bi bi-circle"></i><span>User</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

    <?php elseif ($dataNavbar['id_level'] == 10) : ?>
      <li class="nav-heading">Pages</li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="?page=order">
          <i class="bi bi-person"></i>
          <span>Order</span>
        </a>
      </li><!-- End Profile Page Nav -->
    <?php elseif ($dataNavbar['id_level'] == 11) : ?>
      <li class="nav-item">
        <a class="nav-link collapsed" href="?page=report">
          <i class="bi bi-bar-chart"></i>
          <span>Report</span>
        </a>
      </li>
  </ul>
<?php endif; ?>
</aside>