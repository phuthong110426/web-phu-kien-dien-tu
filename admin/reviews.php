<?php
/**
 * QUẢN LÝ ĐÁNH GIÁ & BÌNH LUẬN KHÁCH HÀNG (CRUD REVIEWS)
 * Phụ trách: Thành viên 6
 * Nâng cấp: lọc theo số sao, tìm kiếm, phân trang, table-responsive, xóa bằng POST + CSRF token
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

// Tạo CSRF token để bảo vệ thao tác xóa
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Nhận bộ lọc từ URL
$rating = (int)($_GET['rating'] ?? 0);
if ($rating < 1 || $rating > 5) {
    $rating = 0;
}
$keyword = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

// Xóa đánh giá bằng POST và kiểm tra CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Yêu cầu không hợp lệ (sai CSRF token)!';
    } else {
        $id = (int)$_POST['delete_id'];
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = "Đã xóa đánh giá #$id!";
    }

    // Quay lại đúng trang và bộ lọc đang xem
    $back = array_filter([
        'rating' => (int)($_POST['rating'] ?? 0) ?: null,
        'q' => trim($_POST['q'] ?? '') ?: null,
        'page' => (int)($_POST['page'] ?? 1) > 1 ? (int)$_POST['page'] : null,
    ]);
    header('Location: reviews.php' . ($back ? '?' . http_build_query($back) : ''));
    exit;
}

// Dựng điều kiện WHERE theo bộ lọc
$where = [];
$params = [];

if ($rating > 0) {
    $where[] = 'r.rating = ?';
    $params[] = $rating;
}

if ($keyword !== '') {
    $where[] = '(p.name LIKE ? OR r.comment LIKE ?)';
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Đếm tổng số đánh giá phù hợp để tính số trang
$count_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    $where_sql
");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Lấy đánh giá của trang hiện tại
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname, p.name AS product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    $where_sql
    ORDER BY r.id DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Tạo URL phân trang và giữ nguyên bộ lọc
function review_page_url($p, $rating, $keyword) {
    $q = array_filter([
        'rating' => $rating ?: null,
        'q' => $keyword !== '' ? $keyword : null,
        'page' => $p > 1 ? $p : null,
    ]);
    return 'reviews.php' . ($q ? '?' . http_build_query($q) : '');
}

$page_title = 'Quản lý Đánh giá';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-chat-left-quote text-primary me-2"></i>Quản Lý Đánh Giá Sản Phẩm</h4>

<!-- Thanh lọc và tìm kiếm -->
<form method="GET" class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Số sao</label>
            <select name="rating" class="form-select form-select-sm">
                <option value="0">Tất cả</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= $rating === $i ? 'selected' : '' ?>><?= $i ?> sao</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Tìm theo tên sản phẩm / nội dung bình luận</label>
            <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control form-control-sm" placeholder="VD: chuột, tai nghe, giao hàng nhanh...">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Lọc</button>
            <a href="reviews.php" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
        </div>
    </div>
</form>

<p class="small text-muted mb-2">Tìm thấy <strong><?= $total ?></strong> đánh giá — trang <?= $page ?>/<?= $total_pages ?></p>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Sản phẩm phụ kiện</th>
                    <th>Số sao</th>
                    <th>Nội dung bình luận</th>
                    <th>Ngày gửi</th>
                    <th>Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Không có đánh giá nào phù hợp.</td></tr>
                <?php endif; ?>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['fullname']) ?></td>
                        <td>
                            <a href="../product_detail.php?id=<?= $r['product_id'] ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($r['product_name']) ?>
                            </a>
                        </td>
                        <td class="text-warning text-nowrap"><?= str_repeat('★', (int)$r['rating']) ?></td>
                        <td><?= htmlspecialchars($r['comment']) ?></td>
                        <td class="small text-nowrap"><?= $r['created_at'] ?></td>
                        <td>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa bình luận này?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="rating" value="<?= $rating ?>">
                                <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= review_page_url($page - 1, $rating, $keyword) ?>">«</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= review_page_url($i, $rating, $keyword) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= review_page_url($page + 1, $rating, $keyword) ?>">»</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
