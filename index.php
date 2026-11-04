<?php
/**
 * TRANG CHỦ WEBSITE BÁN PHỤ KIỆN ĐIỆN TỬ (phong cách apple.com)
 * Phụ trách: Thành viên 2
 *  1. Banner trượt 3 slide (Bootstrap Carousel) có nút tạm dừng
 *  2. Thanh danh mục dạng icon
 *  3. Hai ô sản phẩm lớn (sáng / tối)
 *  4. Kệ "Mới nhất" cuộn ngang có nút trái / phải
 *  5. Lưới sản phẩm đang giảm giá
 *  6. Vì sao chọn TechGear + Banner kêu gọi đăng ký
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/product_card.php';

// ---- Danh mục ----
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();

// ---- Nội dung 3 slide banner (gắn với sản phẩm có sẵn trong CSDL) ----
$hero_slides = [
    [
        'id' => 3, 'theme' => 'theme-dark',
        'eyebrow' => 'Chống ồn chủ động',
        'headline' => 'Yên lặng. Tuyệt đối.',
        'tagline' => 'Sony WH-1000XM5. Chỉ còn bạn và âm nhạc.',
        'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=1600&auto=format&fit=crop&q=80',
    ],
    [
        'id' => 1, 'theme' => 'theme-light',
        'eyebrow' => 'Mới',
        'headline' => 'Nhẹ. Đến khó tin.',
        'tagline' => 'Logitech G Pro X Superlight 2. Chỉ 60 gram.',
        'image' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=1600&auto=format&fit=crop&q=80',
    ],
    [
        'id' => 2, 'theme' => 'theme-soft',
        'eyebrow' => 'Bàn phím cơ không dây',
        'headline' => 'Gõ là mê.',
        'tagline' => 'Keychron K8 Pro. Hotswap, QMK/VIA, đèn RGB.',
        'image' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=1600&auto=format&fit=crop&q=80',
    ],
];
$hero_ids = array_column($hero_slides, 'id');
$placeholders = implode(',', array_fill(0, count($hero_ids), '?'));
$stmt = $pdo->prepare("SELECT id, price, sale_price FROM products WHERE id IN ($placeholders)");
$stmt->execute($hero_ids);
$hero_prices = [];
foreach ($stmt->fetchAll() as $row) {
    $hero_prices[$row['id']] = product_final_price($row);
}

$base_select = "
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
";

// ---- 2 ô lớn: sản phẩm nổi bật xem nhiều nhất (không trùng banner) ----
$stmt = $pdo->prepare("$base_select WHERE p.is_featured = 1 AND p.id NOT IN ($placeholders) ORDER BY p.views DESC LIMIT 2");
$stmt->execute($hero_ids);
$tile_products = $stmt->fetchAll();

// ---- Kệ cuộn ngang: sản phẩm mới nhất ----
$latest_products = $pdo->query("$base_select ORDER BY p.id DESC LIMIT 10")->fetchAll();

// ---- Lưới: sản phẩm đang giảm giá nhiều nhất ----
$sale_products = $pdo->query("
    $base_select
    WHERE p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price
    ORDER BY (p.price - p.sale_price) / p.price DESC
    LIMIT 4
")->fetchAll();

$page_title = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-3">

    <!-- ========== 1. BANNER TRƯỢT ========== -->
    <section id="heroCarousel" class="carousel slide carousel-fade hero-carousel mb-3" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="false" aria-label="Sản phẩm nổi bật">
        <div class="carousel-inner">
            <?php foreach ($hero_slides as $i => $s): ?>
                <?php
                $has_product = isset($hero_prices[$s['id']]);
                $detail_url = $has_product ? 'product_detail.php?id=' . $s['id'] : 'products.php';
                ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <div class="hero-slide <?= $s['theme'] ?>">
                        <div class="hero-copy">
                            <div class="hero-eyebrow"><?= htmlspecialchars($s['eyebrow']) ?></div>
                            <h1 class="hero-headline"><?= htmlspecialchars($s['headline']) ?></h1>
                            <p class="hero-tagline"><?= htmlspecialchars($s['tagline']) ?></p>
                            <p class="hero-price">
                                <?= $has_product ? 'Chỉ từ ' . format_price($hero_prices[$s['id']]) : 'Chính hãng 100%' ?>
                            </p>
                            <div class="hero-ctas">
                                <?php if ($has_product): ?>
                                    <a href="cart.php?action=add&id=<?= $s['id'] ?>" class="btn btn-primary">Mua</a>
                                <?php endif; ?>
                                <a href="<?= $detail_url ?>" class="link-arrow">Tìm hiểu thêm <i class="bi bi-chevron-right small"></i></a>
                            </div>
                        </div>
                        <div class="hero-visual">
                            <img src="<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['headline']) ?>" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="hero-controls">
            <div class="carousel-indicators hero-dots">
                <?php foreach ($hero_slides as $i => $s): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" <?= $i === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="hero-toggle" data-carousel-toggle="#heroCarousel" aria-label="Tạm dừng banner">
                <i class="bi bi-pause-fill"></i>
            </button>
        </div>
    </section>

    <!-- ========== 2. THANH DANH MỤC ========== -->
    <section class="mb-4 reveal">
        <nav class="store-nav" aria-label="Danh mục sản phẩm">
            <a href="products.php" class="store-nav-item">
                <span class="store-nav-icon"><i class="bi bi-grid"></i></span>
                Tất cả
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="products.php?category=<?= $cat['id'] ?>" class="store-nav-item">
                    <span class="store-nav-icon"><i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i></span>
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <!-- ========== 3. HAI Ô SẢN PHẨM LỚN ========== -->
    <?php if (!empty($tile_products)): ?>
        <section class="row g-3 mb-5">
            <?php foreach ($tile_products as $i => $p): ?>
                <div class="col-lg-6 reveal <?= $i === 1 ? 'reveal-d1' : '' ?>">
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="tile <?= $i === 0 ? 'theme-dark' : 'theme-light' ?>">
                        <div class="tile-eyebrow"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></div>
                        <h3><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="tile-sub"><?= htmlspecialchars(product_spec_line($p)) ?></p>
                        <p class="tile-price">Chỉ từ <?= format_price(product_final_price($p)) ?></p>
                        <span class="link-arrow">Tìm hiểu thêm <i class="bi bi-chevron-right small"></i></span>
                        <div class="tile-visual mt-4">
                            <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- ========== 4. KỆ MỚI NHẤT (CUỘN NGANG) ========== -->
    <section class="mb-5 reveal" data-shelf>
        <div class="section-head">
            <h2 class="section-title">Mới nhất. <span class="text-muted">Vừa lên kệ.</span></h2>
            <div class="shelf-controls">
                <button type="button" class="shelf-btn" data-shelf-prev aria-label="Xem trước"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="shelf-btn" data-shelf-next aria-label="Xem tiếp"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
        <div class="shelf-track">
            <?php foreach ($latest_products as $p): ?>
                <div class="shelf-item"><?php render_product_card($p, null); ?></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ========== 5. ĐANG GIẢM GIÁ ========== -->
    <?php if (!empty($sale_products)): ?>
        <section class="mb-5">
            <div class="section-head reveal">
                <h2 class="section-title">Ưu đãi. <span class="text-muted">Giá tốt nhất tuần này.</span></h2>
                <a href="products.php?sort=price_asc" class="link-arrow d-none d-md-inline">Xem tất cả <i class="bi bi-chevron-right small"></i></a>
            </div>
            <div class="row g-4">
                <?php foreach ($sale_products as $i => $p): ?>
                    <?php render_product_card($p, 'col-sm-6 col-lg-3 reveal' . ($i % 4 ? ' reveal-d' . ($i % 4) : '')); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ========== 6. VÌ SAO CHỌN TECHGEAR ========== -->
    <section class="mb-5">
        <div class="section-head reveal">
            <h2 class="section-title">Vì sao chọn TechGear. <span class="text-muted">Mua sắm an tâm.</span></h2>
        </div>
        <div class="row g-3">
            <?php
            $reasons = [
                ['bi-truck', 'Giao hàng miễn phí', 'Toàn quốc, được kiểm tra hàng trước khi thanh toán.'],
                ['bi-shield-check', 'Chính hãng 100%', 'Bảo hành 12 – 24 tháng tại hãng, hoàn tiền nếu phát hiện hàng giả.'],
                ['bi-arrow-repeat', 'Đổi mới 30 ngày', 'Lỗi do nhà sản xuất? Đổi ngay sản phẩm mới, không cần chờ.'],
                ['bi-chat-heart', 'Tư vấn tận tâm', 'Hotline 1900 6868 hỗ trợ chọn phụ kiện hợp với bạn.'],
            ];
            foreach ($reasons as $i => $r): ?>
                <div class="col-sm-6 col-lg-3 reveal <?= $i ? 'reveal-d' . $i : '' ?>">
                    <div class="why-card">
                        <i class="bi <?= $r[0] ?> why-icon"></i>
                        <h5><?= $r[1] ?></h5>
                        <p><?= $r[2] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ========== 7. BANNER KÊU GỌI ========== -->
    <section class="cta-banner mb-5 reveal">
        <?php if (is_logged_in()): ?>
            <h2>Góc làm việc trong mơ.<br>Bắt đầu từ hôm nay.</h2>
            <p>Khám phá hơn 6 danh mục phụ kiện chính hãng từ những thương hiệu hàng đầu.</p>
            <a href="products.php" class="btn btn-primary btn-lg">Vào cửa hàng</a>
        <?php else: ?>
            <h2>Trở thành thành viên.<br>Nhận ưu đãi riêng.</h2>
            <p>Theo dõi đơn hàng, lưu địa chỉ giao hàng và đánh giá sản phẩm bạn yêu thích.</p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a href="register.php" class="btn btn-primary btn-lg">Đăng ký miễn phí</a>
                <a href="login.php" class="btn btn-lg link-arrow" style="color: var(--tg-link-dark);">Đăng nhập <i class="bi bi-chevron-right small"></i></a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
