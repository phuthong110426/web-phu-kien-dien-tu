<?php
/**
 * XEM CHI TIẾT ĐƠN ĐẶT HÀNG & IN HÓA ĐƠN
 * Phụ trách: Thành viên 5
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    $_SESSION['flash_error'] = 'Mã đơn hàng không hợp lệ!';
    header('Location: orders.php');
    exit;
}

// Xử lý cập nhật trạng thái đơn hàng ngay tại trang chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    $valid_statuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses, true)) {
        $stmt_cur = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt_cur->execute([$order_id]);
        $old_status = $stmt_cur->fetchColumn();

        if ($old_status !== false) {
            $pdo->beginTransaction();
            try {
                if ($new_status === 'completed' && $old_status !== 'completed') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    foreach ($stmt_items->fetchAll() as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['product_id']]);
                    }
                } elseif ($old_status === 'completed' && $new_status !== 'completed') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    foreach ($stmt_items->fetchAll() as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                $stmt_update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt_update->execute([$new_status, $order_id]);
                $pdo->commit();

                $msg = "Đã cập nhật trạng thái đơn hàng #$order_id thành công!";
                if ($new_status === 'completed' && $old_status !== 'completed') {
                    $msg .= " Đã tự động trừ số lượng tồn kho của các sản phẩm trong đơn.";
                } elseif ($old_status === 'completed' && $new_status !== 'completed') {
                    $msg .= " Đã tự động hoàn trả số lượng tồn kho của các sản phẩm trong đơn.";
                }
                $_SESSION['flash_success'] = $msg;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_error'] = "Lỗi khi cập nhật trạng thái đơn hàng #$order_id!";
            }
        }

        header("Location: order_detail.php?id=$order_id");
        exit;
    }
}

// Truy vấn thông tin đơn hàng từ bảng orders (kèm thông tin user nếu có)
$stmt_order = $pdo->prepare("
    SELECT o.*, u.fullname AS user_fullname, u.email AS user_email, u.phone AS user_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt_order->execute([$order_id]);
$order = $stmt_order->fetch();

if (!$order) {
    $_SESSION['flash_error'] = "Không tìm thấy đơn hàng #$order_id trong hệ thống!";
    header('Location: orders.php');
    exit;
}

// Truy vấn danh sách sản phẩm trong đơn hàng: JOIN order_details với products
$stmt_details = $pdo->prepare("
    SELECT od.*, p.name AS product_name, p.thumbnail
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
    ORDER BY od.id ASC
");
$stmt_details->execute([$order_id]);
$order_details = $stmt_details->fetchAll();

$status_map = [
    'pending'   => ['label' => 'Chờ xác nhận',  'class' => 'warning', 'icon' => 'bi-hourglass-split'],
    'confirmed' => ['label' => 'Đã xác nhận',   'class' => 'info',    'icon' => 'bi-check2-circle'],
    'shipping'  => ['label' => 'Đang giao hàng','class' => 'primary', 'icon' => 'bi-truck'],
    'completed' => ['label' => 'Hoàn thành',    'class' => 'success', 'icon' => 'bi-check-circle-fill'],
    'cancelled' => ['label' => 'Đã hủy',        'class' => 'danger',  'icon' => 'bi-x-circle-fill'],
];
$badge = $status_map[$order['status']] ?? ['label' => $order['status'], 'class' => 'secondary', 'icon' => 'bi-info-circle'];

$page_title = 'Chi tiết Đơn hàng #' . $order['id'];
require_once __DIR__ . '/includes/header.php';
?>

<style>
@media print {
    .admin-sidebar, .admin-topbar, .no-print { display: none !important; }
    body { background: #fff !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>Chi Tiết Đơn Hàng #<?= $order['id'] ?>
            </h4>
            <span class="badge bg-<?= $badge['class'] ?> px-3 py-2">
                <i class="bi <?= $badge['icon'] ?> me-1"></i><?= $badge['label'] ?>
            </span>
        </div>
        <div class="text-muted small mt-1">
            Đặt ngày: <?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?>
        </div>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i> In đơn hàng
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Cột 1: Thông tin người nhận & Giao hàng -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-person-bounding-box text-primary me-2"></i>Thông Tin Giao Nhận
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="text-muted d-block small">Người nhận hàng:</span>
                    <span class="fw-bold fs-6"><?= htmlspecialchars($order['receiver_name']) ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-muted d-block small">Số điện thoại:</span>
                    <a href="tel:<?= htmlspecialchars($order['receiver_phone']) ?>" class="fw-semibold text-decoration-none">
                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['receiver_phone']) ?>
                    </a>
                </div>
                <div class="mb-3">
                    <span class="text-muted d-block small">Địa chỉ nhận hàng:</span>
                    <span class="fw-medium"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($order['receiver_address']) ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-muted d-block small">Ghi chú từ khách hàng:</span>
                    <?php if (!empty($order['note'])): ?>
                        <div class="p-2 mt-1 rounded-3 bg-light text-secondary small">
                            <?= nl2br(htmlspecialchars($order['note'])) ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic small">Không có ghi chú</span>
                    <?php endif; ?>
                </div>
                <div class="border-top pt-3">
                    <span class="text-muted d-block small">Tài khoản đặt hàng:</span>
                    <?php if (!empty($order['user_id'])): ?>
                        <span class="fw-semibold"><?= htmlspecialchars($order['user_fullname'] ?? 'Khách hàng') ?></span>
                        <span class="text-muted small">(<?= htmlspecialchars($order['user_email'] ?? '') ?>)</span>
                    <?php else: ?>
                        <span class="text-muted small">Khách mua trực tiếp (không đăng nhập)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột 2: Thông tin thanh toán & Cập nhật trạng thái -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-credit-card-2-front text-primary me-2"></i>Thanh Toán & Trạng Thái
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="text-muted d-block small">Phương thức thanh toán:</span>
                    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold fs-6 mt-1">
                        <i class="bi bi-wallet2 me-1"></i><?= htmlspecialchars($order['payment_method']) ?>
                    </span>
                </div>
                <div class="mb-3">
                    <span class="text-muted d-block small">Tổng tiền thanh toán:</span>
                    <div class="fw-bold text-danger fs-4"><?= format_price($order['total_amount']) ?></div>
                </div>

                <div class="border-top pt-3 mt-4 no-print">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Cập nhật trạng thái đơn</label>
                    <form method="POST" class="d-flex gap-2">
                        <select name="status" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ Chờ xác nhận</option>
                            <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>📋 Đã xác nhận</option>
                            <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✅ Đã hoàn thành</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Đã hủy</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary text-nowrap px-3">
                            <i class="bi bi-check2 me-1"></i> Lưu
                        </button>
                    </form>
                    <div class="form-text small mt-2">Khi chuyển sang "Đã hoàn thành", hệ thống tự động trừ tồn kho.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng danh sách sản phẩm trong đơn hàng -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-light py-3 px-4 border-0">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-box-seam text-primary me-2"></i>Danh Sách Sản Phẩm (<?= count($order_details) ?>)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;" class="text-center">#</th>
                    <th style="width: 90px;">Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th class="text-center" style="width: 120px;">Số lượng</th>
                    <th class="text-end" style="width: 160px;">Đơn giá</th>
                    <th class="text-end" style="width: 180px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($order_details)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Không tìm thấy chi tiết sản phẩm cho đơn hàng này.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($order_details as $idx => $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $thumb = $item['thumbnail'] ?? '';
                        $img_src = !empty($thumb) ? ((strpos($thumb, 'http') === 0) ? $thumb : '../' . $thumb) : '';
                    ?>
                        <tr>
                            <td class="text-center text-muted fw-semibold"><?= $idx + 1 ?></td>
                            <td>
                                <?php if (!empty($img_src)): ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>"
                                         alt="<?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?>"
                                         class="rounded-3 border"
                                         style="width: 56px; height: 56px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-muted"
                                         style="width: 56px; height: 56px;">
                                        <i class="bi bi-image" style="font-size: 1.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['product_name'])): ?>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <a href="../product_detail.php?id=<?= $item['product_id'] ?>"
                                       target="_blank"
                                       class="small text-decoration-none text-muted no-print">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Xem trang sản phẩm
                                    </a>
                                <?php else: ?>
                                    <div class="fw-bold text-muted fst-italic">Sản phẩm không còn tồn tại hoặc đã bị xóa</div>
                                    <span class="small text-muted">Mã SP: #<?= $item['product_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                                    x<?= $item['quantity'] ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold"><?= format_price($item['price']) ?></td>
                            <td class="text-end fw-bold text-danger"><?= format_price($subtotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold py-3">Tổng cộng thanh toán:</td>
                    <td class="text-end fw-bold text-danger fs-5 py-3"><?= format_price($order['total_amount']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
