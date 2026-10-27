# 🎧 TECHGEAR - Website Bán Đồ Phụ Kiện Điện Tử (PHP & MySQL)

Bài tập nhóm cuối kỳ môn **Lập Trình Web** — Đề tài: **Website bán hàng cơ bản (Phụ kiện điện tử)**.

---

## 🚀 Hướng dẫn cài đặt và chạy dự án trên XAMPP (Chỉ 3 phút)

1. **Bật XAMPP:**
   * Mở **XAMPP Control Panel** -> Bấm **Start** ở 2 dòng **Apache** và **MySQL**.
2. **Đặt thư mục code vào XAMPP:**
   * Copy thư mục `web-phu-kien-dien-tu` vào đường dẫn: `C:\xampp\htdocs\web-phu-kien-dien-tu`
3. **Import Cơ sở dữ liệu (MySQL):**
   * Mở trình duyệt vào: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   * Bấm vào tab **Import (Nhập)** trên thanh menu trên cùng.
   * Bấm **Choose File** -> Chọn file `database/shop_phukien.sql` trong thư mục dự án -> Cuộn xuống bấm **Go (Thực hiện)**.
4. **Mở trang web:**
   * **Trang khách hàng:** [http://localhost/web-phu-kien-dien-tu/](http://localhost/web-phu-kien-dien-tu/)
   * **Trang quản trị Admin:** [http://localhost/web-phu-kien-dien-tu/admin/](http://localhost/web-phu-kien-dien-tu/admin/)

### 🔑 Tài khoản đăng nhập mẫu:
* **Tài khoản Admin:** Email: `admin@gmail.com` | Mật khẩu: `123456`
* **Tài khoản Khách hàng:** Email: `khach@gmail.com` | Mật khẩu: `123456`

---

## 👥 Phân công nhiệm vụ 6 thành viên trong nhóm

| Thành viên | Vai trò & Chức năng phụ trách | File code làm việc chính |
| :--- | :--- | :--- |
| **Thành viên 1 (Nhóm trưởng)** | Thiết kế CSDL MySQL, Kết nối PDO, Đăng ký, Đăng nhập, Đăng xuất, Phân quyền Admin/User, Hồ sơ cá nhân. | `database/shop_phukien.sql`, `config/db.php`, `login.php`, `register.php`, `logout.php`, `profile.php` |
| **Thành viên 2** | Giao diện Responsive (Bootstrap 5), Trang chủ, Tìm kiếm, Bộ lọc theo Danh mục/Hãng/Giá & **Phân trang (Pagination)**. | `includes/header.php`, `includes/footer.php`, `index.php`, `products.php` |
| **Thành viên 3** | Trang chi tiết sản phẩm (hiển thị nhiều ảnh, thông số kỹ thuật), Giỏ hàng (Thêm/Sửa/Xóa), Đặt hàng (Checkout) & Lịch sử đơn hàng. | `product_detail.php`, `cart.php`, `checkout.php`, `my_orders.php` |
| **Thành viên 4** | Admin: CRUD Danh mục & Sản phẩm, **Phân trang Admin**, tích hợp **CKEditor 5** và **Upload nhiều ảnh sản phẩm**. | `admin/categories.php`, `admin/products.php`, `admin/product_form.php` |
| **Thành viên 5** | Admin: Bảng điều khiển (Dashboard thống kê biểu đồ **Chart.js**), Quản lý Đơn hàng, Quản lý Người dùng & Khóa/Mở tài khoản. | `admin/index.php`, `admin/orders.php`, `admin/users.php` |
| **Thành viên 6** | Chức năng Đánh giá sao & Bình luận sản phẩm, Quản lý bình luận Admin, Nhập dữ liệu mẫu, Deploy & Thiết kế Slide báo cáo. | `admin/reviews.php`, `product_detail.php` (phần review), Slide thuyết trình |

---

## ✅ Đáp ứng đầy đủ tiêu chí chấm điểm của đề bài
- **Backend PHP + MySQL:** Sử dụng PDO chuẩn, chống SQL Injection.
- **Trang User & Trang Admin:** Tách biệt rõ ràng, có kiểm tra phân quyền (`require_admin()`).
- **Giao diện hiện đại, Responsive:** Thiết kế bằng Bootstrap 5 + Bootstrap Icons.
- **Đầy đủ chức năng CRUD:** Danh mục, Sản phẩm, Đơn hàng, Người dùng, Đánh giá.
- **Tính năng nâng cao:**
  - **Phân trang (Pagination)** ở cả trang Cửa hàng (`products.php`) và Admin (`admin/products.php`).
  - **Upload nhiều ảnh** cho 1 sản phẩm (`admin/product_form.php` -> bảng `product_images`).
  - **Sử dụng Editor (CKEditor 5)** khi soạn bài viết mô tả phụ kiện (`admin/product_form.php`).
