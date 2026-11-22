<?php
$id_level = (int) ($_SESSION['id_level'] ?? 0);
$namaSession = htmlspecialchars((string) ($_SESSION['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$bisaCariOrder = in_array($id_level, [9, 10], true);
$kataCari = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
$row = ['level_name' => '-'];

if ($id_level) {
  $query = mysqli_query($config, "SELECT * FROM level WHERE id='" . (int) $id_level . "'");
  if ($query) {
    $level = mysqli_fetch_assoc($query);
    if ($level) {
      $row = $level;
    }
  }
}
?>
<header id="header" class="fixed-top d-flex align-items-center header">

  <div class="d-flex align-items-center justify-content-between">
    <a href="home.php" class="d-flex align-items-center logo">
      <div class="rotate-n-15 sidebar-brand-icon"><img class="" src="img/text.gif"></div>
      <span class="d-lg-block d-none">Laundry Aryo</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <?php if ($bisaCariOrder): ?>
    <div class="search-bar">
      <form class="d-flex align-items-center search-form" method="GET" action="home.php">
        <input type="hidden" name="page" value="order">
        <input type="text" name="q" maxlength="50" placeholder="Cari kode, nama, atau telepon"
          title="Cari kode order, nama customer, atau nomor telepon"
          value="<?php echo htmlspecialchars($kataCari, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div><!-- End Search Bar -->
  <?php endif ?>

  <nav class="ms-auto header-nav">
    <ul class="d-flex align-items-center">

      <?php if ($bisaCariOrder): ?>
        <li class="d-block nav-item d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle" href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon-->
      <?php endif ?>

      <li class="pe-3 nav-item dropdown">

        <a class="d-flex align-items-center pe-0 nav-link nav-profile" href="#" data-bs-toggle="dropdown">
          <span class="d-md-block ps-2 d-none dropdown-toggle"><?php echo $namaSession ?></span>
        </a><!-- End Profile Iamge Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo $namaSession ?></h6>
            <span><?php echo htmlspecialchars($row['level_name'] ?? '-') ?></span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="d-flex align-items-center dropdown-item" href="logout.php">
              <i class="bi-box-arrow-right bi"></i>
              <span>Sign Out</span>
            </a>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header>