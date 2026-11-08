<?php
/**
 * THẺ SẢN PHẨM DÙNG CHUNG (kiểu Apple Store)
 * Phụ trách: Thành viên 2
 * Cách dùng: render_product_card($p) hoặc render_product_card($p, 'col-sm-6 col-lg-4')
 *            Truyền null nếu không cần bọc cột (ví dụ trong kệ cuộn ngang).
 */
if (!function_exists('render_product_card')) {

    // Lấy giá bán thực tế (ưu tiên giá khuyến mãi nếu hợp lệ)
    function product_final_price($p) {
        return ($p['sale_price'] && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    }

    // Rút gọn thông số: "Kết nối: Bluetooth 5.2 | Pin: 30 giờ" -> "Bluetooth 5.2 · 30 giờ"
    function product_spec_line($p, $limit = 2) {
        $specs = array_filter(array_map('trim', explode('|', $p['specifications'] ?? '')));
        $values = [];
        foreach (array_slice($specs, 0, $limit) as $s) {
            $pos = strpos($s, ':');
            $values[] = $pos !== false ? trim(substr($s, $pos + 1)) : $s;
        }
        return implode(' · ', $values);
    }

    function render_product_card($p, $wrapper_class = 'col-sm-6 col-lg-3') {
        $final_price = product_final_price($p);
        $discount = ($final_price < $p['price']) ? round((($p['price'] - $final_price) / $p['price']) * 100) : 0;
        $eyebrow = $discount > 0 ? "Giảm $discount%" : (!empty($p['is_featured']) ? 'Nổi bật' : '');
        $detail_url = 'product_detail.php?id=' . (int)$p['id'];
        ?>
        <?php if ($wrapper_class): ?><div class="<?= htmlspecialchars($wrapper_class) ?>"><?php endif; ?>
            <div class="product-card h-100 d-flex flex-column">
                <a href="<?= $detail_url ?>" class="product-img-wrapper d-block" aria-label="<?= htmlspecialchars($p['name']) ?>">
                    <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                </a>
                <div class="pc-body d-flex flex-column flex-grow-1">
                    <?php if ($eyebrow): ?>
                        <div class="pc-eyebrow"><?= $eyebrow ?></div>
                    <?php endif; ?>
                    <div class="pc-brand"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></div>
                    <h3 class="pc-name"><a href="<?= $detail_url ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
                    <p class="pc-spec"><?= htmlspecialchars(product_spec_line($p)) ?></p>
                    <div class="pc-footer mt-auto">
                        <div>
                            <div class="pc-price"><?= format_price($final_price) ?></div>
                            <?php if ($discount > 0): ?>
                                <div class="pc-old"><?= format_price($p['price']) ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="cart.php?action=add&id=<?= (int)$p['id'] ?>" class="pc-add" title="Thêm vào giỏ" aria-label="Thêm vào giỏ">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php if ($wrapper_class): ?></div><?php endif; ?>
        <?php
    }
}
