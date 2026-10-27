<?php
/**
 * FORM THÊM / SỬA SẢN PHẨM + TÍCH HỢP CKEDITOR 5 + UPLOAD NHIỀU ẢNH
 * Phụ trách: Thành viên 4 (Đáp ứng tính năng nâng cao trong đề)
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$product = null;
$extra_images = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    $img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $img_stmt->execute([$id]);
    $extra_images = $img_stmt->fetchAll();
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands")->fetchAll();

// Thư mục lưu ảnh upload
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'] ?: null;
    $price = (float)$_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock = (int)$_POST['stock'];
    $specifications = trim($_POST['specifications'] ?? '');
    $description = $_POST['description'] ?? ''; // Nội dung từ CKEditor
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $thumbnail = trim($_POST['thumbnail_url'] ?? ($product['thumbnail'] ?? ''));

    // Nếu người dùng upload file ảnh đại diện mới từ máy tính
    if (!empty($_FILES['thumbnail_file']['name'])) {
        $ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
        $filename = 'thumb_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $upload_dir . $filename)) {
            $thumbnail = 'uploads/' . $filename;
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE products
            SET category_id=?, brand_id=?, name=?, price=?, sale_price=?, stock=?, thumbnail=?, specifications=?, description=?, is_featured=?
            WHERE id=?
        ");
        $stmt->execute([$category_id, $brand_id, $name, $price, $sale_price, $stock, $thumbnail, $specifications, $description, $is_featured, $id]);
        $product_id = $id;
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, brand_id, name, price, sale_price, stock, thumbnail, specifications, description, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$category_id, $brand_id, $name, $price, $sale_price, $stock, $thumbnail, $specifications, $description, $is_featured]);
        $product_id = $pdo->lastInsertId();
    }

    // XỬ LÝ TÍNH NĂNG NÂNG CAO: UPLOAD NHIỀU ẢNH PHỤ CÙNG LÚC
    if (!empty($_FILES['gallery_files']['name'][0])) {
        $ins_img = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
        foreach ($_FILES['gallery_files']['tmp_name'] as $idx => $tmp_name) {
            if ($_FILES['gallery_files']['error'][$idx] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gallery_files']['name'][$idx], PATHINFO_EXTENSION);
                $g_name = 'gallery_' . time() . '_' . $idx . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $g_name)) {
                    $ins_img->execute([$product_id, 'uploads/' . $g_name]);
                }
            }
        }
    }

    $_SESSION['flash_success'] = 'Đã lưu thông tin phụ kiện thành công!';
    header('Location: products.php');
    exit;
}

$page_title = $id > 0 ? 'Chỉnh sửa phụ kiện' : 'Thêm phụ kiện mới';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><?= $id > 0 ? "Chỉnh Sửa Phụ Kiện #$id" : 'Thêm Phụ Kiện Mới' ?></h4>
    <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4">
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Tên phụ kiện điện tử *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="featSwitch" <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="featSwitch">Sản phẩm Nổi bật (Trang chủ)</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Danh mục *</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Thương hiệu</label>
                <select name="brand_id" class="form-select">
                    <option value="0">-- Chọn hãng --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($product['brand_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Số lượng tồn kho</label>
                <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($product['stock'] ?? 20) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Giá gốc (VNĐ) *</label>
                <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($product['price'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Giá khuyến mãi (VNĐ - để trống nếu không giảm)</label>
                <input type="number" name="sale_price" class="form-control" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>">
            </div>

            <!-- Upload ảnh đại diện & Upload nhiều ảnh phụ -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Ảnh đại diện (Chọn file từ máy hoặc dán Link ảnh)</label>
                <input type="file" name="thumbnail_file" class="form-control mb-2" accept="image/*">
                <input type="text" name="thumbnail_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh https://..." value="<?= htmlspecialchars($product['thumbnail'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-primary">
                    <i class="bi bi-images me-1"></i>Upload nhiều ảnh phụ (Tính năng nâng cao - giữ Ctrl chọn nhiều ảnh)
                </label>
                <input type="file" name="gallery_files[]" class="form-control" accept="image/*" multiple>
                <?php if (!empty($extra_images)): ?>
                    <div class="d-flex gap-2 mt-2">
                        <?php foreach ($extra_images as $img): ?>
                            <img src="<?= htmlspecialchars(strpos($img['image_url'], 'http') === 0 ? $img['image_url'] : '../' . $img['image_url']) ?>" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Thông số kỹ thuật (ngăn cách nhau bởi dấu <code>|</code>)</label>
                <input type="text" name="specifications" class="form-control" placeholder="VD: Kết nối: Bluetooth 5.2 | Pin: 30 giờ | Bảo hành: 12 tháng" value="<?= htmlspecialchars($product['specifications'] ?? '') ?>">
            </div>

            <!-- TÍCH HỢP RICH TEXT EDITOR (CKEDITOR 5) -->
            <div class="col-12">
                <label class="form-label fw-semibold text-primary">
                    <i class="bi bi-pencil-square me-1"></i>Bài viết mô tả chi tiết (Sử dụng CKEditor theo yêu cầu nâng cao)
                </label>
                <textarea name="description" id="editor" rows="6" class="form-control"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                    <i class="bi bi-save me-1"></i> Lưu Sản Phẩm
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Nhúng thư viện CKEditor 5 CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
ClassicEditor.create(document.querySelector('#editor')).catch(error => console.error(error));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
