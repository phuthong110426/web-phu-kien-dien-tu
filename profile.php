
<?php
/**
 * TRANG THÔNG TIN CÁ NHÂN
 * Phụ trách: Thành viên 1
 */

require_once __DIR__ . '/config/db.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Lấy ID người dùng đang đăng nhập
$user_id = $_SESSION['user']['id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================================
    // 1. XỬ LÝ ĐỔI MẬT KHẨU
    // ==========================================
    if (($_POST['action'] ?? '') === 'change_password') {

        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Lấy mật khẩu hiện tại trong database
        $stmt = $pdo->prepare(
            "SELECT password FROM users WHERE id = ?"
        );
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();

        // Kiểm tra mật khẩu cũ
        if (!$current_user) {
            $error = 'Không tìm thấy tài khoản!';

        } elseif (!password_verify($old_password, $current_user['password'])) {
            $error = 'Mật khẩu cũ không chính xác!';

        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';

        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp!';

        } else {

            // Mã hóa mật khẩu mới
            $hashed_password = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            // Cập nhật mật khẩu vào database
            $stmt = $pdo->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );
            $stmt->execute([$hashed_password, $user_id]);

            $_SESSION['flash_success'] =
                'Đổi mật khẩu thành công!';

            header('Location: profile.php');
            exit;
        }

    } else {

        // ==========================================
        // 2. XỬ LÝ CẬP NHẬT THÔNG TIN CÁ NHÂN
        // ==========================================

        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $stmt = $pdo->prepare(
            "UPDATE users
             SET fullname = ?, phone = ?, address = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $fullname,
            $phone,
            $address,
            $user_id
        ]);

        // Cập nhật thông tin trong session
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;

        $_SESSION['flash_success'] =
            'Cập nhật hồ sơ cá nhân thành công!';

        header('Location: profile.php');
        exit;
    }
}

// Lấy thông tin người dùng để hiển thị
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE id = ?"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'Thông tin cá nhân';

require_once __DIR__ . '/includes/header.php';
?>


<style>
/* ===== TRANG QUẢN LÝ TÀI KHOẢN ===== */
.profile-page {
    min-height: 75vh;
    padding: 34px 0 55px;
    background: #f6f7f9;
}

.profile-heading {
    margin-bottom: 22px;
}

.profile-heading h3 {
    margin: 0 0 5px;
    color: #202938;
    font-size: 24px;
    font-weight: 700;
}

.profile-heading p {
    margin: 0;
    color: #7b8494;
    font-size: 13px;
}

.account-panel {
    max-width: 850px;
    margin: 0 auto;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e9edf2;
    border-radius: 12px;
    box-shadow: 0 3px 14px rgba(25, 42, 70, .035);
}

.account-summary {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 22px 26px;
    border-bottom: 1px solid #edf0f3;
}

.account-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    border-radius: 50%;
    background: #eef3ff;
    color: #316be8;
    font-size: 22px;
}

.account-summary h4 {
    margin: 0 0 4px;
    color: #202938;
    font-size: 17px;
    font-weight: 700;
}

.account-summary p {
    margin: 0;
    color: #8992a2;
    font-size: 13px;
}

.account-section {
    padding: 24px 26px;
}

.section-title {
    margin: 0 0 19px;
    color: #283244;
    font-size: 15px;
    font-weight: 700;
}

.profile-page .form-label {
    margin-bottom: 7px;
    color: #4b5565;
    font-size: 13px;
    font-weight: 600;
}

.profile-page .form-control {
    min-height: 42px;
    border: 1px solid #dfe4eb;
    border-radius: 7px;
    padding: 9px 12px;
    color: #303949;
    font-size: 13px;
    box-shadow: none;
}

.profile-page .form-control:focus {
    border-color: #7b9ce8;
    box-shadow: 0 0 0 3px rgba(49, 107, 232, .08);
}

.profile-page .form-control:disabled {
    background: #f5f6f8;
    color: #7b8494;
}

.profile-page .form-text {
    margin-top: 5px;
    color: #929baa;
    font-size: 11px;
}

.profile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 39px;
    padding: 9px 17px;
    border: 1px solid #316be8;
    border-radius: 7px;
    background: #316be8;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: background .18s ease, border-color .18s ease;
}

.profile-btn:hover {
    border-color: #2459ca;
    background: #2459ca;
    color: #fff;
}

.account-security {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 19px 26px;
    border-top: 1px solid #edf0f3;
    background: #fff;
}

.security-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.security-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border-radius: 9px;
    background: #f3f5f8;
    color: #596579;
    font-size: 17px;
}

.security-info strong {
    display: block;
    margin-bottom: 3px;
    color: #303949;
    font-size: 13px;
}

.security-info small {
    color: #8992a2;
    font-size: 12px;
}

.profile-alert {
    max-width: 850px;
    margin: 0 auto 15px;
    border-radius: 8px;
    font-size: 13px;
}

/* ===== POPUP ĐỔI MẬT KHẨU ===== */
.password-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(20, 30, 48, .48);
}

.password-modal.active {
    display: flex;
}

.password-modal-content {
    position: relative;
    width: 100%;
    max-width: 420px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 26px;
    border: 1px solid #edf0f3;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 12px 38px rgba(0, 0, 0, .16);
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 15px;
    border: 0;
    background: transparent;
    color: #8992a2;
    font-size: 25px;
    cursor: pointer;
}

.modal-close:hover {
    color: #303949;
}

.password-modal-content h4 {
    margin: 0 0 6px;
    color: #202938;
    font-size: 19px;
    font-weight: 700;
}

.modal-description {
    margin-bottom: 20px;
    color: #8992a2;
    font-size: 12px;
}

@media (max-width: 575px) {
    .profile-page {
        padding: 24px 0 38px;
    }

    .account-summary,
    .account-section {
        padding: 19px;
    }

    .account-security {
        align-items: flex-start;
        flex-direction: column;
        padding: 18px 19px;
    }

    .account-security .profile-btn {
        width: 100%;
    }

    .password-modal-content {
        padding: 23px 19px;
    }
}
</style>

<div class="profile-page">
    <div class="container">

        <div class="profile-heading">
            <h3>Tài khoản của tôi</h3>
            <p>Quản lý thông tin cá nhân và thiết lập bảo mật.</p>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success profile-alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($error) && empty($_POST['action'])): ?>
            <div class="alert alert-danger profile-alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="account-panel">

            <div class="account-summary">
                <div class="account-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <h4><?= htmlspecialchars($user['fullname'] ?? 'Tài khoản TECHGEAR') ?></h4>
                    <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
                </div>
            </div>

            <div class="account-section">
                <h5 class="section-title">Thông tin cá nhân</h5>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ và tên</label>
                            <input
                                type="text"
                                name="fullname"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars($user['fullname'] ?? '') ?>"
                                placeholder="Nhập họ và tên"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email đăng nhập</label>
                            <input
                                type="email"
                                class="form-control"
                                value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                disabled
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại</label>
                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                placeholder="Nhập số điện thoại"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Địa chỉ nhận hàng mặc định</label>
                            <input
                                type="text"
                                name="address"
                                class="form-control"
                                value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                                placeholder="Nhập địa chỉ nhận hàng"
                            >
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="profile-btn">
                            <i class="bi bi-check2"></i>
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>

            <div class="account-security">
                <div class="security-info">
                    <div class="security-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <strong>Mật khẩu</strong>
                        <small>Định kỳ thay đổi mật khẩu để bảo vệ tài khoản.</small>
                    </div>
                </div>

                <button type="button" class="profile-btn"
                        onclick="openPasswordModal()">
                    <i class="bi bi-lock"></i>
                    Đổi mật khẩu
                </button>
            </div>

        </div>
    </div>

    <!-- POPUP ĐỔI MẬT KHẨU -->
    <div class="password-modal" id="passwordModal"
         role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">

        <div class="password-modal-content">
            <button type="button" class="modal-close"
                    onclick="closePasswordModal()" aria-label="Đóng">
                &times;
            </button>

            <h4 id="passwordModalTitle">Đổi mật khẩu</h4>
            <p class="modal-description">
                Nhập mật khẩu hiện tại và thiết lập mật khẩu mới cho tài khoản.
            </p>

            <?php if (!empty($error) && (($_POST['action'] ?? '') === 'change_password')): ?>
                <div class="alert alert-danger profile-alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <input type="password" name="old_password"
                           class="form-control"
                           placeholder="Nhập mật khẩu hiện tại"
                           autocomplete="current-password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="new_password"
                           class="form-control"
                           placeholder="Nhập mật khẩu mới"
                           minlength="6" autocomplete="new-password" required>
                    <div class="form-text">Tối thiểu 6 ký tự.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password"
                           class="form-control"
                           placeholder="Nhập lại mật khẩu mới"
                           minlength="6" autocomplete="new-password" required>
                </div>

                <button type="submit" class="profile-btn w-100">
                    Cập nhật mật khẩu
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openPasswordModal() {
    document.getElementById('passwordModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Đóng popup khi nhấn ra ngoài.
document.getElementById('passwordModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closePasswordModal();
    }
});

// Đóng popup bằng phím Escape.
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePasswordModal();
    }
});

// Tự mở popup lại nếu thông tin đổi mật khẩu chưa hợp lệ.
<?php if (!empty($error) && (($_POST['action'] ?? '') === 'change_password')): ?>
    openPasswordModal();
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
