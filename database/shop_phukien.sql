-- ==========================================================
-- CƠ SỞ DỮ LIỆU: WEBSITE BÁN ĐỒ PHỤ KIỆN ĐIỆN TỬ (TECHGEAR)
-- Môn học: Lập trình Web (PHP + MySQL)
-- Hướng dẫn: Mở http://localhost/phpmyadmin -> Chọn tab Import (Nhập) -> Chọn file này -> Bấm Go (Thực hiện)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `shop_phukien` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shop_phukien`;

-- --------------------------------------------------------
-- 1. Bảng `users` (Quản lý tài khoản & Phân quyền - Thành viên 1 & 5)
-- Mật khẩu mặc định cho tất cả tài khoản mẫu là: 123456
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_details`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `status` TINYINT(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`fullname`, `email`, `password`, `phone`, `address`, `role`, `status`) VALUES
('Quản Trị Viên (Admin)', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0901234567', 'Quận 1, TP. Hồ Chí Minh', 'admin', 1),
('Nguyễn Văn Khách', 'khach@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', 'Quận Cầu Giấy, Hà Nội', 'user', 1),
('Trần Thị Minh Anh', 'minhanh@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0912345678', 'Quận Hải Châu, Đà Nẵng', 'user', 1);
-- Lưu ý: Chuỗi hash ở trên tương ứng với mật khẩu 'password' hoặc trong code hỗ trợ đăng nhập bằng '123456'

-- --------------------------------------------------------
-- 2. Bảng `categories` (Danh mục phụ kiện - Thành viên 4)
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-Cpu',
  `description` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Tai nghe', 'tai-nghe', 'bi-headphones', 'Tai nghe Bluetooth, Tai nghe Gaming chụp tai âm thanh vòm'),
(2, 'Bàn phím cơ', 'ban-phim-co', 'bi-keyboard', 'Bàn phím cơ không dây, bàn phím gaming RGB hotswap'),
(3, 'Chuột máy tính', 'chuot-may-tinh', 'bi-mouse2', 'Chuột gaming siêu nhẹ, chuột không dây công thái học'),
(4, 'Sạc & Cáp nhanh', 'sac-cap-nhanh', 'bi-lightning-charge', 'Củ sạc nhanh GaN, cáp sạc Type-C bọc dù siêu bền'),
(5, 'Pin sạc dự phòng', 'pin-sac-du-phong', 'bi-battery-charging', 'Pin dự phòng dung lượng cao, sạc không dây MagSafe'),
(6, 'Loa & Phụ kiện khác', 'loa-phu-kien-khac', 'bi-speaker', 'Loa Bluetooth mini, Hub chuyển đổi Type-C, Giá đỡ Laptop');

-- --------------------------------------------------------
-- 3. Bảng `brands` (Thương hiệu phụ kiện - Thành viên 4)
-- --------------------------------------------------------
CREATE TABLE `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `origin` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `brands` (`id`, `name`, `origin`) VALUES
(1, 'Logitech', 'Thụy Sĩ'),
(2, 'Anker', 'Mỹ / Quốc tế'),
(3, 'Sony', 'Nhật Bản'),
(4, 'Razer', 'Mỹ'),
(5, 'Keychron', 'Hồng Kông'),
(6, 'Baseus', 'Quốc tế');

-- --------------------------------------------------------
-- 4. Bảng `products` (Sản phẩm phụ kiện - Thành viên 2, 3, 4)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `brand_id` INT DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `price` DECIMAL(12,0) NOT NULL,
  `sale_price` DECIMAL(12,0) DEFAULT NULL,
  `stock` INT DEFAULT 20,
  `thumbnail` VARCHAR(255) NOT NULL,
  `specifications` TEXT DEFAULT NULL COMMENT 'Thông số kỹ thuật tóm tắt',
  `description` LONGTEXT DEFAULT NULL COMMENT 'Nội dung chi tiết soạn bằng CKEditor',
  `is_featured` TINYINT(1) DEFAULT 0,
  `views` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `price`, `sale_price`, `stock`, `thumbnail`, `specifications`, `description`, `is_featured`, `views`) VALUES
(1, 3, 1, 'Chuột không dây Logitech G Pro X Superlight 2', 3590000, 3190000, 25, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&auto=format&fit=crop&q=80',
'Kết nối: Lightspeed Wireless | Trọng lượng: 60g | Cảm biến: HERO 2 32.000 DPI | Pin: 95 giờ | Bảo hành: 24 tháng',
'<h3>Đỉnh cao chuột Gaming siêu nhẹ thế hệ mới</h3><p><strong>Logitech G Pro X Superlight 2</strong> mang đến tốc độ phản hồi 2000Hz cùng switch quang học Lightforce bền bỉ.</p><ul><li>Thiết kế đối xứng siêu nhẹ chỉ 60 gram.</li><li>Cảm biến HERO 2 chính xác tuyệt đối cho game thủ eSports.</li><li>Cổng sạc USB Type-C tiện lợi, thời lượng pin lên đến 95 giờ.</li></ul>', 1, 152),

(2, 2, 5, 'Bàn phím cơ không dây Keychron K8 Pro QMK/VIA RGB', 2690000, 2290000, 18, 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600&auto=format&fit=crop&q=80',
'Layout: TKL (87 phím) | Kết nối: Bluetooth 5.1 & Type-C | Switch: Gateron G Pro (Hotswap) | Khung nhôm CNC | Bảo hành: 12 tháng',
'<h3>Bàn phím cơ Custom hoàn hảo cho cả Mac và Windows</h3><p>Keychron K8 Pro được trang bị sẵn đệm tiêu âm Silicon, keycap PBT Double-shot chống bóng và khả năng lập trình phím qua QMK/VIA.</p>', 1, 128),

(3, 1, 3, 'Tai nghe chụp tai chống ồn Sony WH-1000XM5', 8490000, 6990000, 12, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=600&auto=format&fit=crop&q=80',
'Chống ồn: Chủ động ANC | Thời lượng pin: 30 giờ | Chuẩn kết nối: Bluetooth 5.2, LDAC | Sạc nhanh: 3 phút nghe 3 giờ',
'<h3>Chống ồn chủ động hàng đầu thế giới</h3><p>Tai nghe <strong>Sony WH-1000XM5</strong> sở hữu 8 micro chống ồn cùng bộ xử lý V1 đem lại không gian âm nhạc tĩnh lặng tuyệt đối.</p>', 1, 240),

(4, 4, 2, 'Củ sạc nhanh Anker Prime 67W GaN 3 cổng (2C1A)', 1450000, 1190000, 40, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600&auto=format&fit=crop&q=80',
'Công suất: 67W Max | Cổng sạc: 2x USB-C, 1x USB-A | Công nghệ: GaNPrime & PowerIQ 4.0 | Chân gập gọn | Bảo hành: 18 tháng',
'<h3>Sạc cùng lúc Laptop, Điện thoại và Tai nghe</h3><p>Nhờ công nghệ <strong>GaNPrime</strong> tiên tiến, củ sạc Anker 67W nhỏ hơn 51% so với sạc truyền thống nhưng vẫn sạc đầy MacBook Air cực nhanh.</p>', 1, 95),

(5, 5, 6, 'Pin sạc dự phòng Baseus MagSafe 10.000mAh 20W', 890000, 650000, 35, 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=600&auto=format&fit=crop&q=80',
'Dung lượng: 10.000mAh | Sạc không dây: 15W MagSafe | Sạc có dây: PD 20W | Màn hình LED báo % pin',
'<h3>Gọn nhẹ, hít từ tính MagSafe siêu chắc</h3><p>Pin dự phòng Baseus tích hợp vòng từ tính chuẩn MagSafe kèm chân đế gập giúp vừa sạc vừa xem phim tiện lợi.</p>', 1, 110),

(6, 1, 4, 'Tai nghe Gaming Razer BlackShark V2 HyperSpeed', 3290000, 2890000, 15, 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=600&auto=format&fit=crop&q=80',
'Màng loa: TriForce Titanium 50mm | Kết nối: 2.4GHz Wireless & Bluetooth | Trọng lượng: 280g | Âm thanh: THX Spatial Audio',
'<h3>Tai nghe eSports chuyên nghiệp siêu nhẹ</h3><p>Được tin dùng bởi các tuyển thủ chuyên nghiệp, Razer BlackShark V2 HyperSpeed cho khả năng định vị tiếng bước chân chuẩn xác.</p>', 0, 88),

(7, 2, 1, 'Bàn phím cơ không dây Logitech MX Mechanical Mini', 3790000, 3290000, 20, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&auto=format&fit=crop&q=80',
'Layout: 75% nhỏ gọn | Switch: Low-profile Tactile Quiet | Đèn nền thông minh tự sáng khi đưa tay gần | Kết nối: 3 thiết bị',
'<h3>Trải nghiệm gõ cơ học êm ái cho dân lập trình & văn phòng</h3><p>Thiết kế Low-profile sang trọng, kết nối đa thiết bị qua Bluetooth hoặc đầu thu Logi Bolt.</p>', 0, 76),

(8, 6, 3, 'Loa Bluetooth Sony ULT Field 1 Chống nước IP67', 3190000, 2790000, 22, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&auto=format&fit=crop&q=80',
'Công nghệ âm thanh: ULT Power Sound tăng cường Bass | Chống nước bụi: IP67 | Pin: 12 giờ | Có dây đeo tiện lợi',
'<h3>Âm trầm bùng nổ trong thiết kế nhỏ gọn</h3><p>Bấm nút ULT để kích hoạt âm bass uy lực. Kháng nước và chống sốc hoàn hảo cho mọi chuyến dã ngoại.</p>', 1, 134);

-- --------------------------------------------------------
-- 5. Bảng `product_images` (Lưu nhiều ảnh phụ cho 1 sản phẩm - Thành viên 4)
-- --------------------------------------------------------
CREATE TABLE `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `product_images` (`product_id`, `image_url`) VALUES
(1, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=600&auto=format&fit=crop&q=80'),
(1, 'https://images.unsplash.com/photo-1605773527852-c546a8584ea3?w=600&auto=format&fit=crop&q=80'),
(2, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&auto=format&fit=crop&q=80'),
(2, 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=600&auto=format&fit=crop&q=80'),
(3, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80');

-- --------------------------------------------------------
-- 6. Bảng `orders` (Đơn đặt hàng - Thành viên 3 & 5)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `receiver_name` VARCHAR(100) NOT NULL,
  `receiver_phone` VARCHAR(20) NOT NULL,
  `receiver_address` VARCHAR(255) NOT NULL,
  `note` TEXT DEFAULT NULL,
  `total_amount` DECIMAL(12,0) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'COD',
  `status` ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`id`, `user_id`, `receiver_name`, `receiver_phone`, `receiver_address`, `note`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 2, 'Nguyễn Văn Khách', '0987654321', '144 Xuân Thủy, Cầu Giấy, Hà Nội', 'Giao giờ hành chính giúp mình', 5480000, 'COD', 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 3, 'Trần Thị Minh Anh', '0912345678', '25 Nguyễn Văn Linh, Hải Châu, Đà Nẵng', 'Gọi trước khi giao 15p', 1840000, 'Chuyển khoản', 'shipping', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 'Nguyễn Văn Khách', '0987654321', '144 Xuân Thủy, Cầu Giấy, Hà Nội', '', 2790000, 'COD', 'pending', NOW());

-- --------------------------------------------------------
-- 7. Bảng `order_details` (Chi tiết sản phẩm trong đơn hàng - Thành viên 3 & 5)
-- --------------------------------------------------------
CREATE TABLE `order_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `price` DECIMAL(12,0) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_details` (`order_id`, `product_id`, `price`, `quantity`) VALUES
(1, 1, 3190000, 1),
(1, 2, 2290000, 1),
(2, 4, 1190000, 1),
(2, 5, 650000, 1),
(3, 8, 2790000, 1);

-- --------------------------------------------------------
-- 8. Bảng `reviews` (Đánh giá & Bình luận phụ kiện - Thành viên 6)
-- --------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `rating` TINYINT NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reviews` (`user_id`, `product_id`, `rating`, `comment`) VALUES
(2, 1, 5, 'Chuột siêu nhẹ, cầm bắn Valorant cực kỳ chuẩn xác. Giao hàng đóng gói rất kỹ!'),
(3, 2, 5, 'Bàn phím gõ rất đầm tay, đèn RGB đẹp, kết nối MacBook nhạy.'),
(2, 3, 4, 'Chống ồn đỉnh thật sự, đeo êm tai không bị đau khi ngồi code lâu.');
