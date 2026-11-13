# 🎧 TECHGEAR - Website Bán Đồ Phụ Kiện Điện Tử (Nhóm 11 - PHP & MySQL)

> **Bài tập nhóm cuối kỳ môn Lập Trình Web**
> - **Đề tài:** Website bán hàng phụ kiện điện tử (Chuyên phụ kiện điện tử: Tai nghe, Bàn phím cơ, Chuột Gaming, Củ cáp sạc, Loa Bluetooth...)
> - **Công nghệ:** Backend **PHP 8 + MySQL (PDO)** | Frontend **HTML5, CSS3, Bootstrap 5, JavaScript, CKEditor 5, Chart.js**

---

##  1. Hướng dẫn các thành viên tải về và chạy trên XAMPP 

### Bước 1: Tải code về máy
* Bấm nút màu xanh **`<> Code`** ở góc trên bên phải trang GitHub này -> Chọn **`Download ZIP`** (hoặc dùng `git clone`).
* Giải nén thư mục vào đường dẫn `htdocs` của XAMPP:
  * Nếu cài XAMPP ở ổ C: `C:\xampp\htdocs\web-phu-kien-dien-tu`
  * Nếu cài XAMPP ở ổ D: `D:\xampp\htdocs\web-phu-kien-dien-tu`

### Bước 2: Bật XAMPP & Nạp Cơ sở dữ liệu (MySQL)
1. Mở **XAMPP Control Panel** -> Bấm nút **`Start`** ở 2 dòng **`Apache`** và **`MySQL`** (hiện nền xanh lá).
2. Mở trình duyệt vào địa chỉ: **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
3. Bấm vào tab **`Import` (Nhập)** trên thanh menu trên cùng.
4. Bấm **`Choose File` (Chọn tệp)** -> Chọn file **`database/shop_phukien.sql`** trong thư mục dự án -> Cuộn xuống dưới cùng bấm nút **`Go` (Thực hiện)**.
5. *(Khuyến nghị)* Tiếp tục **Import** thêm file **`database/seed_100_products.sql`** vào cơ sở dữ liệu `shop_phukien` để nạp bổ sung **100 sản phẩm phụ kiện thật** khớp ảnh theo từng model (nâng tổng lên **108 sản phẩm**, **25 thương hiệu**).

### Bước 3: Mở trang web và đăng nhập thử
* **Trang cửa hàng (User):** `http://localhost/web-phu-kien-dien-tu/`
* **Trang quản trị (Admin):** `http://localhost/web-phu-kien-dien-tu/admin/`

| Loại tài khoản | Email đăng nhập | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@gmail.com` | `123456` | Toàn quyền vào trang Admin + Mua hàng |
| **Khách hàng (User 1)** | `khach@gmail.com` | `123456` | Mua hàng, xem đơn hàng, gửi đánh giá |
| **Khách hàng (User 2)** | `minhanh@gmail.com` | `123456` | Mua hàng, xem đơn hàng, gửi đánh giá |

---

##  2. Phân công chi tiết & Việc cần làm thêm cho 6 thành viên

Bộ khung dự án (Base Code) đã được khởi tạo sẵn để đảm bảo cả nhóm thống nhất cấu trúc Database và giao diện. **Mỗi thành viên có trách nhiệm:**
1. **Đọc hiểu kỹ toàn bộ code** trong các file mình phụ trách để trình bày và trả lời câu hỏi vấn đáp khi bảo vệ.
2. **Thực hiện các đầu việc cần làm thêm (TODO)** bên dưới cho phần của mình.

---

### Thành viên 1 (Phương Hòa): Thiết kế CSDL + Kết nối Core + Đăng ký / Đăng nhập / Phân quyền
* **File phụ trách chính:**
  * `database/shop_phukien.sql`
  * `config/db.php`
  * `login.php`, `register.php`, `logout.php`, `profile.php`
* **Chức năng đã có trong khung:**
  * Thiết kế 8 bảng MySQL (`users`, `categories`, `brands`, `products`, `product_images`, `orders`, `order_details`, `reviews`).
  * Kết nối PDO, hàm phân quyền `is_logged_in()`, `is_admin()`, `require_admin()`.
  * Đăng ký, Đăng nhập, Đăng xuất, Cập nhật thông tin cá nhân.
* **📌 VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Code thêm chức năng "Đổi mật khẩu"** trong trang `profile.php` (nhập mật khẩu cũ, mật khẩu mới, xác nhận mật khẩu mới).
  - [ ] Xuất **Sơ đồ quan hệ thực thể (ERD)** của 8 bảng từ phpMyAdmin (vào `shop_phukien` -> tab `More` -> `Designer`) để đưa vào Slide thuyết trình.
  - [ ] Kiểm tra và ghép code của các thành viên trong nhóm.

---

### Thành viên 2 (Phú Thông): Giao diện người dùng (UI/UX Responsive) + Trang chủ + Tìm kiếm, Bộ lọc & Phân trang + Trợ lý ảo AI Chatbot + Deploy Hosting
* **File phụ trách chính:**
  * `includes/header.php`, `includes/footer.php`, `includes/product_card.php`
  * `index.php` (Trang chủ)
  * `products.php` (Trang cửa hàng + Tìm kiếm + Lọc + Phân trang)
  * `api/chat_ai.php` (Kết nối Google Gemini AI Chatbot)
  * `assets/css/style.css`, `assets/js/main.js` (Hiệu ứng UI & Chatbot AI)
  * *Hỗ trợ toàn nhóm & Deploy:* `database/seed_100_products.sql`, `admin/product_form.php`, `admin/includes/header.php`, Link Deploy Hosting
* **Chức năng đã có trong khung:**
  * Thanh điều hướng (Navbar) responsive, tìm kiếm theo từ khóa, hiển thị danh mục.
  * Trang chủ hiển thị phụ kiện nổi bật (Flash Sale) và phụ kiện mới.
  * Trang cửa hàng lọc theo Danh mục, Thương hiệu, Sắp xếp giá và **Phân trang (`LIMIT / OFFSET`)**.
* **📌 VIỆC ĐÃ HOÀN THÀNH & CẦN LÀM THÊM:**
  - [x] **Thêm bộ lọc theo Khoảng giá** trong trang `products.php` (*Dưới 1 triệu*, *Từ 1 - 3 triệu*, *Trên 3 triệu*). ✅
  - [x] **Nâng cấp Banner trang chủ (`index.php`):** Banner trượt 3 slide (Bootstrap Carousel) có nút tạm dừng. ✅
  - [x] **Tối ưu giao diện theo phong cách apple:** thêm `assets/css/style.css`, `assets/js/main.js` (hiệu ứng hiện dần khi cuộn, kệ sản phẩm cuộn ngang, thanh lọc dính) và thẻ sản phẩm dùng chung `includes/product_card.php`. ✅
  - [x] **Tích hợp Trợ lý ảo AI Chatbot (Google Gemini API):** Tư vấn mua phụ kiện điện tử trực tiếp trên web theo dữ liệu sản phẩm trong CSDL, giao diện bong bóng chat kính mờ chuẩn Apple. ✅
  - [x] **Tối ưu bộ lọc cửa hàng (`products.php`) & phân trang (`assets/css/style.css`):** Tự động ẩn thương hiệu/danh mục không có sản phẩm hoặc hết hàng (`stock <= 0`) theo bộ lọc hiện tại, tự bỏ hãng không khớp khi chuyển danh mục, và sửa thanh phân trang xuống dòng gọn khi nhiều trang. ✅
  - [x] **Tạo dữ liệu thật khớp ảnh từng model (`database/seed_100_products.sql`):** Bổ sung **100 sản phẩm phụ kiện điện tử thật** phủ đều 6 danh mục và 25 thương hiệu (nâng tổng CSDL lên **108 sản phẩm**). ✅
  - [x] **Hỗ trợ hoàn thiện Admin & tích hợp nhóm:** Bổ sung nút xóa từng ảnh phụ (`admin/product_form.php`), bỏ nhãn `TV4 / TV5 / TV6` khỏi sidebar Admin (`admin/includes/header.php`) cho chuyên nghiệp, kiểm thử và đồng bộ code nhóm lên nhánh `main` GitHub. ✅
  - [ ] **Deploy Website lên Hosting miễn phí** (InfinityFree / 000webhost / AwardSpace): cấu hình kết nối DB trên host (`config/db.php`), import `shop_phukien.sql` + `seed_100_products.sql` và lấy **Link deploy** nộp cho giảng viên.
  - [ ] Chụp ảnh giao diện trên cả màn hình máy tính và điện thoại (Responsive) gửi cho Thành viên 6 làm Slide.

---

###  Thành viên 3: Trang chi tiết phụ kiện + Giỏ hàng (Cart) + Đặt hàng (Checkout) & Đơn hàng của tôi
* **File phụ trách chính:**
  * `product_detail.php` (Chi tiết sản phẩm)
  * `cart.php` (Giỏ hàng Session)
  * `checkout.php` (Thanh toán & Lưu đơn hàng)
  * `my_orders.php` (Lịch sử đơn hàng của khách)
* **Chức năng đã có trong khung:**
  * Xem chi tiết phụ kiện, đổi ảnh qua lại giữa nhiều góc chụp, hiển thị thông số kỹ thuật và mô tả HTML từ CKEditor.
  * Thêm vào giỏ hàng, sửa số lượng, xóa khỏi giỏ, tính tổng tiền.
  * Đặt hàng lưu vào 2 bảng `orders` và `order_details` (dùng Transaction), hủy đơn hàng khi đang chờ xác nhận (`pending`).
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Thêm mục "Sản phẩm liên quan (Cùng danh mục)"** ở cuối trang `product_detail.php` (hiển thị 4 sản phẩm cùng `category_id`).
  - [ ] **Thêm chức năng "Xem chi tiết các món đã mua"** trong trang `my_orders.php` (bấm vào mã đơn hàng sẽ hiện ra danh sách tên sản phẩm, số lượng, đơn giá của đơn đó).
  - [ ] Vẽ sơ đồ luồng mua hàng (Use Case / Flowchart) gửi cho Thành viên 6 đưa vào Slide.

---

### Thành viên 4 (Trần Triết): Admin - Quản lý Danh mục, Thương hiệu & Sản phẩm (CRUD + CKEditor + Upload nhiều ảnh)
* **File phụ trách chính:**
  * `admin/categories.php` (CRUD Danh mục)
  * `admin/brands.php` (CRUD Thương hiệu)
  * `admin/products.php` (Danh sách & Xóa sản phẩm + Phân trang Admin)
  * `admin/product_form.php` (Thêm / Sửa sản phẩm + **CKEditor 5** + **Upload nhiều ảnh**)
* **Chức năng đã có trong khung:**
  * CRUD Danh mục sản phẩm.
  * CRUD Sản phẩm có phân trang, tích hợp sẵn trình soạn thảo **CKEditor 5** và tính năng **Upload nhiều ảnh phụ** lưu vào bảng `product_images`.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [x] **Làm thêm phần CRUD Thương hiệu (`brands`)** (Thêm, sửa, xóa các hãng như Logitech, Sony, Anker, Razer...) tách riêng tại `admin/brands.php`. ✅
  - [ ] Chuẩn bị phần giải thích kỹ thuật về cách hoạt động của `$_FILES` khi upload nhiều ảnh và cách nhúng CKEditor để báo cáo.

---

### Thành viên 5 (Ngọc Hoàn): Admin - Dashboard Thống kê (Chart.js) + Quản lý Đơn hàng & Người dùng
* **File phụ trách chính:**
  * `admin/index.php` (Bảng điều khiển & Biểu đồ thống kê)
  * `admin/orders.php` (Quản lý & Duyệt trạng thái đơn hàng)
  * `admin/users.php` (Quản lý tài khoản & Cấp quyền Admin / Khóa tài khoản)
  * `admin/includes/header.php`, `admin/includes/footer.php`
* **Chức năng đã có trong khung:**
  * Thống kê tổng doanh thu, tổng đơn hàng, tổng sản phẩm, tổng người dùng + Biểu đồ tròn `Chart.js`.
  * Cập nhật trạng thái đơn hàng (`pending` -> `confirmed` -> `shipping` -> `completed` / `cancelled`).
  * Khóa/Mở khóa người dùng và đổi quyền `user` <-> `admin`.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [x] **Làm chức năng "Xem chi tiết đơn hàng" trong `admin/orders.php`:** Khi Admin bấm vào 1 đơn hàng sẽ xem được đơn đó mua những phụ kiện gì, số lượng bao nhiêu. (Đã tạo `admin/order_detail.php`) ✅
  - [x] **Cập nhật tự động trừ tồn kho (`stock`)** khi Admin chuyển trạng thái đơn hàng sang `completed` (Hoàn thành) trong `admin/orders.php` & `admin/order_detail.php`. ✅
  - [x] **Thêm 1 biểu đồ thống kê số lượng đơn hàng theo từng trạng thái trong `admin/index.php` (Chart.js)**. ✅

---

###  Thành viên 6 (Hồng Anh): Đánh giá sản phẩm (Reviews) + Thống kê sao & Làm Slide Thuyết trình
* **File phụ trách chính:**
  * `admin/reviews.php` (Quản lý, lọc & xóa bình luận/đánh giá)
  * Phần thống kê sao & gửi đánh giá trong `product_detail.php`
  * Slide báo cáo (`.pptx` / `.pdf`)
* **Chức năng đã có trong khung:**
  * Khách hàng chấm điểm (1-5 sao) và viết nhận xét ở trang chi tiết phụ kiện.
  * Admin xem danh sách toàn bộ đánh giá và xóa các bình luận vi phạm.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Nâng cấp trang Quản lý Đánh giá (`admin/reviews.php`):** Thêm bộ lọc theo số sao (1–5 sao), tìm kiếm theo tên sản phẩm / nội dung bình luận, phân trang (`LIMIT / OFFSET`), bọc `.table-responsive` và đổi thao tác xóa sang `POST`.
  - [ ] **Nâng cấp Đánh giá tại trang chi tiết (`product_detail.php`):** Tính và hiển thị **điểm đánh giá trung bình** (VD: `4.8 / 5 ★`) cùng thống kê số lượng từng mức 1–5 sao *(tùy chọn nâng cao: chỉ khách đã mua đơn `completed` mới được đánh giá)*.
  - [ ] **Thiết kế Slide thuyết trình (.pptx và xuất .pdf)** bám sát yêu cầu đề bài:
    1. Giới thiệu đề tài & Bảng phân công + Đánh giá mức độ hoàn thành của 6 thành viên.
    2. Phân tích thiết kế hệ thống (Sơ đồ chức năng User/Admin, Sơ đồ CSDL ERD 8 bảng).
    3. Điểm nhấn kỹ thuật (PHP PDO, Phân quyền, Phân trang, Upload nhiều ảnh, CKEditor, Chart.js).
    4. Kịch bản Demo trực tiếp trên web.

---

##  3. Quy trình làm việc chung trên GitHub cho nhóm
1. Mỗi thành viên chỉ chỉnh sửa các file thuộc phần phân công của mình để tránh xung đột code (Conflict).
2. Sau khi code xong và chạy thử không lỗi trên XAMPP cá nhân:
   * Có thể dùng **Git / GitHub Desktop** để `Commit` & `Push` lên nhánh `main`.
   * Hoặc bấm **`Add file` -> `Upload files`** trực tiếp trên trang GitHub này để cập nhật lại file mình vừa sửa và ghi rõ nội dung cập nhật ở ô *Commit changes* (Ví dụ: *"TV2: Thêm bộ lọc khoảng giá trang products.php"*).
