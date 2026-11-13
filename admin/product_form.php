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

// ============================================================
// UPLOAD ẢNH AN TOÀN
//  - Chỉ nhận jpg / png / webp / gif, kiểm tra theo NỘI DUNG file (finfo + getimagesize),
//    không tin đuôi file hay Content-Type do trình duyệt gửi.
//  - Đuôi file lưu xuống đĩa lấy từ loại ảnh thật, tên file ngẫu nhiên.
//  - Giới hạn dung lượng 5MB / ảnh, tối đa 10 ảnh phụ mỗi lần lưu.
// ============================================================
const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const GALLERY_MAX_FILES = 10;
const ALLOWED_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

/**
 * Kiểm tra và lưu 1 file ảnh upload. Trả về đường dẫn tương đối ('uploads/xxx.jpg') hoặc null + $error.
 */
function save_uploaded_image($tmp_name, $size, $err_code, $label, $prefix, $upload_dir, &$error) {
    if ($err_code !== UPLOAD_ERR_OK) {
        $error = $err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE
            ? "$label: file quá lớn (tối đa 5MB)."
            : "$label: upload thất bại (mã lỗi $err_code).";
        return null;
    }
    if (!is_uploaded_file($tmp_name)) {
        $error = "$label: file không hợp lệ.";
        return null;
    }
    if ($size > UPLOAD_MAX_BYTES) {
        $error = "$label: file quá lớn (tối đa 5MB).";
        return null;
    }

    // Xác định loại file thật từ nội dung
    $mime = '';
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp_name) ?: '';
    }
    $info = @getimagesize($tmp_name); // false nếu không phải ảnh thật
    if ($mime === '' && $info) {
        $mime = $info['mime'];
    }
    if (!$info || !isset(ALLOWED_IMAGE_TYPES[$mime])) {
        $error = "$label: chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.";
        return null;
    }

    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . ALLOWED_IMAGE_TYPES[$mime];
    if (!move_uploaded_file($tmp_name, $upload_dir . $filename)) {
        $error = "$label: không lưu được file vào thư mục uploads/.";
        return null;
    }
    return 'uploads/' . $filename;
}

// Thư mục lưu ảnh upload (0755, không dùng 0777)
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
// Lớp bảo vệ thứ 2: cấm Apache thực thi file script trong uploads/
$htaccess = $upload_dir . '.htaccess';
if (is_dir($upload_dir) && !is_file($htaccess)) {
    @file_put_contents($htaccess, "<FilesMatch \"\\.(php|phtml|phar|php[0-9])$\">\n    Require all denied\n</FilesMatch>\n");
}

// XÓA TỪNG ẢNH PHỤ (nút X trên thumbnail, gửi delete_image_id cùng form chính)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image_id']) && $id > 0) {
    $img_id = (int)$_POST['delete_image_id'];
    $img_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
    $img_stmt->execute([$img_id, $id]);
    $img_row = $img_stmt->fetch();

    if ($img_row) {
        $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?")->execute([$img_id, $id]);
        if (strpos($img_row['image_url'], 'uploads/') === 0) {
            // Chỉ xóa file khi không còn dòng nào khác dùng chung ảnh này
            $still_used = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE image_url = ?");
            $still_used->execute([$img_row['image_url']]);
            if ((int)$still_used->fetchColumn() === 0) {
                $file_path = $upload_dir . basename($img_row['image_url']);
                if (is_file($file_path)) {
                    @unlink($file_path);
                }
            }
        }
        $_SESSION['flash_success'] = 'Đã xóa ảnh phụ!';
    } else {
        $_SESSION['flash_error'] = 'Không tìm thấy ảnh cần xóa!';
    }
    header("Location: product_form.php?id=$id");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đọc dữ liệu form (chỉ nhận chuỗi, tránh lỗi khi ai đó gửi mảng)
    $read = function ($key) {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    };
    $old_thumbnail = $product['thumbnail'] ?? '';

    $name           = $read('name');
    $category_id    = (int)$read('category_id');
    $brand_id       = (int)$read('brand_id');
    $price_raw      = $read('price');
    $sale_raw       = $read('sale_price');
    $stock_raw      = $read('stock');
    $specifications = $read('specifications');
    $description    = isset($_POST['description']) && is_string($_POST['description']) ? $_POST['description'] : ''; // HTML từ CKEditor
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $thumb_url      = $read('thumbnail_url');

    // ---------- VALIDATE ----------
    if ($name === '') {
        $errors[] = 'Tên phụ kiện không được để trống.';
    } elseif (mb_strlen($name, 'UTF-8') > 200) {
        $errors[] = 'Tên phụ kiện tối đa 200 ký tự.';
    }

    if (!in_array($category_id, array_map('intval', array_column($categories, 'id')), true)) {
        $errors[] = 'Vui lòng chọn danh mục hợp lệ.';
    }

    if ($brand_id !== 0 && !in_array($brand_id, array_map('intval', array_column($brands, 'id')), true)) {
        $errors[] = 'Thương hiệu đã chọn không tồn tại.';
    }
    $brand_id = $brand_id > 0 ? $brand_id : null;

    $price = null;
    if (!is_numeric($price_raw) || (float)$price_raw <= 0) {
        $errors[] = 'Giá gốc phải là số lớn hơn 0.';
    } elseif ((float)$price_raw > 1000000000) {
        $errors[] = 'Giá gốc quá lớn (tối đa 1.000.000.000 ₫).';
    } else {
        $price = (float)$price_raw;
    }

    $sale_price = null;
    if ($sale_raw !== '') {
        if (!is_numeric($sale_raw) || (float)$sale_raw <= 0) {
            $errors[] = 'Giá khuyến mãi phải là số lớn hơn 0 (hoặc để trống nếu không giảm).';
        } elseif ($price !== null && (float)$sale_raw >= $price) {
            $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
        } else {
            $sale_price = (float)$sale_raw;
        }
    }

    $stock = 0;
    if ($stock_raw === '' || filter_var($stock_raw, FILTER_VALIDATE_INT) === false || (int)$stock_raw < 0) {
        $errors[] = 'Số lượng tồn kho phải là số nguyên từ 0 trở lên.';
    } elseif ((int)$stock_raw > 1000000) {
        $errors[] = 'Số lượng tồn kho quá lớn (tối đa 1.000.000).';
    } else {
        $stock = (int)$stock_raw;
    }

    // Ảnh đại diện: chọn file mới, hoặc dùng link http(s), hoặc giữ ảnh cũ đang lưu
    $has_thumb_file = isset($_FILES['thumbnail_file']['error']) && $_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_NO_FILE;
    if (!$has_thumb_file) {
        if ($thumb_url === '') {
            $errors[] = 'Vui lòng chọn ảnh đại diện (upload file hoặc dán link ảnh).';
        } elseif ($thumb_url === $old_thumbnail) {
            // giữ nguyên ảnh hiện tại (có thể là uploads/... hoặc link http)
        } elseif (!preg_match('#^https?://#i', $thumb_url) || !filter_var($thumb_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Link ảnh đại diện phải bắt đầu bằng http:// hoặc https://.';
        } elseif (strlen($thumb_url) > 255) {
            $errors[] = 'Link ảnh đại diện quá dài (tối đa 255 ký tự).';
        }
    }

    // ---------- NẾU CÓ LỖI: hiện lại form với dữ liệu đã nhập, KHÔNG lưu gì ----------
    // ---------- NẾU HỢP LỆ: xử lý upload ảnh đại diện rồi mới lưu ----------
    $thumbnail = $thumb_url;
    if (!$errors && $has_thumb_file) {
        $up_err = '';
        $saved = save_uploaded_image(
            $_FILES['thumbnail_file']['tmp_name'], $_FILES['thumbnail_file']['size'],
            $_FILES['thumbnail_file']['error'], 'Ảnh đại diện', 'thumb', $upload_dir, $up_err
        );
        if ($saved === null) {
            $errors[] = $up_err;
        } else {
            $thumbnail = $saved;
        }
    }

    if ($errors) {
        // Giữ lại dữ liệu người dùng đã nhập để hiển thị trong form
        $product = array_merge($product ?: [], [
            'name' => $name, 'category_id' => $category_id, 'brand_id' => $brand_id ?? 0,
            'price' => $price_raw, 'sale_price' => $sale_raw, 'stock' => $stock_raw,
            'specifications' => $specifications, 'description' => $description,
            'is_featured' => $is_featured, 'thumbnail' => $thumb_url,
        ]);
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET category_id=?, brand_id=?, name=?, price=?, sale_price=?, stock=?, thumbnail=?, specifications=?, description=?, is_featured=?
                WHERE id=?
            ");
            $stmt->execute([$category_id, $brand_id, $name, $price, $sale_price, $stock, $thumbnail, $specifications, $description, $is_featured, $id]);
            $product_id = $id;

            // Đã thay ảnh đại diện bằng ảnh khác -> dọn file cũ trong uploads/
            if ($thumbnail !== $old_thumbnail && strpos($old_thumbnail, 'uploads/') === 0) {
                $old_file = $upload_dir . basename($old_thumbnail);
                if (is_file($old_file)) {
                    @unlink($old_file);
                }
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, brand_id, name, price, sale_price, stock, thumbnail, specifications, description, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$category_id, $brand_id, $name, $price, $sale_price, $stock, $thumbnail, $specifications, $description, $is_featured]);
            $product_id = $pdo->lastInsertId();
        }

        // XỬ LÝ TÍNH NĂNG NÂNG CAO: UPLOAD NHIỀU ẢNH PHỤ CÙNG LÚC
        // $_FILES['gallery_files'] có dạng mảng song song: name[], tmp_name[], error[], size[]...
        $warnings = [];
        if (isset($_FILES['gallery_files']['name']) && is_array($_FILES['gallery_files']['name'])) {
            $ins_img = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
            $count = 0;
            foreach ($_FILES['gallery_files']['tmp_name'] as $idx => $tmp_name) {
                $code = $_FILES['gallery_files']['error'][$idx];
                if ($code === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $orig = basename((string)$_FILES['gallery_files']['name'][$idx]);
                if (++$count > GALLERY_MAX_FILES) {
                    $warnings[] = 'Chỉ lưu tối đa ' . GALLERY_MAX_FILES . ' ảnh phụ mỗi lần, các ảnh còn lại bị bỏ qua.';
                    break;
                }
                $up_err = '';
                $saved = save_uploaded_image(
                    $tmp_name, $_FILES['gallery_files']['size'][$idx], $code,
                    "Ảnh phụ \"$orig\"", 'gallery', $upload_dir, $up_err
                );
                if ($saved === null) {
                    $warnings[] = $up_err;
                } else {
                    $ins_img->execute([$product_id, $saved]);
                }
            }
        }

        $_SESSION['flash_success'] = 'Đã lưu thông tin phụ kiện thành công!';
        if ($warnings) {
            $_SESSION['flash_error'] = 'Sản phẩm đã lưu nhưng có ảnh phụ bị bỏ qua: ' . implode(' ', $warnings);
        }
        header('Location: products.php');
        exit;
    }
}

$page_title = $id > 0 ? 'Chỉnh sửa phụ kiện' : 'Thêm phụ kiện mới';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><?= $id > 0 ? "Chỉnh Sửa Phụ Kiện #$id" : 'Thêm Phụ Kiện Mới' ?></h4>
    <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Chưa lưu được, vui lòng sửa các lỗi sau:</div>
        <ul class="mb-0 small">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4">
    <form method="POST" enctype="multipart/form-data">
        <!-- Nút lưu mặc định (ẩn): để nhấn Enter trong ô nhập thì lưu sản phẩm, không kích hoạt nút xóa ảnh X bên dưới -->
        <button type="submit" class="d-none" tabindex="-1" aria-hidden="true"></button>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Tên phụ kiện điện tử *</label>
                <input type="text" name="name" class="form-control" required maxlength="200" value="<?= htmlspecialchars($product['name'] ?? '') ?>">
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
                <input type="number" name="stock" class="form-control" required min="0" step="1" value="<?= htmlspecialchars($product['stock'] ?? 20) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Giá gốc (VNĐ) *</label>
                <input type="number" name="price" class="form-control" required min="1" step="1" value="<?= htmlspecialchars($product['price'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Giá khuyến mãi (VNĐ - để trống nếu không giảm)</label>
                <input type="number" name="sale_price" class="form-control" min="1" step="1" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>">
            </div>

            <!-- Upload ảnh đại diện & Upload nhiều ảnh phụ -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Ảnh đại diện (Chọn file từ máy hoặc dán Link ảnh)</label>
                <input type="file" name="thumbnail_file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif">
                <input type="text" name="thumbnail_url" class="form-control form-control-sm" placeholder="Hoặc dán URL ảnh https://..." value="<?= htmlspecialchars($product['thumbnail'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-primary">
                    <i class="bi bi-images me-1"></i>Upload nhiều ảnh phụ (Tính năng nâng cao - giữ Ctrl chọn nhiều ảnh)
                </label>
                <input type="file" name="gallery_files[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" multiple>
                <?php if (!empty($extra_images)): ?>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <?php foreach ($extra_images as $img): ?>
                            <div class="position-relative d-inline-block">
                                <img src="<?= htmlspecialchars(strpos($img['image_url'], 'http') === 0 ? $img['image_url'] : '../' . $img['image_url']) ?>"
                                     class="rounded border" style="width: 64px; height: 64px; object-fit: cover;">
                                <button type="submit" name="delete_image_id" value="<?= $img['id'] ?>" formnovalidate
                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                                        style="width: 22px; height: 22px; transform: translate(35%, -35%); font-size: 13px;"
                                        title="Xóa ảnh phụ này"
                                        onclick="return confirm('Bạn có chắc muốn xóa ảnh phụ này?')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
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