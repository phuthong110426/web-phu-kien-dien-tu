<?php
/**
 * TRANG LỊCH SỬ ĐƠN HÀNG CỦA KHÁCH HÀNG + XEM CHI TIẾT ĐƠN HÀNG
 * Phụ trách: Thành viên 3
 */
require_once __DIR__ . '/config/db.php';

if (!is_logged_in()) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem lịch sử đơn hàng!';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Cho phép khách hủy đơn khi đang ở trạng thái pending
if (isset($_GET['cancel_id'])) {
    $cancel_id = (int)$_GET['cancel_id'];
    $upd = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $upd->execute([$cancel_id, $user_id]);
    $_SESSION['flash_success'] = "Đã hủy đơn hàng #$cancel_id.";
    header('Location: my_orders.php');
    exit;
}

// Lấy danh sách đơn hàng của khách
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Nếu khách bấm xem chi tiết 1 đơn hàng (?detail_id=...)
$detail_order = null;
$detail_items = [];
if (isset($_GET['detail_id'])) {
    $detail_id = (int)$_GET['detail_id'];
    $od_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $od_stmt->execute([$detail_id, $user_id]);
    $detail_order = $od_stmt->fetch();

    if ($detail_order) {
        $items_stmt = $pdo->prepare("
            SELECT od.*, p.name AS product_name, p.thumbnail
            FROM order_details od
            LEFT JOIN products p ON od.product_id = p.id
            WHERE od.order_id = ?
            ORDER BY od.id ASC
        ");
        $items_stmt->execute([$detail_id]);
        $detail_items = $items_stmt->fetchAll();
    } else {
        $_SESSION['flash_error'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này!';
        header('Location: my_orders.php');
        exit;
    }
}

$status_badges = [
    'pending'   => ['Chờ xác nhận', 'warning'],
    'confirmed' => ['Đã xác nhận', 'info'],
    'shipping'  => ['Đang giao hàng', 'primary'],
    'completed' => ['Hoàn thành', 'success'],
    'cancelled' => ['Đã hủy', 'secondary']
];

$page_title = $detail_order ? 'Chi tiết đơn hàng #' . $detail_order['id'] : 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">

    <?php if ($detail_order): ?>
        <?php $badge = $status_badges[$detail_order['status']] ?? [$detail_order['status'], 'secondary']; ?>
        <!-- GIAO DIỆN XEM CHI TIẾT CÁC MÓN TRONG 1 ĐƠN HÀNG -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-receipt text-primary me-2"></i>Chi Tiết Đơn Hàng #<?= $detail_order['id'] ?>
                    <span class="badge bg-<?= $badge[1] ?> ms-2 fs-6"><?= $badge[0] ?></span>
                </h4>
                <div class="text-muted small">Ngày đặt: <?= date('d/m/Y H:i', strtotime($detail_order['created_at'])) ?></div>
            </div>
            <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Quay lại lịch sử đơn hàng
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-geo-alt me-1"></i>Thông Tin Nhận Hàng</h6>
                    <div class="small lh-lg">
                        <div><strong>Người nhận:</strong> <?= htmlspecialchars($detail_order['receiver_name']) ?></div>
                        <div><strong>Số điện thoại:</strong> <?= htmlspecialchars($detail_order['receiver_phone']) ?></div>
                        <div><strong>Địa chỉ:</strong> <?= htmlspecialchars($detail_order['receiver_address']) ?></div>
                        <?php if (!empty($detail_order['note'])): ?>
                            <div><strong>Ghi chú:</strong> <?= htmlspecialchars($detail_order['note']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-wallet2 me-1"></i>Thanh Toán</h6>
                    <div class="small lh-lg">
                        <div><strong>Phương thức:</strong> <span class="badge bg-light text-dark border"><?= htmlspecialchars($detail_order['payment_method']) ?></span></div>
                        <div><strong>Tổng tiền:</strong> <span class="fs-5 fw-bold text-danger"><?= format_price($detail_order['total_amount']) ?></span></div>
                    </div>
                    <?php if ($detail_order['status'] === 'pending'): ?>
                        <div class="mt-auto pt-3">
                            <a href="my_orders.php?cancel_id=<?= $detail_order['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                <i class="bi bi-x-circle me-1"></i> Hủy đơn hàng này
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phụ kiện đã mua</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $item): ?>
                            <?php $subtotal = $item['price'] * $item['quantity']; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($item['thumbnail'])): ?>
                                            <img src="<?= htmlspecialchars($item['thumbnail']) ?>" class="rounded-3 border" style="width: 56px; height: 56px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <?php if (!empty($item['product_name'])): ?>
                                                <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= htmlspecialchars($item['product_name']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Sản phẩm đã ngừng kinh doanh</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-semibold">x<?= $item['quantity'] ?></td>
                                <td class="text-end"><?= format_price($item['price']) ?></td>
                                <td class="text-end fw-bold text-danger"><?= format_price($subtotal) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- DANH SÁCH ĐƠN HÀNG -->
        <h4 class="fw-bold mb-4"><i class="bi bi-bag-check text-primary me-2"></i>Lịch Sử Đơn Hàng Của Tôi</h4>

        <?php if (empty($orders)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <p class="text-muted mb-3">Bạn chưa có đơn hàng nào.</p>
                <div><a href="products.php" class="btn btn-primary">Mua sắm ngay</a></div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Ngày đặt</th>
                                <th>Người nhận</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <?php $badge = $status_badges[$o['status']] ?? [$o['status'], 'secondary']; ?>
                                <tr>
                                    <td class="fw-bold">
                                        <a href="my_orders.php?detail_id=<?= $o['id'] ?>" class="text-decoration-none">#<?= $o['id'] ?></a>
                                    </td>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($o['receiver_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($o['receiver_phone']) ?></div>
                                    </td>
                                    <td class="fw-bold text-danger"><?= format_price($o['total_amount']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['payment_method']) ?></span></td>
                                    <td><span class="badge bg-<?= $badge[1] ?>"><?= $badge[0] ?></span></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="my_orders.php?detail_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>Chi tiết
                                            </a>
                                            <?php if ($o['status'] === 'pending'): ?>
                                                <a href="my_orders.php?cancel_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn muốn hủy đơn hàng này?')">Hủy đơn</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
