<?php
require_once __DIR__ . '/../../config/db.php';
require_admin();
$current_page = basename($_SERVER['PHP_SELF']);

// Danh sách menu Admin: [file, icon, tên, thành viên phụ trách]
$admin_menu = [
    ['index.php',      'bi-grid-1x2',        'Tổng quan',          'TV5'],
    ['categories.php', 'bi-tags',            'Danh mục',           'TV4'],
    ['products.php',   'bi-box-seam',        'Sản phẩm',           'TV4'],
    ['orders.php',     'bi-receipt',         'Đơn hàng',           'TV5'],
    ['users.php',      'bi-people',          'Tài khoản',          'TV5'],
    ['reviews.php',    'bi-chat-left-quote', 'Đánh giá',           'TV6'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - Admin TechGear' : 'Quản Trị Hệ Thống - TechGear' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar Admin (sáng, tối giản) -->
    <aside class="admin-sidebar p-3 d-flex flex-column flex-shrink-0">
        <a href="index.php" class="brand-logo text-decoration-none px-2 mb-3">
            <span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span>
            TechGear <span class="text-muted fw-normal" style="font-size: .85rem;">Admin</span>
        </a>

        <div class="nav-section">Quản lý</div>
        <ul class="nav flex-column mb-auto">
            <?php foreach ($admin_menu as $item): ?>
                <?php
                $is_active = $current_page === $item[0]
                    || ($item[0] === 'products.php' && $current_page === 'product_form.php');
                ?>
                <li>
                    <a href="<?= $item[0] ?>" class="nav-link <?= $is_active ? 'active' : '' ?>">
                        <i class="bi <?= $item[1] ?>"></i> <?= $item[2] ?>
                        <span class="tv-tag"><?= $item[3] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="border-top pt-3 mt-3">
            <div class="d-flex align-items-center gap-2 px-2 mb-3">
                <div class="stat-icon soft-blue mb-0" style="width: 36px; height: 36px; font-size: 1rem;">
                    <i class="bi bi-person"></i>
                </div>
                <div class="small lh-sm">
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                    <div class="text-muted" style="font-size: .78rem;">Quản trị viên</div>
                </div>
            </div>
            <a href="../index.php" class="btn btn-light btn-sm w-100 mb-2">
                <i class="bi bi-shop me-1"></i> Xem cửa hàng
            </a>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- Nội dung chính -->
    <main class="admin-main flex-grow-1">
        <div class="admin-topbar px-4 py-3 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <i class="bi bi-house me-1"></i> Admin <i class="bi bi-chevron-right mx-1" style="font-size: .7rem;"></i>
                <span class="text-dark fw-medium"><?= htmlspecialchars($page_title ?? 'Tổng quan') ?></span>
            </div>
            <div class="d-lg-none">
                <a href="../index.php" class="btn btn-light btn-sm">Cửa hàng</a>
            </div>
        </div>

        <div class="p-4">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
