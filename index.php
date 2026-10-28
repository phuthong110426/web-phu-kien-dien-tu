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

// Hàm in 1 thẻ sản phẩm (dùng lại cho 2 khu vực bên dưới)
function render_product_card($p) {
    $final_price = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $discount = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
    ?>
    <div class="col-sm-6 col-lg-3">
        <div class="product-card h-100 d-flex flex-column">
            <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-img-wrapper d-block">
                <?php if ($discount > 0): ?>
                    <span class="badge-sale">Giảm <?= $discount ?>%</span>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            </a>
            <div class="p-3 pt-3 d-flex flex-column flex-grow-1">
                <div class="small text-muted mb-1"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?> · <?= htmlspecialchars($p['category_name']) ?></div>
                <h6 class="fw-semibold mb-2" style="line-height: 1.35;">
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                </h6>
                <div class="mt-auto">
                    <div class="mb-3">
                        <span class="fs-5 fw-semibold text-danger"><?= format_price($final_price) ?></span>
                        <?php if ($discount > 0): ?>
                            <del class="small ms-1"><?= format_price($p['price']) ?></del>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="cart.php?action=add&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">Thêm vào giỏ</a>
                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-light btn-sm">Chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

$page_title = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- HERO kiểu Apple -->
    <section class="hero-apple mb-4">
        <div class="hero-eyebrow">Mới · Bộ sưu tập Gaming Gear 2026</div>
        <h1 class="hero-title">Phụ kiện xịn.<br>Cho góc làm việc của bạn.</h1>
        <p class="hero-sub">
            Bàn phím cơ, chuột siêu nhẹ, tai nghe chống ồn và sạc nhanh GaN chính hãng từ Logitech, Sony, Anker, Keychron.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="products.php" class="btn btn-primary btn-lg">Mua ngay</a>
            <a href="products.php?sort=popular" class="btn btn-lg link-arrow">Xem bán chạy <i class="bi bi-chevron-right small"></i></a>
        </div>
        <img src="https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=1400&auto=format&fit=crop&q=80" alt="Gaming Gear" class="hero-img">
    </section>

    <!-- Cam kết -->
    <section class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="feature-item">
                <i class="bi bi-truck"></i>
                <div><strong>Giao hàng miễn phí</strong><span>Toàn quốc, kiểm tra trước khi nhận</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-item">
                <i class="bi bi-shield-check"></i>
                <div><strong>Chính hãng 100%</strong><span>Bảo hành 12 – 24 tháng</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-item">
                <i class="bi bi-arrow-repeat"></i>
                <div><strong>Đổi mới 30 ngày</strong><span>Nếu lỗi từ nhà sản xuất</span></div>
            </div>
        </div>
    </section>

    <!-- Danh mục phụ kiện -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="section-title">Mua theo danh mục.</h2>
                <p class="section-sub">Tìm đúng món bạn cần.</p>
            </div>
            <a href="products.php" class="link-arrow d-none d-md-inline">Xem tất cả <i class="bi bi-chevron-right small"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?= $cat['id'] ?>" class="cat-tile">
                        <div class="cat-icon"><i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i></div>
                        <h6 class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></h6>
                        <small>Khám phá</small>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sản phẩm nổi bật -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="section-title">Nổi bật. <span class="text-muted">Đang giảm giá.</span></h2>
            </div>
            <a href="products.php" class="link-arrow d-none d-md-inline">Xem thêm <i class="bi bi-chevron-right small"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_products as $p) render_product_card($p); ?>
        </div>
    </section>

    <!-- Sản phẩm mới nhất -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="section-title">Mới nhất. <span class="text-muted">Vừa lên kệ.</span></h2>
            </div>
            <a href="products.php" class="link-arrow d-none d-md-inline">Toàn bộ cửa hàng <i class="bi bi-chevron-right small"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($latest_products as $p) render_product_card($p); ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
