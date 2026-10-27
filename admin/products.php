<?php
/**
 * QUẢN LÝ SẢN PHẨM PHỤ KIỆN (CRUD + PHÂN TRANG ADMIN)
 * Phụ trách: Thành viên 4
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash_success'] = "Đã xóa sản phẩm #$del_id thành công!";
    header('Location: products.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$total = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = max(1, ceil($total / $limit));

$products = $pdo->query("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

$page_title = 'Quản lý Phụ kiện';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><i class="bi bi-box-seam text-primary me-2"></i>Quản Lý Sản Phẩm Phụ Kiện (CRUD)</h4>
    <a href="product_form.php" class="btn btn-primary fw-semibold">
        <i class="bi bi-plus-circle me-1"></i> Thêm Phụ Kiện Mới (CKEditor & Nhiều ảnh)
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tên phụ kiện</th>
                    <th>Danh mục / Hãng</th>
                    <th>Giá bán</th>
                    <th>Kho</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="fw-bold">#<?= $p['id'] ?></td>
                        <td>
                            <img src="<?= htmlspecialchars(strpos($p['thumbnail'], 'http') === 0 ? $p['thumbnail'] : '../' . $p['thumbnail']) ?>" class="rounded-3 border" style="width: 56px; height: 56px; object-fit: cover;">
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if ($p['is_featured']): ?>
                                <span class="badge bg-danger small">Nổi bật</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <div><?= htmlspecialchars($p['category_name']) ?></div>
                            <span class="text-muted"><?= htmlspecialchars($p['brand_name'] ?? '') ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-danger"><?= format_price($p['sale_price'] ?: $p['price']) ?></div>
                            <?php if ($p['sale_price']): ?>
                                <del class="small text-muted"><?= format_price($p['price']) ?></del>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['stock'] ?></td>
                        <td>
                            <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square"></i> Sửa
                            </a>
                            <a href="products.php?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa phụ kiện này?')">
                                <i class="bi bi-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="products.php?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
