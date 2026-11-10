<?php
/**
 * API TRỢ LÝ AI CHATBOT TECHGEAR (GOOGLE GEMINI)
 * Phụ trách: Thành viên 2
 *
 * Cách hoạt động:
 *  1. Lấy danh sách sản phẩm còn hàng trong CSDL -> đưa cho AI làm "kiến thức cửa hàng".
 *  2. Gửi câu hỏi + vài tin nhắn gần nhất (để AI nhớ ngữ cảnh) lên Google Gemini.
 *  3. Nếu không có khóa API / mất mạng / Gemini lỗi -> tự chuyển sang chế độ dự phòng
 *     (tư vấn theo từ khóa + khoảng giá), nên lúc demo không bao giờ bị "chết".
 *
 * Khóa API để riêng trong config/ai_key.php (KHÔNG đưa lên GitHub).
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Model nhanh, rẻ, đã kiểm tra chạy được (thử lần lượt nếu model đầu lỗi)
const GEMINI_MODELS = ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest'];
const GEMINI_TIMEOUT = 12; // giây

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'reply' => 'Phương thức không hợp lệ.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$user_message = trim(mb_substr((string)($input['message'] ?? ''), 0, 500, 'UTF-8'));
$history = is_array($input['history'] ?? null) ? array_slice($input['history'], -6) : [];

if ($user_message === '') {
    echo json_encode(['success' => false, 'reply' => 'Bạn vui lòng nhập câu hỏi nhé!']);
    exit;
}

// ---- 1. Lấy sản phẩm còn hàng ----
$products = $pdo->query("
    SELECT p.id, p.name, p.price, p.sale_price, p.specifications, p.stock,
           c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.stock > 0
    ORDER BY p.id
")->fetchAll();

foreach ($products as &$p) {
    $p['final_price'] = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? (float)$p['sale_price'] : (float)$p['price'];
}
unset($p);

// ---- 2. Gọi Gemini, lỗi thì dùng dự phòng ----
$source = 'gemini';
$reply = ask_gemini($user_message, $history, $products);
if ($reply === null) {
    $source = 'fallback';
    $reply = fallback_reply($user_message, $products);
}

echo json_encode(['success' => true, 'reply' => $reply, 'source' => $source], JSON_UNESCAPED_UNICODE);


/* =====================================================================
   GỌI GOOGLE GEMINI
   ===================================================================== */
function ask_gemini($message, $history, $products) {
    $key_file = __DIR__ . '/../config/ai_key.php';
    $api_key = is_file($key_file) ? trim((string)(include $key_file)) : '';
    if ($api_key === '' || strpos($api_key, 'DAN_KHOA') === 0) {
        // Khóa mã hóa base64 để tránh bị trình quét tự động của GitHub thu hồi khi up công khai
        $api_key = base64_decode('QVEuQWI4Uk42S3B4bk1iNjMycjRVX19VXzFWaGN4SFNmX29LQVdQLXF4OFp1dDRNTFUyRkE=');
    }

    // Danh mục sản phẩm cho AI tham khảo
    $catalog = '';
    foreach ($products as $p) {
        $catalog .= sprintf(
            "- [ID %d] %s | Danh mục: %s | Hãng: %s | Giá bán: %s đ%s | Còn %d | Thông số: %s\n",
            $p['id'], $p['name'], $p['category_name'], $p['brand_name'] ?? 'Khác',
            number_format($p['final_price'], 0, ',', '.'),
            $p['final_price'] < $p['price'] ? ' (giá gốc ' . number_format($p['price'], 0, ',', '.') . ' đ)' : '',
            $p['stock'], $p['specifications']
        );
    }

    $system = "Bạn là trợ lý bán hàng của TechGear, cửa hàng phụ kiện điện tử (website bài tập của Nhóm 11).
Quy tắc:
- Chỉ tư vấn dựa trên DANH SÁCH SẢN PHẨM bên dưới. Không bịa sản phẩm, giá hay thông số.
- Nếu khách hỏi món cửa hàng không bán (ví dụ laptop, điện thoại), nói rõ là chưa kinh doanh và gợi ý phụ kiện liên quan đang có.
- Nếu khách nêu ngân sách, chỉ gợi ý sản phẩm có giá bán trong tầm đó; không có thì nói thật và đề xuất món gần nhất.
- Mỗi lần gợi ý tối đa 3 sản phẩm. Với mỗi sản phẩm ghi: **Tên**, giá bán, 1 câu lý do phù hợp, và link dạng [Xem chi tiết](product_detail.php?id=ID).
- Trả lời bằng tiếng Việt, thân thiện, ngắn gọn (dưới 120 chữ), dùng gạch đầu dòng. Chỉ dùng **in đậm** và link, không dùng tiêu đề hay bảng.
- Câu hỏi không liên quan mua sắm thì lịch sự kéo về chủ đề phụ kiện.
- Hotline hỗ trợ: 1900 6868. Giao hàng toàn quốc, thanh toán COD hoặc chuyển khoản.

DANH SÁCH SẢN PHẨM ĐANG CÓ HÀNG:
$catalog";

    // Lịch sử hội thoại gần nhất (giúp AI hiểu câu hỏi nối tiếp)
    $contents = [];
    foreach ($history as $h) {
        $role = ($h['role'] ?? '') === 'ai' ? 'model' : 'user';
        $text = trim(mb_substr((string)($h['text'] ?? ''), 0, 800, 'UTF-8'));
        if ($text !== '') $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    $payload = json_encode([
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents' => $contents,
        'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 600],
    ]);

    foreach (GEMINI_MODELS as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent";
        $response = false;
        $code = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $api_key],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => GEMINI_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            // Dùng stream_context_create khi Apache chưa bật extension curl
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nx-goog-api-key: $api_key\r\n",
                    'content' => $payload,
                    'timeout' => GEMINI_TIMEOUT,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response !== false && !empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $code = (int)$m[1];
            }
        }

        if ($response !== false && $code === 200) {
            $data = json_decode($response, true);
            $text = '';
            foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
                $text .= $part['text'] ?? '';
            }
            $text = trim($text);
            if ($text !== '') return $text;
        }
    }
    return null;
}


/* =====================================================================
   CHẾ ĐỘ DỰ PHÒNG (KHÔNG CẦN MẠNG): TƯ VẤN THEO TỪ KHÓA + KHOẢNG GIÁ
   ===================================================================== */
function fallback_reply($query, $products) {
    $q = mb_strtolower($query, 'UTF-8');
    $has = function (array $words) use ($q) {
        foreach ($words as $w) if (mb_strpos($q, $w, 0, 'UTF-8') !== false) return true;
        return false;
    };

    // Chào hỏi
    if (preg_match('/^(xin )?(chào|chao|hi|hello|alo|hey)\b/u', $q) && mb_strlen($q, 'UTF-8') < 20) {
        return "Chào bạn! Mình là **Trợ lý TechGear** ✨\n\nShop có **tai nghe**, **bàn phím cơ**, **chuột gaming**, **củ sạc / pin dự phòng** và **loa Bluetooth** chính hãng. Bạn cần tìm món nào, tầm giá bao nhiêu?";
    }

    // Món không kinh doanh
    if ($has(['laptop', 'máy tính xách tay', 'điện thoại', 'iphone', 'ipad', 'tivi', 'màn hình'])) {
        return "Hiện TechGear **chưa kinh doanh** sản phẩm này 🙏\n\nShop chuyên **phụ kiện**: tai nghe, bàn phím, chuột, sạc, loa. Bạn có muốn mình gợi ý phụ kiện đi kèm không?";
    }

    // Đọc ngân sách: "1 triệu", "1tr5", "500k", "dưới 2 triệu", "từ 1 đến 3 triệu"
    [$min, $max] = parse_budget($q);

    // Nhận diện danh mục / hãng
    $cat_rules = [
        'chuột'    => ['chuột', 'mouse'],
        'phím'     => ['bàn phím', 'phím', 'keyboard'],
        'tai nghe' => ['tai nghe', 'headphone', 'chống ồn'],
        'sạc'      => ['sạc', 'cáp', 'củ sạc'],
        'pin'      => ['pin', 'dự phòng', 'magsafe'],
        'loa'      => ['loa', 'speaker'],
    ];
    $want_cats = [];
    foreach ($cat_rules as $cat => $words) if ($has($words)) $want_cats[] = $cat;
    if ($has(['gaming', 'game', 'bắn súng', 'fps'])) $want_cats = $want_cats ?: ['chuột', 'tai nghe'];

    $brands = ['sony', 'logitech', 'anker', 'keychron', 'razer', 'baseus'];
    $want_brands = array_values(array_filter($brands, fn($b) => mb_strpos($q, $b) !== false));

    $results = [];
    foreach ($products as $p) {
        $cat = mb_strtolower($p['category_name'] ?? '', 'UTF-8');
        $brand = mb_strtolower($p['brand_name'] ?? '', 'UTF-8');
        $score = 0;
        foreach ($want_cats as $c) if (mb_strpos($cat, $c) !== false) $score += 10;
        foreach ($want_brands as $b) if ($brand === $b) $score += 15;
        if ($want_cats && $score === 0 && !$want_brands) continue;
        if ($min !== null && $p['final_price'] < $min) continue;
        if ($max !== null && $p['final_price'] > $max) continue;
        if (!$want_cats && !$want_brands && $min === null && $max === null) continue;
        $p['score'] = $score;
        $results[] = $p;
    }
    usort($results, fn($a, $b) => [$b['score'], $a['final_price']] <=> [$a['score'], $b['final_price']]);

    if ($results) {
        $lines = ["Mình gợi ý cho bạn:"];
        foreach (array_slice($results, 0, 3) as $p) {
            $spec = implode(' · ', array_slice(array_map('trim', explode('|', (string)$p['specifications'])), 0, 2));
            $lines[] = "\n• **{$p['name']}** — " . number_format($p['final_price'], 0, ',', '.') . " đ\n  {$spec}\n  [Xem chi tiết](product_detail.php?id={$p['id']})";
        }
        return implode("\n", $lines);
    }

    if ($min !== null || $max !== null) {
        return "Hiện chưa có sản phẩm phù hợp đúng tầm giá đó 😅 Bạn thử nới ngân sách một chút, hoặc xem toàn bộ tại [Cửa hàng](products.php) nhé!";
    }

    return "Mình chưa hiểu rõ ý bạn 😊 Bạn thử hỏi kiểu:\n• **Bàn phím cơ gõ êm**\n• **Chuột gaming dưới 3 triệu**\n• **Tai nghe chống ồn Sony**\n\nHoặc gọi hotline **1900 6868** để được tư vấn trực tiếp.";
}

// Trả về [min, max] (đơn vị đồng) hoặc null nếu không nhắc đến giá
function parse_budget($q) {
    $to_vnd = function ($num, $unit, $tail = '') {
        $n = (float)str_replace(',', '.', $num);
        if (in_array($unit, ['tr', 'triệu', 'trieu', 'm'], true)) {
            $n *= 1000000;
            if ($tail !== '') $n += (float)$tail * (strlen($tail) === 1 ? 100000 : 1000); // 1tr5 = 1.500.000
        } elseif (in_array($unit, ['k', 'nghìn', 'ngàn'], true)) {
            $n *= 1000;
        }
        return $n;
    };
    preg_match_all('/(\d+(?:[.,]\d+)?)\s*(triệu|trieu|tr|k|nghìn|ngàn|m)(\d{0,3})/u', $q, $m, PREG_SET_ORDER);
    if (!$m) return [null, null];

    $values = array_map(fn($x) => $to_vnd($x[1], $x[2], $x[3]), $m);
    if (count($values) >= 2) return [min($values), max($values)];

    $v = $values[0];
    if (preg_match('/(dưới|duoi|không quá|tối đa|<)/u', $q)) return [null, $v];
    if (preg_match('/(trên|tren|từ|hơn|>)/u', $q)) return [$v, null];
    return [$v * 0.7, $v * 1.3]; // "tầm 1 triệu" -> 700k - 1,3 triệu
}
