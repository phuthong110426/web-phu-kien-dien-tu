<?php
/**
 * TRANG CHỦ WEBSITE BÁN PHỤ KIỆN ĐIỆN TỬ
 * Phụ trách: Thành viên 2
 */
require_once __DIR__ . '/config/db.php';

// Lấy danh mục
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();

// Lấy sản phẩm nổi bật (Flash Sale / Featured)
$featured_products = $pdo->query("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.is_featured = 1
    ORDER BY p.id DESC LIMIT 4
")->fetchAll();

// Lấy sản phẩm mới nhất
$latest_products = $pdo->query("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY p.id DESC LIMIT 8
")->fetchAll();

$page_title = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Hero Banner -->
    <div class="hero-banner p-4 p-lg-5 mb-5 shadow">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-info text-dark fw-bold px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-lightning-fill me-1"></i> SIÊU SALE PHỤ KIỆN CÔNG NGHỆ
                </span>
                <h1 class="display-5 fw-bold mb-3">Nâng Cấp Góc Làm Việc & Gaming Của Bạn</h1>
                <p class="lead text-light opacity-75 mb-4">
                    Chuyên bàn phím cơ Custom, chuột Gaming siêu nhẹ, tai nghe chống ồn ANC và phụ kiện sạc nhanh GaN chính hãng từ Logitech, Sony, Anker, Keychron.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="products.php" class="btn btn-info btn-lg fw-bold px-4">
                        <i class="bi bi-bag-heart me-2"></i>Mua Sắm Ngay
                    </a>
                    <a href="products.php?sort=sale" class="btn btn-outline-light btn-lg px-4">
                        Xem Ưu Đãi Hot
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <img src="https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=700&auto=format&fit=crop&q=80" alt="Gaming Gear" class="img-fluid rounded-4 shadow-lg" style="max-height: 300px; object-fit: cover;">
            </div>
        </div>
    </div>

    <!-- Danh mục phụ kiện -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0"><i class="bi bi-grid-fill text-primary me-2"></i>Danh Mục Phụ Kiện</h4>
            <a href="products.php" class="text-decoration-none fw-semibold small">Xem tất cả <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?= $cat['id'] ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 product-card">
                            <div class="fs-1 text-primary mb-2">
                                <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                            <small class="text-muted">Khám phá ngay</small>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sản phẩm nổi bật / Giảm giá Sốc -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0"><i class="bi bi-fire text-danger me-2"></i>Phụ Kiện Nổi Bật Đang Giảm Giá</h4>
            <a href="products.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Xem thêm</a>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_products as $p): ?>
                <?php
                $final_price = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
                $discount = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card h-100 d-flex flex-column">
                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-img-wrapper d-block">
                            <?php if ($discount > 0): ?>
                                <span class="badge-sale">-<?= $discount ?>%</span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="small text-muted mb-1">
                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></span>
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
                                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tất cả phụ kiện mới nhất -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0"><i class="bi bi-stars text-warning me-2"></i>Sản Phẩm Mới Nhất</h4>
            <a href="products.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Xem toàn bộ cửa hàng</a>
        </div>
        <div class="row g-4">
            <?php foreach ($latest_products as $p): ?>
                <?php
                $final_price = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
                $discount = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card h-100 d-flex flex-column">
                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-img-wrapper d-block">
                            <?php if ($discount > 0): ?>
                                <span class="badge-sale">-<?= $discount ?>%</span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="small text-muted mb-1">
                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($p['brand_name'] ?? 'TechGear') ?></span>
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
                                <a href="cart.php?action=add&id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
                                    <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ hàng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
