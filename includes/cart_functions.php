
<?php
/**
 * CÁC HÀM XỬ LÝ GIỎ HÀNG DÙNG CHUNG
 * TECHGEAR
 */

function load_user_cart($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT product_id, quantity
        FROM cart_items
        WHERE user_id = ?
    ");

    $stmt->execute([(int)$user_id]);

    $cart = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $cart[(int)$item['product_id']] = (int)$item['quantity'];
    }

    return $cart;
}

function save_user_cart($pdo, $user_id, $cart)
{
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            DELETE FROM cart_items
            WHERE user_id = ?
        ");
        $stmt->execute([(int)$user_id]);

        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity)
            SELECT ?, id, ?
            FROM products
            WHERE id = ?
        ");

        foreach ($cart as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;

            if ($product_id > 0 && $quantity > 0) {
                $stmt->execute([
                    (int)$user_id,
                    $quantity,
                    $product_id
                ]);
            }
        }

        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function merge_guest_cart_into_user($pdo, $user_id, $guest_cart)
{
    $user_cart = load_user_cart($pdo, $user_id);

    foreach ($guest_cart as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($product_id > 0 && $quantity > 0) {
            $user_cart[$product_id] =
                ($user_cart[$product_id] ?? 0) + $quantity;
        }
    }

    save_user_cart($pdo, $user_id, $user_cart);

    return load_user_cart($pdo, $user_id);
}
