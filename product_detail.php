<?php
/**
 * TRANG CHI TIẾT PHỤ KIỆN + XEM NHIỀU ẢNH + ĐÁNH GIÁ SAO
 * Phụ trách: Thành viên 3 (Chi tiết) & Thành viên 6 (Bình luận/Đánh giá)
 */
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_error'] = 'Sản phẩm không tồn tại!';
    header('Location: products.php');
    exit;
}

// Tăng lượt xem
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$id]);

// Xử lý gửi đánh giá (Thành viên 6)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Vui lòng đăng nhập để gửi đánh giá!';
        header("Location: login.php");
        exit;
    }
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if ($comment !== '') {
        $ins = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $ins->execute([$_SESSION['user']['id'], $id, $rating, $comment]);
        $_SESSION['flash_success'] = 'Cảm ơn bạn đã gửi đánh giá cho sản phẩm!';
        header("Location: product_detail.php?id=$id");
        exit;
    }
}

// Lấy danh sách nhiều ảnh phụ của sản phẩm
$img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$img_stmt->execute([$id]);
$extra_images = $img_stmt->fetchAll();

// Lấy danh sách đánh giá
$rev_stmt = $pdo->prepare("
    SELECT r.*, u.fullname
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.id DESC
");
$rev_stmt->execute([$id]);
$reviews = $rev_stmt->fetchAll();

$final_price = ($product['sale_price'] && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
$specs = array_filter(array_map('trim', explode('|', $product['specifications'] ?? '')));

$page_title = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row g-4">
            <!-- Gallery Nhiều Ảnh Sản Phẩm -->
            <div class="col-lg-5">
                <div class="border rounded-4 overflow-hidden mb-3 bg-light text-center">
                    <img id="mainProductImage" src="<?= htmlspecialchars($product['thumbnail']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid" style="height: 360px; width: 100%; object-fit: cover;">
                </div>
                <div class="d-flex gap-2 overflow-auto pb-2">
                    <img src="<?= htmlspecialchars($product['thumbnail']) ?>" onclick="document.getElementById('mainProductImage').src=this.src" class="rounded-3 border border-2 border-primary" style="width: 75px; height: 75px; object-fit: cover; cursor: pointer;">
                    <?php foreach ($extra_images as $img): ?>
                        <img src="<?= htmlspecialchars($img['image_url']) ?>" onclick="document.getElementById('mainProductImage').src=this.src" class="rounded-3 border" style="width: 75px; height: 75px; object-fit: cover; cursor: pointer;">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Thông tin mua hàng -->
            <div class="col-lg-7">
                <div class="mb-2">
                    <span class="badge bg-primary"><?= htmlspecialchars($product['brand_name'] ?? 'Chính hãng') ?></span>
                    <span class="badge bg-light text-dark border ms-1"><i class="bi bi-eye me-1"></i><?= $product['views'] ?> lượt xem</span>
                </div>
                <h3 class="fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h3>

                <div class="bg-light p-3 rounded-4 mb-3">
                    <span class="fs-2 fw-bold text-danger"><?= format_price($final_price) ?></span>
                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <del class="text-muted ms-2 fs-5"><?= format_price($product['price']) ?></del>
                        <span class="badge bg-danger ms-2">Tiết kiệm <?= format_price($product['price'] - $product['sale_price']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Bảng thông số kỹ thuật -->
                <?php if (!empty($specs)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-uppercase small text-secondary mb-2"><i class="bi bi-cpu me-1"></i>Thông số kỹ thuật nổi bật:</h6>
                        <div class="row g-2">
                            <?php foreach ($specs as $spec): ?>
                                <div class="col-md-6">
                                    <div class="p-2 border rounded-3 bg-white small">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i> <?= htmlspecialchars($spec) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="cart.php" method="GET" class="d-flex align-items-center gap-3 mb-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <div style="width: 110px;">
                        <input type="number" name="qty" class="form-control form-control-lg text-center fw-bold" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold flex-grow-1">
                        <i class="bi bi-cart-plus me-2"></i>Thêm Vào Giỏ Hàng
                    </button>
                </form>

                <div class="row g-2 small text-secondary border-top pt-3">
                    <div class="col-sm-6"><i class="bi bi-shield-check text-primary me-2"></i>Bảo hành chính hãng 12 - 24 tháng</div>
                    <div class="col-sm-6"><i class="bi bi-truck text-primary me-2"></i>Giao hàng toàn quốc, kiểm tra trước khi nhận</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Bài viết mô tả chi tiết (Render từ CKEditor) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-richtext text-primary me-2"></i>Đặc Điểm Nổi Bật & Đánh Giá Chi Tiết</h5>
                <div class="lh-lg">
                    <?= $product['description'] ?: '<p class="text-muted">Đang cập nhật mô tả chi tiết...</p>' ?>
                </div>
            </div>
        </div>

        <!-- Đánh giá của khách hàng (Thành viên 6) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Đánh Giá Từ Khách Hàng (<?= count($reviews) ?>)</h5>

                <form method="POST" class="mb-4 bg-light p-3 rounded-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Chọn số sao:</label>
                        <select name="rating" class="form-select form-select-sm">
                            <option value="5">⭐⭐⭐⭐⭐ (5 sao - Tuyệt vời)</option>
                            <option value="4">⭐⭐⭐⭐ (4 sao - Hài lòng)</option>
                            <option value="3">⭐⭐⭐ (3 sao - Bình thường)</option>
                            <option value="2">⭐⭐ (2 sao - Tạm được)</option>
                            <option value="1">⭐ (1 sao - Không hài lòng)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="comment" rows="2" class="form-control form-control-sm" placeholder="Chia sẻ cảm nhận của bạn về phụ kiện này..." required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-sm btn-primary fw-semibold">Gửi Đánh Giá</button>
                </form>

                <?php if (empty($reviews)): ?>
                    <p class="text-muted small text-center my-3">Chưa có đánh giá nào. Hãy là người đầu tiên nhận xét!</p>
                <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="border-bottom pb-2">
                                <div class="d-flex justify-content-between">
                                    <strong class="small"><?= htmlspecialchars($rev['fullname']) ?></strong>
                                    <span class="text-warning small"><?= str_repeat('★', (int)$rev['rating']) ?></span>
                                </div>
                                <p class="small mb-1 mt-1"><?= htmlspecialchars($rev['comment']) ?></p>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $rev['created_at'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
