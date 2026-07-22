-- =========================================================
-- HRM SYSTEM - DATABASE SCHEMA
-- Import file này bằng phpMyAdmin (XAMPP) hoặc lệnh:
--   mysql -u root -p < database.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS hrm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrm_system;

SET NAMES utf8mb4;

-- ---------------------------------------------------------
-- Bảng phòng ban
-- ---------------------------------------------------------
CREATE TABLE phong_ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phong_ban VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO phong_ban (ten_phong_ban) VALUES
('Phòng Kinh doanh'),
('Phòng Nhân sự'),
('Phòng IT'),
('Phòng Kế toán');

-- ---------------------------------------------------------
-- Bảng nhân viên (kể cả tài khoản Trưởng phòng để đăng nhập)
-- ---------------------------------------------------------
CREATE TABLE nhan_vien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_nv VARCHAR(20) NOT NULL UNIQUE,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    sdt VARCHAR(20) DEFAULT NULL,
    chuc_vu ENUM('Nhân viên','Trưởng phòng') NOT NULL DEFAULT 'Nhân viên',
    gioi_tinh ENUM('Nam','Nữ','Khác') DEFAULT 'Nam',
    ngay_sinh DATE DEFAULT NULL,
    ngay_vao_lam DATE DEFAULT NULL,
    dia_chi TEXT,
    phong_ban_id INT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    trang_thai ENUM('Hoạt động','Nghỉ việc') NOT NULL DEFAULT 'Hoạt động',
    cccd VARCHAR(20) DEFAULT NULL,
    ngay_cap DATE DEFAULT NULL,
    noi_cap VARCHAR(150) DEFAULT NULL,
    tinh_trang_hon_nhan VARCHAR(50) DEFAULT NULL,
    quoc_tich VARCHAR(50) DEFAULT 'Việt Nam',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phong_ban_id) REFERENCES phong_ban(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Mật khẩu mẫu cho tất cả tài khoản: 123456
-- Hash tạo bằng password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO nhan_vien
(ma_nv, ho_ten, email, mat_khau, sdt, chuc_vu, gioi_tinh, ngay_sinh, ngay_vao_lam, dia_chi, phong_ban_id, trang_thai, cccd, ngay_cap, noi_cap, tinh_trang_hon_nhan, quoc_tich, ghi_chu)
VALUES
('QL001','Nguyễn Văn A','nguyenvana@company.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0901234567','Trưởng phòng','Nam','1990-06-12','2022-03-15','123 Đường ABC, Quận 1, TP. Hồ Chí Minh',1,'Hoạt động','079990123456','2015-05-20','Cục CSQLHC về TTXH','Đã kết hôn','Việt Nam','Trưởng phòng Kinh doanh khu vực phía Nam.'),
('NV001','Trần Thị B','tranthib@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0901234567','Nhân viên','Nữ','1996-02-10','2024-06-10',NULL,1,'Hoạt động',NULL,NULL,NULL,NULL,'Việt Nam',NULL),
('NV002','Lê Văn C','levanc@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0902345678','Nhân viên','Nam','1995-05-18','2024-06-08',NULL,2,'Hoạt động',NULL,NULL,NULL,NULL,'Việt Nam',NULL),
('NV003','Phạm Văn D','phamvand@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0903456789','Nhân viên','Nam','1994-11-02','2024-06-05',NULL,3,'Hoạt động',NULL,NULL,NULL,NULL,'Việt Nam',NULL),
('NV004','Hoàng Thị E','hoangthie@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0904567890','Nhân viên','Nữ','1997-01-25','2023-09-01',NULL,1,'Hoạt động',NULL,NULL,NULL,NULL,'Việt Nam',NULL),
('NV005','Nguyễn Văn F','nguyenvanf@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0905678901','Nhân viên','Nam','1993-08-14','2022-01-10',NULL,4,'Nghỉ việc',NULL,NULL,NULL,NULL,'Việt Nam',NULL),
('NV006','Đỗ Thị G','dothig@example.com','$2y$10$wTsTlj2yksoOV3R0kkPyBecrxFWzPa76a/blVbbswtOU4kU4y89ra','0906780012','Nhân viên','Nữ','1998-03-30','2023-05-15',NULL,2,'Hoạt động',NULL,NULL,NULL,NULL,'Việt Nam',NULL);

-- ---------------------------------------------------------
-- Bảng phòng họp
-- ---------------------------------------------------------
CREATE TABLE phong_hop (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phong VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO phong_hop (ten_phong) VALUES ('Phòng họp A'),('Phòng họp B'),('Phòng họp C');

-- ---------------------------------------------------------
-- Bảng cuộc họp
-- ---------------------------------------------------------
CREATE TABLE cuoc_hop (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tieu_de VARCHAR(255) NOT NULL,
    noi_dung TEXT,
    phong_hop_id INT DEFAULT NULL,
    thoi_gian_bat_dau DATETIME NOT NULL,
    thoi_gian_ket_thuc DATETIME NOT NULL,
    nhac_nho VARCHAR(50) DEFAULT NULL,
    ghi_chu TEXT,
    quyen_truy_cap VARCHAR(50) DEFAULT 'Chỉ thành viên được mời',
    tep_dinh_kem VARCHAR(255) DEFAULT NULL,
    nguoi_tao_id INT DEFAULT NULL,
    trang_thai ENUM('Sắp diễn ra','Đã diễn ra','Đã hủy') NOT NULL DEFAULT 'Sắp diễn ra',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phong_hop_id) REFERENCES phong_hop(id) ON DELETE SET NULL,
    FOREIGN KEY (nguoi_tao_id) REFERENCES nhan_vien(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cuoc_hop_thanh_vien (
    cuoc_hop_id INT NOT NULL,
    nhan_vien_id INT NOT NULL,
    PRIMARY KEY (cuoc_hop_id, nhan_vien_id),
    FOREIGN KEY (cuoc_hop_id) REFERENCES cuoc_hop(id) ON DELETE CASCADE,
    FOREIGN KEY (nhan_vien_id) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dữ liệu mẫu cuộc họp (tháng hiện tại sẽ do người dùng tự tạo qua giao diện)
INSERT INTO cuoc_hop (tieu_de, noi_dung, phong_hop_id, thoi_gian_bat_dau, thoi_gian_ket_thuc, nguoi_tao_id, trang_thai) VALUES
('Họp phòng ban','Họp phòng ban định kỳ đầu tháng',1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 1, 'Sắp diễn ra'),
('Họp triển khai dự án','Triển khai dự án mới cho quý này',2, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 9 HOUR, 1, 'Sắp diễn ra'),
('Họp với khách hàng ABC','Trao đổi hợp đồng với khách hàng ABC',1, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 13 HOUR + INTERVAL 30 MINUTE, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 15 HOUR, 1, 'Sắp diễn ra'),
('Họp đối tác','Họp bàn kế hoạch hợp tác',3, DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 13 HOUR + INTERVAL 30 MINUTE, DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 15 HOUR, 1, 'Sắp diễn ra');

INSERT INTO cuoc_hop_thanh_vien (cuoc_hop_id, nhan_vien_id) VALUES
(1,2),(1,3),(1,4),(2,2),(2,3),(3,4),(3,5),(4,6);

-- ---------------------------------------------------------
-- Bảng yêu cầu của nhân viên
-- ---------------------------------------------------------
CREATE TABLE yeu_cau (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_yc VARCHAR(20) NOT NULL UNIQUE,
    loai_yeu_cau VARCHAR(150) NOT NULL,
    noi_dung TEXT,
    muc_do ENUM('Cao','Trung bình','Thấp') NOT NULL DEFAULT 'Trung bình',
    nguoi_tao_id INT DEFAULT NULL,
    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
    trang_thai ENUM('Chờ xử lý','Đã phê duyệt','Từ chối') NOT NULL DEFAULT 'Chờ xử lý',
    ghi_chu_xu_ly TEXT,
    FOREIGN KEY (nguoi_tao_id) REFERENCES nhan_vien(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Ghi chú: dùng NOW() - INTERVAL để dữ liệu mẫu luôn rơi vào "tháng hiện tại"
-- bất kể ngày bạn import file này, giúp các bộ lọc mặc định (tháng hiện tại)
-- trên trang Thống kê / Yêu cầu của nhân viên luôn hiển thị đúng dữ liệu demo.
INSERT INTO yeu_cau (ma_yc, loai_yeu_cau, noi_dung, muc_do, nguoi_tao_id, thoi_gian, trang_thai) VALUES
('PB16','Duyệt yêu cầu đặt phòng','Là trưởng phòng, tôi muốn phê duyệt yêu cầu của nhân viên.','Cao',2, NOW() - INTERVAL 1 DAY,'Chờ xử lý'),
('PB17','Từ chối yêu cầu','Là trưởng phòng, tôi muốn từ chối yêu cầu nghỉ lý do.','Cao',3, NOW() - INTERVAL 2 DAY,'Chờ xử lý'),
('PB18','Xem danh sách yêu cầu','Là trưởng phòng, tôi muốn xem tất cả yêu cầu đang chờ.','Cao',4, NOW() - INTERVAL 3 DAY,'Đã phê duyệt'),
('PB19','Xem lịch phòng ban','Là trưởng phòng, tôi muốn xem lịch họp của phòng ban.','Cao',5, NOW() - INTERVAL 4 DAY,'Đã phê duyệt'),
('PB20','Hủy cuộc họp đã duyệt','Là trưởng phòng, tôi muốn hủy cuộc họp khi cần thiết.','Trung bình',6, NOW() - INTERVAL 5 DAY,'Đã phê duyệt'),
('PB21','Thống kê cuộc họp phòng ban','Là trưởng phòng, tôi muốn xem số lượng cuộc họp của phòng.','Trung bình',7, NOW() - INTERVAL 6 DAY,'Đã phê duyệt');

-- ---------------------------------------------------------
-- Bảng nhật ký hoạt động (cho tab Nhật ký hoạt động của hồ sơ)
-- ---------------------------------------------------------
CREATE TABLE nhat_ky_hoat_dong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nhan_vien_id INT NOT NULL,
    noi_dung VARCHAR(255) NOT NULL,
    thoi_gian TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nhan_vien_id) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO nhat_ky_hoat_dong (nhan_vien_id, noi_dung) VALUES
(1,'Đăng nhập vào hệ thống'),
(1,'Cập nhật thông tin cá nhân');
