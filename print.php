<?php
require_once 'inc/security.php';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'config/koneksi.php';
require_once 'function.php';

// Struk hanya boleh dibuka oleh user yang sudah login dan masih terdaftar
$idUser = (int) ($_SESSION['id'] ?? 0);
$queryUser = $idUser > 0 ? mysqli_query($config, "SELECT id FROM user WHERE id='$idUser'") : null;

if (!$queryUser || mysqli_num_rows($queryUser) === 0) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$id_order = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_order <= 0) {
    header('Location: home.php?page=order');
    exit;
}

$querySetting = mysqli_query($config, "SELECT * FROM setting LIMIT 1");
$rowSetting = $querySetting ? mysqli_fetch_assoc($querySetting) : null;

// Kolom dipilih eksplisit agar price/subtotal memakai snapshot di trans_order_detail
$query = mysqli_query($config, "
    SELECT
        trans_order_detail.qty,
        trans_order_detail.price,
        trans_order_detail.subtotal,
        trans_order.order_code,
        trans_order.order_date,
        trans_order.order_end_date,
        trans_order.order_status,
        trans_order.payment_method,
        trans_order.order_pay,
        trans_order.order_change,
        trans_order.total,
        trans_order.created_at,
        customer.customer_name,
        type_of_service.service_name
    FROM trans_order_detail
    INNER JOIN trans_order ON trans_order_detail.id_order = trans_order.id
    LEFT JOIN customer ON trans_order.id_customer = customer.id
    LEFT JOIN type_of_service ON trans_order_detail.id_service = type_of_service.id
    WHERE trans_order.id = '$id_order'
      AND trans_order.deleted_at IS NULL
    ORDER BY trans_order_detail.id ASC
");
$row = $query ? mysqli_fetch_all($query, MYSQLI_ASSOC) : [];

// Order tidak ada / sudah dihapus / tidak punya detail
if (empty($row)) {
    header('Location: home.php?page=order');
    exit;
}

$order = $row[0];
$jam = date('H:i', strtotime($order['created_at']));

$id_level = (int) ($_SESSION['id_level'] ?? 0);
$rowLevel = ['level_name' => '-'];

if ($id_level > 0) {
    $queryLevel = mysqli_query($config, "SELECT level_name FROM level WHERE id='$id_level'");
    $level = $queryLevel ? mysqli_fetch_assoc($queryLevel) : null;
    if ($level) {
        $rowLevel = $level;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rowSetting['name'] ?? 'Laundry Aryo'); ?></title>
    <style>
        body {
            font-family: 'Courier New';
            width: 80mm;
            margin: auto;
            padding: 10px;
        }

        .struk {
            text-align: center;
        }

        .line {
            margin: 5px 0;
            border-top: 1px dashed black;
        }

        .info {
            text-align: center;
        }

        .service,
        .summary {
            text-align: left;
        }

        .service .item {
            margin-bottom: 5px;

        }

        .service .item-qty {
            display: flex;
            justify-content: space-between;
        }

        .info .row,
        .summary .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        footer {
            text-align: center;
            font-size: 13px;
            margin-top: 10px;
        }

        @media print {
            body {
                width: 80mm;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="struk">
        <div class="struk-header">
            <div class="info text-center">
                <h3>
                    <p><?php echo htmlspecialchars($rowSetting['name'] ?? '-'); ?></p>
                </h3>
                <p><?php echo htmlspecialchars($rowSetting['email'] ?? '-'); ?></p>
                <p><?php echo htmlspecialchars($rowSetting['address'] ?? '-'); ?></p>
                <p><?php echo htmlspecialchars($rowSetting['phone'] ?? '-'); ?></p>
            </div>
        </div>
        <div class="line"></div>
        <div class="info">
            <div class="row">
                <span><?php echo htmlspecialchars($order['order_date']); ?></span>
                <span><?php echo htmlspecialchars($jam); ?></span>
            </div>
            <div class="row">
                <span><?php echo htmlspecialchars($rowLevel['level_name'] ?? '-'); ?></span>
                <span><?php echo htmlspecialchars($_SESSION['name'] ?? '-'); ?></span>
            </div>
            <div class="row">
                <span>Order Id: </span>
                <span><?php echo htmlspecialchars($order['order_code']); ?></span>
            </div>
            <div class="row">
                <span>Customer Name: </span>
                <span><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></span>
            </div>
            <div class="row">
                <span>Estimasi selesai: </span>
                <span><?php echo htmlspecialchars(formatTanggalIndo($order['order_end_date'])); ?></span>
            </div>
        </div>
        <div class="line"></div>
        <?php foreach ($row as $r) { ?>
            <div class="service">
                <div class="item">
                    <strong><?php echo htmlspecialchars($r['service_name'] ?? '-'); ?></strong>
                    <div class="item-qty">
                        <span><?php echo $r['qty'] / 1000; ?> kg X @ <?php echo (float) $r['price']; ?></span>
                        <span><?php echo (float) $r['subtotal']; ?></span>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="line"></div>
        <div class="summary">
            <div class="row">
                <span>Total</span>
                <span>Rp. <?php echo (float) $order['total']; ?></span>
            </div>
            <?php if ($order['order_status'] == 1) { ?>
                <div class="row">
                    <span>Metode</span>
                    <span><?php echo htmlspecialchars(labelMetodeBayar($order['payment_method'])); ?></span>
                </div>
                <div class="row">
                    <span>Pay</span>
                    <span>Rp. <?php echo (float) $order['order_pay']; ?></span>
                </div>
                <div class="row">
                    <span>Change</span>
                    <span>Rp. <?php echo (float) $order['order_change']; ?></span>
                </div>
            <?php } ?>
        </div>
        <div class="line"></div>
        <div class="footer" class="text-center">
            Terima Kasih!!
        </div>
    </div>
    <script>
        window.print();
    </script>
</body>

</html>
