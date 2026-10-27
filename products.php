<?php
/**
 * TRANG DANH SÁCH SẢN PHẨM + BỘ LỌC + PHÂN TRANG (TÍNH NĂNG NÂNG CAO)
 * Phụ trách: Thành viên 2
 */
require_once __DIR__ . '/config/db.php';

$categories = $pdo->query("SELECT * FROM categories WHERE status = 1")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

// Tham số lọc
$keyword = trim($_GET['keyword'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$brand_id = (int)($_GET['brand'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // Số sản phẩm mỗi trang để dễ demo Phân trang
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($keyword !== '') {
    $where[] = "(p.name LIKE ? OR p.specifications LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
if ($category_id > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}
if ($brand_id > 0) {
    $where[] = "p.brand_id = ?";
    $params[] = $brand_id;
}

$where_sql = implode(" AND ", $where);

$order_sql = "p.id DESC";
if ($sort === 'price_asc') $order_sql = "COALESCE(p.sale_price, p.price) ASC";
elseif ($sort === 'price_desc') $order_sql = "COALESCE(p.sale_price, p.price) DESC";
elseif ($sort === 'popular') $order_sql = "p.views DESC";

// Đếm tổng số sản phẩm để tính số trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where_sql");
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_products / $limit));

// Truy vấn sản phẩm có phân trang (LIMIT & OFFSET)
$sql = "
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = 'Cửa hàng phụ kiện';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="row g-4">
        <!-- Sidebar Bộ lọc -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3"><i class="bi bi-funnel text-primary me-2"></i>Bộ Lọc Phụ Kiện</h5>
                <form method="GET" action="products.php">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Từ khóa</label>
                        <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Tên phụ kiện..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Danh mục</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="0">-- Tất cả danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Thương hiệu</label>
                        <select name="brand" class="form-select form-select-sm">
                            <option value="0">-- Tất cả hãng --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $brand_id === (int)$b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sắp xếp theo</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao xuống thấp</option>
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Xem nhiều nhất</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold mb-2">
                        <i class="bi bi-check2-circle me-1"></i> Áp Dụng Lọc
                    </button>
                    <a href="products.php" class="btn btn-light btn-sm w-100 border">Xóa bộ lọc</a>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-3">
                <div>
                    Tìm thấy <strong class="text-primary"><?= $total_products ?></strong> phụ kiện phù hợp
                </div>
                <div class="small text-muted">Trang <?= $page ?> / <?= $total_pages ?></div>
            </div>

            <?php if (empty($products)): ?>
                <div class="bg-white rounded-4 p-5 text-center shadow-sm">
                    <i class="bi bi-search display-4 text-muted"></i>
                    <h5 class="mt-3">Không tìm thấy phụ kiện nào phù hợp</h5>
                    <a href="products.php" class="btn btn-outline-primary mt-2">Xem tất cả sản phẩm</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $final_price = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
                        $discount = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="product-card h-100 d-flex flex-column">
                                <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-img-wrapper d-block">
                                    <?php if ($discount > 0): ?>
                                        <span class="badge-sale">-<?= $discount ?>%</span>
                                    <?php endif; ?>
                                    <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                </a>
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <div class="small text-muted mb-1">
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></span>
                                        <span class="ms-1"><?= htmlspecialchars($p['category_name']) ?></span>
                                    </div>
                                    <h6 class="fw-bold mb-2">
                                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </a>
                                    </h6>
                                    <div class="mt-auto">
                                        <div class="mb-2">
                                            <span class="fs-5 fw-bold text-danger"><?= format_price($final_price) ?></span>
                                            <?php if ($discount > 0): ?>
                                                <del class="small text-muted ms-1"><?= format_price($p['price']) ?></del>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="cart.php?action=add&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 fw-semibold">
                                                <i class="bi bi-cart-plus me-1"></i> Thêm giỏ
                                            </a>
                                            <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm">Chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- PHÂN TRANG (PAGINATION) -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4 d-flex justify-content-center">
                        <ul class="pagination shadow-sm">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php
                                $query = $_GET;
                                $query['page'] = $i;
                                $url = 'products.php?' . http_build_query($query);
                                ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link fw-semibold" href="<?= htmlspecialchars($url) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
