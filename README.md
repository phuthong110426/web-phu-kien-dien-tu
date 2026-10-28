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

###  Thành viên 1 : Thiết kế CSDL + Kết nối Core + Đăng ký / Đăng nhập / Phân quyền
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

###  Thành viên 2: Giao diện người dùng (UI/UX Responsive) + Trang chủ + Tìm kiếm, Bộ lọc & Phân trang
* **File phụ trách chính:**
  * `includes/header.php`, `includes/footer.php`
  * `index.php` (Trang chủ)
  * `products.php` (Trang cửa hàng + Tìm kiếm + Lọc + Phân trang)
* **Chức năng đã có trong khung:**
  * Thanh điều hướng (Navbar) responsive, tìm kiếm theo từ khóa, hiển thị danh mục.
  * Trang chủ hiển thị phụ kiện nổi bật (Flash Sale) và phụ kiện mới.
  * Trang cửa hàng lọc theo Danh mục, Thương hiệu, Sắp xếp giá và **Phân trang (`LIMIT / OFFSET`)**.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Thêm bộ lọc theo Khoảng giá** vào cột bên trái trang `products.php` (Ví dụ: *Dưới 1 triệu*, *Từ 1 - 3 triệu*, *Trên 3 triệu*).
  - [ ] **Nâng cấp Banner trang chủ (`index.php`):** Đổi thành dạng Slide trượt nhiều ảnh (Bootstrap Carousel) cho sinh động và bắt mắt hơn.
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

###  Thành viên 4: Admin - Quản lý Danh mục, Thương hiệu & Sản phẩm (CRUD + CKEditor + Upload nhiều ảnh)
* **File phụ trách chính:**
  * `admin/categories.php` (CRUD Danh mục)
  * `admin/products.php` (Danh sách & Xóa sản phẩm + Phân trang Admin)
  * `admin/product_form.php` (Thêm / Sửa sản phẩm + **CKEditor 5** + **Upload nhiều ảnh**)
* **Chức năng đã có trong khung:**
  * CRUD Danh mục sản phẩm.
  * CRUD Sản phẩm có phân trang, tích hợp sẵn trình soạn thảo **CKEditor 5** và tính năng **Upload nhiều ảnh phụ** lưu vào bảng `product_images`.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Làm thêm phần CRUD Thương hiệu (`brands`)** (Thêm, sửa, xóa các hãng như Logitech, Sony, Anker, Razer...) ngay trong trang `admin/categories.php` hoặc tách ra `admin/brands.php`.
  - [ ] **Thêm nút xóa từng ảnh phụ** trong trang chỉnh sửa sản phẩm `admin/product_form.php`.
  - [ ] Chuẩn bị phần giải thích kỹ thuật về cách hoạt động của `$_FILES` khi upload nhiều ảnh và cách nhúng CKEditor để báo cáo.

---

###  Thành viên 5: Admin - Dashboard Thống kê (Chart.js) + Quản lý Đơn hàng & Người dùng
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
  - [ ] **Làm chức năng "Xem chi tiết đơn hàng" trong `admin/orders.php`:** Khi Admin bấm vào 1 đơn hàng sẽ xem được đơn đó mua những phụ kiện gì, số lượng bao nhiêu.
  - [ ] **Cập nhật tự động trừ tồn kho (`stock`)** khi Admin chuyển trạng thái đơn hàng sang `completed` (Hoàn thành).
  - [ ] Thêm 1 biểu đồ thống kê số lượng đơn hàng theo từng trạng thái trong `admin/index.php`.

---

###  Thành viên 6: Đánh giá sản phẩm (Reviews) + Nhập dữ liệu mẫu + Deploy Hosting & Làm Slide Thuyết trình
* **File phụ trách chính:**
  * `admin/reviews.php` (Quản lý & Xóa bình luận/đánh giá)
  * Phần form gửi đánh giá sao trong `product_detail.php`
  * Slide báo cáo (`.pptx` / `.pdf`) & Link Deploy
* **Chức năng đã có trong khung:**
  * Khách hàng chấm điểm (1-5 sao) và viết nhận xét ở trang chi tiết phụ kiện.
  * Admin xem danh sách toàn bộ đánh giá và xóa các bình luận vi phạm.
* ** VIỆC CẦN LÀM THÊM (TODO):**
  - [ ] **Nhập thêm dữ liệu thật đẹp:** Đăng nhập vào Admin, thêm mới khoảng **10 - 12 sản phẩm phụ kiện điện tử** (có ảnh nét, nhiều ảnh phụ, thông số kỹ thuật và mô tả soạn bằng CKEditor) để trang web nhìn phong phú khi Demo.
  - [ ] **Deploy Website lên Hosting miễn phí** (InfinityFree / 000webhost / AwardSpace) để lấy **Link deploy** nộp cho giảng viên.
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
