# 🎫 Hệ thống hỗ trợ IT (TicketIT)

Ứng dụng tiếp nhận, phân loại, xử lý và theo dõi yêu cầu hỗ trợ IT nội bộ.
Người dùng gửi **phiếu yêu cầu** qua web; kỹ thuật viên tiếp nhận, trao đổi, ghi chú nội bộ và cập nhật trạng thái;
quản trị viên cấu hình vai trò, nhóm xử lý và danh mục nền.

> 📄 **Báo cáo đồ án chi tiết:** [BaoCao.md](BaoCao.md)

> Toàn bộ mã nguồn, tên bảng/cột cơ sở dữ liệu và tên tệp đã được Việt hoá để dễ đọc, dễ bảo vệ đồ án.
> Các thành phần hạ tầng của framework (bảng `sessions`, `cache`, `jobs`, `notifications`, bảng phân quyền của Spatie,
> route xác thực `login`/`register`...) được giữ nguyên tên gốc.

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.3-06B6D4?logo=tailwindcss&logoColor=white)

---

## ✨ Tính năng

### 🔐 Xác thực & phân quyền
- Đăng nhập, đăng ký, quên/đặt lại mật khẩu, xác nhận mật khẩu — giao diện tiếng Việt.
- 4 vai trò: **Người yêu cầu (requester)**, **Kỹ thuật viên (agent)**, **Trưởng nhóm IT (lead)**, **Quản trị (admin)**.
- Phân quyền theo quyền + policy: xem/sửa/đóng/phân công phiếu, ghi chú nội bộ, tải tệp, quản trị người dùng/danh mục.
- Chặn tài khoản bị vô hiệu hoá (middleware + kiểm tra khi đăng nhập).

### 🎫 Phiếu yêu cầu & vòng đời
- Tạo phiếu với tiêu đề, mô tả, danh mục 2 cấp, mức ảnh hưởng, độ khẩn cấp, phòng ban, vị trí, tệp đính kèm.
- Danh sách phiếu có tìm kiếm, lọc theo trạng thái/ưu tiên/danh mục/nhóm/người phụ trách, phân trang.
- Trang chi tiết: mô tả, tệp đính kèm, dòng thời gian hoạt động, bình luận công khai, **ghi chú nội bộ**,
  đổi trạng thái/ưu tiên, phân công, đóng/mở lại.
- Mã phiếu tự sinh `TK-YYYY-NNNNNN`; mức ưu tiên tính tự động theo ma trận **Ảnh hưởng × Khẩn cấp → P1–P4**.
- Máy trạng thái 8 bước: `moi → da_phan_cong → dang_xu_ly → cho_nguoi_dung / cho_nha_cung_cap → da_giai_quyet → da_dong → mo_lai`.
- Ghi nhận `su_kien_phieu` và `lich_su_trang_thai` cho mọi thay đổi.

### 🗂️ Quản trị & danh mục nền
- CRUD người dùng, bật/tắt tài khoản, gán vai trò, phòng ban, vị trí, nhóm xử lý.
- CRUD phòng ban, vị trí, nhóm xử lý (trưởng nhóm/thành viên/chiến lược phân công/định mức).
- Dữ liệu mẫu: 5 phòng ban, 5 vị trí, 5 nhóm xử lý, 8 danh mục cha + 22 danh mục con, 7 người dùng, 7 phiếu demo.

### 🔔 Thông báo & trải nghiệm
- Chuông thông báo trong ứng dụng (dropdown + trang quản lý, đánh dấu đã đọc) và email qua Mailpit SMTP.
- Tổng quan theo vai trò: phiếu đang mở, chưa phân công, được giao cho tôi, P1, phiếu gần đây, hàng đợi chưa phân công.
- Sidebar + topbar + breadcrumb, responsive, Tailwind CSS 4 + Alpine.js.
- Hồ sơ cá nhân: cập nhật thông tin, đổi mật khẩu, xoá tài khoản có xác nhận.

---

## 🧱 Công nghệ sử dụng

| Thành phần | Phiên bản / ghi chú |
|---|---|
| Framework | Laravel 13.36 (PHP 8.3) |
| Cơ sở dữ liệu | MySQL 8.4 (`ticket_it`, `utf8mb4_unicode_ci`) |
| Giao diện | Blade, Tailwind CSS 4.3, Alpine.js 3.17, Vite 8 |
| Xác thực | Laravel Breeze 2.4 (Blade) |
| Phân quyền | spatie/laravel-permission 8.3 |
| Code style | Laravel Pint |
| Hàng đợi / cache / phiên | Driver `database` |
| Email dev | Mailpit (`127.0.0.1:1025`, UI `:8025`) |


---

## 🗄️ Cấu trúc cơ sở dữ liệu (tiếng Việt)

| Bảng | Cột chính | Ý nghĩa |
|---|---|---|
| `nguoi_dung` | ten, email, dien_thoai, password, phong_ban_id, vi_tri_id, dang_hoat_dong | Tài khoản người dùng |
| `phong_ban` | ma, ten, dang_hoat_dong | Danh mục phòng ban |
| `vi_tri` | ten, dang_hoat_dong | Vị trí / phòng làm việc |
| `nhom_xu_ly` | ma, ten, truong_nhom_id, chien_luoc_phan_cong, dinh_muc | Nhóm kỹ thuật viên |
| `nhom_xu_ly_nguoi_dung` | nhom_xu_ly_id, nguoi_dung_id, la_truong_nhom | Thành viên nhóm |
| `danh_muc` | danh_muc_cha_id, ma, ten, nhom_mac_dinh_id, uu_tien_mac_dinh | Danh mục sự cố 2 cấp |
| `phieu_yeu_cau` | ma, tieu_de, mo_ta, nguoi_yeu_cau_id, trang_thai, uu_tien, anh_huong, khan_cap | Phiếu yêu cầu hỗ trợ |
| `binh_luan_phieu` | phieu_yeu_cau_id, nguoi_dung_id, noi_dung, loai, la_phan_hoi_dau | Bình luận / ghi chú nội bộ |
| `tep_dinh_kem` | phieu_yeu_cau_id, binh_luan_id, nguoi_tai_len_id, duong_dan, ten_goc, kich_thuoc | Tệp đính kèm |
| `su_kien_phieu` | phieu_yeu_cau_id, nguoi_thuc_hien_id, loai, tu_gia_tri, den_gia_tri, du_lieu_them | Nhật ký hoạt động |
| `lich_su_trang_thai` | phieu_yeu_cau_id, trang_thai_truoc, trang_thai_sau, bat_dau_luc, ket_thuc_luc | Lịch sử trạng thái |
| `nguoi_theo_doi` | phieu_yeu_cau_id, nguoi_dung_id | Người theo dõi phiếu |

Giá trị trạng thái lưu trong CSDL cũng là tiếng Việt không dấu:
`moi`, `da_phan_cong`, `dang_xu_ly`, `cho_nguoi_dung`, `cho_nha_cung_cap`, `da_giai_quyet`, `da_dong`, `mo_lai`;
loại bình luận: `cong_khai`, `noi_bo`.

---

## 📁 Cấu trúc mã nguồn

```
app/
├── Enums/              TrangThaiPhieu, UuTienPhieu, MucAnhHuong, MucKhanCap, LoaiBinhLuan, LoaiSuKienPhieu
├── Http/
│   ├── Controllers/    PhieuYeuCauController, BinhLuanPhieuController, TepDinhKemPhieuController,
│   │                   ThongBaoController, TongQuanController, HoSoController
│   │   ├── Admin/      NguoiDungController, PhongBanController, ViTriController, NhomXuLyController
│   │   └── Auth/       PhienDangNhapController, DangKyController, DatLaiMatKhauController, ...
│   ├── Middleware/     EnsureUserIsActive
│   └── Requests/       LuuPhieuYeuCauRequest, CapNhatPhieuYeuCauRequest, LuuBinhLuanRequest, ...
├── Models/             NguoiDung, PhongBan, ViTri, NhomXuLy, DanhMuc, PhieuYeuCau,
│                       BinhLuanPhieu, TepDinhKem, SuKienPhieu, LichSuTrangThai, NguoiTheoDoi
├── Notifications/      ThongBaoPhieu
├── Policies/           PhieuYeuCauPolicy, TepDinhKemPolicy
└── Services/Phieu/     DichVuPhieuYeuCau, DichVuTepDinhKem, DichVuThongBaoPhieu

resources/views/
├── phieu-yeu-cau/      index, create, show
├── quan-tri/           nguoi-dung, phong-ban, vi-tri, nhom-xu-ly
├── ho-so/              edit + partials
├── thong-bao/          index
├── tong-quan.blade.php
├── auth/               dang-nhap, dang-ky, quen-mat-khau, dat-lai-mat-khau, xac-nhan-mat-khau, xac-thuc-email
└── layouts/            app, guest

database/migrations/    tao_bang_nguoi_dung, tao_bang_to_chuc, tao_bang_nhom_xu_ly,
                        tao_bang_danh_muc, tao_bang_phieu_yeu_cau
```

Tên hàm, biến và quan hệ trong Eloquent vẫn giữ tiếng Anh (`$phieu->requester`, `statusHistories()`...)
theo quy ước của Laravel; phần hiển thị và dữ liệu đều là tiếng Việt.


---

## ⚙️ Yêu cầu hệ thống

| Thành phần | Yêu cầu tối thiểu |
|---|---|
| PHP | **≥ 8.3**, extension: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `zip`, `curl` |
| Composer | 2.x |
| MySQL | **≥ 8.0** |
| Node.js / npm | Node ≥ 18 |
| Web server | Laragon (Apache/Nginx) hoặc `php artisan serve` |
| Email dev | Mailpit của Laragon |

---

## 🚀 Cài đặt

```bash
composer install
npm install
copy .env.example .env      # macOS/Linux: cp .env.example .env
php artisan key:generate
```

Cấu hình `.env` quan trọng:

```dotenv
APP_NAME="Hệ thống hỗ trợ IT"
APP_URL=http://ticketit.test
APP_LOCALE=vi
APP_TIMEZONE=Asia/Ho_Chi_Minh

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_it
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

Tạo database và nạp dữ liệu mẫu:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ticket_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Mở `http://127.0.0.1:8000` (hoặc `http://ticketit.test` nếu dùng Laragon với DocumentRoot trỏ `.../public`).

---

## 🔑 Tài khoản demo

Mật khẩu chung: **`11111111`**

| Vai trò | Email | Ghi chú |
|---|---|---|
| Quản trị (admin) | `admin@ticket.it` | Toàn quyền hệ thống |
| Trưởng nhóm IT (lead) | `lead@ticket.it` | Trưởng nhóm Hỗ trợ chung |
| Kỹ thuật viên (agent) | `agent1@ticket.it` | Nhóm Hỗ trợ chung + Hạ tầng – Mạng |
| Kỹ thuật viên (agent) | `agent2@ticket.it` | Nhóm Thiết bị + Ứng dụng – ERP |
| Người yêu cầu | `requester@ticket.it` | Phòng HCNS — Phòng 305 |
| Người yêu cầu | `requester2@ticket.it` | Phòng Kế toán — Phòng 201 |
| Người yêu cầu | `director@ticket.it` | Ban Giám hiệu |

Tài khoản tạo qua trang **Đăng ký** mặc định nhận vai trò `requester`.

---

## 🧹 Chất lượng mã nguồn

```bash
vendor/bin/pint           # định dạng mã nguồn theo chuẩn Laravel
php artisan route:list    # danh sách route tiếng Việt
```

---

## 🗺️ Hướng phát triển

- Phân loại tự động danh mục/ưu tiên từ nội dung phiếu.
- Định tuyến tự động theo nhóm – tải công việc.
- SLA theo giờ hành chính (tạm dừng khi chờ người dùng) và báo cáo tuân thủ.
- Xuất báo cáo Excel/PDF và đánh giá hài lòng người dùng.
