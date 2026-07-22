# HRM System – Hệ thống quản lý nhân sự (Trưởng phòng)

Website quản lý nhân sự viết bằng **HTML, CSS, JavaScript, PHP (thuần) và MySQL**,
chạy trên **XAMPP**. Dự án được tách file rõ ràng theo từng chức năng, dễ đọc, dễ mở rộng.

---

## 0. CHANGELOG — Các lỗi đã sửa (bản cập nhật mới nhất)

Dựa trên kết quả kiểm thử thực tế, các lỗi sau đã được xác định và sửa:

| # | Lỗi | Nguyên nhân | Cách sửa |
|---|---|---|---|
| 1 | Trang **Thống kê** bị lỗi/trắng trang (HTTP 500) | Câu truy vấn "Top nhân viên nhiều yêu cầu nhất" vi phạm chế độ SQL `ONLY_FULL_GROUP_BY` (mặc định bật trên MySQL 8/MariaDB bản mới) | Bổ sung đủ cột vào mệnh đề `GROUP BY` trong `statistics.php` |
| 2 | Trang **Yêu cầu của nhân viên** mở lên trống trơn, không duyệt/từ chối/xem chi tiết được | Bộ lọc ngày mặc định giới hạn đúng **tháng dương lịch hiện tại**, trong khi dữ liệu mẫu lại có ngày cố định trong quá khứ | Đổi dữ liệu mẫu (`database.sql`) sang ngày **tương đối** (`NOW() - INTERVAL...`) để luôn khớp thời điểm import; đồng thời nới bộ lọc mặc định trong `requests.php` thành "90 ngày gần nhất" |
| 3 | Thẻ "Yêu cầu trong tháng" / "Cuộc họp trong tháng" ở Tổng quan hiển thị sai/bằng 0 | Cùng nguyên nhân #2 — so khớp đúng tháng dương lịch thay vì khung thời gian gần đây | Đổi sang khung **30 ngày gần nhất** (`index.php`), tránh lệch ranh giới đầu/cuối tháng |
| 4 | Danh sách "Lịch phòng họp sắp tới" ở Tổng quan không bấm vào xem chi tiết được | Thiếu thẻ `<a>` bao quanh từng dòng cuộc họp | Bọc lại bằng `<a href="meeting_view.php?id=...">` trong `index.php` |
| 5 | Tải ảnh đại diện / tệp đính kèm đôi khi không lưu được, đặc biệt với ảnh chụp từ điện thoại (thường 3-8MB) | PHP mặc định chỉ cho upload tối đa **2MB**; khi vượt giới hạn, code cũ không kiểm tra mã lỗi upload nên vẫn âm thầm lưu tên file vào CSDL dù file thật chưa từng được ghi lên server, gây ảnh vỡ | Thêm file `.htaccess` nâng giới hạn lên 10MB; thêm hàm `validate_upload_error()` kiểm tra mã lỗi upload và báo rõ ràng cho người dùng thay vì âm thầm thất bại (áp dụng cho cả 5 điểm upload ảnh đại diện + tệp đính kèm cuộc họp) |
| 6 | **Trang "Chi tiết cuộc họp" hiển thị trống toàn bộ** (Phòng họp, Người tạo, Thời gian, Nội dung đều là "—") dù cuộc họp đã tạo đầy đủ dữ liệu trong CSDL | **Lỗi trùng tên biến nghiêm trọng**: `includes/sidebar.php` dùng vòng lặp `foreach ($menu as $m)`, còn `meeting_view.php` / `employee/meeting_view.php` cũng dùng biến `$m` để lưu dữ liệu cuộc họp. Vì PHP `include()` dùng chung phạm vi biến với file gọi nó, khi `sidebar.php` được include (ngay trước phần hiển thị chi tiết), vòng lặp menu đã **ghi đè hoàn toàn** lên `$m`, xóa sạch dữ liệu cuộc họp trước khi kịp hiển thị | Đổi tên biến vòng lặp trong `sidebar.php` từ `$m` thành `$__sidebarMenuItem` (tiền tố đặc thù, không thể trùng); đồng thời đổi tên `$m` thành `$meeting` trong 2 trang chi tiết cuộc họp để rõ ràng và an toàn kép |
| 7 | **Đính kèm tệp sai định dạng (VD: .exe) khi tạo cuộc họp khiến cuộc họp KHÔNG được tạo luôn** (đáng lẽ chỉ nên bỏ qua tệp, vẫn tạo cuộc họp bình thường) | Ở lần sửa lỗi trước, khi thêm kiểm tra định dạng tệp đính kèm chặt chẽ hơn, đã vô tình đưa lỗi "sai định dạng tệp" vào chung mảng lỗi chặn toàn bộ form — khiến việc validate tệp đính kèm (vốn là trường TÙY CHỌN) chặn luôn cả các trường bắt buộc khác | Tách riêng việc kiểm tra tệp đính kèm ra khỏi mảng lỗi chặn form; nếu tệp không hợp lệ (sai định dạng, lỗi upload, hết quyền ghi...) thì chỉ bỏ qua việc lưu tệp và hiển thị **cảnh báo nhẹ màu vàng** sau khi tạo cuộc họp thành công, không còn chặn việc tạo cuộc họp nữa (`meeting_create.php`, `calendar.php`) |

> Toàn bộ danh sách trên đã được kiểm thử lại bằng kịch bản tự động (đăng nhập 2 vai trò, duyệt/từ chối
> yêu cầu, tạo/hủy cuộc họp, upload ảnh đại diện thật, xem chi tiết cuộc họp...) trên môi trường **bật sẵn
> `ONLY_FULL_GROUP_BY`** để mô phỏng đúng cấu hình MySQL/MariaDB nghiêm ngặt mà nhiều bản XAMPP mới sử dụng
> mặc định.

**Lưu ý khi cập nhật bản này:** vì bảng `yeu_cau` mẫu đổi sang ngày tương đối, nếu bạn đã có sẵn
database `hrm_system` từ bản cũ, nên **xóa database cũ và import lại `database.sql` mới** để đảm bảo
dữ liệu demo hiển thị đúng ngay từ đầu (Cấu trúc → chọn hết bảng → Xóa, hoặc chạy `DROP DATABASE hrm_system;`
rồi Import lại).

---

## 1. Cấu trúc thư mục

```
hrm_system/
├── database.sql                # File cài đặt CSDL (import 1 lần)
├── config/
│   └── db.php                  # Kết nối MySQL (PDO)
├── includes/
│   ├── auth.php                # Session, phân quyền, các hàm dùng chung
│   ├── head_meta.php           # <head> dùng chung cho các trang
│   ├── header.php              # Thanh topbar (chuông, avatar, dropdown)
│   └── sidebar.php             # Menu điều hướng bên trái + modal đăng xuất
├── assets/
│   ├── css/style.css           # Toàn bộ giao diện
│   ├── js/main.js              # Xử lý modal, AJAX, preview ảnh...
│   └── img/                    # Ảnh mặc định
├── ajax/
│   ├── request_action.php      # Duyệt / từ chối yêu cầu (AJAX)
│   └── employee_delete.php     # Xóa nhân viên (AJAX)
├── uploads/
│   ├── avatars/                # Ảnh đại diện tải lên
│   └── attachments/            # Tệp đính kèm cuộc họp
├── login.php / logout.php
├── index.php                   # Tổng quan (Dashboard) - Trưởng phòng
├── requests.php                # Yêu cầu của nhân viên - Trưởng phòng
├── request_view.php
├── employees.php                # Danh sách nhân viên
├── employee_add.php             # Thêm nhân viên
├── employee_edit.php            # Sửa nhân viên
├── employee_view.php            # Xem chi tiết nhân viên
├── employee_export.php          # Xuất Excel (CSV)
├── calendar.php                  # Lịch phòng họp
├── meeting_create.php            # Tạo cuộc họp
├── meeting_view.php              # Chi tiết / hủy cuộc họp
├── statistics.php                # Thống kê, báo cáo
├── profile.php                   # Hồ sơ tài khoản trưởng phòng
└── employee/                     # ⭐ KHU VỰC DÀNH CHO NHÂN VIÊN
    ├── includes/                  # head/sidebar/header riêng cho nhân viên
    ├── index.php                  # Tổng quan của nhân viên
    ├── requests.php               # Gửi yêu cầu mới + xem yêu cầu của mình
    ├── meetings.php               # Lịch họp được mời tham dự
    ├── meeting_view.php           # Xem chi tiết cuộc họp (chỉ đọc)
    └── profile.php                # Hồ sơ cá nhân + đổi mật khẩu
```

---

## 2. Phân quyền truy cập (quan trọng)

Hệ thống có **2 vai trò** dùng chung 1 trang đăng nhập `login.php`, tự động điều hướng theo vai trò:

| Vai trò (`chuc_vu`) | Sau khi đăng nhập vào | Có thể truy cập |
|---|---|---|
| **Trưởng phòng** | `index.php` | Toàn bộ trang quản lý ở thư mục gốc |
| **Nhân viên** | `employee/index.php` | Chỉ các trang trong thư mục `employee/` |

- Các trang quản lý gốc (`index.php`, `employees.php`, `statistics.php`...) dùng hàm `require_manager()` —
  nhân viên gõ thẳng URL cũng sẽ bị đá về `login.php`.
- Các trang trong `employee/` dùng hàm `require_employee()` — Trưởng phòng vào nhầm cũng bị chặn.
- Các API AJAX quản lý (`ajax/request_action.php`, `ajax/employee_delete.php`) kiểm tra `chuc_vu` phía server,
  nhân viên gọi trực tiếp cũng không thực hiện được.
- Nhân viên xem chi tiết cuộc họp chỉ được xem cuộc họp **mình được mời**, không xem được cuộc họp khác.

---

## 3. Cài đặt với XAMPP

1. Cài **XAMPP** (đã có sẵn Apache + MySQL + PHP ≥ 7.4, khuyến nghị PHP 8.x).
2. Copy toàn bộ thư mục `hrm_system` vào:
   - Windows: `C:\xampp\htdocs\hrm_system`
   - macOS: `/Applications/XAMPP/htdocs/hrm_system`
3. Mở **XAMPP Control Panel**, bấm **Start** cho **Apache** và **MySQL**.
4. Mở trình duyệt vào `http://localhost/phpmyadmin`.
5. Tạo cơ sở dữ liệu:
   - Cách 1 (khuyên dùng): vào tab **Import**, chọn file `database.sql`, bấm **Go**.
     File này tự tạo database `hrm_system` và toàn bộ bảng + dữ liệu mẫu.
   - Cách 2 (dòng lệnh):
     ```
     mysql -u root -p --default-character-set=utf8mb4 < database.sql
     ```
     > Lưu ý: luôn import với charset `utf8mb4` để không bị lỗi font tiếng Việt.
6. Kiểm tra file `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hrm_system');
   define('DB_USER', 'root');
   define('DB_PASS', '');   // XAMPP mặc định không có mật khẩu root
   ```
   Nếu MySQL của bạn có mật khẩu, sửa `DB_PASS` cho đúng.
7. Truy cập: `http://localhost/hrm_system/login.php`

### Tài khoản đăng nhập demo

| Email                        | Mật khẩu | Vai trò       | Đăng nhập vào |
|-------------------------------|----------|----------------|----------------|
| nguyenvana@company.com        | 123456   | Trưởng phòng   | Khu vực quản lý (gốc) |
| tranthib@example.com          | 123456   | Nhân viên      | Khu vực nhân viên (`employee/`) |
| levanc@example.com            | 123456   | Nhân viên      | Khu vực nhân viên (`employee/`) |

Hệ thống tự nhận diện vai trò và điều hướng đúng khu vực sau khi đăng nhập —
bạn không cần chọn "đăng nhập với vai trò gì", chỉ cần dùng đúng tài khoản.

---

## 4. Các chức năng đã hoàn thiện

### Khu vực Trưởng phòng
- **Đăng nhập / Đăng xuất** có kiểm tra phiên (session), mã hoá mật khẩu bằng `password_hash`.
- **Tổng quan (Dashboard)**: thống kê nhanh, biểu đồ tròn (Chart.js), biểu đồ cột yêu cầu theo loại,
  lịch họp sắp tới, danh sách nhân viên mới.
- **Yêu cầu của nhân viên**: lọc theo loại / trạng thái / khoảng ngày / từ khoá, duyệt – từ chối
  bằng AJAX (không tải lại trang), xem chi tiết yêu cầu.
- **Danh sách nhân viên**: tìm kiếm, lọc theo trạng thái/phòng ban, phân trang, xuất Excel (CSV),
  xem – sửa – xóa (AJAX).
- **Thêm / Sửa nhân viên**: đầy đủ trường thông tin, tải ảnh đại diện, kiểm tra trùng email/mã NV.
- **Lịch phòng họp**: xem theo tháng, điều hướng tháng trước/sau, lịch mini, danh sách cuộc họp sắp tới.
- **Tạo cuộc họp**: chọn phòng họp, thời gian, thành viên tham dự (tìm kiếm, chọn tất cả),
  tệp đính kèm, quyền truy cập.
- **Chi tiết cuộc họp**: xem thông tin, danh sách thành viên, hủy cuộc họp.
- **Thống kê**: biểu đồ đường theo 6 tháng gần nhất, biểu đồ tròn trạng thái/mức độ, bảng top
  nhân viên có nhiều yêu cầu nhất, lọc theo tháng/phòng ban.
- **Hồ sơ tài khoản trưởng phòng**: 4 tab (Thông tin cá nhân, Thông tin công việc, Cài đặt tài khoản,
  Nhật ký hoạt động), đổi ảnh đại diện, đổi mật khẩu có kiểm tra mật khẩu cũ.

### Khu vực Nhân viên (`employee/`)
- **Tổng quan**: thống kê số yêu cầu của bản thân (tổng/chờ xử lý/đã duyệt/từ chối),
  danh sách cuộc họp sắp tới mình được mời, yêu cầu gần đây.
- **Yêu cầu của tôi**: form gửi yêu cầu mới (loại yêu cầu, nội dung, mức độ ưu tiên) —
  mã yêu cầu tự sinh (VD: `YC0007`), danh sách yêu cầu đã gửi kèm trạng thái xử lý, lọc theo trạng thái.
- **Lịch họp của tôi**: danh sách cuộc họp được mời (Sắp tới / Đã diễn ra / Tất cả),
  xem chi tiết cuộc họp + danh sách thành viên tham dự (chỉ xem, không sửa/hủy được).
- **Hồ sơ cá nhân**: sửa số điện thoại, ngày sinh, giới tính, địa chỉ, ảnh đại diện; đổi mật khẩu.
  (Email, mã NV, phòng ban, chức vụ do Trưởng phòng quản lý, nhân viên không tự sửa được.)

---

## 5. Ghi chú kỹ thuật

- Toàn bộ truy vấn dùng **PDO + prepared statements** để chống SQL Injection.
- Toàn bộ output dùng hàm `e()` (htmlspecialchars) để chống XSS.
- Mật khẩu lưu dạng băm `bcrypt` (`password_hash` / `password_verify`).
- Biểu đồ dùng thư viện **Chart.js** (tải qua CDN, cần internet khi mở trang).
- Icon dùng **Font Awesome 6** (tải qua CDN).
- Ảnh đại diện demo dùng dịch vụ `ui-avatars.com` khi nhân viên chưa có ảnh tải lên.
- Thư mục `uploads/` cần quyền ghi (trên Windows XAMPP mặc định đã có quyền ghi).

## 6. Có thể mở rộng thêm

- Thêm gửi email thông báo khi duyệt/từ chối yêu cầu hoặc tạo cuộc họp (dùng PHPMailer).
- Thêm xác thực CAPTCHA / giới hạn số lần đăng nhập sai cho bảo mật.
- Cho phép Trưởng phòng chọn thẳng nhân viên để "Đăng nhập thử" (impersonate) khi cần hỗ trợ.

Chúc bạn triển khai thành công! Nếu cần chỉnh sửa hoặc bổ sung chức năng, cứ yêu cầu thêm nhé.
