<?php
$filter     = filterLaporan();
$where      = laporanWhere($config, $filter);
$ringkasan  = laporanRingkasan($config, $where);
$rowOrder   = laporanOrder($config, $where);
$rowLayanan = laporanLayanan($config, $where);

$tautanFilter = queryLaporan($filter);
$keterangan   = keteranganLaporan($filter);
$daftarCucian = [0 => 'Antrian', 1 => 'Dicuci', 2 => 'Siap Diambil', 3 => 'Sudah Diambil'];
?>
<div class="pagetitle">
    <h1>Laporan</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active">Laporan</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filter Laporan</h5>
                    <form method="get" action="home.php" class="row g-2 align-items-end">
                        <input type="hidden" name="page" value="report">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter['date'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter['end_date'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Status Bayar</label>
                            <select name="status" class="form-select">
                                <option value="">Semua</option>
                                <option value="0" <?php echo $filter['status'] === '0' ? 'selected' : ''; ?>>Belum Bayar</option>
                                <option value="1" <?php echo $filter['status'] === '1' ? 'selected' : ''; ?>>Lunas</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Status Cucian</label>
                            <select name="cucian" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ($daftarCucian as $nilai => $label): ?>
                                    <option value="<?php echo $nilai; ?>" <?php echo $filter['cucian'] === (string) $nilai ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-12">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="?page=report" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Order <span>| Periode</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo number_format($ringkasan['total_order'], 0, ',', '.'); ?></h6>
                            <span class="text-muted small pt-2">order tercatat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Pendapatan <span>| Lunas</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 22px;"><?php echo rupiah($ringkasan['pendapatan']); ?></h6>
                            <span class="text-muted small pt-2">dari order lunas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Belum Dibayar <span>| Piutang</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="color: #dc3545; background: #fdecee;">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6 style="font-size: 22px;"><?php echo rupiah($ringkasan['piutang']); ?></h6>
                            <span class="text-muted small pt-2">belum masuk kas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Total Cucian <span>| Kg</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-basket"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?php echo formatKg($ringkasan['gram']); ?> kg</h6>
                            <span class="text-muted small pt-2">qty layanan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <h5 class="card-title mb-1">Daftar Order</h5>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div>
                            <a href="report-export.php?<?php echo htmlspecialchars($tautanFilter, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Export CSV</a>
                            <a href="report-print.php?<?php echo htmlspecialchars($tautanFilter, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">Cetak</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Customer</th>
                                    <th>Estimasi</th>
                                    <th class="text-end">Qty (kg)</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowOrder)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada order pada periode ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $totalGram = 0; $totalRupiah = 0; ?>
                                    <?php foreach ($rowOrder as $index => $order): ?>
                                        <?php
                                        $totalGram   += (int) $order['gram'];
                                        $totalRupiah += (float) $order['total'];
                                        ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo formatTanggalSingkat($order['order_date']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php echo formatTanggalSingkat($order['order_end_date']); ?>
                                                <?php if (cucianLewatTenggat($order['order_end_date'], $order['laundry_status'])): ?>
                                                    <span class="badge bg-danger">Lewat</span>
                                                <?php endif ?>
                                            </td>
                                            <td class="text-end"><?php echo formatKg($order['gram']); ?></td>
                                            <td class="text-end"><?php echo rupiah($order['total']); ?></td>
                                            <td>
                                                <?php echo badgeStatusBayar($order['order_status']); ?>
                                                <?php echo badgeStatusCucian($order['laundry_status']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong><?php echo formatKg($totalGram); ?></strong></td>
                                        <td class="text-end"><strong><?php echo rupiah($totalRupiah); ?></strong></td>
                                        <td></td>
                                    </tr>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Rekap per Layanan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th class="text-end">Qty (kg)</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowLayanan)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada layanan pada periode ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $totalLayananGram = 0; $totalLayananSubtotal = 0; ?>
                                    <?php foreach ($rowLayanan as $layanan): ?>
                                        <?php
                                        $totalLayananGram     += (int) $layanan['gram'];
                                        $totalLayananSubtotal += (float) $layanan['subtotal'];
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($layanan['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end"><?php echo formatKg($layanan['gram']); ?></td>
                                            <td class="text-end"><?php echo rupiah($layanan['subtotal']); ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                    <tr>
                                        <td class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong><?php echo formatKg($totalLayananGram); ?></strong></td>
                                        <td class="text-end"><strong><?php echo rupiah($totalLayananSubtotal); ?></strong></td>
                                    </tr>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mb-0">Subtotal memakai harga snapshot saat order dibuat, bukan harga layanan saat ini.</p>
                </div>
            </div>
        </div>
    </div>
</section>
