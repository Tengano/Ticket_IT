# Kế hoạch Đồ án — Hệ thống Ticket hỗ trợ IT (IT Helpdesk / ITSM mini)

> **Đề tài:** Hệ thống ticket hỗ trợ IT
> **Ý tưởng cốt lõi / kỹ thuật:** *Tự động phân loại – định tuyến yêu cầu* và *đo lường thời gian xử lý (SLA/KPI)*
> **Stack:** Laravel 13.x (PHP 8.3) + MySQL 8.4 + Blade/Tailwind 4 + Vite
> **Mô hình:** Agile/Scrum rút gọn, sprint 2 tuần · **Cập nhật:** 13/10/2026

---

## 1. Mục tiêu & Phạm vi

**Mục tiêu:** Xây dựng hệ thống tiếp nhận – phân loại – định tuyến – theo dõi – đo lường yêu cầu hỗ trợ IT cho một tổ chức (trường học/doanh nghiệp) trên nền Laravel + MySQL, thay thế quy trình nhận yêu cầu qua chat/điện thoại/giấy tờ vốn không có hàng đợi, không có cam kết thời gian và không đo được hiệu suất.

**3 trụ kỹ thuật trọng tâm (chiếm ~60% khối lượng, là phần "ăn điểm"):**

| # | Trụ kỹ thuật | Kết quả đo được |
|---|---|---|
| T1 | **Tự động phân loại** yêu cầu (danh mục + mức ưu tiên) từ tiêu đề & nội dung | Accuracy / macro-F1 trên tập test ≥ **80%** |
| T2 | **Tự động định tuyến & phân công** theo kỹ năng – nhóm – khu vực – tải công việc | ≥ **90%** ticket gán đúng nhóm ngay lần đầu; 0 ticket "treo" quá X phút |
| T3 | **Đo thời gian xử lý theo SLA** (giờ làm việc, tạm dừng, cảnh báo trước, vi phạm) | Báo cáo SLA tuân thủ %, FRT, MTTR, P90, tỉ lệ mở lại |

**Trong phạm vi (MVP – Must have):**
- Đăng nhập & phân quyền 4 vai trò: **Người yêu cầu (Requester) / Kỹ thuật viên (Agent) / Trưởng nhóm IT (Lead) / Quản trị (Admin)**.
- Gửi ticket qua web, đính kèm tệp, theo dõi trạng thái, trao đổi 2 chiều (bình luận công khai / **ghi chú nội bộ**).
- **Phân loại tự động đa lớp**: luật từ khóa → Naive Bayes (train từ dữ liệu lịch sử) → (tùy chọn) LLM fallback; ghi log mọi dự đoán để đánh giá.
- **Định tuyến tự động**: danh mục → nhóm xử lý; chọn agent theo chiến lược (round-robin / ít việc nhất / khớp kỹ năng), leo thang khi quá hạn.
- **SLA engine**: chính sách SLA theo mức ưu tiên/danh mục, tính theo **giờ làm việc**, tạm dừng khi chờ người dùng, cảnh báo 80%, ghi nhận vi phạm.
- **Báo cáo & Dashboard**: SLA compliance, MTTR, FRT, thời gian theo trạng thái, hiệu suất agent, biểu đồ xu hướng, **xuất Excel/PDF**.
- Nhật ký hoạt động (audit log), thông báo trong hệ thống + email (Mailpit khi dev).
- Vòng đời ticket đầy đủ: Mới → Đã phân công → Đang xử lý → Chờ (người dùng/nhà cung cấp) → Đã giải quyết → Đóng → Mở lại.

**Nên có (Should have):** Knowledge Base + gợi ý bài viết khi tạo ticket (giảm tải), đánh giá hài lòng (CSAT), tag/tìm kiếm nâng cao, dashboard theo nhóm/kỹ năng.

**Có thể có (Could have):** Real-time (Reverb/Pusher), tiếp nhận ticket qua email IMAP, phân tích mức độ khẩn cấp bằng LLM, gắn ticket với tài sản/thiết bị, kênh Zalo/Telegram.

**Ngoài phạm vi:** App mobile native, remote control, CMDB đầy đủ theo ITIL, SSO/LDAP thật, multi-tenant.

---

## 2. Kiểm tra môi trường (đã kiểm tra thực tế trên máy bạn)

| Thành phần | Máy bạn | Yêu cầu | Kết luận |
|---|---|---|---|
| PHP | 8.3.30 (ZTS x64, Laragon) | ≥ 8.3 | ✅ Đạt |
| Composer | 2.10.2 | ≥ 2.x | ✅ Đạt |
| MySQL | 8.4.3 (`bin/mysql/mysql-8.4.3-winx64`) | ≥ 8.0 | ✅ Đạt |
| Node / npm | 24.14.1 / 11.11.0 | Node ≥ 18 | ✅ Đạt |
| Git | 2.53.0.windows.2 | — | ✅ Đạt |
| Web server | Apache + Nginx (Laragon) | — | ✅ (virtual host `*.test`) |
| Mail (dev) | Mailpit (Laragon) | — | ✅ Test email thông báo |
| Redis server | Có sẵn trong Laragon | (tùy chọn) | ⚠️ **Thiếu extension `phpredis`** → không dùng Redis cho queue/cache |
| Hệ điều hành | Windows 11 | — | ✅ |

**Extension PHP đã có:** `bcmath, calendar, ctype, curl, dom, exif, fileinfo, filter, gd, iconv, intl, json, mbstring, mysqli, mysqlnd, openssl, pcre, PDO, pdo_mysql, pdo_sqlite, sodium, sqlite3, tokenizer, xml, xsl, zip, zlib` → đủ cho Laravel + Excel/PDF + xử lý ảnh.

**Extension còn thiếu & cách xử lý:**

| Thiếu | Ảnh hưởng | Xử lý |
|---|---|---|
| `redis` (phpredis) | Không dùng Redis cho queue/cache | `QUEUE_CONNECTION=database`, `CACHE_STORE=database` (bảng `jobs`, `cache` có sẵn trong skeleton) |
| `pcntl` | Queue worker không hỗ trợ signal (graceful restart) | Chạy `php artisan queue:work` bình thường trên Windows; restart thủ công khi đổi code |
| `xdebug` / `pcov` | Không đo coverage khi `php artisan test` | Cài Xdebug 3 (bản khớp PHP 8.3) nếu muốn coverage — **không bắt buộc** |
| cron (không có trên Windows) | Scheduler không tự chạy | Dev: `php artisan schedule:work`; Demo: Windows Task Scheduler gọi `php artisan schedule:run` mỗi phút |

**Lưu ý riêng cho đề tài:**
- **SLA cần giờ làm việc** → bắt buộc có scheduler chạy mỗi 5 phút. Đây là thành phần "sống" của hệ thống, phải demo được.
- **Bộ phân loại Naive Bayes** cần dữ liệu lịch sử → phải **seeder ~300–400 ticket mẫu có nhãn** trước khi đánh giá độ chính xác.
- **MySQL 8.4** mặc định `caching_sha2_password` — PHP 8.3 mysqlnd hỗ trợ tốt, cứ để mặc định; cấu hình `charset=utf8mb4`, `collation=utf8mb4_unicode_ci` để lưu tiếng Việt + emoji.

---

## 3. Kiến trúc & Công nghệ

**Mô hình:** MVC + Service Layer (dùng đúng chuẩn Laravel, không nhồi logic vào Controller).

- **Backend:** Laravel 13 (PHP 8.3), Eloquent ORM, Migration + Seeder + Factory, Form Request validate, **Service/Action class cho 3 trụ kỹ thuật**, Observer + Event/Listener + Queue Job.
- **Frontend:** Blade + Tailwind CSS 4 (mặc định skeleton Laravel 13) + Alpine.js (tương tác nhẹ) + Chart.js (biểu đồ) + Vite 8.
- **CSDL:** MySQL 8.4, InnoDB, `utf8mb4`, index đầy đủ cho ticket/hàng đợi/báo cáo.
- **Auth & phân quyền:** Session auth (Breeze) + `spatie/laravel-permission` (role + permission) + Policy cho từng ticket.
- **Queue & Scheduler:** driver `database` (do thiếu phpredis) — job: gửi mail, tính lại SLA, train model.
- **Phân loại:** PHP thuần (rule có trọng số + Naive Bayes tự cài đặt, **không phụ thuộc thư viện ML ngoài**) + **lớp tùy chọn gọi LLM API** (Gemini/OpenAI qua Guzzle) sau feature flag.

**Luồng nghiệp vụ chính (auto-classify + auto-route + SLA):**

```
Requester tạo ticket (subject + description + tệp đính kèm)
        │
        ▼
[TicketObserver@created] → TicketCreated event
        │
        ├─► (1) ClassificationService::predict()
        │       ├─ Lớp A: Rule/keyword có trọng số (bảng classification_rules)
        │       ├─ Lớp B: Naive Bayes đa thức (model đã train, lưu trong DB)
        │       ├─ Lớp C: LLM API (tùy chọn, feature flag)
        │       └─ Hợp nhất điểm → confidence
        │              ├─ confidence ≥ τ (0.65) → tự gán danh mục + ưu tiên
        │              └─ confidence < τ  → đưa vào hàng đợi Triage (chờ người xác nhận)
        │
        ├─► (2) RoutingService::route()
        │       ├─ category/subcategory → team (team_route_rules)
        │       ├─ priority P1 → leo thang Lead + on-call
        │       ├─ location/zone → agent phụ trách khu vực
        │       └─ AssignmentStrategy (round_robin | least_open | skill_weighted)
        │              → gán assignee + ghi ticket_assignments(reason, score)
        │
        └─► (3) SlaService::start()
                ├─ Chọn sla_policy khớp (priority × category × team)
                ├─ Tính due theo GIỜ LÀM VIỆC (BusinessTimeCalculator)
                ├─ warning_at = 80% mốc thời gian
                └─ Tạo ticket_sla + ghi timeline

[Scheduler mỗi 5 phút] php artisan tickets:check-sla
        ├─ sắp tới hạn (≥ warning_at) → thông báo Agent (+Lead)
        ├─ quá hạn                → đánh dấu breach, ghi sla_events, thông báo, leo thang
        └─ chưa có agent > X phút → thông báo Lead + tự động gán
```

**Cấu trúc thư mục dự kiến (phần mở rộng so với skeleton):**

```
app/
├── Console/Commands/   # tickets:check-sla, classifier:train, classifier:evaluate, classifier:prune
├── Enums/              # TicketStatus, TicketPriority, SlaState, CommentType, AssignmentStrategy
├── Events/ Listeners/  # TicketCreated, TicketAssigned, SlaWarning, SlaBreached, TicketResolved
├── Http/
│   ├── Controllers/    # Ticket, Comment, Attachment, Category, Team, SlaPolicy, Report, Dashboard, Admin
│   ├── Middleware/     # EnsureUserHasRole, LogTicketAccess
│   ├── Requests/       # StoreTicketRequest, UpdateTicketRequest, AssignTicketRequest...
│   └── Resources/      # TicketResource (dùng cho AJAX/API nội bộ)
├── Jobs/               # SendTicketNotification, RetrainClassifier, RecalculateTicketMetrics
├── Models/             # Ticket, TicketSla, Category, Team, SlaPolicy, ClassificationRule, ClassifierModel...
├── Notifications/      # TicketAssignedNotification, SlaWarningNotification, TicketCommented...
├── Policies/           # TicketPolicy, TicketCommentPolicy
├── Services/
│   ├── Classification/ # ClassificationService, RuleClassifier, NaiveBayesClassifier, LlmClassifier, Tokenizer
│   ├── Routing/        # RoutingService + AssignmentStrategy (interface + 3 lớp triển khai)
│   └── Sla/            # SlaService, BusinessTimeCalculator, SlaStateResolver
└── Support/            # BusinessTime, TextNormalizer, MetricsCollector
database/{migrations,seeders,factories}   # + database/data/test_tickets.csv
resources/views/{tickets,dashboard,reports,admin,kb}
routes/{web.php,console.php}
```

```
Requester ──tạo ticket──► tickets ──► ClassificationService ──► RoutingService ──► Agent
                            │                                          │
                            └──► SlaService ──► Dashboard & Báo cáo ◄───┘
ClassificationService ──► model NB + log dự đoán (ticket_classifications)
Agent phản hồi/đóng ──► ghi ticket_events ──► cập nhật SLA + KPI
```

---

## 4. Ba trụ kỹ thuật cốt lõi (đặc tả để lập trình & viết báo cáo)

### 4.1. TRỤ 1 — Bộ máy phân loại yêu cầu (Classification Engine)

**Đầu vào:** `tiêu đề + mô tả + (tùy chọn) danh mục người dùng tự chọn + phòng ban/khu vực của người gửi`.
**Đầu ra:** `category_id`, `subcategory_id`, `priority` (gợi ý), `team_id` (gợi ý), `confidence ∈ [0,1]`, `explanation` (từ khóa khớp để hiển thị "vì sao").

**Kiến trúc lai 3 lớp (hybrid, có thể bật/tắt từng lớp):**

| Lớp | Kỹ thuật | Ưu điểm | Hạn chế | Khi nào dùng |
|---|---|---|---|---|
| **A. Rule-based** | Từ khóa / cụm từ / regex có **trọng số** lưu trong DB | Chính xác cao với từ khóa đặc thù (VD: `mật khẩu`, `wifi`, `máy in`, `sập điện`), giải thích được, 0 chi phí | Không bắt được biến thể câu chữ mới | Luôn bật (nền tảng, có thể sửa không cần deploy) |
| **B. Naive Bayes** (multinomial + Laplace smoothing) | Tự cài đặt PHP thuần, train từ ticket **đã có nhãn vàng** (nhãn do người xác nhận) | Tự học từ dữ liệu lịch sử, không cần API/model ngoài, phù hợp đề tài (thuật toán rõ ràng để bảo vệ) | Cần đủ dữ liệu (≥ 20–50 mẫu/danh mục), nhạy với từ mới | Bật khi đã có ≥ 200 ticket có nhãn |
| **C. LLM API** (tùy chọn) | Gọi Gemini/OpenAI qua Guzzle, yêu cầu trả **JSON** đúng schema | Hiểu ngôn ngữ tự nhiên, xử lý câu dài/lủng củng | Chi phí, cần mạng, phụ thuộc bên ngoài, cần chống "ảo giác" | Chỉ gọi khi A+B cho confidence < τ; lưu cache theo `sha1(text)` |

**Tiền xử lý văn bản (`TextNormalizer` + `Tokenizer`)** — đây là phần phải viết rõ trong báo cáo:
1. Ghép `subject + "\n" + description`, bỏ HTML, gộp khoảng trắng, `mb_strtolower`.
2. **Chuẩn hóa dấu tiếng Việt**: tạo **2 phiên bản token** — bản giữ dấu và bản bỏ dấu (`Str::ascii()`), để "mat khau" và "mật khẩu" cùng khớp.
3. Loại stopwords tiếng Việt (`và, của, cho, tôi, em, ạ, giúp, bị, không được, nhưng…`) + ký tự đặc biệt.
4. Tokenize theo unigram + **bigram** (bắt cụm như "máy in", "mật khẩu wifi", "màn hình xanh").
5. Chuẩn hóa viết tắt/từ lóng nội bộ qua bảng `text_aliases` (`mk`→`mật khẩu`, `wifi`→`wifi`, `máy lạnh`→`điều hòa`…).

**Công thức lớp A (rule):** với mỗi danh mục *c*, `rule_score(c) = Σ weight(k)` của các từ khóa khớp, cộng `priority_boost` nếu luật có cờ ưu tiên (VD: "sập", "cháy", "toàn bộ"). Chuẩn hóa: `rule_norm(c) = rule_score(c) / max_c rule_score(c)`.

**Công thức lớp B (Naive Bayes):**
- Train: `P(c) = N_c / N`; `P(t|c) = (count(t,c) + 1) / (Σ_t' count(t',c) + |V|)` (Laplace α = 1)
- Predict: `score(c) = log P(c) + Σ_t∈doc log P(t|c)`; confidence = **softmax** trên top-1/top-2: `conf = exp(s₁)/Σexp(s_c)` (hoặc dùng `s₁ − s₂` để quyết định "mơ hồ").
- Lưu model theo **phiên bản**: `classifier_models(model_version, trained_at, samples, accuracy, is_active)`; bảng `classifier_terms(model_id, category_id, term, count, log_prob)`.

**Hợp nhất & ra quyết định:**
```
combined(c) = w_A · rule_norm(c) + w_B · nb_prob(c) + (w_C · llm_prob(c) nếu có)
             (mặc định w_A = 0.55, w_B = 0.45; cấu hình trong bảng settings)
if max combined ≥ τ (0.65)  → tự gán danh mục + ghi ticket_classifications(source = rule|nb|llm|hybrid)
else                        → ticket vào hàng đợi Triage; UI hiện top-3 gợi ý để Agent chọn 1 click
```
**Học tăng cường (active learning / feedback loop):** mỗi lần Agent xác nhận hoặc sửa danh mục → lưu `final_category_id`, `was_corrected`; job `RetrainClassifier` (đêm, cần ≥ N nhãn mới) train lại model và **tự so sánh accuracy với model đang dùng**, chỉ kích hoạt nếu tốt hơn hoặc bằng.

**Đo lường (bắt buộc có trong báo cáo):**
- Bộ test độc lập: `database/data/test_tickets.csv` (100 ticket gán nhãn thủ công, **không** dùng để train).
- Command `php artisan classifier:evaluate --holdout=0.2 --model=latest` → **accuracy, precision/recall/F1 theo từng danh mục, macro-F1, confusion matrix**, xuất `storage/app/reports/classifier_v{n}.md`.
- Bảng so sánh: **Rule-only vs NB-only vs Hybrid** ở các ngưỡng τ = 0.5 / 0.6 / 0.65 / 0.8; biểu đồ accuracy theo số lượng dữ liệu train (50/100/200/300) và theo τ — dùng chính bảng này để biện luận chọn τ.
- Chỉ số vận hành cần ghi nhận: tỉ lệ ticket được gán tự động (automation rate), tỉ lệ phải Triage, số lần bị sửa lại danh mục, thời gian phản hồi trung bình của bộ phân loại (< 50 ms/ticket).

### 4.2. TRỤ 2 — Bộ máy định tuyến & phân công (Routing / Assignment Engine)

**Câu hỏi nghiệp vụ:** *"Ticket này phải vào tay ai, trong bao lâu, và vì sao?"* — hệ thống phải trả lời được cả 3 vế và **lưu vết quyết định** (`ticket_assignments.reason`).

**Bước 1 — Xác định phạm vi xử lý (routing):**

| Thứ tự áp dụng | Nguồn dữ liệu | Ví dụ |
|---|---|---|
| 1. Khu vực / địa điểm | `locations.zone` + `agent_zones` | Ticket ở "Phòng 305 – Khu A" → agent phụ trách Khu A |
| 2. Danh mục / danh mục con | `team_route_rules(category_id → team_id, priority)` | Danh mục "Mạng – Wifi" → Nhóm Hạ tầng |
| 3. Mức ưu tiên | `priority` (P1–P4) | P1 (hệ thống dùng chung sập) → leo thang Lead + on-call ngay |
| 4. Người gửi đặc biệt | `users.is_vip`, phòng ban | Ban giám hiệu/Ban giám đốc → hàng đợi senior |
| 5. Kỹ năng chuyên môn | `skills` + `skill_user.level` | "ERP – phân hệ kế toán" → cần skill `erp-accounting` ≥ 3 |

**Bước 2 — Chọn kỹ thuật viên trong nhóm:** triển khai bằng **Strategy Pattern** (`AssignmentStrategy` interface + 3 lớp), chọn theo `teams.assignment_strategy`:

| Chiến lược | Công thức / quy tắc | Khi dùng |
|---|---|---|
| `RoundRobinStrategy` | Xoay vòng theo `last_assigned_at` | Nhóm đều tay, yêu cầu đơn giản |
| `LeastOpenTicketsStrategy` | Chọn agent có `open_tickets` nhỏ nhất → tie-break theo `last_assigned_at` | Giảm tải, tránh dồn việc |
| `SkillWeightedStrategy` (**khuyến nghị**) | `score = 0.5·skill_match + 0.3·(1 − load_ratio) + 0.2·zone_match` | Hệ thống hỗ trợ đa chuyên môn |

```
skill_match = (Σ level kỹ năng khớp) / (Σ level kỹ năng yêu cầu của danh mục)   → [0,1]
load_ratio  = open_tickets / capacity                                           → [0,1+]
zone_match  = 1 nếu cùng khu vực, 0.5 nếu khu vực lân cận (bảng zone_neighbors), 0 nếu khác
điều kiện ứng viên: is_active, đúng team, có ≥ 1 kỹ năng khớp, chưa vượt capacity
```

**Bước 3 — Xử lý bất thường & leo thang:**

| Tình huống | Hành vi hệ thống |
|---|---|
| Không có ứng viên khớp (nhóm trống/quá tải) | Ticket có `team_id` nhưng `assignee_id = null` → vào **pool** của nhóm + thông báo Lead |
| Chưa có agent sau **X phút giờ làm việc** (15' với P1–P2, 60' với P3–P4) | Thông báo Lead → auto-assign theo chiến lược → ghi `ticket_events(type=escalated)` |
| Ticket P1 | Thông báo ngay cho Lead + on-call, bỏ qua giới hạn hàng đợi |
| Ticket mở lại (reopen) | Ưu tiên gán lại **người xử lý cũ** nếu còn hoạt động; nếu không thì xử lý như ticket mới |
| Agent nghỉ phép/bị vô hiệu hóa | Job chuyển ticket đang mở sang gán lại (reassign) theo chiến lược |

**Đo lường cho báo cáo:** routing accuracy (nhóm đúng theo người kiểm tra) ≥ 90%, TTA trung bình giảm so với làm thủ công, độ lệch tải giữa các agent (độ lệch chuẩn `open_tickets`), tỉ lệ ticket phải leo thang, tỉ lệ ticket bị gán lại.

### 4.3. TRỤ 3 — Đo thời gian xử lý theo SLA (Time Measurement Engine)

**Nguyên tắc:** mọi mốc thời gian phải tính theo **giờ làm việc** (bỏ cuối tuần, ngày lễ, giờ nghỉ trưa), phải **tạm dừng** khi chờ người dùng, và phải **giải trình được** (có timeline + lý do). Đây là trụ tạo khác biệt so với một hệ thống "CRUD ticket" thông thường.

**Dữ liệu lịch làm việc:** `business_calendars` (tên, timezone) → `business_hours` (thứ trong tuần, **nhiều khung/ngày**, ví dụ 08:00–12:00 & 13:00–17:00) → `holidays` (ngày lễ/nghỉ bù). Mỗi `sla_policy` trỏ tới 1 lịch.

**Thuật toán `BusinessTimeCalculator` (2 hàm lõi):**
- `addWorkingMinutes(CarbonImmutable $start, int $minutes): CarbonImmutable` — cộng dồn phút **chỉ trong các khung làm việc**; khung nào chưa đủ thì sang khung kế tiếp/ngày làm việc kế tiếp.
- `workingMinutesBetween(CarbonImmutable $a, CarbonImmutable $b): int` — đếm số phút làm việc giữa 2 mốc (dùng để tính tạm dừng và KPI).
- Có `guard` chống vòng lặp vô hạn; tự nhảy qua ngày lễ và cuối tuần (mã nguồn đầy đủ ở Phụ lục A.3).

**Chính sách SLA (`sla_policies`) — bộ mặc định để triển khai:**

| Mức ưu tiên | Phản hồi đầu tiên | Giải quyết | Cảnh báo trước | Ghi chú |
|---|---|---|---|---|
| **P1 – Nghiêm trọng** (sập hệ thống dùng chung) | 15 phút | 2 giờ làm việc | 80% | Leo thang Lead ngay khi tạo |
| **P2 – Cao** (ảnh hưởng cả phòng ban) | 30 phút | 4 giờ | 80% | |
| **P3 – Trung bình** (1 người bị ảnh hưởng) | 2 giờ | 1 ngày làm việc | 80% | Mặc định cho phần lớn ticket |
| **P4 – Thấp** (đề nghị/cải tiến) | 1 ngày | 3 ngày làm việc | 80% | |

**Bảng `ticket_sla` (mỗi ticket 1 dòng):** `sla_policy_id, first_response_due_at, resolution_due_at, warning_at, first_responded_at, resolved_at, paused_minutes, first_response_met, resolution_met, state (ok|warning|paused|breached|met), breached_at, computed_at`.

**Vòng đời SLA & sự kiện:**

| Sự kiện | Xử lý của hệ thống |
|---|---|
| Ticket được tạo | Chọn policy khớp (`priority × category × team`, **policy cụ thể nhất thắng** theo cột `specificity`) → tính `due` theo giờ làm việc → state `ok` |
| Agent phản hồi công khai lần đầu | Set `first_responded_at`, `first_response_met = (now ≤ due)`, ghi `sla_events(type=first_response)` |
| Chuyển trạng thái "Chờ người dùng / Chờ nhà cung cấp" | `SlaService::pause()` → tạo `ticket_pauses(started_at)` |
| Người dùng phản hồi lại / quay lại xử lý | `SlaService::resume()` → `paused_minutes += workingMinutesBetween(paused_at, now)`; **dời** `due` thêm đúng số phút tạm dừng (theo giờ làm việc) |
| Đạt mốc 80% (`warning_at`) | Scheduler ghi `sla_events(type=warning)` + thông báo Agent (và Lead nếu P1–P2) |
| Quá `due` | state `breached`, ghi `breached_at`, thông báo Agent + Lead, badge đỏ xuyên suốt hệ thống |
| Ticket "Đã giải quyết" | Chốt `resolution_met`, dừng đồng hồ; nếu người dùng mở lại → `reopened_count++` và tính lại SLA nếu policy có `restart_on_reopen` |

**Các cột thời gian lưu trực tiếp trên `tickets`:** `first_responded_at, resolved_at, closed_at, handling_minutes, waiting_minutes, paused_minutes, reopened_count` → là **nguồn dữ liệu duy nhất** cho mọi báo cáo (không tính lại bằng tay).

**Mã giả tính lại định kỳ (`tickets:check-sla`, chạy mỗi 5 phút):**
```
for each ticket_sla where state not in (met, breached):
    now = CarbonImmutable::now()
    if ticket đang pause: continue
    if now >= warning_at and warning_sent_at is null:
        mark warning_sent_at, state = warning, notify(agent, lead), log sla_event(warning)
    if now >= resolution_due_at (và chưa resolve):
        state = breached, breached_at = now, resolution_met = false
        notify(agent, lead), log sla_event(breach), escalate nếu P1–P2
```

### 4.4. Bộ chỉ số đo lường (KPI) — định nghĩa & công thức dùng cho báo cáo

| Chỉ số | Viết tắt | Công thức (mọi khoảng thời gian đều là **giờ làm việc**) |
|---|---|---|
| Thời gian phản hồi đầu tiên | **FRT** | `first_responded_at − created_at − paused_minutes` |
| Thời gian tới lúc được gán | **TTA** | `assigned_at − created_at` (mốc gán đầu tiên) |
| Thời gian giải quyết | **TTR / MTTR** | `resolved_at − created_at − paused_minutes` (MTTR = trung bình TTR) |
| Thời gian xử lý thực | Handling | `resolved_at − assigned_at − waiting_minutes` |
| Thời gian chờ | Waiting | Tổng phút ở trạng thái "Chờ người dùng/Nhà cung cấp" (`ticket_pauses`) |
| Tỉ lệ giải quyết ngay lần đầu | **FCR** | `ticket đóng không mở lại / tổng ticket đóng` |
| Tỉ lệ tuân thủ SLA | SLA % | `số ticket met / tổng ticket có SLA` (tính riêng cho FRT và TTR) |
| Tỉ lệ mở lại | Reopen rate | `tổng reopened_count / tổng ticket đóng` |
| Giá trị phân vị 90 | **P90** | Phân vị 90 của phân bố TTR (thể hiện "đuôi chậm" mà trung bình che mất) |
| Tồn đọng | Backlog | Số ticket chưa `resolved` theo nhóm/agent + tuổi ticket (age) |
| Thông lượng | Throughput | Số ticket đóng theo ngày/tuần |
| Độ chính xác phân loại | Accuracy / macro-F1 | Trên tập test gán nhãn (mục 4.1) |

> **Lưu ý phương pháp:** trình bày cả **trung bình (mean)** và **trung vị (median)**; dùng P90 để không bị "che" bởi một vài ticket xử lý rất nhanh. Với KPI theo agent chỉ dùng để **cân bằng tải & cải tiến quy trình**, không dùng để "đánh giá cá nhân" — nêu rõ điều này trong báo cáo để tránh phản biện.

---

## 5. Thư viện cần cài (đã đối chiếu tương thích PHP 8.3 + Laravel 13)

> **Không cần cài thêm extension PHP nào** (máy đã đủ). Chỉ cần package Composer (backend) và npm (frontend).

**Composer (backend) — version đã kiểm tra từ Packagist:**

| Gói | Phiên bản dùng | Lệnh | Mục đích | Ghi chú tương thích |
|---|---|---|---|---|
| laravel/framework | ^13.x | (có sẵn khi `create-project`) | Framework | ✅ PHP ^8.3 |
| laravel/breeze | v2.4.2 | `composer require laravel/breeze --dev` → `php artisan breeze:install blade` | Scaffold auth (Blade + Tailwind) | ✅ illuminate ^11\|^12\|^13 |
| spatie/laravel-permission | ^8.3 | `composer require spatie/laravel-permission` | Phân quyền role/permission | ✅ PHP ^8.3, illuminate ^12\|^13 |
| spatie/laravel-activitylog | **^4.12** (⚠️ không dùng 5.x) | `composer require "spatie/laravel-activitylog:^4.12"` | Nhật ký hoạt động/audit | ⚠️ Bản **5.x yêu cầu PHP ^8.4** — máy bạn 8.3 nên phải dùng 4.12.3 |
| maatwebsite/excel | ^4.0 | `composer require maatwebsite/excel` | Xuất/nhập Excel báo cáo | ✅ PHP ^8.3, illuminate ^12\|^13 |
| barryvdh/laravel-dompdf | ^3.1 | `composer require barryvdh/laravel-dompdf` | Xuất PDF (báo cáo SLA, phiếu ticket) | ✅ illuminate ^9→^13 |
| laravel/pint (dev) | (có sẵn) | — | Chuẩn hoá code style | ✅ |
| laravel/pail (dev) | ^1.2 | `composer require laravel/pail --dev` | Xem log realtime | ✅ |
| barryvdh/laravel-debugbar (dev, tùy chọn) | ^3.x | `composer require --dev barryvdh/laravel-debugbar` | Debug (+ xem số query N+1) | ✅ |
| phpunit/phpunit (dev) | ^12.5 | (có sẵn) | Kiểm thử tự động | ✅ |

> **Không dùng:** `laravel/horizon` (cần Redis + phpredis — máy bạn thiếu extension), `laravel/scout` (cần driver search), các package ML ngoài (tự cài Naive Bayes bằng PHP để bài có "chất" kỹ thuật và không phụ thuộc mạng).

**npm (frontend):**

| Gói | Lệnh | Mục đích |
|---|---|---|
| tailwindcss 4 + @tailwindcss/vite | (có sẵn trong skeleton Laravel 13) | Giao diện |
| alpinejs | `npm i alpinejs` | Tương tác nhẹ (dropdown, modal, đếm ngược SLA) |
| chart.js | `npm i chart.js` | Biểu đồ dashboard/báo cáo |
| sweetalert2 | `npm i sweetalert2` | Xác nhận/hộp thoại đẹp |
| flatpickr | `npm i flatpickr` | Chọn khoảng thời gian cho báo cáo |
| @fortawesome/fontawesome-free *(hoặc)* lucide | `npm i lucide` | Icon |

**Trình tự cài đặt (copy chạy lần lượt):**
```bat
:: 1) Tạo dự án trong thư mục web của Laragon (được virtual host ticket-it.test)
cd K:\Apps\Laragon\www
composer create-project laravel/laravel ticket-it
cd ticket-it

:: 2) Tạo DB rồi cập nhật .env (xem Phụ lục A.1), sau đó:
php artisan key:generate
php artisan migrate

:: 3) Package backend
composer require laravel/breeze --dev
php artisan breeze:install blade
composer require spatie/laravel-permission
composer require "spatie/laravel-activitylog:^4.12"
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require laravel/pail --dev

:: 4) Package frontend
npm install
npm i alpinejs chart.js sweetalert2 flatpickr lucide

:: 5) Chạy (3 terminal riêng biệt khi dev)
php artisan serve            :: hoặc truy cập http://ticket-it.test
npm run dev
php artisan schedule:work     :: trụ SLA sống
:: (khi cần) php artisan queue:work
```

> **Lưu ý thư mục làm việc hiện tại:** workspace `K:\Apps\Laragon\www\New folder (2)` đang **trống** và tên thư mục có khoảng trắng + dấu ngoặc → không nên làm virtual host. **Khuyến nghị** tạo dự án ở `www\ticket-it` rồi mở lại VS Code tại đó (toàn bộ tài liệu kế hoạch/phụ lục này copy sang thư mục dự án là dùng ngay).

---

## 6. Mô hình dữ liệu (bảng chính)

**Nhóm 1 — Người dùng & tổ chức**

| Bảng | Trường quan trọng | Ghi chú |
|---|---|---|
| `users` | id, name, email, password, phone, department_id, location_id, is_active, is_vip, avatar | Bổ sung cột so với mặc định: `department_id`, `location_id`, `is_vip`, `is_active`, `last_assigned_at` |
| `roles`, `permissions`, `model_has_roles` | — | Spatie Permission |
| `departments` | id, code, name | Phòng ban/khoa |
| `locations` | id, name, building, floor, zone | Vị trí (phòng/khu) — dùng cho định tuyến theo khu vực |
| `teams` | id, code, name, lead_id, business_calendar_id, **assignment_strategy**, capacity | Nhóm xử lý (Helpdesk, Hạ tầng, Thiết bị, Ứng dụng, Bảo mật) |
| `team_user` | team_id, user_id, is_lead, capacity | Thành viên nhóm |
| `skills` / `skill_user` | id, code, name / user_id, skill_id, level(1–5) | Kỹ năng & mức độ |
| `agent_zones` / `zone_neighbors` | user_id, location_id / zone, neighbor_zone | Vùng phụ trách |

**Nhóm 2 — Danh mục & phân loại**

| Bảng | Trường quan trọng | Ghi chú |
|---|---|---|
| `categories` | id, parent_id, code, name, default_team_id, default_priority, required_skills(json), is_active | 2 cấp (danh mục – danh mục con) |
| `team_route_rules` | id, category_id, team_id, priority, condition(json) | Luật định tuyến |
| `classification_rules` | id, name, category_id, team_id, keywords(json), regex, weight, priority_boost, is_urgent_signal, is_active | **Lớp A** (rule) |
| `text_aliases` | id, alias, canonical | Chuẩn hoá viết tắt (`mk` → `mật khẩu`) |
| `classifier_models` | id, version, algorithm, samples, vocab_size, accuracy, macro_f1, params(json), is_active, trained_at | Phiên bản model NB |
| `classifier_classes` | classifier_model_id, category_id, doc_count, term_total, log_prior | Xác suất tiên nghiệm |
| `classifier_terms` | classifier_model_id, category_id, term, count, log_prob | Xác suất có điều kiện |
| `ticket_classifications` | ticket_id, classifier_model_id, predicted_category_id, confidence, source, scores, matched_keywords, final_category_id, was_corrected, corrected_by, corrected_at | **Log mọi dự đoán** — nguồn dữ liệu để đánh giá & retrain |
| `classifier_evaluations` | classifier_model_id, holdout_ratio, accuracy, macro_f1, report(json) | Kết quả đánh giá từng lần |

**Nhóm 3 — Ticket & vận hành**

| Bảng | Trường quan trọng | Ghi chú |
|---|---|---|
| `tickets` | id, code, subject, description, requester_id, department_id, location_id, category_id, assigned_team_id, assignee_id, status, priority, impact, urgency, channel, ai_confidence, ai_category_id, needs_triage, first_responded_at, resolved_at, closed_at, handling_minutes, waiting_minutes, paused_minutes, reopened_count, satisfaction_rating | **Bảng trung tâm** — chứa sẵn các cột KPI |
| `ticket_comments` | id, ticket_id, user_id, body, type(public\|internal), is_first_response | Ghi chú nội bộ **không** hiển thị cho Requester |
| `ticket_attachments` | id, ticket_id, ticket_comment_id, uploaded_by, disk, path, original_name, mime, size | Lưu tệp, kiểm tra MIME & quyền tải |
| `ticket_events` | id, ticket_id, actor_id, type, from, to, meta(json) | Timeline + audit (kể cả `classified` với confidence & từ khóa khớp) |
| `ticket_status_histories` | ticket_id, from_status, to_status, actor_id, reason, started_at, ended_at, minutes | Đếm thời gian theo từng trạng thái |
| `ticket_assignments` | ticket_id, from_user_id, to_user_id, to_team_id, strategy, score, reason, assigned_by | **Giải trình quyết định định tuyến** |
| `ticket_watchers` | ticket_id, user_id | Người theo dõi |
| `ticket_relations` | ticket_id, related_ticket_id, type(duplicate\|related\|blocked_by) | Liên kết ticket |
| `ticket_pauses` | ticket_id, reason, started_at, ended_at, minutes | Khoảng tạm dừng SLA (chờ người dùng/nhà cung cấp) |

**Nhóm 4 — Cấu hình & vận hành**

| Bảng | Trường quan trọng | Ghi chú |
|---|---|---|
| `sla_policies` | name, priority, category_id, team_id, business_calendar_id, first_response_minutes, resolution_minutes, warning_percent, restart_on_reopen, specificity, is_active | Chính sách SLA |
| `ticket_sla` | ticket_id, sla_policy_id, first_response_due_at, resolution_due_at, warning_at, first_response_met, resolution_met, state, breached_at | Đồng hồ SLA từng ticket |
| `sla_events` | ticket_id, type(warning\|breach\|paused\|resumed\|met), target, occurred_at, meta | Lịch sử vi phạm/cảnh báo |
| `business_calendars` / `business_hours` / `holidays` | — | Lịch làm việc & ngày lễ (**cơ sở tính giờ làm việc**) |
| `knowledge_articles` | id, title, body, category_id, views, helpful_count, tags(json) | Knowledge Base (Should have) |
| `settings` | key, value, type, group | Bật/tắt AI, chỉnh τ, trọng số, tham số leo thang |
| `notifications` | (mặc định Laravel) | Thông báo trong app |
| `activity_log` | (Spatie 4.x) | Nhật ký thao tác toàn hệ thống |
| `jobs`, `failed_jobs`, `cache` | (mặc định Laravel) | Queue driver `database` |

**Quan hệ chính:**
- `User (requester) 1–n Ticket`; `User (assignee) 1–n Ticket`; `Team 1–n Ticket`.
- `Category 1–n Category (parent/child)`; `Category 1–n TeamRouteRule`; `Category 1–n ClassificationRule`.
- `Ticket 1–1 TicketSla`; `Ticket 1–n TicketPause / SlaEvent / TicketEvent / TicketComment / TicketAttachment / TicketAssignment`.
- `Ticket 1–n TicketClassification` (giữ **toàn bộ** lịch sử dự đoán, kể cả các lần bị sửa) → phục vụ đánh giá và học lại.
- `SlaPolicy n–1 BusinessCalendar 1–n BusinessHour / Holiday`.

**Chỉ mục (index) bắt buộc — để báo cáo không bị chậm:**

| Bảng | Index | Phục vụ truy vấn |
|---|---|---|
| `tickets` | `(assigned_team_id, status)`, `(assignee_id, status)`, `(status, priority)`, `(category_id, created_at)`, `(sla_state)`, `(created_at)` | Hàng đợi, dashboard, báo cáo theo thời gian |
| `ticket_sla` | `(state)`, `(resolution_due_at)`, `(warning_at)` | Scheduler quét mỗi 5 phút |
| `ticket_events` | `(ticket_id, type)`, `(created_at)` | Timeline + thống kê |
| `classifier_terms` | `(classifier_model_id, term)` | Tra xác suất khi dự đoán |
| `ticket_classifications` | `(ticket_id)`, `(predicted_category_id, was_corrected)` | Đánh giá & retrain |

---

## 7. Vòng đời ticket, trạng thái & cách tính mức ưu tiên

**Trạng thái (`status`)** — Enum `TicketStatus` trong `app/Enums`:

| Trạng thái | Ý nghĩa | Ai chuyển được | Ảnh hưởng SLA |
|---|---|---|---|
| `new` | Vừa tạo, đang chờ phân loại/định tuyến | Hệ thống | Đồng hồ chạy |
| `triage` | Cần người xác nhận danh mục (confidence < τ) | Agent/Lead | Đồng hồ chạy |
| `assigned` | Đã có nhóm + người xử lý | Hệ thống/Lead/Agent | Đồng hồ chạy |
| `in_progress` | Đang xử lý | Agent | Đồng hồ chạy |
| `pending_user` | Chờ người yêu cầu phản hồi | Agent | **Tạm dừng** |
| `pending_vendor` | Chờ nhà cung cấp/đơn vị ngoài | Agent | **Tạm dừng** |
| `resolved` | Đã xử lý xong, chờ xác nhận | Agent | Chốt `resolution_met` |
| `closed` | Đóng (tự động sau N ngày hoặc do người dùng xác nhận) | Requester/Agent/hệ thống | Kết thúc |
| `reopened` *(là sự kiện, không phải trạng thái riêng)* | Mở lại từ `resolved`/`closed` | Requester/Agent | `reopened_count++`, tính lại SLA nếu policy cho phép |

**Luật chuyển trạng thái (state machine — validate bằng Enum + guard):**
```
new ──► triage ──► assigned ──► in_progress ──► resolved ──► closed
 │                    ▲              │  ▲            │
 │                    └──────────────┘  │            └──► reopened/đóng lại
 └──► assigned (nếu tự tin phân loại)    │
        in_progress ◄─► pending_user / pending_vendor (pause/resume SLA)
```
- Không cho phép nhảy trạng thái tùy tiện (VD: `new → closed`) — mọi chuyển đổi ghi vào `ticket_status_histories` (from, to, actor, lý do) + `ticket_events`.
- Đóng ticket chỉ khi `resolved` quá N ngày hoặc Requester xác nhận (chống đóng "ép").

**Tính mức ưu tiên P1–P4 bằng ma trận Impact × Urgency (chuẩn ITIL):**

| Impact ↓ / Urgency → | 1 – Thấp (có thể chờ) | 2 – Trung bình (ảnh hưởng công việc) | 3 – Cao (không làm việc được) |
|---|---|---|---|
| **3 – Toàn tổ chức** (hệ thống dùng chung sập) | P3 | **P2** | **P1** |
| **2 – Phòng ban/nhóm** | P4 | P3 | **P2** |
| **1 – Cá nhân** | P4 | P4 | P3 |

- Requester chọn Impact/Urgency ở form tạo ticket (có mô tả gợi ý để tránh chọn sai); hệ thống **có quyền nâng** mức ưu tiên nếu bộ phân loại phát hiện **tín hiệu khẩn cấp** trong nội dung (`sập, cháy, mất dữ liệu, toàn bộ, không ai vào được`) — ghi rõ lý do vào `ticket_events(meta)`.
- Lead/Agent có thể hạ/nâng mức ưu tiên nhưng **phải nhập lý do** → lưu vết, phục vụ báo cáo "số lần điều chỉnh ưu tiên".

---

## 8. Product Backlog (Epic → User Story) & mức ưu tiên

| # | Epic | User Story tiêu biểu | Ưu tiên |
|---|---|---|---|
| E1 | Xác thực & phân quyền | Đăng nhập/đăng xuất; Admin quản lý tài khoản, gán vai trò (Requester/Agent/Lead/Admin); chặn truy cập trái phép | **Must** |
| E2 | Danh mục nền | Quản lý phòng ban, vị trí/khu vực, nhóm xử lý, kỹ năng, lịch làm việc & ngày lễ | **Must** |
| E3 | Ticket & cộng tác | Tạo ticket có đính kèm; xem danh sách + lọc; trao đổi công khai & ghi chú nội bộ; đổi trạng thái; đóng/mở lại | **Must** |
| E4 | **Phân loại tự động (trụ 1)** | Hệ thống tự gán danh mục + mức ưu tiên kèm confidence & giải thích; Agent xác nhận/sửa ở màn Triage; model tự học lại từ nhãn vàng | **Must** |
| E5 | **Định tuyến & phân công (trụ 2)** | Ticket tự vào đúng nhóm; tự gán agent theo chiến lược; agent tự nhận việc (claim); leo thang khi không ai nhận | **Must** |
| E6 | **SLA & đo thời gian (trụ 3)** | Cấu hình chính sách SLA; theo dõi đồng hồ theo giờ làm việc; cảnh báo 80%; ghi nhận vi phạm; đóng băng khi chờ người dùng | **Must** |
| E7 | Thông báo | Thông báo trong app + email khi được gán/có phản hồi/sắp trễ/vi phạm | **Must** |
| E8 | Dashboard & Báo cáo | Dashboard theo vai trò; báo cáo SLA compliance, MTTR, FRT, P90, hiệu suất agent; lọc theo thời gian | **Should** |
| E9 | Xuất dữ liệu | Xuất danh sách ticket & báo cáo SLA ra Excel/PDF | **Should** |
| E10 | Knowledge Base | Đăng bài hướng dẫn; gợi ý bài viết khi tạo ticket theo tiêu đề (dùng lại Tokenizer); đánh giá hữu ích | **Should** |
| E11 | CSAT | Đánh giá 1–5 sao sau khi đóng ticket + thống kê | **Should** |
| E12 | Audit & bảo mật | Nhật ký thao tác; giới hạn tải tệp; rate limit; phân quyền theo Policy | **Should** |
| E13 | Kênh mở rộng | Nhận ticket qua email IMAP; thông báo Telegram/Google Chat; real-time | **Could** |
| E14 | Tài sản/CMDB mini | Gắn ticket với thiết bị/phần mềm; lịch sử hỏng hóc theo thiết bị | **Could** |

**Định nghĩa "Must/Should/Could"** dùng để chốt phạm vi từng sprint — nếu hết thời gian thì cắt từ dưới lên (E13, E14, E11, E10) và **tuyệt đối không cắt E4–E6** vì đó là ý tưởng cốt lõi của đề tài.

---

## 9. Chức năng chi tiết theo vai trò (dùng để vẽ Use Case & phân công việc)

**1) Người yêu cầu (Requester) — nhân viên/giảng viên/sinh viên**
- Tạo ticket: tiêu đề, mô tả (khuyến khích mô tả ai–ở đâu–hiện tượng), chọn danh mục *(có thể bỏ trống để hệ thống tự đoán)*, mức ảnh hưởng/khẩn cấp, vị trí/phòng, đính kèm ảnh chụp lỗi.
- Theo dõi danh sách ticket của mình + trạng thái + **đồng hồ SLA**; nhận thông báo khi có phản hồi.
- Trả lời, gửi thêm ảnh, xác nhận đã xử lý xong, đánh giá CSAT, mở lại ticket nếu chưa hết lỗi.
- Tìm bài viết hướng dẫn (KB) trước khi tạo ticket (giảm tải cho IT).

**2) Kỹ thuật viên (Agent)**
- Xem hàng đợi theo nhóm + ticket được gán + **ticket chưa có người nhận (pool)** → nhận việc (claim).
- Xử lý: ghi chú nội bộ, trao đổi với người dùng, đính kèm bằng chứng, đổi trạng thái, tạm dừng khi chờ người dùng, đánh dấu đã giải quyết.
- Xác nhận/sửa **danh mục do hệ thống đề xuất** ở màn Triage (thao tác này nuôi dữ liệu train cho bộ phân loại).
- Xem đồng hồ SLA từng ticket, biết mình đang quá tải hay không (số ticket mở / capacity).

**3) Trưởng nhóm IT (Lead)**
- Toàn cảnh hàng đợi theo nhóm, phân công/gán lại, xử lý leo thang, đổi mức ưu tiên (kèm lý do).
- Cấu hình chính sách SLA cho nhóm, lịch làm việc, ngày lễ.
- Xem báo cáo: ticket quá hạn, sắp quá hạn, tải theo agent, danh mục nào phát sinh nhiều bất thường.

**4) Quản trị (Admin)**
- Quản lý người dùng, vai trò, quyền; danh mục & danh mục con; nhóm–kỹ năng–khu vực; vị trí & phòng ban.
- Cấu hình luật phân loại (`classification_rules`), alias từ ngữ, tham số hệ thống (τ, trọng số, hạn mức leo thang).
- Train lại / kích hoạt phiên bản model phân loại; xem các báo cáo đánh giá độ chính xác.
- Nhật ký hoạt động, sao lưu/khôi phục.

---

## 10. Kế hoạch Sprint (8 sprint ≈ 15 tuần, mỗi sprint 2 tuần)

| Sprint | Thời lượng | Nội dung chính | Kết quả bàn giao (demo) |
|---|---|---|---|
| **Sprint 0** | 1 tuần | Setup Laragon, `create-project`, DB, `.env`, Git, cài package, layout khung, phân quyền role seed | App chạy `ticket-it.test`, đăng nhập được, layout hiển thị |
| **Sprint 1** | 2 tuần | Auth (Breeze) + phân quyền 4 vai trò + Policy; CRUD danh mục nền (phòng ban, vị trí, nhóm, kỹ năng, lịch làm việc & ngày lễ) | Đăng nhập 4 vai trò, chặn truy cập trái phép, danh mục đầy đủ |
| **Sprint 2** | 2 tuần | Ticket & vòng đời: tạo/xem/lọc, bình luận + ghi chú nội bộ, đính kèm, state machine, timeline, thông báo, ma trận ưu tiên | Luồng ticket thủ công chạy trọn vẹn (chưa có tự động hoá) |
| **Sprint 3** | 2 tuần | **TRỤ 1** — Tokenizer, RuleClassifier, NaiveBayes, ClassificationService, Triage, train/evaluate, dashboard mini về độ chính xác | Ticket tự gán danh mục + confidence; báo cáo accuracy ≥ 80% |
| **Sprint 4** | 2 tuần | **TRỤ 2** — Routing rules, 3 chiến lược gán, claim, leo thang ticket treo, log quyết định | Ticket tự vào đúng nhóm/agent; đo routing accuracy |
| **Sprint 5** | 2 tuần | **TRỤ 3** — BusinessTimeCalculator, SlaService (pause/resume), scheduler cảnh báo 80%/vi phạm, UI đồng hồ SLA, KPI | Đồng hồ SLA chạy đúng theo giờ làm việc; cảnh báo & vi phạm hoạt động |
| **Sprint 6** | 2 tuần | Dashboard theo vai trò, báo cáo SLA/KPI + biểu đồ, xuất Excel/PDF, KB gợi ý, CSAT, audit log, bảo mật | Bộ báo cáo hoàn chỉnh + xuất file |
| **Sprint 7** | 1–2 tuần | Hoàn thiện – dọn lỗi – tối ưu truy vấn (N+1, index) – viết tài liệu chương thuật toán – số liệu thực nghiệm – chuẩn bị bảo vệ | Bản hoàn thiện + số liệu + slide, sẵn sàng bảo vệ |

**Phân bổ nếu làm 1 mình (rút gọn ~10 tuần):**
- Gộp Sprint 1 + 2 thành **2 tuần** (dùng Breeze + CRUD cơ bản, chưa cần nhiều màn hình admin đẹp).
- Trụ 1 (2 tuần) → bắt buộc giữ; **có thể chỉ làm Rule + NB**, bỏ lớp LLM.
- Trụ 2 (1,5 tuần) → chỉ làm 2 chiến lược (`least_open`, `skill_weighted`).
- Trụ 3 (2 tuần) → bắt buộc giữ (đây là phần dễ bảo vệ và ít rủi ro nhất).
- Báo cáo + tài liệu: **1,5–2 tuần** (đừng dồn vào 3 ngày cuối).

**Cột mốc kiểm tra (Gate) — phải đạt mới được sang phần sau:**

| Mốc | Điều kiện đạt |
|---|---|
| G1 (hết Sprint 1) | Đăng nhập + phân quyền chạy đúng; danh mục nền đủ dữ liệu thật |
| G2 (hết Sprint 2) | Tạo → xử lý → đóng ticket thủ công trọn vẹn, có timeline |
| G3 (hết Sprint 3) | `classifier:evaluate` in ra accuracy ≥ 80% trên tập test; có bảng so sánh theo τ |
| G4 (hết Sprint 4) | ≥ 90% ticket vào đúng nhóm; leo thang treo hoạt động |
| G5 (hết Sprint 5) | 10 unit test của BusinessTimeCalculator/SlaService **đều xanh**; scheduler chạy đúng trên lịch có nghỉ trưa + ngày lễ |
| G6 (hết Sprint 6) | Báo cáo SLA xuất được Excel + PDF, số liệu khớp với CSDL |
| G7 (trước bảo vệ) | Demo 3 kịch bản (P1 sập mạng, P3 máy in, ticket sai danh mục) + số liệu thực nghiệm |

---

## 11. Kiểm thử & tiêu chí đánh giá

**Bộ kiểm thử tự động (PHPUnit — `php artisan test`):**

| Nhóm test | Nội dung bắt buộc | Loại |
|---|---|---|
| `BusinessTimeCalculatorTest` | Ngoài giờ làm việc, cuối tuần, ngày lễ, qua nghỉ trưa, cộng nhiều ngày, `workingMinutesBetween` các ca biên | Unit |
| `TokenizerTest` / `NaiveBayesTest` | Tiếng Việt có dấu & không dấu, teencode, từ dừng, dữ liệu nhỏ đã biết trước kết quả phân loại | Unit |
| `ClassifierAccuracyTest` | Đọc `database/data/test_tickets.csv`, chạy dự đoán, **assert accuracy ≥ 0.80** (dùng `markTestSkipped` nếu model chưa train) | Integration |
| `SlaServiceTest` | `start()` tính đúng due; `pause/resume` dời due đúng số phút làm việc; `markFirstResponse` đúng hạn/trễ | Unit |
| `SlaCheckCommandTest` | Ticket sắp tới hạn → tạo `sla_events(warning)` + gửi notification (dùng `Notification::fake()`) | Feature |
| `RoutingServiceTest` | Gán đúng nhóm theo danh mục; cân bằng tải; tie-break; không gán trùng khi gọi song song | Feature |
| `TicketFlowTest` | Requester tạo ticket → tự phân loại → tự gán → agent phản hồi → đóng → CSAT | Feature |
| `AuthorizationTest` | Requester không xem/sửa ticket người khác; Agent không xem ghi chú của nhóm khác; chỉ Lead gán lại | Feature |

**Tiêu chí đánh giá kết quả đề tài (đưa vào báo cáo dưới dạng bảng "KPI mục tiêu vs thực tế"):**

| Tiêu chí | Mục tiêu | Cách đo |
|---|---|---|
| Độ chính xác phân loại | ≥ 80% (macro-F1 ≥ 0.75) | `classifier:evaluate` trên 100 ticket gán nhãn tay |
| Tỉ lệ ticket được phân loại tự động | ≥ 70% (phần còn lại vào Triage) | Đếm `needs_triage = false / tổng ticket` |
| Độ chính xác định tuyến nhóm | ≥ 90% | So nhãn nhóm của 50 ticket với quyết định người kiểm tra |
| Tỉ lệ tuân thủ SLA | ≥ 85% (trên dữ liệu demo 300+ ticket) | Báo cáo SLA (`resolution_met`) |
| MTTR giảm | Giảm ≥ 20% so với 100 ticket "làm tay" mô phỏng | So sánh 2 đợt dữ liệu |
| Thời gian phản hồi hệ thống | Tạo ticket + phân loại + định tuyến < 1,5 giây | Đo bằng `php artisan test` hoặc Laravel Debugbar |
| Không có ticket bị "treo" | 0 ticket chưa có agent quá 60 phút giờ làm việc | Scheduler + báo cáo |
| Tỉ lệ mở lại | ≤ 10% | Báo cáo reopened rate |
| Độ phủ kiểm thử | ≥ 60% các service lõi (`--coverage` cần Xdebug) | `php artisan test --coverage` |

**Kiểm thử thủ công / kiểm thử hiệu năng (nên làm để bảo vệ tốt):**
- Sinh 5.000 ticket bằng seeder → đo thời gian tải trang danh sách & trang báo cáo (mục tiêu < 1,5 giây/trang khi đã có index).
- Kiểm tra N+1 bằng Laravel Debugbar ở trang danh sách ticket & dashboard.
- Kiểm tra upload tệp sai định dạng/quá dung lượng; thử tải tệp của ticket mình không có quyền.
- Kiểm tra 2 agent cùng bấm "Nhận việc" (claim) ở cùng thời điểm → chỉ 1 người nhận được.

---

## 12. Quy trình Agile (Scrum rút gọn) & Definition of Done

- **Vai trò:** Product Owner (người đặt yêu cầu — giảng viên/khách hàng), Scrum Master (điều phối), Dev Team (lập trình + kiểm thử). Làm 1 mình thì bạn đảm nhiệm cả 3 nhưng **vẫn ghi rõ trong báo cáo** để thấy mình đã áp dụng quy trình.
- **Artifacts:** Product Backlog (mục 8) → Sprint Backlog (`TODO_TICKET_IT.md`) → Increment (phần mềm chạy được).
- **Nghi thức:** Sprint Planning (đầu sprint) · Daily 15' (hôm qua/hôm nay/vướng mắc) · Sprint Review (demo cho PO) · Retrospective (1 điều làm tốt – 1 điều cần sửa).
- **Công cụ quản lý:** GitHub Projects / Trello (kanban: To Do → In Progress → Review → Done); mỗi task gắn nhãn `tru-1`, `tru-2`, `tru-3`.

**Definition of Done (DoD) — một task chỉ được coi là "Done" khi:**
1. Chạy đúng chức năng theo mô tả; đã kiểm thử thủ công **và** có test tự động (với service lõi).
2. `php artisan migrate:fresh --seed` chạy sạch từ đầu; có seeder/factory cho dữ liệu liên quan.
3. Có validate bằng Form Request; có phân quyền bằng Policy; không lộ ghi chú nội bộ ra ngoài.
4. Không phát sinh N+1 (kiểm tra Debugbar); truy vấn danh sách/báo cáo có index phù hợp.
5. UI hiển thị tiếng Việt, không lỗi console, dùng được trên màn hình 1366×768 trở lên.
6. Chạy `vendor/bin/pint` (không còn diff), commit message rõ ràng, cập nhật trạng thái trên backlog.
7. Nếu task chạm vào trụ kỹ thuật: **cập nhật số liệu đo (accuracy/SLA/routing) vào báo cáo thực nghiệm**.

**Quy ước Git đề xuất:** `main` (ổn định) · `develop` · nhánh `feature/tru1-classifier`, `feature/tru3-sla`… · commit theo mẫu `feat(sla): tinh due theo gio lam viec`.

---

## 13. Rủi ro & Cách giảm thiểu

| Rủi ro | Mức | Giảm thiểu |
|---|---|---|
| **Thiếu dữ liệu để train bộ phân loại** (accuracy thấp vì quá ít mẫu) | **Cao** | Seeder sinh 300–400 ticket có nhãn phân bố hợp lý; bảng so sánh accuracy theo số mẫu (50→300) để thấy xu hướng; nếu vẫn thấp thì hạ τ hoặc tăng trọng số lớp rule |
| Phạm vi phình to (KB, CSAT, IMAP, real-time) | **Cao** | Bám MoSCoW: cắt từ E13/E14/E11/E10; **không đụng** vào E4–E6 |
| Tính giờ làm việc sai (quên nghỉ trưa, ngày lễ, vắt qua cuối tuần) | **Cao** | Viết test cho BusinessTimeCalculator **trước** khi viết SlaService; test bằng dữ liệu ngày cụ thể (đã ghi ở Phụ lục A.3) |
| Scheduler không chạy (Windows không có cron) → demo "chết" | **Cao** | Ghi rõ checklist demo: chạy `schedule:work` hoặc Task Scheduler; viết lệnh `tickets:check-sla` **có thể chạy tay** để demo ngay |
| Phụ thuộc API LLM (chi phí/mạng/ảo giác) | Trung bình | Đặt sau feature flag `CLASSIFIER_LLM_ENABLED=false`; cache theo `sha1(text)`; **hệ thống phải chạy tốt khi không có mạng** |
| Hiệu năng báo cáo chậm khi dữ liệu lớn | Trung bình | Index theo bảng (mục 6), tránh `whereHas` nặng, có thể tạo bảng tổng hợp `ticket_metrics` cập nhật theo job |
| Tranh chấp dữ liệu khi 2 agent cùng nhận việc / claim trùng | Trung bình | Dùng transaction + `lockForUpdate()` (hoặc cột `locked_by`), test ca đồng thời |
| MySQL 8.4 khác biệt (utf8mb4, `caching_sha2_password`) | Thấp | Dùng `utf8mb4_unicode_ci`, user mặc định; test tạo ticket có emoji + tiếng Việt có dấu |
| Bảo mật: tải tệp độc hại, xem trộm ticket người khác | Trung bình | Validate MIME/kích thước, lưu ngoài `public/`, phục vụ qua route có Policy kiểm tra; rate limit; CSRF |
| Mất dữ liệu khi đang phát triển | Trung bình | Seeder tái tạo nhanh + `mysqldump` định kỳ; giữ file `database/data/*.csv` trong Git |
| Thời gian cuối kỳ không kịp viết báo cáo | **Cao** | Viết báo cáo song song từ Sprint 3 (mỗi trụ có sẵn bảng số liệu), dành hẳn Sprint 7 |

---

## 14. Checklist khởi động (làm ngay trong tuần này)

- [ ] Mở **Laragon → Start All** (Apache/Nginx + MySQL 8.4) và kiểm tra `http://localhost/phpmyadmin`.
- [ ] `cd K:\Apps\Laragon\www` → `composer create-project laravel/laravel ticket-it` → mở VS Code tại `K:\Apps\Laragon\www\ticket-it`.
- [ ] Tạo DB `ticket_it` (HeidiSQL) với `utf8mb4 / utf8mb4_unicode_ci`; cập nhật `.env` theo **Phụ lục A.1**.
- [ ] `php artisan key:generate && php artisan migrate` → truy cập `http://ticket-it.test` (hoặc `php artisan serve`).
- [ ] `git init` + commit khung dự án (kèm 3 file tài liệu: kế hoạch, phụ lục mã nguồn, TODO).
- [ ] Cài package theo mục 5 (chú ý **activitylog ^4.12**, không dùng 5.x trên PHP 8.3).
- [ ] Tạo bảng `business_calendars/hours/holidays` + **BusinessTimeCalculator** + viết **6 unit test** (Phụ lục A.3) — *đây là việc nên làm đầu tiên vì nó khoá chặt trụ 3*.
- [ ] Seeder danh mục (8 danh mục cha) + 5 nhóm IT + 8 agent có kỹ năng + lịch làm việc T2–T6 (08:00–12:00, 13:00–17:00) + vài ngày lễ.
- [ ] Chốt lại phạm vi MVP với người hướng dẫn (mục 1) và bắt đầu **Sprint 1** theo `TODO_TICKET_IT.md`.

---

## Tài liệu kèm theo

| Tệp | Nội dung |
|---|---|
| `KE_HOACH_HE_THONG_TICKET_IT.md` | Kế hoạch tổng thể (tài liệu này) |
| `PHU_LUC_MA_NGUON_TICKET_IT.md` | Mã nguồn mẫu: cấu hình, migration, BusinessTimeCalculator, SlaService, Command, Classifier, Routing, Observer, test |
| `TODO_TICKET_IT.md` | Danh sách công việc theo 8 sprint để tick tiến độ hằng ngày |

> **Ba câu hỏi nên chuẩn bị sẵn câu trả lời khi bảo vệ:**
> 1. *Vì sao dùng Naive Bayes mà không gọi AI/LLM?* → Vì chạy offline, miễn phí, giải thích được công thức, dữ liệu nhạy cảm không ra ngoài; LLM vẫn có nhưng chỉ là lớp dự phòng sau feature flag.
> 2. *Vì sao ngưỡng τ = 0.65?* → Vì đã đo trên tập test (bảng accuracy theo τ) và 0.65 cho cân bằng tốt nhất giữa automation rate và độ chính xác.
> 3. *Điểm khác biệt so với các hệ thống ticket khác?* → Không chỉ CRUD ticket mà có **bộ máy quyết định** (phân loại – định tuyến) và **bộ máy đo lường** theo giờ làm việc, có số liệu kiểm chứng (accuracy, SLA %, MTTR, P90).











