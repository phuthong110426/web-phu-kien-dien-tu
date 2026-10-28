<?php
require_once __DIR__ . '/../config/db.php';

// Lấy danh sách danh mục cho menu
$nav_categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();
$current_file = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - TechGear' : 'TechGear - Phụ Kiện Điện Tử Chính Hãng' ?></title>
    <!-- Font Inter (hỗ trợ tiếng Việt) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- CSS giao diện riêng của nhóm -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Thanh điều hướng chính (kính mờ kiểu Apple) -->
<nav class="navbar navbar-expand-lg navbar-light navbar-apple sticky-top py-2">
    <div class="container">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span>
            TechGear
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $current_file === 'index.php' ? 'active' : '' ?>" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_file === 'products.php' ? 'active' : '' ?>" href="products.php">Cửa hàng</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Danh mục</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item fw-semibold" href="products.php">Tất cả phụ kiện</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($nav_categories as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="products.php?category=<?= $cat['id'] ?>">
                                    <i class="bi <?= htmlspecialchars($cat['icon']) ?> me-2"></i><?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <!-- Ô tìm kiếm -->
            <form class="search-pill my-2 my-lg-0 ms-lg-auto me-lg-2" action="products.php" method="GET">
                <i class="bi bi-search"></i>
                <input class="form-control" type="search" name="keyword" placeholder="Tìm tai nghe, bàn phím, chuột..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
            </form>

            <div class="d-flex align-items-center gap-1">
                <!-- Giỏ hàng -->
                <a class="icon-btn" href="cart.php" title="Giỏ hàng">
                    <i class="bi bi-bag"></i>
                    <?php if (get_cart_count() > 0): ?>
                        <span class="cart-count"><?= get_cart_count() ?></span>
                    <?php endif; ?>
                </a>

                <!-- Tài khoản -->
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <a class="icon-btn" href="#" data-bs-toggle="dropdown" title="Tài khoản">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                                <div class="text-muted" style="font-size: .8rem;"><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item text-primary" href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Trang quản trị</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="my_orders.php"><i class="bi bi-bag-check me-2"></i>Đơn hàng của tôi</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-gear me-2"></i>Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-dark btn-sm ms-1" href="login.php">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Thông báo Flash Message -->
<div class="container mt-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
</div>
