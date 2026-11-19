<?php
function tanggal($date)
{
    $waktu = strtotime($date);
    return date('d F Y', $waktu);
}

// Membuat kode order berikutnya: TR-ddmmyy-NNN
function generateOrderCode($config)
{
    $queryNoTrans = mysqli_query($config, "SELECT MAX(id) AS id_trans FROM trans_order");
    $rowNoTrans = $queryNoTrans ? mysqli_fetch_assoc($queryNoTrans) : null;
    $id_trans = ((int) ($rowNoTrans['id_trans'] ?? 0)) + 1;

    return "TR-" . date("dmy") . "-" . sprintf("%03d", $id_trans);
}

$daftarError = [
    'customer'    => 'Pilih customer terlebih dahulu.',
    'service'     => 'Tambahkan minimal satu layanan yang valid.',
    'qty'         => 'Qty harus lebih dari 0.',
    'tanggal'     => 'Tanggal order tidak valid.',
    'status'      => 'Status order tidak valid.',
    'simpan'      => 'Order gagal disimpan. Silakan coba lagi.',
    'bayar'       => 'Nominal pembayaran tidak valid.',
    'kurang'      => 'Nominal pembayaran kurang dari total tagihan.',
    'sudah-bayar' => 'Order ini sudah dibayar.',
];
$gagal = is_string($_GET['gagal'] ?? null) ? $_GET['gagal'] : '';
$errorMessage = $daftarError[$gagal] ?? '';

$id_order_detail = isset($_GET['detail']) ? (int) $_GET['detail'] : 0;
$rowOrder = null;
$rowDetail = [];

if ($id_order_detail > 0) {
    $queryOrder = mysqli_query($config, "SELECT trans_order.*, customer.customer_name FROM trans_order LEFT JOIN customer ON trans_order.id_customer = customer.id WHERE trans_order.id = '$id_order_detail'");
    $rowOrder = $queryOrder ? mysqli_fetch_assoc($queryOrder) : null;

    if (!$rowOrder) {
        header("location:?page=order");
        exit;
    }

    $queryDetail = mysqli_query($config, "SELECT trans_order_detail.*, type_of_service.service_name FROM trans_order_detail LEFT JOIN type_of_service ON trans_order_detail.id_service = type_of_service.id WHERE trans_order_detail.id_order = '$id_order_detail' ORDER BY trans_order_detail.id DESC");
    $rowDetail = $queryDetail ? mysqli_fetch_all($queryDetail, MYSQLI_ASSOC) : [];
}

if (isset($_POST['save'])) {
    $id_customer    = (int) ($_POST['id_customer'] ?? 0);
    $order_status   = (int) ($_POST['order_status'] ?? 0);
    $order_date     = trim($_POST['order_date'] ?? '');
    $order_end_date = trim($_POST['order_end_date'] ?? '');
    $id_services    = $_POST['id_service'] ?? [];
    $qtys           = $_POST['qty'] ?? [];

    $tanggalValid = function ($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    };

    $detailRows = [];
    $grandTotal = 0.0;
    $errorCode  = '';

    if ($id_customer <= 0) {
        $errorCode = 'customer';
    } elseif (!in_array($order_status, [0, 1], true)) {
        $errorCode = 'status';
    } elseif (!$tanggalValid($order_date) || !$tanggalValid($order_end_date)) {
        $errorCode = 'tanggal';
    } else {
        $queryCustomerCheck = mysqli_query($config, "SELECT id FROM customer WHERE id='$id_customer' AND deleted_at IS NULL");
        if (!$queryCustomerCheck || !mysqli_fetch_assoc($queryCustomerCheck)) {
            $errorCode = 'customer';
        }
    }

    if ($errorCode === '' && (!is_array($id_services) || count($id_services) === 0)) {
        $errorCode = 'service';
    }

    if ($errorCode === '') {
        foreach ($id_services as $i => $rawService) {
            $id_service = (int) $rawService;
            $qtyKg = (float) ($qtys[$i] ?? 0);

            if ($id_service <= 0 || $qtyKg <= 0) {
                $errorCode = 'qty';
                break;
            }

            $queryService = mysqli_query($config, "SELECT id, price FROM type_of_service WHERE id='$id_service' AND deleted_at IS NULL");
            $service = $queryService ? mysqli_fetch_assoc($queryService) : null;

            if (!$service) {
                $errorCode = 'service';
                break;
            }

            // Subtotal dihitung di server: harga dari database x qty (kg)
            $subtotal = $service['price'] * $qtyKg;

            $detailRows[] = [
                'id_service' => $id_service,
                'qty'        => (int) round($qtyKg * 1000),
                'price'      => (int) $service['price'],
                'subtotal'   => $subtotal,
            ];
            $grandTotal += $subtotal;
        }
    }

    if ($errorCode === '' && count($detailRows) === 0) {
        $errorCode = 'service';
    }

    if ($errorCode !== '') {
        header("location:?page=tambah-order&gagal=" . $errorCode);
        exit;
    }

    // Total dihitung ulang di server; grand_total dari form tidak dipakai
    $order_code = generateOrderCode($config);
    $total = (int) round($grandTotal);

    mysqli_begin_transaction($config);

    $insertOrder = mysqli_query($config, "INSERT INTO trans_order (id_customer, order_code, order_status, order_date, order_end_date, total, order_pay, order_change) VALUES ('$id_customer', '$order_code', '$order_status', '$order_date', '$order_end_date', '$total', 0, 0)");

    if (!$insertOrder) {
        mysqli_rollback($config);
        header("location:?page=tambah-order&gagal=simpan");
        exit;
    }

    $id_order = mysqli_insert_id($config);
    $detailTersimpan = true;

    foreach ($detailRows as $detail) {
        $subtotal = number_format($detail['subtotal'], 2, '.', '');
        $insertDetail = mysqli_query($config, "INSERT INTO trans_order_detail (id_order, id_service, qty, price, subtotal, notes) VALUES ('$id_order', '{$detail['id_service']}', '{$detail['qty']}', '{$detail['price']}', '$subtotal', '')");

        if (!$insertDetail) {
            $detailTersimpan = false;
            break;
        }
    }

    if (!$detailTersimpan) {
        mysqli_rollback($config);
        header("location:?page=tambah-order&gagal=simpan");
        exit;
    }

    mysqli_commit($config);

    header("location:?page=order&tambah=berhasil");
    exit;
}

if (isset($_POST['save2'])) {
    // Pembayaran hanya bisa dari halaman detail order yang valid
    if (!$rowOrder) {
        header("location:?page=order");
        exit;
    }

    $id_order      = $id_order_detail;
    $order_pay_raw = $_POST['order_pay'] ?? '';
    $total         = (int) $rowOrder['total'];
    $errorCode     = '';

    if ($rowOrder['order_status'] != 0) {
        $errorCode = 'sudah-bayar';
    } elseif (!is_numeric($order_pay_raw)) {
        $errorCode = 'bayar';
    } else {
        $order_pay = (float) $order_pay_raw;
        if ($order_pay < $total) {
            $errorCode = 'kurang';
        }
    }

    if ($errorCode !== '') {
        header("location:?page=tambah-order&detail=" . $id_order . "&gagal=" . $errorCode);
        exit;
    }

    $order_pay    = (int) round($order_pay);
    $order_change = $order_pay - $total;
    $id_customer  = (int) $rowOrder['id_customer'];
    $now          = date('Y-m-d H:i:s');

    mysqli_begin_transaction($config);

    // Kunci baris order supaya dua pembayaran bersamaan tidak lolos dua-duanya
    $queryLock = mysqli_query($config, "SELECT order_status FROM trans_order WHERE id='$id_order' FOR UPDATE");
    $rowLock   = $queryLock ? mysqli_fetch_assoc($queryLock) : null;

    if (!$rowLock || $rowLock['order_status'] != 0) {
        mysqli_rollback($config);
        header("location:?page=tambah-order&detail=" . $id_order . "&gagal=sudah-bayar");
        exit;
    }

    // Total memakai nilai dari database, bukan hidden input grand_total
    $update = mysqli_query($config, "UPDATE trans_order SET order_status=1, order_pay='$order_pay', order_change='$order_change' WHERE id='$id_order' AND order_status=0");

    if (!$update || mysqli_affected_rows($config) === 0) {
        mysqli_rollback($config);
        header("location:?page=tambah-order&detail=" . $id_order . "&gagal=sudah-bayar");
        exit;
    }

    // Cegah baris pickup ganda (data lama bisa saja sudah punya)
    $queryPickup = mysqli_query($config, "SELECT id FROM trans_laundry_pickup WHERE id_order='$id_order' LIMIT 1");
    $pickupAda   = $queryPickup && mysqli_fetch_assoc($queryPickup);

    if (!$pickupAda) {
        $insertPickup = mysqli_query($config, "INSERT INTO trans_laundry_pickup (id_order, id_customer, pickup_date, notes) VALUES ('$id_order', '$id_customer', '$now', '')");

        if (!$insertPickup) {
            mysqli_rollback($config);
            header("location:?page=tambah-order&detail=" . $id_order . "&gagal=simpan");
            exit;
        }
    }

    mysqli_commit($config);

    header("location:?page=tambah-order&detail=" . $id_order . "&status-pickup");
    exit;
}

$orderCode = generateOrderCode($config);

$queryCustomer = mysqli_query($config, "SELECT * FROM customer WHERE deleted_at IS NULL ORDER BY id DESC");
$rowCustomer = mysqli_fetch_all($queryCustomer, MYSQLI_ASSOC);

if (isset($_GET['id_customer'])) {
    $id_customer = (int) $_GET['id_customer'];
    $queryCustomers = mysqli_query($config, "SELECT * FROM customer WHERE id = '$id_customer' AND deleted_at IS NULL");
    $rowCustomers = $queryCustomers ? mysqli_fetch_assoc($queryCustomers) : null;
}

$queryServices = mysqli_query($config, "SELECT * FROM type_of_service WHERE deleted_at IS NULL ORDER BY id DESC");
$rowServices = mysqli_fetch_all($queryServices, MYSQLI_ASSOC);

?>

<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($_GET['detail'])): ?>
                        <h5 class="card-title">Detail Order</h5>
                        <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif ?>
                        <?php if (isset($_GET['status-pickup'])): ?>
                            <div class="alert alert-success">Pembayaran berhasil disimpan.</div>
                        <?php endif ?>
                        <div class="table-responsive mb-3">
                            <div class="mb-3" align="right">
                                <a href="?page=order" class="btn btn-secondary">Back</a>
                            </div>
                            <table class="table table-stripped">
                                <tr>
                                    <th>Code</th>
                                    <td>:</td>
                                    <td><?php echo $rowOrder['order_code']; ?></td>
                                    <th>Date</th>
                                    <td>:</td>
                                    <td><?php echo tanggal($rowOrder['order_date']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>:</td>
                                    <td><?php echo $rowOrder['customer_name']; ?></td>
                                    <th>End Date</th>
                                    <td>:</td>
                                    <td><?php echo tanggal($rowOrder['order_end_date']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>:</td>
                                    <td><?php echo $rowOrder['order_status'] == 0 ? 'Process' : 'Picked' ?></td>
                                </tr>
                            </table>
                            <br><br>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Type of Service</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total = 0; ?>
                                    <?php foreach ($rowDetail as $key => $data) { ?>
                                        <tr>
                                            <td><?php echo $key + 1; ?></td>
                                            <td><?php echo htmlspecialchars($data['service_name'] ?? '-'); ?></td>
                                            <td><?php echo $data['qty'] / 1000; ?></td>
                                            <td><?php echo $data['price']; ?></td>
                                            <td><?php echo (float) $data['subtotal'];
                                                $total += (float) $data['subtotal']; ?></td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td colspan="4">Total</td>
                                        <td><?php echo (float) $total; ?></td>
                                    </tr>
                                    <?php if (isset($_GET['detail'])) { ?>
                                        <?php if ($rowOrder['order_status'] == 1) { ?>

                                            <tr>
                                                <td colspan="4">Pay</td>
                                                <td><?php echo $rowOrder['order_pay']; ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4">Change</td>
                                                <td><?php echo $rowOrder['order_change']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (isset($_GET['detail']) && !isset($_GET['print'])) { ?>
                            <?php if ($rowOrder['order_status'] == 0) { ?>
                                <div class="mb-3" align="center">
                                    <!-- Button trigger modal -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Pay</button>
                                </div>
                            <?php } ?>
                        <?php } ?>

                    <?php else: ?>
                        <h5 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add' ?> Order</h5>
                        <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif ?>
                        <form action="" method="post">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Code</label>
                                        <input readonly name="order_code" type="text" class="form-control" value="<?php echo $orderCode ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Name</label>
                                        <?php if (isset($_GET['id_customer']) && $rowCustomers) { ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($rowCustomers['customer_name']) ?>" readonly>
                                            <input type="hidden" name="id_customer" value="<?php echo $rowCustomers['id'] ?>">
                                        <?php } else { ?>
                                            <select name="id_customer" id="" class="form-control">
                                                <option value="">Select Customer</option>
                                                <?php foreach ($rowCustomer as $customer): ?>
                                                    <option <?php echo isset($_GET['edit']) ? ($customer['id'] == $row['id_customer'] ? 'selected' : '') : '' ?> value="<?php echo $customer['id'] ?>"><?php echo $customer['customer_name'] ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        <?php } ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Name Service</label>
                                        <select name="id_service" id="id_service" class="form-control">
                                            <option value="">Select Service</option>
                                            <?php foreach ($rowServices as $service): ?>
                                                <option data-price="<?php echo $service['price'] ?>" value="<?php echo $service['id'] ?>"><?php echo $service['service_name'] ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Order Date</label>
                                        <input name="order_date" type="date" class="form-control" value="<?php echo isset($_GET['edit']) ? $row['order_date'] : '' ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="" class="form-label">End Date</label>
                                        <input name="order_end_date" type="date" class="form-control" value="<?php echo isset($_GET['edit']) ? $row['order_end_date'] : '' ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="" class="form-label">Order Status</label>
                                        <select name="order_status" id="" class="form-control">
                                            <option value="">Select Status</option>
                                            <option value="0">Process</option>
                                            <option value="1">Pick Up</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div align="right" class="mb-3">
                                <button type="button" class="btn btn-primary addRow" id="addRow">Add Row</button>
                            </div>
                            <table class="table" id="myTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Service</th>
                                        <th>Qty</th>
                                        <th>Sub Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <br>
                            <p><strong>Total: Rp. <span id="grandTotal">0</span></strong></p>
                            <input type="hidden" name="grand_total" id="grandTotalInput" value="0">
                            <div class="mb-3">
                                <button name="save" type="submit" class="btn btn-success">Save</button>
                            </div>
                        </form>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal -->
<?php if ($id_order_detail > 0 && $rowOrder['order_status'] == 0): ?>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Pay Order Item</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="total" class="form-label">Total</label>
                        <input readonly type="text" id="total" class="form-control" value="<?php echo $rowOrder['total']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="pay" class="form-label">Pay</label>
                        <input type="number" step="any" min="<?php echo $rowOrder['total']; ?>" name="order_pay" id="pay" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="save2">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>
<script>
    // var, let, const
    // var : ketika nilainya tidak ada, maka tidak ada error
    // let : harus mempunyai nilai
    // const :isi nya tidak boleh berubah
    // const button = document.getElementById('addRow');
    // const button = document.getElementsByClassName('addRow');
    const button = document.querySelector('#addRow');
    const tbody = document.querySelector('#myTable tbody');
    const select = document.querySelector('#id_service');
    // button.textContent = "duar";
    // button.style.color = "red";
    const grandTotal = document.getElementById('grandTotal');
    const grandTotalInput = document.getElementById('grandTotalInput');

    let no = 1;
    button.addEventListener("click", function() {

        const selectedService = select.options[select.selectedIndex];
        const serviceValue = selectedService.value;
        if (!serviceValue) {
            alert('select service require');
            return;
        }
        const serviceName = selectedService.textContent;
        const servicePrice = selectedService.dataset.price;

        const tr = document.createElement('tr'); //<tr></tr>
        tr.innerHTML = `
        <td>${no}</td>
        <td><input type='hidden' name='id_service[]' class='id_services' value='${select.value}'>${serviceName}</td>
        <td>
            <input type='number' name='qty[]' value='1' class='qtys'>
            <input type='hidden' class='priceInput' name='price[]' value='${servicePrice}'>
        </td>
        <td><input type='hidden' name='total[]' class='totals' value='${servicePrice}'><span class='totalText'>${servicePrice}</span></td>
        <td><button class='btn btn-danger btn-sm removeRow' type='button'>Delete</button></td>`;

        tbody.appendChild(tr);
        updateGrandTotal();
        no++;

        select.value = "";


    });

    tbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest("tr").remove();
        }

        updateNumber();
        updateGrandTotal();

    });

    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('qtys')) {
            const row = e.target.closest('tr');
            const qty = parseFloat(e.target.value) || 0;
            const price = parseInt(row.querySelector('[name="price[]"]').value);

            row.querySelector('.totalText').textContent = price * qty;
            row.querySelector('.totals').value = price * qty;
            updateGrandTotal();

        }
    });

    function updateNumber() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row, index) {
            row.cells[0].textContent = index + 1;
        });

        no = rows.length + 1;
    }

    function updateGrandTotal() {
        const totalCells = tbody.querySelectorAll('.totals');
        let grand = 0;
        totalCells.forEach(function(input) {
            grand += parseInt(input.value) || 0;
        });
        grandTotal.textContent = grand.toLocaleString('id-ID');
        grandTotalInput.value = grand;
    }
</script>