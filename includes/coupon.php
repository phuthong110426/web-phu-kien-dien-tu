
<?php
/**
 * QUẢN LÝ MÃ GIẢM GIÁ TECHGEAR
 */

function get_techgear_coupons()
{
    return [
        'TECHGEAR10' => [
            'type' => 'percent',
            'value' => 10,
            'min_order' => 0,
            'max_discount' => 200000
        ],

        'WELCOME50' => [
            'type' => 'fixed',
            'value' => 50000,
            'min_order' => 500000,
            'max_discount' => 50000
        ],

        'TECHGEAR20' => [
            'type' => 'percent',
            'value' => 20,
            'min_order' => 2000000,
            'max_discount' => 300000
        ]
    ];
}

function calculate_techgear_discount($code, $subtotal)
{
    $coupons = get_techgear_coupons();
    $code = strtoupper(trim($code));

    if (!isset($coupons[$code])) {
        return [
            'valid' => false,
            'discount' => 0,
            'message' => 'Mã giảm giá không tồn tại.'
        ];
    }

    $coupon = $coupons[$code];

    if ($subtotal < $coupon['min_order']) {
        return [
            'valid' => false,
            'discount' => 0,
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu của mã.'
        ];
    }

    if ($coupon['type'] === 'percent') {
        $discount = $subtotal * $coupon['value'] / 100;
    } else {
        $discount = $coupon['value'];
    }

    $discount = min($discount, $coupon['max_discount'], $subtotal);

    return [
        'valid' => true,
        'discount' => (int)$discount,
        'message' => 'Áp dụng mã giảm giá thành công!'
    ];
}
