
<?php
/**
 * TRANG THANH TOÁN ĐẶT HÀNG
 * TECHGEAR
 * Phụ trách giao diện: Thành viên 3
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/coupon.php';
require_once __DIR__ . '/includes/cart_functions.php';

// Đồng bộ giỏ hàng từ database nếu người dùng đã đăng nhập
if (is_logged_in()) {
    $current_user_id = (int)$_SESSION['user']['id'];
    $_SESSION['cart'] = load_user_cart($pdo, $current_user_id);
}

// Ưu tiên lấy sản phẩm đã chọn từ giỏ hàng
$checkout_cart = $_SESSION['checkout_cart'] ?? ($_SESSION['cart'] ?? []);

if (!is_array($checkout_cart) || empty($checkout_cart)) {
    header('Location: cart.php');
    exit;
}

// Chỉ giữ sản phẩm có số lượng hợp lệ
$checkout_cart = array_filter(
    $checkout_cart,
    function ($quantity) {
        return (int)$quantity > 0;
    }
);

if (empty($checkout_cart)) {
    header('Location: cart.php');
    exit;
}

$ids = array_map('intval', array_keys($checkout_cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id IN ($placeholders)
");

$stmt->execute($ids);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng tiền sản phẩm
$cart_items = [];
$total_amount = 0;

foreach ($rows as $r) {
    $product_id = (int)$r['id'];

    $unit_price = (
        !empty($r['sale_price']) &&
        $r['sale_price'] < $r['price']
    ) ? $r['sale_price'] : $r['price'];

    $qty = (int)$checkout_cart[$product_id];
    $subtotal = $unit_price * $qty;

    $total_amount += $subtotal;

    $r['unit_price'] = $unit_price;
    $r['cart_qty'] = $qty;
    $r['subtotal'] = $subtotal;

    $cart_items[] = $r;
}

// Tránh tạo đơn hàng nếu sản phẩm trong giỏ không còn tồn tại
if (empty($cart_items)) {
    unset($_SESSION['checkout_cart']);
    header('Location: cart.php');
    exit;
}

// Tính lại mã giảm giá từ tổng tiền thực tế
$applied_coupon = $_SESSION['applied_coupon'] ?? '';
$coupon_discount = 0;

if ($applied_coupon !== '') {
    $coupon_result = calculate_techgear_discount(
        $applied_coupon,
        $total_amount
    );

    if ($coupon_result['valid']) {
        $coupon_discount = $coupon_result['discount'];
    } else {
        unset($_SESSION['applied_coupon']);
        $applied_coupon = '';
    }
}

$final_total = max(0, $total_amount - $coupon_discount);
$error = '';

// Kiểm tra tồn kho sơ bộ để cảnh báo sớm trước khi bấm đặt
$stock_issues = [];
foreach ($cart_items as $item) {
    $avail = (int)($item['stock'] ?? 0);
    if ($avail <= 0) {
        $stock_issues[] = $item['name'] . ' hiện đã hết hàng.';
    } elseif ($item['cart_qty'] > $avail) {
        $stock_issues[] = $item['name'] . " chỉ còn $avail sản phẩm trong kho (bạn đang đặt {$item['cart_qty']}).";
    }
}
if (!empty($stock_issues)) {
    $error = implode(' ', $stock_issues) . ' Vui lòng quay lại giỏ hàng để điều chỉnh.';
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $receiver_phone = trim($_POST['receiver_phone'] ?? '');
    $receiver_address = trim($_POST['receiver_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';
    $note = trim($_POST['note'] ?? '');

    $user_id = is_logged_in()
        ? (int)$_SESSION['user']['id']
        : null;

    // Kiểm tra thông tin người nhận & phương thức thanh toán
    if (
        $receiver_name === '' ||
        $receiver_phone === '' ||
        $receiver_address === ''
    ) {
        $error = 'Vui lòng nhập đầy đủ thông tin người nhận.';
    } elseif (!in_array($payment_method, ['COD', 'Chuyển khoản'], true)) {
        $error = 'Phương thức thanh toán không hợp lệ.';
    } else {
        // Ghi thông tin mã giảm giá vào ghi chú đơn hàng
        $order_note = $note;
        if ($applied_coupon !== '' && $coupon_discount > 0) {
            $coupon_note = sprintf(
                '[Mã giảm giá: %s | Giảm: %sđ]',
                $applied_coupon,
                number_format($coupon_discount, 0, ',', '.')
            );

            $order_note = $order_note !== ''
                ? $order_note . ' ' . $coupon_note
                : $coupon_note;
        }

        try {
            $pdo->beginTransaction();

            // Khóa và kiểm tra lại tồn kho thực tế trước khi tạo đơn
            $lock_stmt = $pdo->prepare("
                SELECT id, name, stock
                FROM products
                WHERE id IN ($placeholders)
                FOR UPDATE
            ");
            $lock_stmt->execute($ids);
            $locked_products = [];
            foreach ($lock_stmt->fetchAll(PDO::FETCH_ASSOC) as $lp) {
                $locked_products[(int)$lp['id']] = $lp;
            }

            foreach ($cart_items as $item) {
                $pid = (int)$item['id'];
                if (!isset($locked_products[$pid])) {
                    throw new RuntimeException("Sản phẩm \"{$item['name']}\" không còn tồn tại.");
                }
                $avail = (int)$locked_products[$pid]['stock'];
                if ($avail <= 0) {
                    throw new RuntimeException("Sản phẩm \"{$item['name']}\" hiện đã hết hàng.");
                }
                if ((int)$item['cart_qty'] > $avail) {
                    throw new RuntimeException("Sản phẩm \"{$item['name']}\" chỉ còn $avail sản phẩm trong kho.");
                }
            }

            // Tạo đơn hàng với tổng tiền sau khi giảm giá
            $ins = $pdo->prepare("
                INSERT INTO orders (
                    user_id,
                    receiver_name,
                    receiver_phone,
                    receiver_address,
                    note,
                    total_amount,
                    payment_method,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $ins->execute([
                $user_id,
                $receiver_name,
                $receiver_phone,
                $receiver_address,
                $order_note,
                $final_total,
                $payment_method
            ]);

            // Lấy mã đơn hàng vừa tạo
            $order_id = $pdo->lastInsertId();

            // Lưu chi tiết đơn hàng
            // Giá sản phẩm được lưu trước khi trừ coupon.
            $detail_stmt = $pdo->prepare("
                INSERT INTO order_details (
                    order_id,
                    product_id,
                    price,
                    quantity
                )
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $detail_stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['unit_price'],
                    $item['cart_qty']
                ]);
            }

            /*
             * XÓA SẢN PHẨM ĐÃ ĐẶT KHỎI GIỎ HÀNG DATABASE
             *
             * Chỉ xóa sản phẩm thuộc tài khoản hiện tại.
             * Các sản phẩm chưa được chọn thanh toán vẫn được giữ lại.
             */
            if ($user_id !== null) {

                $checkout_ids = array_map(
                    'intval',
                    array_column($cart_items, 'id')
                );

                if (!empty($checkout_ids)) {

                    $delete_placeholders = implode(
                        ',',
                        array_fill(0, count($checkout_ids), '?')
                    );

                    $delete_stmt = $pdo->prepare("
                        DELETE FROM cart_items
                        WHERE user_id = ?
                        AND product_id IN ($delete_placeholders)
                    ");

                    $delete_stmt->execute(array_merge(
                        [$user_id],
                        $checkout_ids
                    ));
                }
            }

            // Hoàn tất transaction
            // Đơn hàng và việc xóa giỏ hàng cùng thành công hoặc cùng rollback.
            $pdo->commit();

            // Đồng bộ lại giỏ hàng sau khi đặt hàng thành công
            if ($user_id !== null) {

                // Lấy lại giỏ hàng còn lại từ database
                $_SESSION['cart'] = load_user_cart($pdo, $user_id);

            } else {

                // Khách chưa đăng nhập: chỉ xóa sản phẩm đã đặt khỏi session
                foreach (array_keys($checkout_cart) as $pid) {
                    unset($_SESSION['cart'][$pid]);
                }
            }
            // Xóa dữ liệu thanh toán tạm và mã giảm giá đã sử dụng
            unset(
                $_SESSION['checkout_cart'],
                $_SESSION['applied_coupon']
            );

            // Cập nhật danh sách sản phẩm còn được chọn
            $_SESSION['selected_cart_ids'] = array_values(
                array_intersect(
                    $_SESSION['selected_cart_ids'] ?? [],
                    array_keys($_SESSION['cart'])
                )
            );

            // Nếu khách chọn chuyển khoản QR Demo
            if ($payment_method === 'Chuyển khoản') {

                $_SESSION['demo_payment_order_id'] = $order_id;

                header(
                    'Location: payment.php?order_id=' . $order_id
                );
                exit;
            }

            // Nếu khách chọn COD
            $_SESSION['flash_success'] =
                "Đặt hàng thành công! Mã đơn hàng của bạn là #$order_id.";

            header('Location: ' . (is_logged_in() ? 'my_orders.php' : 'index.php'));
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage();
        }
    }
}

// Lấy thông tin người dùng để điền sẵn biểu mẫu (giữ lại dữ liệu POST nếu có lỗi)
$user = $_SESSION['user'] ?? [
    'fullname' => '',
    'phone' => '',
    'address' => ''
];
$form_name = $_POST['receiver_name'] ?? ($user['fullname'] ?? '');
$form_phone = $_POST['receiver_phone'] ?? ($user['phone'] ?? '');
$form_address = $_POST['receiver_address'] ?? ($user['address'] ?? '');
$form_payment = $_POST['payment_method'] ?? 'COD';
$form_note = $_POST['note'] ?? '';

$page_title = 'Thanh toán đơn hàng';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-credit-card text-primary me-2"></i>
        Xác Nhận Đặt Hàng
    </h4>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="row g-4">

            <!-- THÔNG TIN NGƯỜI NHẬN -->
            <div class="col-lg-7">

                <div class="card border-0 shadow-sm rounded-4 p-4">

                    <h5 class="fw-bold mb-3">
                        Thông Tin Người Nhận
                    </h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Họ và tên người nhận *
                        </label>

                        <input
                            type="text"
                            name="receiver_name"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($form_name) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số điện thoại liên hệ *
                        </label>

                        <input
                            type="text"
                            name="receiver_phone"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($form_phone) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Địa chỉ giao hàng chi tiết *
                        </label>

                        <input
                            type="text"
                            name="receiver_address"
                            class="form-control"
                            placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành..."
                            required
                            value="<?= htmlspecialchars($form_address) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Phương thức thanh toán
                        </label>

                        <select
                            name="payment_method"
                            class="form-select"
                            required
                        >
                            <option value="COD" <?= $form_payment === 'COD' ? 'selected' : '' ?>>
                                Thanh toán khi nhận hàng (COD)
                            </option>

                            <option value="Chuyển khoản" <?= $form_payment === 'Chuyển khoản' ? 'selected' : '' ?>>
                                Chuyển khoản ngân hàng / QR
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Ghi chú đơn hàng
                        </label>

                        <textarea
                            name="note"
                            rows="2"
                            class="form-control"
                            placeholder="Ghi chú thời gian giao hàng..."
                        ><?= htmlspecialchars($form_note) ?></textarea>
                    </div>

                </div>

            </div>

            <!-- TÓM TẮT ĐƠN HÀNG -->
            <div class="col-lg-5">

                <div class="card border-0 shadow-sm rounded-4 p-4">

                    <h5 class="fw-bold mb-3">
                        Sản Phẩm Đặt Mua
                    </h5>

                    <?php foreach ($cart_items as $item): ?>

                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">

                            <div>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($item['name']) ?>
                                </div>

                                <span class="text-muted">
                                    SL: x<?= (int)$item['cart_qty'] ?>
                                </span>
                            </div>

                            <span class="fw-bold">
                                <?= format_price($item['subtotal']) ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between mt-3 mb-2">
                        <span>Tạm tính:</span>

                        <strong>
                            <?= format_price($total_amount) ?>
                        </strong>
                    </div>

                    <?php if ($applied_coupon !== '' && $coupon_discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                Mã giảm giá (<?= htmlspecialchars($applied_coupon) ?>):
                            </span>

                            <strong class="text-success">
                                -<?= format_price($coupon_discount) ?>
                            </strong>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Phí vận chuyển:</span>

                        <span class="text-success fw-semibold">
                            Miễn phí
                        </span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <span class="fw-bold">
                            Tổng thanh toán:
                        </span>

                        <span class="fs-4 fw-bold text-danger">
                            <?= format_price($final_total) ?>
                        </span>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg w-100 fw-bold"
                    >
                        <i class="bi bi-check2-circle me-1"></i>
                        Hoàn Tất Đặt Hàng
                    </button>

                    <a
                        href="cart.php"
                        class="btn btn-light w-100 mt-2"
                    >
                        Quay lại giỏ hàng
                    </a>

                </div>

            </div>

        </div>

    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
