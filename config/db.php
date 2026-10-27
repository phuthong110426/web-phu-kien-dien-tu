<?php
/**
 * FILE KẾT NỐI CƠ SỞ DỮ LIỆU & HÀM DÙNG CHUNG
 * Phụ trách chính: Thành viên 1 (Nhóm trưởng)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'shop_phukien';
$username = 'root';
$password = ''; // Mặc định XAMPP mật khẩu là rỗng

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;max-width:650px;margin:50px auto;padding:24px;border:2px solid #dc3545;border-radius:12px;background:#fff5f5;">
        <h2 style="color:#dc3545;margin-top:0;">⚠️ Chưa kết nối được Cơ sở dữ liệu (shop_phukien)</h2>
        <p>Vui lòng làm theo 3 bước sau để chạy web:</p>
        <ol style="line-height:1.8;">
            <li>Mở <b>XAMPP Control Panel</b>, bấm <b>Start</b> ở cả <b>Apache</b> và <b>MySQL</b>.</li>
            <li>Truy cập <a href="http://localhost/phpmyadmin" target="_blank"><b>http://localhost/phpmyadmin</b></a></li>
            <li>Bấm vào tab <b>Import (Nhập)</b> ở thanh trên cùng -> Chọn file <code>database/shop_phukien.sql</code> trong thư mục dự án -> Bấm <b>Go (Thực hiện)</b> ở cuối trang.</li>
        </ol>
        <p style="color:#666;font-size:14px;">Chi tiết lỗi kỹ thuật: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}

// Hàm định dạng tiền tệ VNĐ
function format_price($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

// Kiểm tra đã đăng nhập chưa
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Kiểm tra có phải Admin không
function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

// Bắt buộc quyền Admin khi vào thư mục /admin/
function require_admin() {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Bạn cần đăng nhập bằng tài khoản Quản trị viên (Admin) để truy cập trang này!';
        header('Location: ../login.php');
        exit;
    }
}

// Hàm tổng số lượng sản phẩm trong giỏ hàng
function get_cart_count() {
    if (empty($_SESSION['cart'])) return 0;
    return array_sum($_SESSION['cart']);
}
?>
