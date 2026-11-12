<?php
/**
 * CRUD THƯƠNG HIỆU (BRANDS)
 * Phụ trách: Thành viên 4
 *
 * Bảng `brands`: id, name, origin.
 * Xóa hãng: các sản phẩm thuộc hãng đó tự chuyển brand_id = NULL (FK ON DELETE SET NULL),
 * nên không bị mất sản phẩm.
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

// Thêm thương hiệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    if ($name === '') {
        $_SESSION['flash_error'] = 'Tên thương hiệu không được để trống!';
    } else {
        $dup = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
        $dup->execute([$name]);
        if ($dup->fetch()) {
            $_SESSION['flash_error'] = "Thương hiệu \"$name\" đã tồn tại!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO brands (name, origin) VALUES (?, ?)");
            $stmt->execute([$name, $origin !== '' ? $origin : null]);
            $_SESSION['flash_success'] = 'Đã thêm thương hiệu mới thành công!';
        }
    }
    header('Location: brands.php');
    exit;
}

// Cập nhật thương hiệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brand'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    if ($id <= 0 || $name === '') {
        $_SESSION['flash_error'] = 'Dữ liệu không hợp lệ, tên thương hiệu không được để trống!';
        header('Location: brands.php' . ($id > 0 ? "?edit_brand=$id" : ''));
        exit;
    }
    $dup = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?) AND id <> ?");
    $dup->execute([$name, $id]);
    if ($dup->fetch()) {
        $_SESSION['flash_error'] = "Thương hiệu \"$name\" đã tồn tại!";
        header("Location: brands.php?edit_brand=$id");
        exit;
    }
    $stmt = $pdo->prepare("UPDATE brands SET name = ?, origin = ? WHERE id = ?");
    $stmt->execute([$name, $origin !== '' ? $origin : null, $id]);
    $_SESSION['flash_success'] = 'Đã cập nhật thương hiệu!';
    header('Location: brands.php');
    exit;
}

// Xóa thương hiệu (dùng POST để tránh bị xóa nhầm qua link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_brand'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM brands WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = 'Đã xóa thương hiệu! Các sản phẩm thuộc hãng này được chuyển về "chưa chọn hãng".';
    }
    header('Location: brands.php');
    exit;
}

// Danh sách + số sản phẩm của từng hãng
$brands = $pdo->query("
    SELECT b.*, COUNT(p.id) AS product_count
    FROM brands b
    LEFT JOIN products p ON p.brand_id = b.id
    GROUP BY b.id, b.name, b.origin
    ORDER BY b.id DESC
")->fetchAll();

$edit_brand = null;
if (isset($_GET['edit_brand'])) {
    $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_brand']]);
    $edit_brand = $stmt->fetch() ?: null;
}

$page_title = 'Quản lý Thương hiệu';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-award text-primary me-2"></i>Quản Lý Thương Hiệu (CRUD)</h4>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><?= $edit_brand ? 'Sửa Thương Hiệu #' . $edit_brand['id'] : 'Thêm Thương Hiệu Mới' ?></h6>
            <form method="POST">
                <?php if ($edit_brand): ?>
                    <input type="hidden" name="id" value="<?= $edit_brand['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tên thương hiệu *</label>
                    <input type="text" name="name" class="form-control" required maxlength="100" placeholder="VD: Logitech, Sony, Anker..." value="<?= htmlspecialchars($edit_brand['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Xuất xứ</label>
                    <input type="text" name="origin" class="form-control" maxlength="100" placeholder="VD: Nhật Bản, Mỹ..." value="<?= htmlspecialchars($edit_brand['origin'] ?? '') ?>">
                </div>
                <?php if ($edit_brand): ?>
                    <button type="submit" name="update_brand" class="btn btn-warning fw-semibold w-100 mb-2">Lưu Cập Nhật</button>
                    <a href="brands.php" class="btn btn-light border w-100">Hủy</a>
                <?php else: ?>
                    <button type="submit" name="add_brand" class="btn btn-primary fw-semibold w-100">Thêm Thương Hiệu</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tên thương hiệu</th>
                        <th>Xuất xứ</th>
                        <th>Số sản phẩm</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Chưa có thương hiệu nào.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($brands as $b): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($b['name']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($b['origin'] ?? '-') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int)$b['product_count'] ?></span></td>
                            <td class="text-nowrap">
                                <a href="brands.php?edit_brand=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa thương hiệu này? <?= (int)$b['product_count'] > 0 ? 'Có ' . (int)$b['product_count'] . ' sản phẩm sẽ chuyển về chưa chọn hãng.' : '' ?>')">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" name="delete_brand" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
