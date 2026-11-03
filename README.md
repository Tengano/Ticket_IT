# 🎫 IT Helpdesk — Hệ thống Ticket hỗ trợ IT

Ứng dụng tiếp nhận, phân loại, xử lý và theo dõi yêu cầu hỗ trợ IT nội bộ theo quy trình ITIL rút gọn. Người dùng gửi ticket qua web; kỹ thuật viên tiếp nhận, trao đổi, ghi chú nội bộ và cập nhật trạng thái; quản trị viên cấu hình vai trò, nhóm xử lý và danh mục nền.

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.3-06B6D4?logo=tailwindcss&logoColor=white)
![Tests](https://img.shields.io/badge/tests-83%20passed-brightgreen)

---

## 📌 Trạng thái dự án

| Sprint | Nội dung | Trạng thái |
|---|---|---|
| Sprint 0 | Khởi tạo project, layout, môi trường | ✅ Hoàn thành |
| Sprint 1 | Auth, vai trò/quyền, quản lý người dùng, danh mục nền | ✅ Hoàn thành |
| Sprint 2 | Ticket & vòng đời, bình luận, đính kèm, thông báo | ✅ Hoàn thành |
| Sprint 3–7 | Phân loại tự động, định tuyến, SLA, báo cáo, tài liệu | 🗺️ Theo kế hoạch |


---

## ✨ Tính năng đã có

### 🔐 Xác thực & phân quyền
- Đăng nhập, đăng ký, quên/đặt lại mật khẩu, xác nhận mật khẩu — giao diện tiếng Việt.
- 4 vai trò: **Người yêu cầu (requester)**, **Kỹ thuật viên (agent)**, **Trưởng nhóm IT (lead)**, **Quản trị (admin)**.
- Phân quyền theo permission + policy: xem/sửa/đóng/gán ticket, ghi chú nội bộ, tải tệp, quản trị người dùng/danh mục.
- Chặn tài khoản bị vô hiệu hoá (middleware + kiểm tra khi đăng nhập).
- Requester chỉ xem/đóng/mở lại ticket của mình; ghi chú nội bộ và tệp nội bộ chỉ dành cho staff.

### 🎫 Ticket & vòng đời
- Tạo ticket với tiêu đề, mô tả, danh mục 2 cấp, mức ảnh hưởng, độ khẩn cấp, phòng ban, vị trí, tệp đính kèm.
- Danh sách ticket có tìm kiếm, lọc theo trạng thái/ưu tiên/danh mục/người phụ trách, phân trang.
- Trang chi tiết: mô tả, tệp đính kèm, timeline hoạt động, bình luận công khai, **ghi chú nội bộ**, đổi trạng thái/ưu tiên, phân công, đóng/mở lại.
- Mã ticket tự sinh `TK-YYYY-NNNNNN`; ma trận **Impact × Urgency → P1–P4**.
- State machine 8 trạng thái: `new → assigned → in_progress → waiting_on_user/waiting_on_vendor → resolved → closed → reopened`, có guard chặn chuyển trạng thái sai.
- Ghi nhận `ticket_events` và `ticket_status_histories` cho mọi thay đổi; lưu mốc `first_responded_at`, `resolved_at`, `closed_at`, `handling_minutes`, `reopened_count`.

### 🗂️ Quản trị & danh mục nền
- CRUD người dùng, bật/tắt tài khoản, gán vai trò, phòng ban, vị trí, nhóm xử lý, kỹ năng.
- CRUD phòng ban, vị trí, nhóm xử lý (trưởng nhóm/thành viên/chiến lược phân công/định mức), kỹ năng.
- CRUD lịch làm việc: nhiều khung giờ/ngày, ngày lễ, lịch mặc định — nền tảng cho SLA ở Sprint 5.
- Seeder dữ liệu mẫu: 5 phòng ban, 5 vị trí, 5 nhóm, 9 kỹ năng, 8 danh mục cha + 22 danh mục con, 7 người dùng, 7 ticket demo.

### 🔔 Thông báo & trải nghiệm
- Chuông thông báo trong app (dropdown + trang quản lý, đánh dấu đã đọc) và email qua Mailpit SMTP.
- Dashboard theo vai trò: ticket đang mở, chưa phân công, được giao cho tôi, P1, ticket gần đây, hàng đợi chưa phân công.
- Layout sidebar + topbar + breadcrumb, responsive, Tailwind CSS 4 + Alpine.js.
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
| Kiểm thử | PHPUnit 12.5 |
| Code style | Laravel Pint 1.32 |
| Hàng đợi / cache / session | Driver `database` |
| Email dev | Mailpit (`127.0.0.1:1025`, UI `:8025`) |
| Hỗ trợ AI (tuỳ chọn) | Laravel Boost 2.10 (dev) |

---

## ⚙️ Yêu cầu hệ thống

| Thành phần | Yêu cầu tối thiểu |
|---|---|
| PHP | **≥ 8.3** (đã kiểm tra với 8.3.30), extension: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`, `curl` |
| Composer | 2.x |
| MySQL | **≥ 8.0** (đã kiểm tra với 8.4.3) |
| Node.js / npm | Node ≥ 18 (đã kiểm tra với Node 24) |
| Web server | Laragon (Apache/Nginx) hoặc `php artisan serve` |
| Email dev | Mailpit của Laragon (SMTP `127.0.0.1:1025`, UI `http://127.0.0.1:8025`) |

---

## 🚀 Cài đặt

### 1. Cài dependency

```bash
composer install
npm install
```

### 2. Tạo file môi trường

```bash
# Windows
copy .env.example .env

# macOS / Linux
cp .env.example .env

php artisan key:generate
```

Các biến quan trọng trong `.env`:

```dotenv
APP_NAME="IT Helpdesk"
APP_URL=http://ticketit.test
APP_LOCALE=vi
APP_TIMEZONE=Asia/Ho_Chi_Minh

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_it
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="helpdesk@ticketit.test"
```

> Nếu MySQL có mật khẩu, cập nhật `DB_PASSWORD`. Nếu không dùng MySQL, có thể chuyển `.env` sang SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=database/database.sqlite`).

### 3. Tạo database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ticket_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Hoặc tạo bằng HeidiSQL / phpMyAdmin với charset `utf8mb4`, collation `utf8mb4_unicode_ci`.

### 4. Chạy migration + dữ liệu mẫu

```bash
php artisan migrate:fresh --seed
```

Seeder sẽ tạo sẵn vai trò/quyền, phòng ban, vị trí, nhóm xử lý, kỹ năng, lịch làm việc, danh mục 2 cấp, tài khoản demo và ticket mẫu.

### 5. Build frontend & chạy ứng dụng

```bash
npm run build
php artisan serve
```

Mở `http://127.0.0.1:8000`. Khi phát triển giao diện, dùng `npm run dev` (hoặc `composer run dev`) để có HMR.

### 6. Chạy bằng Laragon (khuyến nghị)

1. Đặt project tại `K:\Apps\Laragon\www\TicketIT`.
2. Bật **Start All** (Apache + MySQL).
3. Vhost `auto.TicketIT.test.conf` phải trỏ:
   ```apache
   define ROOT "K:/Apps/laragon/www/TicketIT/public"
   ```
4. Apache phải dùng **PHP 8.3** (`K:\Apps\Laragon\etc\apache2\mod_php.conf` trỏ tới `php8apache2_4.dll` của PHP 8.3.30).
5. Mở `http://ticketit.test`.

> **Xử lý sự cố thường gặp**
> - Trang `Index of /`: DocumentRoot đang trỏ thư mục gốc → sửa về `.../TicketIT/public` (hoặc file `.htaccess` ở root sẽ tự rewrite vào `public/`).
> - Lỗi `500` kèm `Composer detected issues... running 7.4.x`: Apache đang dùng PHP 7.4 → chuyển sang PHP 8.3 rồi restart Apache.
> - Không nhận email: bật Mailpit trong Laragon và kiểm tra `MAIL_PORT=1025`.

---

## 🔑 Tài khoản demo

Mật khẩu chung: **`password`**

| Vai trò | Email | Ghi chú |
|---|---|---|
| Quản trị (admin) | `admin@ticketit.test` | Toàn quyền hệ thống |
| Trưởng nhóm IT (lead) | `lead@ticketit.test` | Trưởng nhóm Hỗ trợ chung, có `report.view` |
| Kỹ thuật viên (agent) | `agent1@ticketit.test` | Nhóm Hỗ trợ chung + Hạ tầng – Mạng |
| Kỹ thuật viên (agent) | `agent2@ticketit.test` | Nhóm Thiết bị + Ứng dụng – ERP |
| Người yêu cầu | `requester@ticketit.test` | Phòng HCNS — Phòng 305 |
| Người yêu cầu | `requester2@ticketit.test` | Phòng Kế toán — Phòng 201 |
| Người yêu cầu VIP | `director@ticketit.test` | Ban Giám hiệu, `is_vip = true` |

> Tài khoản tạo qua trang **Đăng ký** mặc định nhận vai trò `requester`.

---

## 🧪 Kiểm thử & chất lượng mã nguồn

```bash
# Toàn bộ test
php artisan test

# Chạy một nhóm test
php artisan test --filter=TicketLifecycleTest
php artisan test tests/Feature/Ticket

# Format code
vendor/bin/pint --dirty

# Build frontend
npm run build
```

- **83 test / 281 assertions** bao phủ: đăng nhập 4 vai trò, phân quyền, quản lý người dùng, CRUD danh mục, tạo ticket, ma trận ưu tiên, vòng đời trạng thái, đóng/mở lại, ghi chú nội bộ, đính kèm, thông báo và bảo mật tệp.
- PHPUnit dùng **SQLite `:memory:`** theo `phpunit.xml` nên chạy nhanh và độc lập với MySQL.
- Test nằm trong `tests/Feature` (Auth, Admin, Ticket, AccessControl) và `tests/Unit` (state machine, priority matrix).

---

## 🗂️ Cấu trúc chính

```text
app/
├── Enums/                     # TicketStatus, TicketPriority, Impact, Urgency, CommentType, TicketEventType
├── Http/
│   ├── Controllers/           # Dashboard, Ticket, TicketComment, TicketAttachment, Notification, Profile
│   │   ├── Admin/             # User, Department, Location, Team, Skill, BusinessCalendar
│   │   └── Auth/              # Breeze controllers
│   ├── Middleware/            # EnsureUserIsActive
│   └── Requests/              # StoreTicketRequest, StoreTicketCommentRequest, Admin/*
├── Models/                    # Ticket, TicketComment, TicketAttachment, TicketEvent, User, Team, Category...
├── Notifications/             # TicketNotification (database + mail)
├── Policies/                  # TicketPolicy, TicketAttachmentPolicy
├── Services/Tickets/          # TicketService, TicketNotifier, TicketAttachmentService
└── Providers/

database/
├── migrations/                # users, organization, teams, categories, tickets, permissions, notifications
├── factories/
└── seeders/                   # RolePermission, Organization, Category, DemoUser, DemoTicket

resources/views/
├── layouts/                   # app (sidebar/topbar), guest
├── components/                # page-header, status-badge, priority-badge, stat-card, icon...
├── tickets/                   # index, create, show
├── admin/                     # users, departments, locations, teams, skills, calendars
├── notifications/
├── auth/ · profile/ · dashboard.blade.php

lang/vi/                       # auth, passwords, validation, pagination
tests/Feature/ · tests/Unit/
```

---

## 📐 Quy tắc nghiệp vụ

**Mã ticket:** `TK-YYYY-NNNNNN` (ví dụ `TK-2026-000001`), tăng dần theo năm.

**Ma trận Impact × Urgency:**

| Impact \ Urgency | Thấp | Trung bình | Cao |
|---|---|---|---|
| **Cao** | P3 | P2 | **P1** |
| **Trung bình** | P4 | P3 | P2 |
| **Thấp** | P4 | P4 | P3 |

**Vòng đời trạng thái:**

```text
new → assigned → in_progress ↔ waiting_on_user / waiting_on_vendor
                         ↓
                     resolved → closed → reopened → in_progress...
```

**Phân quyền chính:**

| Hành động | Requester | Agent / Lead | Admin |
|---|---|---|---|
| Xem ticket | Chỉ ticket của mình | Tất cả | Tất cả |
| Tạo ticket | ✅ | ✅ | ✅ |
| Đổi trạng thái / phân công | ❌ | ✅ | ✅ |
| Ghi chú nội bộ | ❌ | ✅ | ✅ |
| Đóng / mở lại | Ticket của mình (mở lại khi đã resolved/closed) | Tất cả | Tất cả |
| Quản lý người dùng / danh mục | ❌ | ❌ | ✅ |

**Tệp đính kèm:** tối đa 5 tệp/ticket, mỗi tệp ≤ 5 MB; hỗ trợ ảnh, PDF, Word, Excel, PowerPoint, text, CSV, ZIP/RAR. Tệp của ghi chú nội bộ chỉ staff được tải.

**Thông báo:** gửi khi tạo ticket, phân công, đổi trạng thái, có bình luận/ghi chú nội bộ hoặc tệp mới; hiển thị trong app và gửi email qua Mailpit.

---

## 🗺️ Lộ trình tiếp theo

| Sprint | Nội dung |
|---|---|
| Sprint 3 | Phân loại tự động: Rule + Naive Bayes, tiền xử lý tiếng Việt, hàng đợi triage, đánh giá accuracy/F1 |
| Sprint 4 | Định tuyến & phân công: rule danh mục → nhóm, 3 chiến lược gán, claim, leo thang |
| Sprint 5 | SLA engine: giờ làm việc, tạm dừng, cảnh báo 80%, vi phạm, KPI FRT/MTTR/P90 |
| Sprint 6 | Dashboard báo cáo, xuất Excel/PDF, Knowledge Base, CSAT, audit log, bảo mật |
| Sprint 7 | ERD/Use Case, tài liệu thuật toán, số liệu thực nghiệm, demo, slide bảo vệ |

Các cột phục vụ Sprint 3–5 đã có sẵn trên bảng `tickets`: `ai_confidence`, `ai_category_id`, `needs_triage`, `first_responded_at`, `resolved_at`, `closed_at`, `handling_minutes`, `waiting_minutes`, `paused_minutes`, `reopened_count`.

---

## 📚 Tài liệu nội bộ

| File | Nội dung |
|---|---|
| `KE_HOACH_HE_THONG_TICKET_IT.md` | Kế hoạch tổng thể, kiến trúc, 3 trụ kỹ thuật, KPI |
| `PHU_LUC_MA_NGUON_TICKET_IT.md` | Mã nguồn mẫu: classification, routing, SLA, BusinessTimeCalculator |
| `TODO_TICKET_IT.md` | Danh sách công việc theo Sprint + ghi chú triển khai |

> Các file tài liệu này đang được `.gitignore` (chỉ lưu cục bộ), không ảnh hưởng đến ứng dụng.

---

## ⚠️ Giới hạn hiện tại

- Chưa có **SLA engine** và bộ lọc SLA state (Sprint 5).
- Chưa có **phân loại tự động** — người dùng chọn danh mục thủ công (Sprint 3).
- Định tuyến mới ở mức gán nhóm mặc định theo danh mục và phân công thủ công (Sprint 4).
- Thông báo gửi đồng bộ; nếu Mailpit chưa bật, email được ghi log và không chặn thao tác, thông báo trong app vẫn hoạt động.
- Chưa có Knowledge Base, CSAT, báo cáo/xuất Excel-PDF và audit log giao diện (Sprint 6).

---

## 📄 Mục đích sử dụng

Dự án phục vụ học tập/đồ án và demo quy trình IT Helpdesk nội bộ. Framework Laravel được phát hành theo giấy phép MIT; mã nguồn ứng dụng có thể điều chỉnh cho mục đích học tập.
