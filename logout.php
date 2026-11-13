
<?php
/**
 * ĐĂNG XUẤT TÀI KHOẢN
 * TECHGEAR
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/cart_functions.php';

if (is_logged_in()) {

    $user_id = (int)$_SESSION['user']['id'];

    $cart = $_SESSION['cart'] ?? [];

    save_user_cart($pdo, $user_id, $cart);
}

unset(
    $_SESSION['user'],
    $_SESSION['cart'],
    $_SESSION['selected_cart_ids'],
    $_SESSION['checkout_cart'],
    $_SESSION['applied_coupon'],
    $_SESSION['coupon_message']
);

$_SESSION['flash_success'] = 'Bạn đã đăng xuất thành công!';

header('Location: index.php');
exit;
