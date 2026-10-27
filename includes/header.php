<?php
require_once __DIR__ . '/../config/db.php';

// Lấy danh sách danh mục cho menu
$nav_categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - TechGear' : 'TechGear - Phụ Kiện Điện Tử Chính Hãng' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --dark-bg: #0f172a;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .navbar-tech {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .product-card {
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1);
        }
        .product-img-wrapper {
            height: 210px;
            overflow: hidden;
            background: #f1f5f9;
            position: relative;
        }
        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-img-wrapper img {
            transform: scale(1.05);
        }
        .badge-sale {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ef4444;
            color: #fff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .hero-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #1d4ed8 100%);
            border-radius: 20px;
            color: white;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Main Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-tech sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="index.php">
            <i class="bi bi-controller text-info me-2 fs-3"></i>
            <span>TECH<span class="text-info">GEAR</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Thanh tìm kiếm -->
            <form class="d-flex mx-lg-4 my-2 my-lg-0 flex-grow-1" action="products.php" method="GET">
                <div class="input-group">
                    <input class="form-control border-0" type="search" name="keyword" placeholder="Tìm tai nghe, bàn phím cơ, chuột Logitech, sạc Anker..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                    <button class="btn btn-info text-dark fw-semibold px-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i>Trang chủ</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-grid me-1"></i>Danh mục
                    </a>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item fw-semibold" href="products.php">Tất cả phụ kiện</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($nav_categories as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="products.php?category=<?= $cat['id'] ?>">
                                    <i class="bi <?= htmlspecialchars($cat['icon']) ?> me-2 text-primary"></i><?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <!-- Giỏ hàng -->
                <li class="nav-item">
                    <a class="btn btn-outline-info position-relative px-3" href="cart.php">
                        <i class="bi bi-cart3 me-1"></i> Giỏ hàng
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= get_cart_count() ?>
                        </span>
                    </a>
                </li>

                <!-- Tài khoản -->
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="btn btn-light dropdown-toggle fw-semibold" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-primary me-1"></i><?= htmlspecialchars($_SESSION['user']['fullname']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item text-primary fw-semibold" href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Quản trị Admin</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="my_orders.php"><i class="bi bi-bag-check me-2"></i>Đơn hàng của tôi</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-gear me-2"></i>Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-info fw-semibold" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Thông báo Flash Message -->
<div class="container mt-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
</div>
