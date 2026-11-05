<?php
/**
 * TRANG CỬA HÀNG: TÌM KIẾM + LỌC (DANH MỤC, HÃNG, KHOẢNG GIÁ) + SẮP XẾP + PHÂN TRANG
 * Phụ trách: Thành viên 2 
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product_card.php';

$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

// ---- Các lựa chọn hợp lệ ----
$price_options = [
    ''       => 'Mọi mức giá',
    'under1' => 'Dưới 1 triệu',
    '1to3'   => 'Từ 1 – 3 triệu',
    'over3'  => 'Trên 3 triệu',
];
$sort_options = [
    'newest'     => 'Mới nhất',
    'popular'    => 'Xem nhiều nhất',
    'price_asc'  => 'Giá thấp đến cao',
    'price_desc' => 'Giá cao xuống thấp',
];

// ---- Tham số lọc ----
$keyword     = trim($_GET['keyword'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$brand_id    = (int)($_GET['brand'] ?? 0);
$price       = array_key_exists($_GET['price'] ?? '', $price_options) ? ($_GET['price'] ?? '') : '';
$sort        = array_key_exists($_GET['sort'] ?? '', $sort_options) ? $_GET['sort'] : 'newest';
$page        = max(1, (int)($_GET['page'] ?? 1));
$limit       = 6; // 6 sản phẩm / trang để thấy rõ phân trang khi demo
$offset      = ($page - 1) * $limit;

// Giá bán thực tế (ưu tiên giá khuyến mãi hợp lệ)
$price_expr = "(CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END)";

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
// Lọc theo khoảng giá
if ($price === 'under1') {
    $where[] = "$price_expr < 1000000";
} elseif ($price === '1to3') {
    $where[] = "$price_expr BETWEEN 1000000 AND 3000000";
} elseif ($price === 'over3') {
    $where[] = "$price_expr > 3000000";
}
$where_sql = implode(" AND ", $where);

$order_map = [
    'newest'     => "p.id DESC",
    'popular'    => "p.views DESC",
    'price_asc'  => "$price_expr ASC",
    'price_desc' => "$price_expr DESC",
];
$order_sql = $order_map[$sort];

// Đếm tổng số sản phẩm để tính số trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where_sql");
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_products / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Truy vấn sản phẩm có phân trang (LIMIT & OFFSET)
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Tạo URL giữ nguyên bộ lọc hiện tại, chỉ thay đổi vài tham số
function shop_url($changes = []) {
    $q = $_GET;
    unset($q['page']);
    foreach ($changes as $k => $v) {
        if ($v === null || $v === '' || $v === 0) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return 'products.php' . ($q ? '?' . http_build_query($q) : '');
}

// Tên danh mục / hãng đang chọn (để hiện tiêu đề và chip)
$current_category = null;
foreach ($categories as $c) {
    if ((int)$c['id'] === $category_id) $current_category = $c;
}
$current_brand = null;
foreach ($brands as $b) {
    if ((int)$b['id'] === $brand_id) $current_brand = $b;
}
$has_filters = $keyword !== '' || $brand_id > 0 || $price !== '';

$page_title = $current_category ? $current_category['name'] : 'Cửa hàng phụ kiện';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Tiêu đề trang -->
    <header class="page-hero reveal">
        <h1 class="page-title"><?= $current_category ? htmlspecialchars($current_category['name']) . '.' : 'Cửa hàng.' ?></h1>
        <p class="page-sub">
            <?= $current_category && $current_category['description']
                ? htmlspecialchars($current_category['description'])
                : 'Cách tốt nhất để mua phụ kiện bạn yêu thích.' ?>
        </p>
    </header>

    <!-- Chip danh mục -->
    <nav class="store-nav mb-2 reveal reveal-d1" aria-label="Danh mục">
        <a href="<?= htmlspecialchars(shop_url(['category' => null])) ?>" class="store-nav-item <?= $category_id === 0 ? 'active' : '' ?>">
            <span class="store-nav-icon"><i class="bi bi-grid"></i></span>
            Tất cả
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= htmlspecialchars(shop_url(['category' => (int)$cat['id']])) ?>" class="store-nav-item <?= $category_id === (int)$cat['id'] ? 'active' : '' ?>">
                <span class="store-nav-icon"><i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i></span>
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Thanh lọc dính khi cuộn -->
    <div class="filter-bar">
        <form method="GET" action="products.php" class="filter-row">
            <?php if ($category_id > 0): ?>
                <input type="hidden" name="category" value="<?= $category_id ?>">
            <?php endif; ?>

            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" class="form-control" placeholder="Tìm trong cửa hàng" value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <select name="brand" class="form-select pill-select" data-autosubmit aria-label="Thương hiệu">
                <option value="0">Mọi thương hiệu</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $brand_id === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="price" class="form-select pill-select" data-autosubmit aria-label="Khoảng giá">
                <?php foreach ($price_options as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $price === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="form-select pill-select" data-autosubmit aria-label="Sắp xếp">
                <?php foreach ($sort_options as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <noscript><button type="submit" class="btn btn-dark btn-sm">Lọc</button></noscript>

            <span class="result-meta ms-auto"><?= $total_products ?> sản phẩm</span>
        </form>
    </div>

    <!-- Chip bộ lọc đang áp dụng (bấm để bỏ) -->
    <?php if ($has_filters): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <?php if ($keyword !== ''): ?>
                <a href="<?= htmlspecialchars(shop_url(['keyword' => null])) ?>" class="chip chip-remove">“<?= htmlspecialchars($keyword) ?>” <i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
            <?php if ($current_brand): ?>
                <a href="<?= htmlspecialchars(shop_url(['brand' => null])) ?>" class="chip chip-remove"><?= htmlspecialchars($current_brand['name']) ?> <i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
            <?php if ($price !== ''): ?>
                <a href="<?= htmlspecialchars(shop_url(['price' => null])) ?>" class="chip chip-remove"><?= $price_options[$price] ?> <i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($category_id ? 'products.php?category=' . $category_id : 'products.php') ?>" class="link-arrow small ms-1">Xóa bộ lọc</a>
        </div>
    <?php endif; ?>

    <!-- Lưới sản phẩm -->
    <?php if (empty($products)): ?>
        <div class="empty-state mb-5 reveal">
            <i class="bi bi-search"></i>
            <h3 class="section-title mt-3">Không tìm thấy sản phẩm.</h3>
            <p class="section-sub mb-4">Thử từ khóa khác hoặc bỏ bớt bộ lọc nhé.</p>
            <a href="products.php" class="btn btn-primary">Xem tất cả phụ kiện</a>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($products as $i => $p): ?>
                <?php render_product_card($p, 'col-sm-6 col-lg-4 reveal' . ($i % 3 ? ' reveal-d' . ($i % 3) : '')); ?>
            <?php endforeach; ?>
        </div>

        <!-- PHÂN TRANG (PAGINATION) -->
        <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mb-5" aria-label="Phân trang">
                <ul class="pagination">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(shop_url(['page' => $page - 1])) ?>" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(shop_url(['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(shop_url(['page' => $page + 1])) ?>" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
