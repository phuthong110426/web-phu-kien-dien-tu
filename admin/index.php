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

// Thống kê số lượng đơn hàng theo từng trạng thái cho Biểu đồ
$order_status_stats = $pdo->query("SELECT status, COUNT(*) as total FROM orders GROUP BY status")->fetchAll();

$status_vn_map = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$status_color_map = [
    'pending'   => '#ff9f0a',
    'confirmed' => '#00b4d8',
    'shipping'  => '#0071e3',
    'completed' => '#34c759',
    'cancelled' => '#ff375f',
];

$order_status_labels = [];
$order_status_data = [];
$order_status_colors = [];

foreach ($order_status_stats as $row) {
    $st = $row['status'];
    $order_status_labels[] = $status_vn_map[$st] ?? $st;
    $order_status_data[] = (int)$row['total'];
    $order_status_colors[] = $status_color_map[$st] ?? '#8e8e93';
}

$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="mb-4">
    <h2 class="section-title">Xin chào, <?= htmlspecialchars($_SESSION['user']['fullname']) ?>.</h2>
    <p class="section-sub">Đây là tình hình cửa hàng TechGear hôm nay.</p>
</div>

<!-- 4 Thẻ thống kê -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon soft-blue"><i class="bi bi-currency-exchange"></i></div>
            <div class="stat-label">Tổng doanh thu</div>
            <p class="stat-value"><?= format_price($total_revenue) ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon soft-green"><i class="bi bi-bag-check"></i></div>
            <div class="stat-label">Tổng đơn hàng</div>
            <p class="stat-value"><?= $total_orders ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon soft-orange"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Phụ kiện đang bán</div>
            <p class="stat-value"><?= $total_products ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon soft-purple"><i class="bi bi-people"></i></div>
            <div class="stat-label">Tài khoản</div>
            <p class="stat-value"><?= $total_users ?></p>
        </div>
    </div>
</div>

<!-- 2 Biểu đồ thống kê -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart text-primary me-2"></i>Thống Kê Phụ Kiện Theo Danh Mục</h6>
            <canvas id="categoryChart" height="230"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-line text-primary me-2"></i>Thống Kê Đơn Hàng Theo Trạng Thái</h6>
            <canvas id="orderStatusChart" height="230"></canvas>
        </div>
    </div>
</div>

<!-- Đơn hàng mới nhất -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0"><i class="bi bi-receipt text-primary me-2"></i>Đơn Hàng Mới Nhất</h6>
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
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td class="fw-bold">
                                    <a href="order_detail.php?id=<?= $o['id'] ?>" class="text-decoration-none">#<?= $o['id'] ?></a>
                                </td>
                                <td><?= htmlspecialchars($o['receiver_name']) ?></td>
                                <td class="fw-bold text-danger"><?= format_price($o['total_amount']) ?></td>
                                <?php
                                $st = [
                                    'pending' => ['Chờ xác nhận', 'warning'],
                                    'confirmed' => ['Đã xác nhận', 'info'],
                                    'shipping' => ['Đang giao', 'primary'],
                                    'completed' => ['Hoàn thành', 'success'],
                                    'cancelled' => ['Đã hủy', 'secondary'],
                                ][$o['status']] ?? [$o['status'], 'secondary'];
                                ?>
                                <td><span class="badge bg-<?= $st[1] ?>"><?= $st[0] ?></span></td>
                                <td>
                                    <a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        Chi tiết
                                    </a>
                                </td>
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
// 1. Biểu đồ Danh mục phụ kiện (Doughnut Chart)
const ctxCat = document.getElementById('categoryChart');
new Chart(ctxCat, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($cat_stats, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($cat_stats, 'total'))) ?>,
            backgroundColor: ['#0071e3', '#34c759', '#ff9f0a', '#ff375f', '#af52de', '#5ac8fa'],
            borderWidth: 0
        }]
    },
    options: {
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Inter' } } }
        }
    }
});

// 2. Biểu đồ Đơn hàng theo Trạng thái (Bar Chart)
const ctxStatus = document.getElementById('orderStatusChart');
new Chart(ctxStatus, {
    type: 'bar',
    data: {
        labels: <?= json_encode($order_status_labels) ?>,
        datasets: [{
            label: 'Số lượng đơn',
            data: <?= json_encode($order_status_data) ?>,
            backgroundColor: <?= json_encode($order_status_colors) ?>,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: { family: 'Inter' }
                },
                grid: {
                    color: '#f0f0f2'
                }
            },
            x: {
                ticks: {
                    font: { family: 'Inter' }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
