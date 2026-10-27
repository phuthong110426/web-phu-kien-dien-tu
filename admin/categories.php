<?php
/**
 * CRUD DANH MỤC PHỤ KIỆN & THƯƠNG HIỆU
 * Phụ trách: Thành viên 4
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

// Thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-cpu');
    $desc = trim($_POST['description'] ?? '');
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $icon, $desc]);
        $_SESSION['flash_success'] = 'Đã thêm danh mục mới thành công!';
    }
    header('Location: categories.php');
    exit;
}

// Cập nhật danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-cpu');
    $desc = trim($_POST['description'] ?? '');
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $icon, $desc, $id]);
    $_SESSION['flash_success'] = 'Đã cập nhật danh mục!';
    header('Location: categories.php');
    exit;
}

// Xóa danh mục
if (isset($_GET['delete_cat'])) {
    $id = (int)$_GET['delete_cat'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    $_SESSION['flash_success'] = 'Đã xóa danh mục!';
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$edit_cat = null;
if (isset($_GET['edit_cat'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_cat']]);
    $edit_cat = $stmt->fetch();
}

$page_title = 'Quản lý Danh mục';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-tags text-primary me-2"></i>Quản Lý Danh Mục Phụ Kiện (CRUD)</h4>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><?= $edit_cat ? 'Sửa Danh Mục #' . $edit_cat['id'] : 'Thêm Danh Mục Mới' ?></h6>
            <form method="POST">
                <?php if ($edit_cat): ?>
                    <input type="hidden" name="id" value="<?= $edit_cat['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tên danh mục *</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Icon Bootstrap (VD: bi-headphones, bi-keyboard)</label>
                    <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($edit_cat['icon'] ?? 'bi-cpu') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mô tả ngắn</label>
                    <textarea name="description" rows="2" class="form-control"><?= htmlspecialchars($edit_cat['description'] ?? '') ?></textarea>
                </div>
                <?php if ($edit_cat): ?>
                    <button type="submit" name="update_category" class="btn btn-warning fw-semibold w-100 mb-2">Lưu Cập Nhật</button>
                    <a href="categories.php" class="btn btn-light border w-100">Hủy</a>
                <?php else: ?>
                    <button type="submit" name="add_category" class="btn btn-primary fw-semibold w-100">Thêm Danh Mục</button>
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
                        <th>Icon</th>
                        <th>Tên danh mục</th>
                        <th>Mô tả</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td class="fs-4 text-primary"><i class="bi <?= htmlspecialchars($c['icon']) ?>"></i></td>
                            <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($c['description']) ?></td>
                            <td>
                                <a href="categories.php?edit_cat=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="categories.php?delete_cat=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa danh mục này?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
