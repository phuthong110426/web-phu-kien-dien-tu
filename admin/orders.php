<?php
/**
 * QUẢN LÝ ĐƠN HÀNG & CẬP NHẬT TRẠNG THÁI (TỰ ĐỘNG TRỪ / HOÀN TỒN KHO)
 * Phụ trách: Thành viên 5
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $valid_statuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];

    if ($order_id > 0 && in_array($new_status, $valid_statuses, true)) {
        $stmt_cur = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt_cur->execute([$order_id]);
        $old_status = $stmt_cur->fetchColumn();

        if ($old_status !== false) {
            $pdo->beginTransaction();
            try {
                // Tự động trừ kho khi chuyển sang completed, hoặc hoàn kho khi đổi từ completed sang trạng thái khác
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

                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $order_id]);
                $pdo->commit();

                $msg = "Đã cập nhật trạng thái đơn hàng #$order_id!";
                if ($new_status === 'completed' && $old_status !== 'completed') {
                    $msg .= " Đã tự động trừ số lượng tồn kho của các sản phẩm trong đơn.";
                } elseif ($old_status === 'completed' && $new_status !== 'completed') {
                    $msg .= " Đã tự động hoàn trả số lượng tồn kho của các sản phẩm trong đơn.";
                }
                $_SESSION['flash_success'] = $msg;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_error'] = "Lỗi khi cập nhật đơn hàng #$order_id!";
            }
        }
    }

    header('Location: orders.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

$page_title = 'Quản lý Đơn hàng';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-receipt text-primary me-2"></i>Quản Lý Đơn Đặt Hàng</h4>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã ĐH</th>
                    <th>Khách hàng & Địa chỉ</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Ngày đặt</th>
                    <th>Cập nhật trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Chưa có đơn đặt hàng nào trong hệ thống.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="fw-bold">
                                <a href="order_detail.php?id=<?= $o['id'] ?>" class="text-decoration-none fw-bold">#<?= $o['id'] ?></a>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($o['receiver_name']) ?> - <?= htmlspecialchars($o['receiver_phone']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($o['receiver_address']) ?></div>
                            </td>
                            <td class="fw-bold text-danger"><?= format_price($o['total_amount']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['payment_method']) ?></span></td>
                            <td class="small"><?= $o['created_at'] ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="width: 150px;">
                                        <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?= $o['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                        <option value="shipping" <?= $o['status'] === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                                        <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary text-nowrap">
                                    <i class="bi bi-eye me-1"></i>Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
