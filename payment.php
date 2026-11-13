
<?php
/**
 * TRANG THANH TOÁN QR DEMO
 * TECHGEAR - Mô phỏng thanh toán chuyển khoản
 */

require_once __DIR__ . '/config/db.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Lấy mã đơn hàng
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Kiểm tra mã đơn hàng trong session
if (
    $order_id <= 0 ||
    !isset($_SESSION['demo_payment_order_id']) ||
    (int)$_SESSION['demo_payment_order_id'] !== $order_id
) {
    header('Location: my_orders.php');
    exit;
}

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
      AND user_id = ?
      AND payment_method = 'Chuyển khoản'
    LIMIT 1
");

$stmt->execute([
    $order_id,
    $_SESSION['user']['id']
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Không tìm thấy đơn hàng
if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// Thông tin tài khoản ngân hàng DEMO
$bank_name = 'MB Bank (DEMO)';
$account_number = '0123456789';
$account_name = 'NGUYEN VAN A';

$amount = (int)$order['total_amount'];
$transfer_content = 'TECHGEAR DH' . $order_id;

// Dữ liệu tạo mã QR DEMO
$qr_content = "Ngan hang: $bank_name\n"
            . "So tai khoan: $account_number\n"
            . "Chu tai khoan: $account_name\n"
            . "So tien: $amount\n"
            . "Noi dung: $transfer_content";

$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
        . urlencode($qr_content);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán QR | TECHGEAR</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
        }

        .payment-card {
            background: #fff;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #86868b;
            font-size: 14px;
        }

        .payment-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        .qr-section {
            text-align: center;
        }

        .qr-section img {
            width: 280px;
            max-width: 100%;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 15px;
        }

        .qr-section p {
            margin-top: 15px;
            color: #86868b;
            font-size: 13px;
        }

        .order-info h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .info-row span:first-child {
            color: #86868b;
        }

        .info-row strong {
            text-align: right;
        }

        .amount {
            color: #0071e3;
            font-size: 22px;
        }

        .notice {
            background: #f5f5f7;
            padding: 15px;
            border-radius: 10px;
            margin-top: 22px;
            font-size: 13px;
            line-height: 1.6;
            color: #555;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 13px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-primary {
            background: #0071e3;
            color: white;
        }

        .btn-secondary {
            background: #f5f5f7;
            color: #1d1d1f;
        }

        @media (max-width: 700px) {
            .payment-content {
                grid-template-columns: 1fr;
            }

            .payment-card {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="payment-card">

        <div class="header">
            <h1>Thanh toán đơn hàng</h1>
            <p>TECHGEAR | Thanh toán chuyển khoản QR </p>
        </div>

        <div class="payment-content">

            <div class="qr-section">
                <img
                    src="<?php echo htmlspecialchars($qr_url); ?>"
                    alt="Mã QR thanh toán TECHGEAR"
                >

                <p>Quét mã QR để xem thông tin chuyển khoản</p>
            </div>

            <div class="order-info">

                <h2>Thông tin thanh toán</h2>

                <div class="info-row">
                    <span>Mã đơn hàng</span>
                    <strong>#<?php echo $order_id; ?></strong>
                </div>

                <div class="info-row">
                    <span>Ngân hàng</span>
                    <strong><?php echo htmlspecialchars($bank_name); ?></strong>
                </div>

                <div class="info-row">
                    <span>Số tài khoản</span>
                    <strong><?php echo htmlspecialchars($account_number); ?></strong>
                </div>

                <div class="info-row">
                    <span>Chủ tài khoản</span>
                    <strong><?php echo htmlspecialchars($account_name); ?></strong>
                </div>

                <div class="info-row">
                    <span>Số tiền</span>
                    <strong class="amount">
                        <?php echo number_format($amount, 0, ',', '.'); ?>₫
                    </strong>
                </div>

                <div class="info-row">
                    <span>Nội dung</span>
                    <strong><?php echo htmlspecialchars($transfer_content); ?></strong>
                </div>

                <div class="notice">
                    <strong>Lưu ý:</strong>
                    Đây là giao diện thanh toán mô phỏng phục vụ demo.
                    Thông tin ngân hàng và mã QR không dùng để giao dịch thật.
                </div>

            </div>

        </div>

        <div class="actions">
            <a href="my_orders.php" class="btn btn-primary">
                Xem đơn hàng
            </a>

            <a href="index.php" class="btn btn-secondary">
                Tiếp tục mua sắm
            </a>
        </div>

    </div>
</div>

</body>
</html>
