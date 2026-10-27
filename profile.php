<?php
/**
 * TRANG THÔNG TIN CÁ NHÂN
 * Phụ trách: Thành viên 1
 */
require_once __DIR__ . '/config/db.php';
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$fullname, $phone, $address, $user_id]);

    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['address'] = $address;
    $_SESSION['flash_success'] = 'Cập nhật hồ sơ cá nhân thành công!';
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'Thông tin cá nhân';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-vcard text-primary me-2"></i>Thông Tin Cá Nhân</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email đăng nhập</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên</label>
                        <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($user['fullname']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ nhận hàng mặc định</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Lưu Thay Đổi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
