<?php
/**
 * QUẢN LÝ ĐƠN HÀNG & CẬP NHẬT TRẠNG THÁI
 * Phụ trách: Thành viên 5
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $_SESSION['flash_success'] = "Đã cập nhật trạng thái đơn hàng #$order_id!";
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-bold">#<?= $o['id'] ?></td>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
