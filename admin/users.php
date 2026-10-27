<?php
/**
 * QUẢN LÝ NGƯỜI DÙNG & PHÂN QUYỀN
 * Phụ trách: Thành viên 5
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

if (isset($_GET['toggle_status'])) {
    $uid = (int)$_GET['toggle_status'];
    if ($uid !== (int)$_SESSION['user']['id']) {
        $pdo->prepare("UPDATE users SET status = 1 - status WHERE id = ?")->execute([$uid]);
        $_SESSION['flash_success'] = "Đã cập nhật trạng thái hoạt động của tài khoản #$uid!";
    }
    header('Location: users.php');
    exit;
}

if (isset($_GET['toggle_role'])) {
    $uid = (int)$_GET['toggle_role'];
    if ($uid !== (int)$_SESSION['user']['id']) {
        $pdo->prepare("UPDATE users SET role = IF(role='admin','user','admin') WHERE id = ?")->execute([$uid]);
        $_SESSION['flash_success'] = "Đã đổi quyền hạn tài khoản #$uid!";
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

$page_title = 'Quản lý Tài khoản';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-people text-primary me-2"></i>Quản Lý Người Dùng & Phân Quyền</h4>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Quyền hạn</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'info text-dark' ?>">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $u['status'] ? 'success' : 'secondary' ?>">
                            <?= $u['status'] ? 'Hoạt động' : 'Đã khóa' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                            <a href="users.php?toggle_role=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Đổi quyền</a>
                            <a href="users.php?toggle_status=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning">Khóa/Mở</a>
                        <?php else: ?>
                            <span class="text-muted small">Đang đăng nhập</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
