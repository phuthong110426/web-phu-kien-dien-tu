<?php
/**
 * Thẻ sản phẩm dùng chung cho toàn hệ thống
 * Phụ trách: Thành viên 2
 */

if (!function_exists('format_price')) {
    function format_price($price) {
        return number_format($price, 0, ',', '.') . ' đ';
    }
}

if (!function_exists('product_final_price')) {
    function product_final_price($p) {
        return ($p['sale_price'] !== null && $p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    }
}

if (!function_exists('product_spec_line')) {
    function product_spec_line($p) {
        return $p['specifications'] ?? '';
    }
}

if (!function_exists('render_product_card')) {
    function render_product_card($p, $wrapper_class = '') {
        $final_price = product_final_price($p);
        $has_sale = ($p['sale_price'] !== null && $p['sale_price'] > 0 && $p['sale_price'] < $p['price']);
        $discount_percent = $has_sale ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100) : 0;
        
        if ($wrapper_class) {
            echo '<div class="' . htmlspecialchars($wrapper_class) . '">';
        }
        ?>
        <div class="card product-card h-100 border-0 shadow-sm position-relative">
            <?php if ($has_sale): ?>
                <span class="badge bg-danger position-absolute top-0 start-0 m-2 z-1">Giảm <?= $discount_percent ?>%</span>
            <?php endif; ?>
            <a href="product_detail.php?id=<?= $p['id'] ?>">
                <img src="<?= htmlspecialchars($p['thumbnail']) ?>" class="card-img-top object-fit-cover" style="height: 200px;" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            </a>
            <div class="card-body d-flex flex-column">
                <span class="text-muted small mb-1"><?= htmlspecialchars($p['category_name'] ?? 'Phụ kiện') ?></span>
                <h6 class="card-title text-truncate-2">
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="text-decoration-line-none text-dark fw-bold"><?= htmlspecialchars($p['name']) ?></a>
                </h6>
                <p class="card-text text-muted small text-truncate mb-2"><?= htmlspecialchars($p['specifications'] ?? '') ?></p>
                <div class="mt-auto">
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-danger fw-bold fs-6 me-2"><?= format_price($final_price) ?></span>
                        <?php if ($has_sale): ?>
                            <span class="text-muted text-decoration-line-through small"><?= format_price($p['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="cart.php?action=add&id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm w-100">Thêm vào giỏ</a>
                </div>
            </div>
        </div>
        <?php
        if ($wrapper_class) {
            echo '</div>';
        }
    }
}
