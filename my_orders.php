<?php
/**
 * TRANG LỊCH SỬ ĐƠN HÀNG CỦA KHÁCH HÀNG
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

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$status_badges = [
    'pending' => ['Chờ xác nhận', 'warning'],
    'confirmed' => ['Đã xác nhận', 'info'],
    'shipping' => ['Đang giao hàng', 'primary'],
    'completed' => ['Hoàn thành', 'success'],
    'cancelled' => ['Đã hủy', 'secondary']
];

$page_title = 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
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
                                <td class="fw-bold">#<?= $o['id'] ?></td>
                                <td class="small"><?= $o['created_at'] ?></td>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars($o['receiver_name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($o['receiver_phone']) ?></div>
                                </td>
                                <td class="fw-bold text-danger"><?= format_price($o['total_amount']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['payment_method']) ?></span></td>
                                <td><span class="badge bg-<?= $badge[1] ?>"><?= $badge[0] ?></span></td>
                                <td>
                                    <?php if ($o['status'] === 'pending'): ?>
                                        <a href="my_orders.php?cancel_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn muốn hủy đơn hàng này?')">Hủy đơn</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
