<?php
// Ringkasan dashboard dihitung di server dari data aktif (bukan input user)
$queryRingkasan = mysqli_query($config, "
    SELECT
        (SELECT COUNT(*) FROM customer WHERE deleted_at IS NULL) AS total_customer,
        (SELECT COUNT(*) FROM type_of_service WHERE deleted_at IS NULL) AS total_service,
        (SELECT COUNT(*) FROM trans_order
            WHERE deleted_at IS NULL
              AND YEAR(order_date) = YEAR(CURDATE())
              AND MONTH(order_date) = MONTH(CURDATE())) AS order_bulan,
        (SELECT COALESCE(SUM(total), 0) FROM trans_order
            WHERE deleted_at IS NULL
              AND order_status = 1
              AND YEAR(order_date) = YEAR(CURDATE())
              AND MONTH(order_date) = MONTH(CURDATE())) AS pendapatan,
        (SELECT COUNT(*) FROM trans_order
            WHERE deleted_at IS NULL
              AND order_status = 0) AS belum_bayar
");
$ringkasan = $queryRingkasan ? mysqli_fetch_assoc($queryRingkasan) : null;

$totalCustomer = (int) ($ringkasan['total_customer'] ?? 0);
$totalService  = (int) ($ringkasan['total_service'] ?? 0);
$orderBulan    = (int) ($ringkasan['order_bulan'] ?? 0);
$pendapatan    = (int) ($ringkasan['pendapatan'] ?? 0);
$belumBayar    = (int) ($ringkasan['belum_bayar'] ?? 0);

// Lima order terbaru yang masih aktif
$queryTerbaru = mysqli_query($config, "
    SELECT trans_order.id, trans_order.order_code, trans_order.order_date, trans_order.order_end_date,
           trans_order.total, trans_order.order_status, trans_order.laundry_status, customer.customer_name
    FROM trans_order
    LEFT JOIN customer ON customer.id = trans_order.id_customer
    WHERE trans_order.deleted_at IS NULL
    ORDER BY trans_order.id DESC
    LIMIT 5
");
$rowTerbaru = $queryTerbaru ? mysqli_fetch_all($queryTerbaru, MYSQLI_ASSOC) : [];
?>
<div class="pagetitle">
    <h1>Dashboard</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <div class="col-xxl col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Order <span>| Bulan Ini</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cart"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo number_format($orderBulan, 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">order tercatat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Pendapatan <span>| Bulan Ini</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 22px;">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">dari order lunas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Customer <span>| Aktif</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo number_format($totalCustomer, 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">customer terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Jenis Service <span>| Aktif</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #6f42c1; background: #f0eafa;">
                            <i class="bi bi-tags"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo number_format($totalService, 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">layanan tersedia</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Belum Bayar <span>| Order</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #dc3545; background: #fdecee;">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo number_format($belumBayar, 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">perlu diselesaikan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-12">
            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Order Terbaru <span>| 5 terakhir</span></h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Customer</th>
                                    <th>Tanggal</th>
                                    <th>Estimasi</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowTerbaru)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada order.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rowTerbaru as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="?page=tambah-order&detail=<?php echo (int) $order['id']; ?>">
                                                    <?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php echo formatTanggalSingkat($order['order_end_date']); ?>
                                                <?php if (cucianLewatTenggat($order['order_end_date'], $order['laundry_status'])): ?>
                                                    <span class="badge bg-danger">Lewat</span>
                                                <?php endif ?>
                                            </td>
                                            <td>Rp <?php echo number_format((int) $order['total'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php echo badgeStatusBayar($order['order_status']); ?>
                                                <?php echo badgeStatusCucian($order['laundry_status']); ?>
                                            </td>
                                            <td>
                                                <a href="?page=tambah-order&detail=<?php echo (int) $order['id']; ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                                <a href="print.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-sm btn-outline-warning">Struk</a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
