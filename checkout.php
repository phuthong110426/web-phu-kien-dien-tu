<?php
/**
 * TRANG THANH TOÁN ĐẶT HÀNG & LỊCH SỬ ĐƠN HÀNG
 * Phụ trách: Thành viên 3
 */
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$rows = $stmt->fetchAll();

$cart_items = [];
$total_amount = 0;
foreach ($rows as $r) {
    $unit_price = ($r['sale_price'] && $r['sale_price'] < $r['price']) ? $r['sale_price'] : $r['price'];
    $qty = $_SESSION['cart'][$r['id']];
    $subtotal = $unit_price * $qty;
    $total_amount += $subtotal;
    $r['unit_price'] = $unit_price;
    $r['cart_qty'] = $qty;
    $r['subtotal'] = $subtotal;
    $cart_items[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $receiver_phone = trim($_POST['receiver_phone'] ?? '');
    $receiver_address = trim($_POST['receiver_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';
    $note = trim($_POST['note'] ?? '');
    $user_id = is_logged_in() ? $_SESSION['user']['id'] : null;

    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO orders (user_id, receiver_name, receiver_phone, receiver_address, note, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $ins->execute([$user_id, $receiver_name, $receiver_phone, $receiver_address, $note, $total_amount, $payment_method]);
    $order_id = $pdo->lastInsertId();

    $detail_stmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $detail_stmt->execute([$order_id, $item['id'], $item['unit_price'], $item['cart_qty']]);
    }
    $pdo->commit();

    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = "Đặt hàng thành công! Mã đơn hàng của bạn là #$order_id.";
    header('Location: my_orders.php');
    exit;
}

$user = $_SESSION['user'] ?? ['fullname' => '', 'phone' => '', 'address' => ''];
$page_title = 'Thanh toán đơn hàng';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-credit-card text-primary me-2"></i>Xác Nhận Đặt Hàng</h4>
    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">Thông Tin Người Nhận</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên người nhận *</label>
                        <input type="text" name="receiver_name" class="form-control" required value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại liên hệ *</label>
                        <input type="text" name="receiver_phone" class="form-control" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ giao hàng chi tiết *</label>
                        <input type="text" name="receiver_address" class="form-control" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành..." required value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức thanh toán</label>
                        <select name="payment_method" class="form-select">
                            <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                            <option value="Chuyển khoản">Chuyển khoản ngân hàng / VietQR</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú đơn hàng</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="Ghi chú thời gian giao hàng..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">Sản Phẩm Đặt Mua</h5>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                <span class="text-muted">SL: x<?= $item['cart_qty'] ?></span>
                            </div>
                            <span class="fw-bold"><?= format_price($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                        <span class="fw-bold">Tổng cộng:</span>
                        <span class="fs-4 fw-bold text-danger"><?= format_price($total_amount) ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Hoàn Tất Đặt Hàng
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
