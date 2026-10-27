<?php
/**
 * DASHBOARD THỐNG KÊ DOANH THU & BIỂU ĐỒ CHART.JS
 * Phụ trách: Thành viên 5
 */
$page_title = 'Bảng điều khiển';
require_once __DIR__ . '/includes/header.php';

$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Thống kê số lượng sản phẩm theo danh mục cho Biểu đồ
$cat_stats = $pdo->query("
    SELECT c.name, COUNT(p.id) AS total
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
")->fetchAll();

$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><i class="bi bi-speedometer2 text-primary me-2"></i>Tổng Quan Hệ Thống</h4>
    <span class="badge bg-primary px-3 py-2">Xin chào, <?= htmlspecialchars($_SESSION['user']['fullname']) ?></span>
</div>

<!-- 4 Thẻ thống kê -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-primary text-white">
            <div class="small opacity-75">Tổng Doanh Thu</div>
            <h4 class="fw-bold my-1"><?= format_price($total_revenue) ?></h4>
            <small><i class="bi bi-graph-up-arrow me-1"></i>Đơn hàng hợp lệ</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-success text-white">
            <div class="small opacity-75">Tổng Đơn Hàng</div>
            <h4 class="fw-bold my-1"><?= $total_orders ?> đơn</h4>
            <small><i class="bi bi-bag-check me-1"></i>Toàn hệ thống</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-info text-dark">
            <div class="small opacity-75">Tổng Phụ Kiện</div>
            <h4 class="fw-bold my-1"><?= $total_products ?> sản phẩm</h4>
            <small><i class="bi bi-box-seam me-1"></i>Đang kinh doanh</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-warning text-dark">
            <div class="small opacity-75">Thành Viên</div>
            <h4 class="fw-bold my-1"><?= $total_users ?> tài khoản</h4>
            <small><i class="bi bi-people me-1"></i>Admin & Khách hàng</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-3">Thống Kê Phụ Kiện Theo Danh Mục</h6>
            <canvas id="categoryChart" height="230"></canvas>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0">Đơn Hàng Mới Nhất</h6>
                <a href="orders.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mã</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td class="fw-bold">#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['receiver_name']) ?></td>
                                <td class="fw-bold text-danger"><?= format_price($o['total_amount']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($o['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('categoryChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($cat_stats, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($cat_stats, 'total'))) ?>,
            backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
        }]
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
