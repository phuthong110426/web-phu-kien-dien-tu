<?php
/**
 * GIỎ HÀNG & THANH TOÁN (CHECKOUT)
 * Phụ trách: Thành viên 3
 */
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$qty = max(1, (int)($_GET['qty'] ?? 1));

if ($action === 'add' && $id > 0) {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    $_SESSION['flash_success'] = 'Đã thêm sản phẩm vào giỏ hàng!';
    header('Location: cart.php');
    exit;
}

if ($action === 'remove' && $id > 0) {
    unset($_SESSION['cart'][$id]);
    $_SESSION['flash_success'] = 'Đã xóa sản phẩm khỏi giỏ hàng!';
    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] ?? [] as $pid => $q) {
        $pid = (int)$pid;
        $q = (int)$q;
        if ($q <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid] = $q;
        }
    }
    $_SESSION['flash_success'] = 'Đã cập nhật số lượng giỏ hàng!';
    header('Location: cart.php');
    exit;
}

// Lấy thông tin chi tiết các sản phẩm trong giỏ
$cart_items = [];
$total_amount = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $unit_price = ($r['sale_price'] && $r['sale_price'] < $r['price']) ? $r['sale_price'] : $r['price'];
        $quantity = $_SESSION['cart'][$r['id']];
        $subtotal = $unit_price * $quantity;
        $total_amount += $subtotal;
        $r['unit_price'] = $unit_price;
        $r['cart_qty'] = $quantity;
        $r['subtotal'] = $subtotal;
        $cart_items[] = $r;
    }
}

$page_title = 'Giỏ hàng của bạn';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-cart3 text-primary me-2"></i>Giỏ Hàng Của Bạn</h4>

    <?php if (empty($cart_items)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-cart-x display-3 text-muted"></i>
            <h5 class="mt-3">Giỏ hàng của bạn đang trống</h5>
            <p class="text-muted small">Hãy chọn thêm các mẫu tai nghe, bàn phím, chuột gaming yêu thích nhé!</p>
            <div>
                <a href="products.php" class="btn btn-primary px-4 fw-semibold">Tiếp Tục Mua Sắm</a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Phụ kiện</th>
                                        <th>Đơn giá</th>
                                        <th style="width: 110px;">Số lượng</th>
                                        <th>Thành tiền</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= htmlspecialchars($item['thumbnail']) ?>" class="rounded-3 border" style="width: 64px; height: 64px; object-fit: cover;">
                                                    <div>
                                                        <a href="product_detail.php?id=<?= $item['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-semibold"><?= format_price($item['unit_price']) ?></td>
                                            <td>
                                                <input type="number" name="quantities[<?= $item['id'] ?>]" value="<?= $item['cart_qty'] ?>" min="1" class="form-control form-control-sm text-center">
                                            </td>
                                            <td class="fw-bold text-danger"><?= format_price($item['subtotal']) ?></td>
                                            <td>
                                                <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa sản phẩm này khỏi giỏ?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 bg-light d-flex justify-content-between">
                            <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Mua thêm phụ kiện</a>
                            <button type="submit" name="update_cart" class="btn btn-dark btn-sm fw-semibold"><i class="bi bi-arrow-clockwise me-1"></i>Cập nhật số lượng</button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold border-bottom pb-2 mb-3">Tóm Tắt Đơn Hàng</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= format_price($total_amount) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Phí vận chuyển:</span>
                            <span class="text-success fw-semibold">Miễn phí</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Tổng thanh toán:</span>
                            <span class="fs-4 fw-bold text-danger"><?= format_price($total_amount) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary btn-lg w-100 fw-bold">
                            Tiến Hành Đặt Hàng <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
