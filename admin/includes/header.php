<?php
require_once __DIR__ . '/../../config/db.php';
require_admin();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - Admin TechGear' : 'Quản Trị Hệ Thống - TechGear' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #0f172a;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 4px;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #2563eb;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar Admin -->
    <div class="sidebar p-3 d-flex flex-column flex-shrink-0">
        <a href="index.php" class="text-white text-decoration-none d-flex align-items-center mb-4 px-2">
            <i class="bi bi-controller text-info fs-3 me-2"></i>
            <span class="fs-5 fw-bold">ADMIN <span class="text-info">TECHGEAR</span></span>
        </a>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển (TV5)
                </a>
            </li>
            <li>
                <a href="categories.php" class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>">
                    <i class="bi bi-tags me-2"></i> Danh mục & Hãng (TV4)
                </a>
            </li>
            <li>
                <a href="products.php" class="nav-link <?= in_array($current_page, ['products.php', 'product_form.php']) ? 'active' : '' ?>">
                    <i class="bi bi-box-seam me-2"></i> Quản lý Phụ kiện (TV4)
                </a>
            </li>
            <li>
                <a href="orders.php" class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>">
                    <i class="bi bi-receipt me-2"></i> Quản lý Đơn hàng (TV5)
                </a>
            </li>
            <li>
                <a href="users.php" class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
                    <i class="bi bi-people me-2"></i> Quản lý Tài khoản (TV5)
                </a>
            </li>
            <li>
                <a href="reviews.php" class="nav-link <?= $current_page === 'reviews.php' ? 'active' : '' ?>">
                    <i class="bi bi-chat-left-quote me-2"></i> Quản lý Đánh giá (TV6)
                </a>
            </li>
        </ul>
        <hr class="border-secondary">
        <a href="../index.php" class="btn btn-outline-info btn-sm w-100 mb-2">
            <i class="bi bi-box-arrow-up-right me-1"></i> Xem Trang Chủ Khách
        </a>
        <a href="../logout.php" class="btn btn-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
        </a>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
