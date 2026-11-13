<?php
/**
 * TRANG ĐĂNG NHẬP HỆ THỐNG
 * Phụ trách: Thành viên 1
 */
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Hỗ trợ cả mật khẩu hash và mật khẩu demo '123456'
    if ($user && ($password === '123456' || password_verify($password, $user['password']))) {
        if ((int)$user['status'] === 0) {
            $error = 'Tài khoản của bạn đã bị khóa bởi Quản trị viên!';
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'address' => $user['address'],
                'role' => $user['role']
            ];
            $_SESSION['flash_success'] = 'Chào mừng ' . $user['fullname'] . ' đã quay trở lại!';
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    } else {
        $error = 'Email hoặc mật khẩu không chính xác!';
    }
}

$page_title = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-person-badge text-primary display-5"></i>
                    <h4 class="fw-bold mt-2">Đăng Nhập Tài Khoản</h4>
                    <p class="text-muted small">Đăng nhập để mua sắm và theo dõi đơn hàng phụ kiện</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nhapemail@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu (mẫu: 123456)" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng Nhập
                    </button>
                </form>

                <div class="bg-light rounded-3 p-3 mt-4 small">
                    <div class="fw-semibold text-secondary mb-1"><i class="bi bi-info-circle me-1"></i>Tài khoản test nhanh:</div>
                    <div>• <b>Admin:</b> <code>admin@gmail.com</code> | Mật khẩu: <code>123456</code></div>
                    <div>• <b>Khách:</b> <code>khach@gmail.com</code> | Mật khẩu: <code>123456</code></div>
                </div>

                <div class="text-center mt-3 small">
                    Chưa có tài khoản? <a href="register.php" class="fw-semibold text-decoration-none">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
