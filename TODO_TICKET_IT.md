# Danh sách công việc (To-Do) — Hệ thống Ticket hỗ trợ IT

> Trạng thái: ⬜ chưa làm · 🔄 đang làm · ✅ xong · ⏸️ tạm hoãn
> Kế hoạch chi tiết: `KE_HOACH_HE_THONG_TICKET_IT.md` · Phụ lục mã nguồn: `PHU_LUC_MA_NGUON_TICKET_IT.md`

## 🏁 Sprint 0 — Khởi tạo (1 tuần)

- [x] ⬜ Mở Laragon → **Start All** (Apache/Nginx + MySQL 8.4)
- [ ] ⬜ `cd K:\Apps\Laragon\www && composer create-project laravel/laravel ticket-it`
- [ ] ⬜ Mở project trong VS Code tại `K:\Apps\Laragon\www\ticket-it`
- [ ] ⬜ Tạo DB `ticket_it` (HeidiSQL) + cấu hình `.env` (MySQL, utf8mb4, queue/cache = database)
- [ ] ⬜ `php artisan serve` hoặc `http://ticket-it.test` (Laragon auto virtual host) → chạy được trang mặc định
- [ ] ⬜ `git init` + commit khung dự án
- [ ] ⬜ Cài package: Breeze (blade), spatie/laravel-permission 8.3, spatie/laravel-activitylog 4.12.3, maatwebsite/excel 4.0.4, barryvdh/laravel-dompdf 3.1, laravel/pint (dev)
- [ ] ⬜ Cài npm: tailwindcss 4 + @tailwindcss/vite, alpinejs, chart.js, sweetalert2, flatpickr
- [ ] ⬜ Layout khung: sidebar + topbar + breadcrumb (Blade component) + trang dashboard rỗng
- [ ] ⬜ `.env`: `APP_NAME="IT Helpdesk"`, `APP_LOCALE=vi`, `APP_TIMEZONE=Asia/Ho_Chi_Minh`, `QUEUE_CONNECTION=database`

## 🔐 Sprint 1 — Auth & phân quyền (2 tuần)

- [ ] ⬜ Breeze: đăng nhập/đăng ký/quên mật khẩu + giao diện tiếng Việt
- [ ] ⬜ Seeder role/permission: requester, agent, lead, admin + permission (`ticket.view.own`, `ticket.assign`, `report.view`, `admin.manage`…)
- [ ] ⬜ Middleware + `TicketPolicy` (xem/sửa/đóng/gán), chặn truy cập trái phép
- [ ] ⬜ Quản lý người dùng (Admin): CRUD, bật/tắt tài khoản, gán vai trò, gán nhóm/kỹ năng
- [ ] ⬜ Danh mục nền: departments, locations, teams, skills, business_calendars/hours/holidays (CRUD + seeder)
- [ ] ⬜ Test: đăng nhập 4 vai trò, kiểm tra quyền (Feature test)

## 🎫 Sprint 2 — Ticket & vòng đời (2 tuần)

- [ ] ⬜ Migration + Model: tickets, ticket_comments, ticket_attachments, ticket_events, ticket_status_histories, ticket_watchers
- [ ] ⬜ Form tạo ticket (subject/description/danh mục/mức ảnh hưởng/độ khẩn cấp/đính kèm) + validate (`StoreTicketRequest`)
- [ ] ⬜ Danh sách ticket có bộ lọc (trạng thái, ưu tiên, danh mục, người phụ trách, SLA state) + phân trang
- [ ] ⬜ Chi tiết ticket: timeline, bình luận công khai, **ghi chú nội bộ**, đổi trạng thái, đóng/mở lại, đính kèm
- [ ] ⬜ Ma trận Impact × Urgency → priority (P1–P4); sinh mã ticket `TK-2026-000123`
- [ ] ⬜ State machine (Enum + guard chuyển trạng thái), ghi `ticket_events` cho mọi thay đổi
- [ ] ⬜ Thông báo: chuông trong app + email (Mailpit) khi có phản hồi/đổi trạng thái
- [ ] ⬜ Test: tạo/đóng/mở lại ticket, chặn requester xem ticket của người khác

## 🤖 Sprint 3 — TRỤ 1: Phân loại tự động (2 tuần)

- [ ] ⬜ Migration + Model: categories (2 cấp), classification_rules, classifier_models/terms/classes, ticket_classifications, classifier_evaluations, text_aliases
- [ ] ⬜ Seeder: ~8 danh mục cha + 20 danh mục con (Tài khoản/Mật khẩu, Mạng–Wifi, Máy in, Phần cứng, Email, Phần mềm–ERP, Hệ thống học vụ, Bảo mật…)
- [ ] ⬜ `TextNormalizer` + `Tokenizer`: lowercase, bỏ HTML, **bỏ dấu tiếng Việt** (bắt cả "mat khau" và "mật khẩu"), stopwords, unigram + **bigram**, alias viết tắt
- [ ] ⬜ `RuleClassifier`: khớp từ khóa/regex có trọng số → điểm theo danh mục + **danh sách từ khóa khớp** (giải thích được cho người dùng)
- [ ] ⬜ `NaiveBayesClassifier`: `train()` + `predict()` (Laplace α=1), lưu model có `model_version`, tính log-prob ngoài request (job)
- [ ] ⬜ `ClassificationService`: hợp nhất điểm (wA=0.55 rule, wB=0.45 NB, LLM tùy chọn), ngưỡng τ = 0.65, trả `confidence` + `explanation`, ghi `ticket_classifications`
- [ ] ⬜ Hàng đợi **Triage**: ticket confidence thấp → Agent chọn từ top-3 gợi ý → lưu nhãn vàng → job retrain
- [ ] ⬜ Artisan: `classifier:train`, `classifier:evaluate --holdout=0.2`, `classifier:prune`, `classifier:sample` (sinh dữ liệu)
- [ ] ⬜ Dữ liệu train: seeder 300–400 ticket lịch sử có nhãn (trải 90 ngày) + `database/data/test_tickets.csv` (100 ticket nhãn tay, KHÔNG dùng để train)
- [ ] ⬜ Chạy đánh giá: accuracy / precision / recall / F1 / **confusion matrix** → `storage/app/reports/classifier_v{n}.md`
- [ ] ⬜ Bảng so sánh Rule-only vs NB-only vs Hybrid theo τ = 0.5/0.6/0.65/0.8 + theo số mẫu train (50/100/200/300)
- [ ] ⬜ UI: "Danh mục hệ thống đề xuất (82% – khớp: mật khẩu, không đăng nhập) [Đúng] [Sửa]"
- [ ] ⬜ Test: accuracy ≥ 80% trên tập test; unit test Tokenizer + NB với dữ liệu nhỏ đã biết trước kết quả

## 🧭 Sprint 4 — TRỤ 2: Định tuyến & phân công (2 tuần)

- [ ] ⬜ Migration: team_route_rules (category → team), user_skills (level 1–5), team_user (is_lead, capacity), agent_zones
- [ ] ⬜ `RoutingService`: danh mục → nhóm; P1 → leo thang Lead/on-call; khu vực → agent phụ trách; VIP → hàng đợi senior
- [ ] ⬜ 3 chiến lược gán (interface + lớp): `RoundRobinStrategy`, `LeastOpenTicketsStrategy`, `SkillWeightedStrategy` — điểm = 0.5·kỹ năng + 0.3·(1−tải) + 0.2·khu vực
- [ ] ⬜ Cấu hình chiến lược theo từng nhóm (`teams.assignment_strategy`), có màn hình chọn
- [ ] ⬜ Ghi vết quyết định: `ticket_assignments` (danh sách ứng viên, điểm, lý do, người gán) — phục vụ giải thích & kiểm thử
- [ ] ⬜ Cơ chế **claim** (agent tự nhận ticket trong pool) + giới hạn capacity, chặn nhận quá tải
- [ ] ⬜ Leo thang: chưa có agent sau X phút → thông báo Lead → tự động gán; gán lại/chuyển nhóm thủ công
- [ ] ⬜ Test: gán đúng nhóm theo danh mục; chia việc cân bằng khi tải lệch; tie-break ổn định; không gán trùng khi 2 request đồng thời (transaction + lock)

## ⏱️ Sprint 5 — TRỤ 3: SLA & đo thời gian xử lý (2 tuần)

- [ ] ⬜ Migration: sla_policies, ticket_sla, sla_events, ticket_pauses
- [ ] ⬜ `BusinessTimeCalculator`: `addWorkingMinutes()`, `workingMinutesBetween()` (nhiều khung/ngày, cuối tuần, ngày lễ, guard chống lặp vô hạn)
- [ ] ⬜ `SlaService`: `start()`, `pause()`, `resume()` (dời mốc due theo giờ làm việc), `markFirstResponse()`, `resolve()`, `close()`
- [ ] ⬜ Cột thời gian trên `tickets`: `first_responded_at`, `resolved_at`, `closed_at`, `handling_minutes`, `paused_minutes`, `waiting_minutes`, `reopened_count`
- [ ] ⬜ Bảng SLA mặc định P1–P4 (15'/2h, 30'/4h, 2h/1 ngày, 1 ngày/3 ngày) theo giờ làm việc
- [ ] ⬜ Command `tickets:check-sla` + schedule mỗi 5 phút: cảnh báo 80%, đánh dấu vi phạm, ghi `sla_events`, thông báo Agent/Lead
- [ ] ⬜ UI SLA: badge màu (còn nhiều / sắp trễ / trễ), đồng hồ đếm ngược ở trang chi tiết ticket, giải thích mốc due
- [ ] ⬜ Trang cấu hình SLA (Admin/Lead): chính sách theo ưu tiên/danh mục/nhóm + lịch làm việc + ngày lễ
- [ ] ⬜ Job/KPI: tính FRT, TTA, TTR, handling/waiting, tỉ lệ mở lại, FCR, SLA met %, MTTR, P90
- [ ] ⬜ Test unit (bắt buộc): ticket tạo ngoài giờ, cuối tuần, ngày lễ, qua nghỉ trưa, tạm dừng giữa phiên, mở lại, cộng phút vắt qua nhiều ngày

## 📊 Sprint 6 — Dashboard, báo cáo, KB, hoàn thiện (2 tuần)

- [ ] ⬜ Dashboard theo vai trò (Requester / Agent / Lead / Admin) bằng Chart.js: ticket của tôi, hàng đợi nhóm, SLA sắp trễ, biểu đồ xu hướng
- [ ] ⬜ Báo cáo: SLA compliance theo tuần/tháng, ticket theo danh mục/nguồn, hiệu suất agent, backlog, top vi phạm
- [ ] ⬜ Xuất Excel (maatwebsite/excel 4.0.4) + PDF (dompdf 3.1.2) cho danh sách ticket & báo cáo SLA
- [ ] ⬜ Bộ lọc + khoảng thời gian cho báo cáo, lưu/khôi phục filter
- [ ] ⬜ Knowledge Base: CRUD bài viết, gợi ý bài viết khi gõ tiêu đề (dùng cùng Tokenizer), đánh giá "hữu ích"
- [ ] ⬜ CSAT: đánh giá 1–5 sao sau khi đóng ticket + thống kê
- [ ] ⬜ Audit log (activitylog 4.12.3) + màn hình tra cứu nhật ký
- [ ] ⬜ Bảo mật: validate upload, chặn tải tệp ngoài quyền, rate limit form tạo ticket, CSRF/XSS, ẩn ghi chú nội bộ với Requester
- [ ] ⬜ `php artisan test` toàn bộ + `vendor/bin/pint` + review code

## 📄 Sprint 7 — Tài liệu & bảo vệ (1–2 tuần)

- [ ] ⬜ Vẽ ERD + Use Case + Activity/Sequence cho luồng auto-classify & SLA
- [ ] ⬜ Viết chương thuật toán: tokenizer, Naive Bayes (công thức + Laplace), hợp nhất điểm, BusinessTime, công thức KPI
- [ ] ⬜ Bảng số liệu thực nghiệm: accuracy theo τ, accuracy theo lượng dữ liệu train, SLA compliance trước/sau, thời gian phản hồi hệ thống
- [ ] ⬜ Script demo: "Không in được từ máy 305" → gán nhóm Thiết bị + SLA P3; "Sập mạng cả phòng" → P1 + leo thang Lead
- [ ] ⬜ Slide + kịch bản thuyết trình; chuẩn bị câu hỏi phản biện (vì sao NB? vì sao τ? xử lý ngôn ngữ teencode? mở rộng ra sao?)
- [ ] ⬜ Backup CSDL (`mysqldump`) + `.env.example` + README cài đặt (cho người chấm chạy lại được)


