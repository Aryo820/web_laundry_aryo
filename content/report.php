<?php
$query = mysqli_query($config, "SELECT * FROM trans_order WHERE deleted_at IS NULL ORDER BY order_date ASC");
$row = $query ? mysqli_fetch_all($query, MYSQLI_ASSOC) : [];
$max = count($row);

if ($max > 0) {
    $minDate = $row[0]['order_date'];
    $maxDate = $row[$max - 1]['order_date'];
} else {
    $minDate = date('Y-m-d');
    $maxDate = date('Y-m-d');
}

$tanggalValid = function ($value) {
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
};

$date = $minDate;
$end_date = $maxDate;

if (isset($_POST['filter'])) {
    $inputDate = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
    $inputEnd  = is_string($_POST['end_date'] ?? null) ? $_POST['end_date'] : '';

    if ($tanggalValid($inputDate)) {
        $date = $inputDate;
    }
    if ($tanggalValid($inputEnd)) {
        $end_date = $inputEnd;
    }

    // Tukar jika urutan tanggal terbalik
    if ($date > $end_date) {
        [$date, $end_date] = [$end_date, $date];
    }
}

$dateSafe = mysqli_real_escape_string($config, $date);
$endDateSafe = mysqli_real_escape_string($config, $end_date);

// Ambil kolom secara eksplisit supaya price/subtotal berasal dari snapshot detail,
// bukan tertimpa type_of_service.price
$queryTransOrderDetail = mysqli_query($config, "
    SELECT
        trans_order_detail.id_service,
        trans_order_detail.qty,
        trans_order_detail.price,
        trans_order_detail.subtotal,
        type_of_service.service_name,
        trans_order.order_date
    FROM trans_order_detail
    INNER JOIN trans_order ON trans_order.id = trans_order_detail.id_order
    LEFT JOIN type_of_service ON type_of_service.id = trans_order_detail.id_service
    WHERE trans_order.deleted_at IS NULL
      AND trans_order.order_date BETWEEN '$dateSafe' AND '$endDateSafe'
    ORDER BY trans_order.order_date ASC, trans_order.id ASC, trans_order_detail.id ASC
");
$rowTransOrderDetail = $queryTransOrderDetail ? mysqli_fetch_all($queryTransOrderDetail, MYSQLI_ASSOC) : [];
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data Report</h5>
                    <form action="" method="post">
                        <label for="">Start Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
                        <label for="">End Date</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <button type="submit" name="filter" class="btn btn-secondary">Filter</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Service Name</th>
                                    <th>Date</th>
                                    <th>Qty (kg)</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($rowTransOrderDetail)): ?>
                                    <?php $grandTotal = 0; ?>
                                    <?php foreach ($rowTransOrderDetail as $index => $data): ?>
                                        <tr>
                                            <td><?php echo $index + 1 ?></td>
                                            <td><?php echo htmlspecialchars($data['service_name'] ?? '-') ?></td>
                                            <td><?php echo htmlspecialchars($data['order_date']) ?></td>
                                            <td><?php echo $data['qty'] / 1000 ?></td>
                                            <td><?php echo $data['price'] ?></td>
                                            <td><?php echo (float) $data['subtotal'];
                                                $grandTotal += (float) $data['subtotal']; ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                                        <td><strong><?php echo (float) $grandTotal; ?></strong></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data transaksi untuk rentang tanggal ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
