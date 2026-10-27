<?php
/**
 * QUẢN LÝ ĐÁNH GIÁ & BÌNH LUẬN KHÁCH HÀNG (CRUD REVIEWS)
 * Phụ trách: Thành viên 6
 */
require_once __DIR__ . '/../config/db.php';
require_admin();

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
    $_SESSION['flash_success'] = "Đã xóa đánh giá #$id!";
    header('Location: reviews.php');
    exit;
}

$reviews = $pdo->query("
    SELECT r.*, u.fullname, p.name AS product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.id DESC
")->fetchAll();

$page_title = 'Quản lý Đánh giá';
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-chat-left-quote text-primary me-2"></i>Quản Lý Đánh Giá Sản Phẩm</h4>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Sản phẩm phụ kiện</th>
                <th>Số sao</th>
                <th>Nội dung bình luận</th>
                <th>Ngày gửi</th>
                <th>Xóa</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($r['fullname']) ?></td>
                    <td>
                        <a href="../product_detail.php?id=<?= $r['product_id'] ?>" target="_blank" class="text-decoration-none">
                            <?= htmlspecialchars($r['product_name']) ?>
                        </a>
                    </td>
                    <td class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></td>
                    <td><?= htmlspecialchars($r['comment']) ?></td>
                    <td class="small"><?= $r['created_at'] ?></td>
                    <td>
                        <a href="reviews.php?delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa bình luận này?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
