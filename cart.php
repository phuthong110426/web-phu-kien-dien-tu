
<?php
/**
 * GIỎ HÀNG TECHGEAR
 * Chọn sản phẩm muốn thanh toán
 * Phụ trách: Thành viên 3
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/coupon.php';
require_once __DIR__ . '/includes/cart_functions.php';

// Khởi tạo giỏ hàng session
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xác định tài khoản đang đăng nhập
$current_user_id = is_logged_in()
    ? (int)$_SESSION['user']['id']
    : 0;

// Nếu đã đăng nhập, lấy giỏ hàng từ database
if ($current_user_id > 0) {
    $_SESSION['cart'] = load_user_cart($pdo, $current_user_id);
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$qty = max(1, (int)($_GET['qty'] ?? 1));

// Thêm sản phẩm vào giỏ hàng
if ($action === 'add' && $id > 0) {

    // Kiểm tra sản phẩm và tồn kho
    $stmt = $pdo->prepare("
        SELECT id, name, stock FROM products WHERE id = ?
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['flash_error'] = 'Sản phẩm không tồn tại.';
    } elseif ((int)$product['stock'] <= 0) {
        $_SESSION['flash_error'] = 'Sản phẩm "' . $product['name'] . '" hiện đã hết hàng!';
    } else {
        $stock = (int)$product['stock'];
        $current_qty = (int)($_SESSION['cart'][$id] ?? 0);

        if ($current_qty >= $stock) {
            $_SESSION['flash_error'] = 'Sản phẩm "' . $product['name'] . "\" đã đạt số lượng tối đa trong kho ($stock).";
        } else {
            $requested_total = $current_qty + $qty;
            $_SESSION['cart'][$id] = min($stock, $requested_total);

            // Tự động tick chọn sản phẩm mới thêm
            if (isset($_SESSION['selected_cart_ids']) && is_array($_SESSION['selected_cart_ids'])) {
                $_SESSION['selected_cart_ids'] = array_values(array_unique(array_merge(
                    array_map('intval', $_SESSION['selected_cart_ids']),
                    [$id]
                )));
            }

            unset($_SESSION['checkout_cart']);

            // Lưu vào database nếu đã đăng nhập
            if ($current_user_id > 0) {
                save_user_cart($pdo, $current_user_id, $_SESSION['cart']);
            }

            if ($requested_total > $stock) {
                $_SESSION['flash_success'] = "Đã thêm sản phẩm vào giỏ (tối đa theo tồn kho: $stock sản phẩm).";
            } else {
                $_SESSION['flash_success'] = 'Đã thêm sản phẩm vào giỏ hàng!';
            }
        }
    }

    header('Location: cart.php');
    exit;
}

// Xóa sản phẩm khỏi giỏ hàng
if ($action === 'remove' && $id > 0) {

    unset($_SESSION['cart'][$id], $_SESSION['checkout_cart']);

    // Đồng bộ database
    if ($current_user_id > 0) {
        save_user_cart($pdo, $current_user_id, $_SESSION['cart']);
    }

    // Xóa khỏi danh sách sản phẩm được chọn
    $_SESSION['selected_cart_ids'] = array_values(array_diff(
        $_SESSION['selected_cart_ids'] ?? [],
        [$id]
    ));

    $_SESSION['flash_success'] = 'Đã xóa sản phẩm khỏi giỏ hàng!';

    header('Location: cart.php');
    exit;
}

// Xử lý cập nhật giỏ hàng, coupon và đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['update_cart']) ||
        isset($_POST['checkout_selected']) ||
        isset($_POST['apply_coupon']) ||
        isset($_POST['remove_coupon'])
    ) {

        // Lấy thông tin tồn kho hiện tại của các sản phẩm trong giỏ
        $stock_map = [];
        $existing_cart_ids = array_keys($_SESSION['cart']);
        if (!empty($existing_cart_ids)) {
            $ph_stock = implode(',', array_fill(0, count($existing_cart_ids), '?'));
            $stmt_stock = $pdo->prepare("SELECT id, name, stock FROM products WHERE id IN ($ph_stock)");
            $stmt_stock->execute($existing_cart_ids);
            foreach ($stmt_stock->fetchAll(PDO::FETCH_ASSOC) as $sp) {
                $stock_map[(int)$sp['id']] = $sp;
            }
        }

        // Cập nhật số lượng sản phẩm (có giới hạn theo tồn kho)
        $stock_clamped_notes = [];
        foreach ($_POST['quantities'] ?? [] as $pid => $q) {

            $pid = (int)$pid;
            $q = (int)$q;

            if (!isset($_SESSION['cart'][$pid])) {
                continue;
            }

            $sp_info = $stock_map[$pid] ?? null;
            $avail_stock = $sp_info ? (int)$sp_info['stock'] : 0;

            if ($q <= 0 || $avail_stock <= 0) {
                unset($_SESSION['cart'][$pid]);
                if ($sp_info && $avail_stock <= 0) {
                    $stock_clamped_notes[] = $sp_info['name'] . ' (đã hết hàng nên được gỡ khỏi giỏ)';
                }
            } else {
                if ($q > $avail_stock) {
                    $q = $avail_stock;
                    $stock_clamped_notes[] = $sp_info['name'] . " (tối đa $avail_stock)";
                }
                $_SESSION['cart'][$pid] = $q;
            }
        }

        unset($_SESSION['checkout_cart']);

        // Lưu giỏ hàng sau khi cập nhật
        if ($current_user_id > 0) {
            save_user_cart($pdo, $current_user_id, $_SESSION['cart']);
        }

        // Lưu danh sách sản phẩm được chọn
        $selected_ids = array_map(
            'intval',
            $_POST['selected_products'] ?? []
        );

        $selected_ids = array_values(array_unique(array_filter(
            $selected_ids,
            function ($pid) {
                return isset($_SESSION['cart'][$pid]);
            }
        )));

        $_SESSION['selected_cart_ids'] = $selected_ids;

        // Tính tổng tiền sản phẩm được chọn
        $coupon_total = 0;
        $cart_ids = array_keys($_SESSION['cart']);

        if (!empty($cart_ids)) {

            $placeholders = implode(
                ',',
                array_fill(0, count($cart_ids), '?')
            );

            $stmt = $pdo->prepare("
                SELECT id, price, sale_price
                FROM products
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($cart_ids);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {

                $pid = (int)$product['id'];

                if (in_array($pid, $selected_ids, true)) {

                    $price = (
                        !empty($product['sale_price']) &&
                        $product['sale_price'] < $product['price']
                    ) ? $product['sale_price'] : $product['price'];

                    $coupon_total += $price * (int)$_SESSION['cart'][$pid];
                }
            }
        }

        // Áp dụng mã giảm giá
        if (isset($_POST['apply_coupon'])) {

            $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

            if (empty($selected_ids)) {

                unset($_SESSION['applied_coupon']);

                $_SESSION['coupon_message'] =
                    'Vui lòng chọn sản phẩm trước khi áp dụng mã giảm giá.';

            } else {

                $result = calculate_techgear_discount(
                    $coupon_code,
                    $coupon_total
                );

                if ($result['valid']) {

                    $_SESSION['applied_coupon'] = $coupon_code;

                    $_SESSION['coupon_message'] = $result['message'];

                } else {

                    unset($_SESSION['applied_coupon']);

                    $_SESSION['coupon_message'] = $result['message'];
                }
            }

            header('Location: cart.php');
            exit;
        }

        // Gỡ mã giảm giá
        if (isset($_POST['remove_coupon'])) {

            unset($_SESSION['applied_coupon']);

            $_SESSION['coupon_message'] = 'Đã gỡ mã giảm giá.';

            header('Location: cart.php');
            exit;
        }

        // Chuyển sản phẩm được chọn sang checkout
        if (isset($_POST['checkout_selected'])) {

            if (!empty($stock_clamped_notes)) {
                $_SESSION['flash_error'] =
                    'Số lượng đã được điều chỉnh theo tồn kho cho: ' . implode(', ', $stock_clamped_notes) . '. Vui lòng kiểm tra lại trước khi đặt hàng!';

                header('Location: cart.php');
                exit;
            }

            if (empty($selected_ids)) {

                $_SESSION['flash_error'] =
                    'Vui lòng chọn ít nhất một sản phẩm để thanh toán!';

                header('Location: cart.php');
                exit;
            }

            $_SESSION['checkout_cart'] = [];

            foreach ($selected_ids as $pid) {
                $_SESSION['checkout_cart'][$pid] =
                    $_SESSION['cart'][$pid];
            }

            header('Location: checkout.php');
            exit;
        }

        if (!empty($stock_clamped_notes)) {
            $_SESSION['flash_error'] =
                'Số lượng đã được điều chỉnh theo tồn kho cho: ' . implode(', ', $stock_clamped_notes) . '.';
        } else {
            $_SESSION['flash_success'] = 'Đã cập nhật giỏ hàng thành công!';
        }

        header('Location: cart.php');
        exit;
    }
}

// Lấy thông tin sản phẩm trong giỏ
$cart_items = [];
$total_amount = 0;
$selected_total = 0;

if (!empty($_SESSION['cart'])) {

    $ids = array_keys($_SESSION['cart']);

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id IN ($placeholders)
    ");

    $stmt->execute($ids);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {

        $unit_price = (
            !empty($r['sale_price']) &&
            $r['sale_price'] < $r['price']
        ) ? $r['sale_price'] : $r['price'];

        $quantity = (int)$_SESSION['cart'][$r['id']];

        $subtotal = $unit_price * $quantity;

        $total_amount += $subtotal;

        $r['unit_price'] = $unit_price;
        $r['cart_qty'] = $quantity;
        $r['subtotal'] = $subtotal;

        $cart_items[] = $r;
    }
}

// Mặc định chọn tất cả sản phẩm khi chưa có lựa chọn
if (!isset($_SESSION['selected_cart_ids'])) {
    $_SESSION['selected_cart_ids'] = array_keys($_SESSION['cart']);
}

// Chỉ giữ ID còn tồn tại trong giỏ
$_SESSION['selected_cart_ids'] = array_values(array_filter(
    $_SESSION['selected_cart_ids'],
    function ($pid) {
        return isset($_SESSION['cart'][$pid]);
    }
));

$selected_ids = array_map(
    'intval',
    $_SESSION['selected_cart_ids']
);

// Tính tổng tiền sản phẩm được chọn
foreach ($cart_items as $item) {

    if (in_array((int)$item['id'], $selected_ids, true)) {
        $selected_total += $item['subtotal'];
    }
}

// Kiểm tra mã giảm giá
$coupon_discount = 0;
$applied_coupon = $_SESSION['applied_coupon'] ?? '';
$coupon_config = null;

// Thông báo một lần
$flash_success = $_SESSION['flash_success'] ?? '';
$coupon_message = $_SESSION['coupon_message'] ?? '';

if ($applied_coupon !== '') {

    $coupon_result = calculate_techgear_discount(
        $applied_coupon,
        $selected_total
    );

    if ($coupon_result['valid']) {

        $coupon_discount = $coupon_result['discount'];

        $coupon_config = get_techgear_coupons()[$applied_coupon] ?? null;

    } else {

        if ($coupon_message === '') {
            $coupon_message = 'Mã giảm giá ' . $applied_coupon . ' đã được gỡ: ' . $coupon_result['message'];
        }

        unset($_SESSION['applied_coupon']);

        $applied_coupon = '';
    }
}

$final_total = max(0, $selected_total - $coupon_discount);

unset(
    $_SESSION['flash_success'],
    $_SESSION['coupon_message']
);

$page_title = 'Giỏ hàng của bạn';

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .cart-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #0d6efd;
    }

    .cart-product-image {
        width: 68px;
        height: 68px;
        object-fit: cover;
    }

    .cart-summary {
        position: sticky;
        top: 100px;
    }

    .cart-product-row {
        transition: background 0.2s;
    }

    .cart-product-row:hover {
        background: #fafafa;
    }

    .cart-total {
        color: #dc3545;
    }

    @media (max-width: 768px) {
        .cart-summary {
            position: static;
        }
    }
</style>

<div class="container my-4">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-cart3 text-primary me-2"></i>
        Giỏ Hàng Của Bạn
    </h4>

    <?php if ($flash_success !== ''): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if ($coupon_message !== ''): ?>
        <div class="alert <?= strpos($coupon_message, 'thành công') !== false || strpos($coupon_message, 'Đã gỡ') !== false ? 'alert-success' : 'alert-warning' ?>">
            <?= htmlspecialchars($coupon_message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>

        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">

            <i class="bi bi-cart-x display-3 text-muted"></i>

            <h5 class="mt-3">Giỏ hàng của bạn đang trống</h5>

            <p class="text-muted small">
                Hãy chọn thêm các mẫu tai nghe, bàn phím,
                chuột gaming yêu thích nhé!
            </p>

            <div>
                <a href="products.php" class="btn btn-primary px-4 fw-semibold">
                    Tiếp Tục Mua Sắm
                </a>
            </div>

        </div>

    <?php else: ?>

        <form method="POST" id="cartForm">

            <div class="row g-4">

                <!-- DANH SÁCH SẢN PHẨM -->
                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                        <div class="p-3 bg-light border-bottom">

                            <label class="d-flex align-items-center gap-2 fw-semibold mb-0">

                                <input
                                    type="checkbox"
                                    id="selectAll"
                                    class="cart-checkbox"
                                    <?= count($selected_ids) === count($cart_items) ? 'checked' : '' ?>
                                >

                                Chọn tất cả (<?= count($cart_items) ?> sản phẩm)

                            </label>

                        </div>

                        <div class="table-responsive">

                            <table class="table align-middle mb-0">

                                <thead class="table-light">

                                    <tr>
                                        <th style="width:45px;"></th>
                                        <th>Phụ kiện</th>
                                        <th>Đơn giá</th>
                                        <th style="width:110px;">Số lượng</th>
                                        <th>Thành tiền</th>
                                        <th></th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($cart_items as $item): ?>

                                        <tr
                                            class="cart-product-row"
                                            data-price="<?= (float)$item['unit_price'] ?>"
                                            data-stock="<?= max(1, (int)$item['stock']) ?>"
                                        >

                                            <td>

                                                <input
                                                    type="checkbox"
                                                    name="selected_products[]"
                                                    value="<?= (int)$item['id'] ?>"
                                                    class="cart-checkbox product-checkbox"
                                                    <?= in_array((int)$item['id'], $selected_ids, true) ? 'checked' : '' ?>
                                                >

                                            </td>

                                            <td>

                                                <div class="d-flex align-items-center gap-3">

                                                    <img
                                                        src="<?= htmlspecialchars($item['thumbnail'] ?? '') ?>"
                                                        class="rounded-3 border cart-product-image"
                                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                                    >

                                                    <div>

                                                        <a
                                                            href="product_detail.php?id=<?= (int)$item['id'] ?>"
                                                            class="fw-bold text-dark text-decoration-none"
                                                        >
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </a>
                                                        <?php if ((int)$item['stock'] <= 0): ?>
                                                            <small class="text-danger d-block">Sản phẩm hiện đã hết hàng</small>
                                                        <?php else: ?>
                                                            <small class="text-muted d-block">Còn <?= (int)$item['stock'] ?> sản phẩm</small>
                                                        <?php endif; ?>

                                                    </div>

                                                </div>

                                            </td>

                                            <td class="fw-semibold">
                                                <?= format_price($item['unit_price']) ?>
                                            </td>

                                            <td>

                                                <input
                                                    type="number"
                                                    name="quantities[<?= (int)$item['id'] ?>]"
                                                    value="<?= (int)$item['cart_qty'] ?>"
                                                    min="1"
                                                    max="<?= max(1, (int)$item['stock']) ?>"
                                                    class="form-control form-control-sm text-center quantity-input"
                                                >

                                            </td>

                                            <td class="fw-bold text-danger row-subtotal">
                                                <?= format_price($item['subtotal']) ?>
                                            </td>

                                            <td>

                                                <a
                                                    href="cart.php?action=remove&id=<?= (int)$item['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Xóa sản phẩm này khỏi giỏ?')"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        <div class="p-3 bg-light d-flex justify-content-between flex-wrap gap-2">

                            <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>
                                Mua thêm phụ kiện
                            </a>

                            <button
                                type="submit"
                                name="update_cart"
                                value="1"
                                class="btn btn-dark btn-sm fw-semibold"
                            >
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Cập nhật số lượng
                            </button>

                        </div>

                    </div>

                </div>

                <!-- TÓM TẮT ĐƠN HÀNG -->
                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm rounded-4 p-4 cart-summary">

                        <h5 class="fw-bold border-bottom pb-2 mb-3">
                            Tóm Tắt Đơn Hàng
                        </h5>

                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">Tổng sản phẩm trong giỏ:</span>

                            <strong><?= count($cart_items) ?></strong>

                        </div>

                        <div class="d-flex justify-content-between mb-2">

                            <span>Sản phẩm đã chọn:</span>

                            <strong id="selectedCount"><?= count($selected_ids) ?></strong>

                        </div>

                        <div class="d-flex justify-content-between mb-3">

                            <span>Tạm tính:</span>

                            <strong id="selectedTotal">
                                <?= format_price($selected_total) ?>
                            </strong>

                        </div>

                        <!-- MÃ GIẢM GIÁ -->
                        <div class="border rounded-3 p-3 mb-3">

                            <label class="form-label fw-semibold">

                                <i class="bi bi-ticket-perforated me-1"></i>
                                Mã giảm giá

                            </label>

                            <div class="input-group">

                                <input
                                    type="text"
                                    name="coupon_code"
                                    id="couponCode"
                                    class="form-control"
                                    placeholder="Nhập mã giảm giá"
                                    value="<?= htmlspecialchars($applied_coupon) ?>"
                                    <?= $applied_coupon !== '' ? 'readonly' : '' ?>
                                >

                                <?php if ($applied_coupon !== ''): ?>

                                    <button
                                        type="submit"
                                        name="remove_coupon"
                                        value="1"
                                        class="btn btn-outline-danger"
                                    >
                                        Gỡ mã
                                    </button>

                                <?php else: ?>

                                    <button
                                        type="submit"
                                        name="apply_coupon"
                                        value="1"
                                        class="btn btn-dark"
                                    >
                                        Áp dụng
                                    </button>

                                <?php endif; ?>

                            </div>

                            <?php if ($applied_coupon !== ''): ?>

                                <small id="couponStatusText" class="text-success d-block mt-2">
                                    Đang áp dụng mã <?= htmlspecialchars($applied_coupon) ?>
                                </small>

                            <?php else: ?>

                                <small id="couponStatusText" class="text-muted d-block mt-2">
                                    Nhập mã ưu đãi để được giảm giá.
                                </small>

                            <?php endif; ?>

                        </div>

                        <div class="d-flex justify-content-between mb-2">

                            <span class="text-muted">Phí vận chuyển:</span>

                            <span class="text-success fw-semibold">Miễn phí</span>

                        </div>

                        <div class="d-flex justify-content-between mb-2">

                            <span class="text-muted">Giảm giá:</span>

                            <strong class="text-success" id="couponDiscount">
                                -<?= format_price($coupon_discount) ?>
                            </strong>

                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <span class="fw-bold">Tổng thanh toán:</span>

                            <span class="fs-4 fw-bold cart-total" id="finalTotal">
                                <?= format_price($final_total) ?>
                            </span>

                        </div>

                        <button
                            type="submit"
                            name="checkout_selected"
                            value="1"
                            class="btn btn-primary btn-lg w-100 fw-bold"
                            id="checkoutButton"
                            <?= empty($selected_ids) ? 'disabled' : '' ?>
                        >
                            Tiến Hành Đặt Hàng
                            <i class="bi bi-arrow-right ms-1"></i>
                        </button>

                        <p class="text-muted small text-center mt-3 mb-0">
                            Chỉ những sản phẩm được chọn mới được thanh toán.
                        </p>

                    </div>

                </div>

            </div>

        </form>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const selectedTotal = document.getElementById('selectedTotal');
    const couponDiscount = document.getElementById('couponDiscount');
    const finalTotal = document.getElementById('finalTotal');
    const checkoutButton = document.getElementById('checkoutButton');

    const couponStatusText = document.getElementById('couponStatusText');

    if (!selectAll || !checkoutButton) {
        return;
    }

    const coupon = <?= json_encode($coupon_config ? [
        'code' => $applied_coupon,
        'type' => $coupon_config['type'],
        'value' => $coupon_config['value'],
        'min_order' => $coupon_config['min_order'],
        'max_discount' => $coupon_config['max_discount']
    ] : null) ?>;

    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
    }

    function getClampedQty(row) {
        const input = row.querySelector('.quantity-input');
        const maxStock = Number(row.dataset.stock) || 1;
        let qty = Math.floor(Number(input.value) || 1);
        if (qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;
        return qty;
    }

    function updateSummary() {

        let total = 0;
        let count = 0;

        checkboxes.forEach(function (checkbox) {

            const row = checkbox.closest('tr');
            const price = Number(row.dataset.price);
            const quantity = getClampedQty(row);
            const rowSubtotal = price * quantity;

            const subtotalCell = row.querySelector('.row-subtotal');
            if (subtotalCell) {
                subtotalCell.textContent = formatMoney(rowSubtotal);
            }

            if (checkbox.checked) {
                total += rowSubtotal;
                count++;
            }

        });

        let discount = 0;

        if (coupon && total >= Number(coupon.min_order)) {

            if (coupon.type === 'percent') {

                discount = total * Number(coupon.value) / 100;

            } else {

                discount = Number(coupon.value);

            }

            discount = Math.floor(Math.min(
                discount,
                Number(coupon.max_discount),
                total
            ));
        }

        if (coupon && couponStatusText) {
            if (total >= Number(coupon.min_order)) {
                couponStatusText.className = 'text-success d-block mt-2';
                couponStatusText.textContent = 'Đang áp dụng mã ' + coupon.code;
            } else {
                couponStatusText.className = 'text-warning d-block mt-2';
                couponStatusText.textContent =
                    'Cần tối thiểu ' + formatMoney(Number(coupon.min_order)) + ' để áp dụng mã ' + coupon.code;
            }
        }

        const payable = Math.max(0, total - discount);

        selectedCount.textContent = count;

        selectedTotal.textContent = formatMoney(total);

        couponDiscount.textContent = '-' + formatMoney(discount);

        finalTotal.textContent = formatMoney(payable);

        checkoutButton.disabled = count === 0;

        selectAll.checked =
            checkboxes.length > 0 && count === checkboxes.length;

        selectAll.indeterminate =
            count > 0 && count < checkboxes.length;
    }

    selectAll.addEventListener('change', function () {

        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });

        updateSummary();

    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateSummary);
    });

    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('input', updateSummary);
        input.addEventListener('change', function () {
            const row = input.closest('tr');
            input.value = getClampedQty(row);
            updateSummary();
        });
    });

    updateSummary();

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
