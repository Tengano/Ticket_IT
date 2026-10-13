# Phụ lục mã nguồn mẫu — Hệ thống Ticket hỗ trợ IT

> Tài liệu này chứa **mã nguồn mẫu cho các phần khó** (3 trụ kỹ thuật), viết theo đúng convention **Laravel 13 + PHP 8.3 + MySQL 8.4** để có thể tạo trực tiếp bằng `php artisan make:*` rồi dán phần thân vào.
> Kèm theo mỗi phần là: file cần tạo, lệnh artisan sinh file, và ghi chú kiểm thử.

## A.1. Cấu hình môi trường, scheduler và route

**`.env` (phần cần sửa so với mặc định):**
```dotenv
APP_NAME="IT Helpdesk"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://ticket-it.test
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_LOCALE=vi
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_it
DB_USERNAME=root
DB_PASSWORD=
# MySQL 8.4: charset/collation đặt trong config/database.php
# 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'

# Máy bạn KHÔNG có extension phpredis -> dùng database cho queue & cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# Mail dev: Mailpit của Laragon (SMTP 1025, UI 8025)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="helpdesk@ticket-it.test"
MAIL_FROM_NAME="${APP_NAME}"

# Cấu hình 3 trụ kỹ thuật
CLASSIFIER_AUTO_APPLY_THRESHOLD=0.65   # ngưỡng tau
CLASSIFIER_WEIGHT_RULE=0.55
CLASSIFIER_WEIGHT_NB=0.45
CLASSIFIER_MIN_SAMPLES_PER_CLASS=20
CLASSIFIER_LLM_ENABLED=false           # lớp C: tùy chọn
CLASSIFIER_LLM_MODEL=gemini-2.5-flash
GEMINI_API_KEY=
SLA_CHECK_WINDOW_MINUTES=5
ASSIGN_UNASSIGNED_AFTER_MINUTES_P1P2=15
ASSIGN_UNASSIGNED_AFTER_MINUTES_P3P4=60
```

**`config/helpdesk.php` (file cấu hình riêng cho đề tài):**
```php
<?php

return [
    'classifier' => [
        'threshold'        => (float) env('CLASSIFIER_AUTO_APPLY_THRESHOLD', 0.65),
        'weights'          => [
            'rule' => (float) env('CLASSIFIER_WEIGHT_RULE', 0.55),
            'nb'   => (float) env('CLASSIFIER_WEIGHT_NB', 0.45),
            'llm'  => 0.30,
        ],
        'min_samples'      => (int) env('CLASSIFIER_MIN_SAMPLES_PER_CLASS', 20),
        'llm_enabled'      => (bool) env('CLASSIFIER_LLM_ENABLED', false),
        'stopwords'        => ['va', 'cua', 'cho', 'toi', 'em', 'a', 'giup', 'bi', 'khong', 'duoc', 'nhu', 'voi', 'thi', 'la', 'co', 'nay', 'do'],
    ],
    'sla' => [
        'window_minutes'   => (int) env('SLA_CHECK_WINDOW_MINUTES', 5),
        'warning_percent'  => 80,
    ],
    'assignment' => [
        'unassigned_after' => [
            'P1' => (int) env('ASSIGN_UNASSIGNED_AFTER_MINUTES_P1P2', 15),
            'P2' => (int) env('ASSIGN_UNASSIGNED_AFTER_MINUTES_P1P2', 15),
            'P3' => (int) env('ASSIGN_UNASSIGNED_AFTER_MINUTES_P3P4', 60),
            'P4' => (int) env('ASSIGN_UNASSIGNED_AFTER_MINUTES_P3P4', 60),
        ],
        'weights' => ['skill' => 0.5, 'load' => 0.3, 'zone' => 0.2],
    ],
];
```

**`routes/console.php` (Laravel 13 — đăng ký scheduler tại đây):**
```php
<?php

use Illuminate\Support\Facades\Schedule;

// Trụ 3: quét SLA mỗi 5 phút (cảnh báo 80% + đánh dấu vi phạm + leo thang ticket treo)
Schedule::command('tickets:check-sla')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Trụ 1: train lại bộ phân loại khi có đủ nhãn mới (ban đêm)
Schedule::command('classifier:train --if-enough-labels')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Dọn model cũ, giữ 5 phiên bản gần nhất
Schedule::command('classifier:prune --keep=5')->weekly();
```

> **Chạy scheduler trên Windows:**
> - Khi dev/demo: mở terminal thứ 2 → `php artisan schedule:work`
> - Khi demo "chuyên nghiệp": tạo Task Scheduler chạy mỗi 1 phút → `K:\Apps\Laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan schedule:run`
> - Queue worker: `php artisan queue:work --tries=3` (terminal thứ 3)

**`routes/web.php` (khung route chính):**
```php
<?php

use App\Http\Controllers\{DashboardController, TicketController, TicketCommentController,
    TicketAttachmentController, ReportController, SlaPolicyController, TriageController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Ticket (Requester + Agent + Lead + Admin)
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])->name('tickets.attachments.store');
    Route::get('attachments/{attachment}', [TicketAttachmentController::class, 'download'])->name('attachments.download');
    Route::post('tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('tickets/{ticket}/rate', [TicketController::class, 'rate'])->name('tickets.rate');

    // Trụ 1: hàng đợi xác nhận phân loại (Triage)
    Route::middleware('can:assign-tickets')->group(function () {
        Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
        Route::post('triage/{ticket}/confirm', [TriageController::class, 'confirm'])->name('triage.confirm');
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    });

    // Báo cáo (Lead + Admin)
    Route::middleware('can:view-reports')->group(function () {
        Route::get('reports/sla', [ReportController::class, 'sla'])->name('reports.sla');
        Route::get('reports/sla/export/{format}', [ReportController::class, 'export'])->name('reports.sla.export');
        Route::resource('sla-policies', SlaPolicyController::class)->except('show');
    });
});
```

## A.2. Migration các bảng lõi

**Lệnh sinh file:**
```bat
php artisan make:migration create_org_tables            :: departments, locations, teams, skills, agent_zones
php artisan make:migration create_category_rule_tables  :: categories, team_route_rules, classification_rules, text_aliases
php artisan make:migration create_classifier_tables     :: classifier_models/classes/terms, ticket_classifications, classifier_evaluations
php artisan make:migration create_ticket_tables         :: tickets, ticket_comments, ticket_attachments, ticket_events, ticket_assignments
php artisan make:migration create_sla_tables            :: business_calendars, business_hours, holidays, sla_policies, ticket_sla, ticket_pauses, sla_events
php artisan make:migration add_helpdesk_columns_to_users_table
```

**`create_sla_tables` — lịch làm việc & SLA (trụ 3):**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_calendars', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('timezone', 64)->default('Asia/Ho_Chi_Minh');
            $t->timestamps();
        });

        // Nhiều khung/ngày: ví dụ thứ 2 có 08:00-12:00 và 13:00-17:00
        Schema::create('business_hours', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_calendar_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('weekday');   // 1 = Thứ 2 ... 7 = Chủ nhật
            $t->time('start_time');
            $t->time('end_time');
            $t->index(['business_calendar_id', 'weekday']);
        });

        Schema::create('holidays', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_calendar_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->string('name')->nullable();
            $t->unique(['business_calendar_id', 'date']);
        });

        Schema::create('sla_policies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('priority', 2)->nullable();                    // P1..P4 (null = mọi mức)
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $t->foreignId('business_calendar_id')->constrained();
            $t->unsignedSmallInteger('first_response_minutes');
            $t->unsignedSmallInteger('resolution_minutes');
            $t->unsignedTinyInteger('warning_percent')->default(80);
            $t->boolean('restart_on_reopen')->default(true);
            $t->unsignedTinyInteger('specificity')->default(0);       // càng lớn càng cụ thể -> ưu tiên
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ticket_sla', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('sla_policy_id')->constrained();
            $t->timestamp('first_response_due_at');
            $t->timestamp('resolution_due_at');
            $t->timestamp('warning_at');
            $t->timestamp('first_responded_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->unsignedInteger('paused_minutes')->default(0);
            $t->boolean('first_response_met')->nullable();
            $t->boolean('resolution_met')->nullable();
            $t->string('state', 16)->default('ok')->index();          // ok|warning|paused|breached|met
            $t->timestamp('breached_at')->nullable();
            $t->timestamp('warning_sent_at')->nullable();
            $t->timestamp('computed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ticket_pauses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $t->string('reason', 32);        // pending_user|pending_vendor|on_hold
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('minutes')->nullable();     // phút LÀM VIỆC bị tạm dừng
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['ticket_id', 'started_at']);
        });

        Schema::create('sla_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $t->string('type', 24);          // first_response|warning|breach|paused|resumed|met|escalated
            $t->string('target', 24)->nullable();    // first_response|resolution
            $t->timestamp('occurred_at');
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['ticket_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_events');
        Schema::dropIfExists('ticket_pauses');
        Schema::dropIfExists('ticket_sla');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('business_calendars');
    }
};
```

## A.3. BusinessTimeCalculator — trái tim của trụ "đo thời gian"

**Hai model cần có trước:** `BusinessCalendar` (hasMany `hours`, `holidays`) với cast `date` cho `holidays`.

```php
<?php
// app/Models/BusinessCalendar.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCalendar extends Model
{
    protected $fillable = ['name', 'timezone'];

    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
}
```

**`app/Services/Sla/BusinessTimeCalculator.php` (phần 1):**
```php
<?php

namespace App\Services\Sla;

use App\Models\BusinessCalendar;
use Carbon\CarbonImmutable;

/**
 * Tính thời gian theo GIỜ LÀM VIỆC:
 *  - bỏ cuối tuần & ngày lễ (theo business_calendars)
 *  - hỗ trợ NHIỀU khung giờ/ngày (sáng 08:00-12:00, chiều 13:00-17:00)
 *  - dùng đơn vị phút (int) để tránh sai số của float
 */
class BusinessTimeCalculator
{
    /** @var array<int, array<int, array{0:int,1:int}>> weekday (1=Mon..7=Sun) => [[từ phút, đến phút], ...] */
    private array $windows = [];

    /** @var array<string, true> ngày lễ dạng 'Y-m-d' */
    private array $holidays = [];

    public function __construct(BusinessCalendar $calendar)
    {
        foreach ($calendar->hours as $hour) {
            $this->windows[$hour->weekday][] = [
                $this->toMinutes($hour->start_time),
                $this->toMinutes($hour->end_time),
            ];
        }

        foreach ($this->windows as $weekday => $ranges) {
            usort($this->windows[$weekday], fn (array $a, array $b) => $a[0] <=> $b[0]);
        }

        foreach ($calendar->holidays as $holiday) {
            $this->holidays[$holiday->date->format('Y-m-d')] = true;
        }
    }

    public static function for(BusinessCalendar $calendar): self
    {
        return new self($calendar);
    }

    /**
     * Cộng $minutes phút LÀM VIỆC vào $start (nếu $start ngoài giờ làm việc thì nhảy vào đầu khung kế tiếp).
     */
    public function addWorkingMinutes(CarbonImmutable $start, int $minutes): CarbonImmutable
    {
        if ($minutes <= 0) {
            return $start;
        }

        $remaining = $minutes;
        $cursor    = $start;
        $guard     = 0;

        while ($remaining > 0 && $guard++ < 3660) {          // guard ~10 năm, chống lặp vô hạn khi lịch cấu hình sai
            $dayStart = $cursor->startOfDay();

            foreach ($this->rangesFor($cursor) as [$from, $to]) {
                $fromAt = $dayStart->addMinutes($from);
                $toAt   = $dayStart->addMinutes($to);

                if ($cursor->greaterThanOrEqualTo($toAt)) {
                    continue;                                // đã qua khung này trong ngày
                }

                if ($cursor->lessThan($fromAt)) {
                    $cursor = $fromAt;                       // ngoài giờ làm việc -> nhảy vào đầu khung
                }

                $available = (int) $cursor->diffInMinutes($toAt);

                if ($available >= $remaining) {
                    return $cursor->addMinutes($remaining);  // đủ phút ngay trong khung này
                }

                $remaining -= $available;
                $cursor     = $toAt;                         // hết khung -> sang khung kế tiếp
            }

            $cursor = $cursor->startOfDay()->addDay();       // hết ngày -> sang ngày kế tiếp (tự bỏ T7/CN/lễ)
        }

        return $cursor;
    }
}
```

**Bổ sung vào `BusinessTimeCalculator`: 3 method còn lại (đặt sau `addWorkingMinutes()`, ngay trước dấu `}` cuối cùng của class):**
```php
    /**
     * Đếm số phút LÀM VIỆC trong khoảng [a, b]. Dùng cho tạm dừng SLA và tính KPI.
     */
    public function workingMinutesBetween(CarbonImmutable $a, CarbonImmutable $b): int
    {
        if (! $b->greaterThan($a)) {
            return 0;
        }

        $total  = 0;
        $cursor = $a;
        $guard  = 0;

        while ($cursor->lessThan($b) && $guard++ < 3660) {
            $dayStart = $cursor->startOfDay();

            foreach ($this->rangesFor($cursor) as [$from, $to]) {
                $fromAt = $dayStart->addMinutes($from);
                $toAt   = $dayStart->addMinutes($to);

                if ($cursor->greaterThanOrEqualTo($toAt) || $b->lessThanOrEqualTo($fromAt)) {
                    continue;                                // khung này không giao với [a, b]
                }

                $overlapStart = $cursor->greaterThan($fromAt) ? $cursor : $fromAt;
                $overlapEnd   = $b->lessThan($toAt) ? $b : $toAt;

                if ($overlapEnd->greaterThan($overlapStart)) {
                    $total += (int) $overlapStart->diffInMinutes($overlapEnd);
                }

                if ($b->lessThanOrEqualTo($toAt)) {
                    return $total;                           // đã bao hết khoảng cần đếm
                }

                $cursor = $toAt;
            }

            $cursor = $cursor->startOfDay()->addDay();
        }

        return $total;
    }

    /** Có đang trong giờ làm việc không? (dùng để hiển thị "ngoài giờ làm việc"). */
    public function isWorkingMoment(CarbonImmutable $moment): bool
    {
        $dayStart = $moment->startOfDay();

        foreach ($this->rangesFor($moment) as [$from, $to]) {
            if ($moment->greaterThanOrEqualTo($dayStart->addMinutes($from))
                && $moment->lessThan($dayStart->addMinutes($to))) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, array{0:int,1:int}> */
    private function rangesFor(CarbonImmutable $moment): array
    {
        if (isset($this->holidays[$moment->format('Y-m-d')])) {
            return [];                                       // ngày lễ -> ngày không làm việc
        }

        return $this->windows[$moment->dayOfWeekIso] ?? [];  // 1=Mon ... 7=Sun
    }

    private function toMinutes(string $time): int
    {
        return ((int) substr($time, 0, 2)) * 60 + ((int) substr($time, 3, 2));
    }
```

**Ghi chú kiểm thử (làm trước khi viết SlaService):**
- Ví dụ lịch: T2–T6, khung 08:00–12:00 & 13:00–17:00 (**480 phút/ngày**); `2026-01-01` là ngày lễ.
- `addWorkingMinutes('2026-10-16 16:30', 120)` → `2026-10-19 09:30` (vắt qua cuối tuần).
- `addWorkingMinutes('2026-10-17 09:00', 60)` (thứ 7) → `2026-10-19 09:00`.
- `addWorkingMinutes('2026-10-16 11:30', 120)` → `2026-10-16 14:30` (vắt qua nghỉ trưa).
- `workingMinutesBetween('2026-10-16 16:00', '2026-10-19 10:00')` → `60 + 120 = 180`.
- `workingMinutesBetween('2026-10-16 17:00', '2026-10-19 08:00')` → `0`.

## A.4. SlaService — tạo, tạm dừng, khôi phục và chốt SLA

```php
<?php
// app/Services/Sla/SlaService.php
namespace App\Services\Sla;

use App\Enums\SlaState;
use App\Models\SlaEvent;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketPause;
use App\Models\TicketSla;
use Carbon\CarbonImmutable;

class SlaService
{
    /** Chọn policy khớp nhất và khởi tạo đồng hồ SLA cho ticket. */
    public function start(Ticket $ticket, ?CarbonImmutable $at = null): TicketSla
    {
        $at     = $at ?? CarbonImmutable::now();
        $policy = SlaPolicy::matchFor($ticket);                 // xem Phụ lục A.5
        $calc   = BusinessTimeCalculator::for($policy->calendar);

        $firstDue   = $calc->addWorkingMinutes($at, $policy->first_response_minutes);
        $resDue     = $calc->addWorkingMinutes($at, $policy->resolution_minutes);
        $warningMin = (int) round($policy->resolution_minutes * $policy->warning_percent / 100);
        $warningAt  = $calc->addWorkingMinutes($at, $warningMin);

        return TicketSla::create([
            'ticket_id'             => $ticket->id,
            'sla_policy_id'         => $policy->id,
            'first_response_due_at' => $firstDue,
            'resolution_due_at'     => $resDue,
            'warning_at'            => $warningAt,
            'state'                 => SlaState::Ok->value,
            'computed_at'           => $at,
        ]);
    }

    /** Ghi nhận phản hồi công khai đầu tiên của agent -> chốt chỉ số FRT. */
    public function markFirstResponse(Ticket $ticket, ?CarbonImmutable $at = null): void
    {
        $at  = $at ?? CarbonImmutable::now();
        $sla = $ticket->sla;

        if (! $ticket->first_responded_at) {
            $ticket->forceFill(['first_responded_at' => $at])->save();
        }

        if (! $sla || $sla->first_responded_at) {
            return;
        }

        $sla->update([
            'first_responded_at' => $at,
            'first_response_met' => $at->lessThanOrEqualTo($sla->first_response_due_at),
        ]);
    }

    /** Tạm dừng đồng hồ khi ticket chuyển sang "chờ người dùng/nhà cung cấp". */
    public function pause(Ticket $ticket, string $reason, ?int $actorId = null): ?TicketPause
    {
        $sla = $ticket->sla;

        if (! $sla || in_array($sla->state, [SlaState::Met->value, SlaState::Breached->value], true)) {
            return null;
        }

        $pause = TicketPause::create([
            'ticket_id'  => $ticket->id,
            'reason'     => $reason,                 // pending_user|pending_vendor|on_hold
            'started_at' => now(),
            'created_by' => $actorId,
        ]);

        $sla->update(['state' => SlaState::Paused->value]);

        return $pause;
    }

    /** Quay lại xử lý: cộng dồn phút làm việc đã tạm dừng và DỜI các mốc SLA tương ứng. */
    public function resume(Ticket $ticket): void
    {
        $sla   = $ticket->sla;
        $pause = $ticket->pauses()->whereNull('ended_at')->latest('started_at')->first();

        if (! $sla || ! $pause) {
            return;
        }

        $now  = CarbonImmutable::now();
        $calc = BusinessTimeCalculator::for($sla->policy->calendar);

        $minutes = $calc->workingMinutesBetween(CarbonImmutable::parse($pause->started_at), $now);

        $pause->update(['ended_at' => $now, 'minutes' => $minutes]);

        $sla->update([
            'first_response_due_at' => $sla->first_responded_at
                ? $sla->first_response_due_at                                       // đã phản hồi -> không cần dời
                : $calc->addWorkingMinutes(CarbonImmutable::parse($sla->first_response_due_at), $minutes),
            'resolution_due_at' => $calc->addWorkingMinutes(CarbonImmutable::parse($sla->resolution_due_at), $minutes),
            'warning_at'        => $calc->addWorkingMinutes(CarbonImmutable::parse($sla->warning_at), $minutes),
            'paused_minutes'    => $sla->paused_minutes + $minutes,
            'state'             => SlaState::Ok->value,
            'computed_at'       => $now,
        ]);

        $ticket->increment('paused_minutes', $minutes);
    }

    /** Agent đánh dấu đã giải quyết -> chốt TTR và trạng thái đạt/không đạt SLA. */
    public function resolve(Ticket $ticket, ?CarbonImmutable $at = null): void
    {
        $at  = $at ?? CarbonImmutable::now();
        $sla = $ticket->sla;

        $ticket->forceFill([
            'resolved_at'      => $at,
            'handling_minutes' => $ticket->assigned_at
                ? BusinessTimeCalculator::for($sla->policy->calendar)
                    ->workingMinutesBetween(CarbonImmutable::parse($ticket->assigned_at), $at)
                : 0,
        ])->save();

        if (! $sla) {
            return;
        }

        $sla->update([
            'resolved_at'    => $at,
            'resolution_met' => $sla->state !== SlaState::Breached->value
                                && $at->lessThanOrEqualTo($sla->resolution_due_at),
            'state'          => SlaState::Met->value,
            'computed_at'    => $at,
        ]);

        SlaEvent::create([
            'ticket_id'   => $ticket->id,
            'type'        => 'met',
            'target'      => 'resolution',
            'occurred_at' => $at,
            'meta'        => ['resolution_met' => $sla->fresh()->resolution_met],
        ]);
    }

    /** Số phút làm việc còn lại tới mốc giải quyết (âm = đã trễ). Dùng cho badge đếm ngược. */
    public function remainingWorkingMinutes(Ticket $ticket): ?int
    {
        $sla = $ticket->sla;

        if (! $sla || $sla->state === SlaState::Met->value) {
            return null;
        }

        $calc = BusinessTimeCalculator::for($sla->policy->calendar);
        $due  = CarbonImmutable::parse($sla->resolution_due_at);
        $now  = CarbonImmutable::now();

        return $now->lessThan($due)
            ? $calc->workingMinutesBetween($now, $due)
            : -1 * $calc->workingMinutesBetween($due, $now);
    }
}
```

> ⚠️ **Bổ sung cột cần thiết:** code trên dùng `tickets.assigned_at` (mốc gán **đầu tiên**, phục vụ chỉ số TTA). Hãy thêm vào migration `tickets`:
> ```php
> $t->timestamp('assigned_at')->nullable()->index();
> ```

**`SlaPolicy::matchFor()` — chọn chính sách cụ thể nhất (policy ưu tiên cao hơn policy chung):**
```php
<?php
// app/Models/SlaPolicy.php  (phần method quan trọng)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPolicy extends Model
{
    protected $fillable = [
        'name', 'priority', 'category_id', 'team_id', 'business_calendar_id',
        'first_response_minutes', 'resolution_minutes', 'warning_percent',
        'restart_on_reopen', 'specificity', 'is_active',
    ];

    protected $casts = [
        'warning_percent'    => 'integer',
        'restart_on_reopen'  => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(BusinessCalendar::class, 'business_calendar_id')
            ->with(['hours', 'holidays']);          // nạp sẵn để BusinessTimeCalculator không query lẻ
    }

    /** Chọn policy khớp nhất cho ticket: ưu tiên policy có nhiều tiêu chí khớp nhất (specificity cao nhất). */
    public static function matchFor(Ticket $ticket): self
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($q) use ($ticket) {
                $q->whereNull('priority')->orWhere('priority', $ticket->priority);
            })
            ->where(function ($q) use ($ticket) {
                $q->whereNull('category_id')->orWhere('category_id', $ticket->category_id);
            })
            ->where(function ($q) use ($ticket) {
                $q->whereNull('team_id')->orWhere('team_id', $ticket->assigned_team_id);
            })
            ->orderByRaw('(
                (CASE WHEN priority    IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN category_id IS NOT NULL THEN 2 ELSE 0 END) +
                (CASE WHEN team_id     IS NOT NULL THEN 4 ELSE 0 END)
            ) DESC')
            ->orderBy('resolution_minutes')
            ->firstOrFail();
    }
}
```

## A.5. CheckSlaCommand — "nhịp tim" của hệ thống SLA

```php
<?php
// app/Console/Commands/CheckSlaCommand.php
namespace App\Console\Commands;

use App\Enums\SlaState;
use App\Enums\TicketStatus;
use App\Models\SlaEvent;
use App\Models\Ticket;
use App\Models\TicketSla;
use App\Notifications\SlaBreachedNotification;
use App\Notifications\SlaWarningNotification;
use App\Services\Routing\RoutingService;
use App\Services\Sla\BusinessTimeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckSlaCommand extends Command
{
    protected $signature = 'tickets:check-sla {--dry-run : Chỉ in ra, không ghi dữ liệu}';

    protected $description = 'Quét SLA: cảnh báo mốc 80%, đánh dấu vi phạm, leo thang ticket chưa có người nhận';

    public function handle(RoutingService $routing): int
    {
        $now       = CarbonImmutable::now();
        $dry       = (bool) $this->option('dry-run');
        $warned    = 0;
        $breached  = 0;
        $escalated = 0;

        TicketSla::query()
            ->with(['policy.calendar', 'ticket.assignee', 'ticket.assignedTeam.lead'])
            ->whereNotIn('state', [SlaState::Met->value, SlaState::Breached->value])
            ->whereHas('ticket', fn ($q) => $q
                ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value]))
            ->chunkById(200, function ($rows) use ($now, $dry, &$warned, &$breached) {
                foreach ($rows as $sla) {
                    $ticket = $sla->ticket;

                    if (! $ticket || $sla->state === SlaState::Paused->value) {
                        continue;                            // đang chờ người dùng -> đồng hồ đứng yên
                    }

                    // (1) Cảnh báo mốc 80%
                    if (! $sla->warning_sent_at && $now->greaterThanOrEqualTo($sla->warning_at)) {
                        $this->line("  [CẢNH BÁO] {$ticket->code} – sắp tới hạn SLA");
                        $warned++;

                        if (! $dry) {
                            $sla->update([
                                'warning_sent_at' => $now,
                                'state'           => SlaState::Warning->value,
                                'computed_at'     => $now,
                            ]);

                            SlaEvent::create([
                                'ticket_id'   => $ticket->id,
                                'type'        => 'warning',
                                'target'      => 'resolution',
                                'occurred_at' => $now,
                                'meta'        => ['due_at' => (string) $sla->resolution_due_at],
                            ]);

                            $ticket->assignee?->notify(new SlaWarningNotification($ticket, 'resolution'));

                            if (in_array($ticket->priority, ['P1', 'P2'], true)) {
                                $ticket->assignedTeam?->lead?->notify(new SlaWarningNotification($ticket, 'resolution'));
                            }
                        }
                    }

                    // (2) Vi phạm SLA
                    if ($now->greaterThanOrEqualTo($sla->resolution_due_at)) {
                        $this->line("  [VI PHẠM] {$ticket->code} – quá hạn giải quyết");
                        $breached++;

                        if (! $dry) {
                            $sla->update([
                                'state'          => SlaState::Breached->value,
                                'breached_at'    => $now,
                                'resolution_met' => false,
                                'computed_at'    => $now,
                            ]);

                            SlaEvent::create([
                                'ticket_id'   => $ticket->id,
                                'type'        => 'breach',
                                'target'      => 'resolution',
                                'occurred_at' => $now,
                                'meta'        => ['due_at' => (string) $sla->resolution_due_at],
                            ]);

                            $ticket->assignee?->notify(new SlaBreachedNotification($ticket, 'resolution'));
                            $ticket->assignedTeam?->lead?->notify(new SlaBreachedNotification($ticket, 'resolution'));
                        }
                    }
                }
            });

        // (3) Ticket đã vào nhóm nhưng chưa có người nhận quá lâu -> leo thang + tự gán
        $limits = config('helpdesk.assignment.unassigned_after');

        Ticket::query()
            ->whereNull('assignee_id')
            ->whereNotNull('assigned_team_id')
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->with(['sla.policy.calendar', 'assignedTeam.lead'])
            ->chunkById(200, function ($tickets) use ($now, $limits, $routing, $dry, &$escalated) {
                foreach ($tickets as $ticket) {
                    $calc   = $ticket->sla ? BusinessTimeCalculator::for($ticket->sla->policy->calendar) : null;
                    $limit  = $limits[$ticket->priority] ?? 60;
                    $waited = $calc
                        ? $calc->workingMinutesBetween($ticket->created_at->toImmutable(), $now)
                        : (int) $ticket->created_at->diffInMinutes($now);

                    if ($waited < $limit) {
                        continue;
                    }

                    $this->line("  [LEO THANG] {$ticket->code} – chờ {$waited} phút chưa có người nhận");
                    $escalated++;

                    if ($dry) {
                        continue;
                    }

                    $ticket->events()->create([
                        'type' => 'escalated',
                        'meta' => ['waited_minutes' => $waited, 'limit' => $limit],
                    ]);

                    $ticket->assignedTeam?->lead?->notify(new SlaWarningNotification($ticket, 'unassigned'));

                    // tự gán theo chiến lược của nhóm (ghi vết vào ticket_assignments)
                    $routing->assign($ticket, strategy: $ticket->assignedTeam?->assignment_strategy);
                }
            });

        $this->info("Quét SLA xong: {$warned} cảnh báo, {$breached} vi phạm, {$escalated} leo thang.");

        return self::SUCCESS;
    }
}
```

> `--dry-run` rất hữu ích khi demo/báo cáo: in ra danh sách ticket sắp tới hạn & vi phạm **mà không làm thay đổi dữ liệu**.

**Notification mẫu (database + mail):**
```php
<?php
// app/Notifications/SlaWarningNotification.php
namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $target) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'code'      => $this->ticket->code,
            'subject'   => $this->ticket->subject,
            'priority'  => $this->ticket->priority,
            'target'    => $this->target,
            'due_at'    => optional($this->ticket->sla)->resolution_due_at?->toDateTimeString(),
            'url'       => route('tickets.show', $this->ticket),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[IT Helpdesk] Sắp tới hạn SLA – {$this->ticket->code}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Ticket {$this->ticket->code} ({$this->ticket->priority}) đã đạt mốc cảnh báo SLA.")
            ->line("Tiêu đề: {$this->ticket->subject}")
            ->action('Mở ticket', route('tickets.show', $this->ticket))
            ->line('Vui lòng xử lý trước mốc thời gian cam kết.');
    }
}
```

## A.6. TRỤ 1 — Tokenizer & Naive Bayes (PHP thuần, không cần thư viện ML)

**`app/Services/Classification/Tokenizer.php`:**
```php
<?php

namespace App\Services\Classification;

use Illuminate\Support\Str;

/**
 * Tiền xử lý văn bản tiếng Việt:
 *  - bỏ HTML, ký tự đặc biệt, gộp khoảng trắng, lowercase
 *  - tách token ở CẢ HAI dạng: có dấu và không dấu (Str::ascii)
 *      -> "mật khẩu" và "mat khau" đều khớp
 *  - loại stopwords, sinh thêm bigram ("may_in", "mat_khau")
 */
class Tokenizer
{
    /** @var array<int, string> */
    private array $stopwords;

    /** @param array<int, string>|null $stopwords */
    public function __construct(?array $stopwords = null)
    {
        $this->stopwords = $stopwords ?? config('helpdesk.classifier.stopwords', []);
    }

    /** @return array<int, string> */
    public function tokenize(string $text): array
    {
        $clean = Str::of($text)
            ->replaceMatches('/<[^>]+>/', ' ')                  // bỏ thẻ HTML
            ->replaceMatches('/[^\p{L}\p{N}\s]/u', ' ')         // bỏ ký tự đặc biệt (giữ chữ/số Unicode)
            ->lower()
            ->squish()
            ->toString();

        $ascii = Str::of(Str::ascii($clean))->lower()->squish()->toString();

        $words = [];
        foreach (array_unique([$clean, $ascii]) as $variant) {
            foreach (preg_split('/\s+/', $variant) ?: [] as $word) {
                if ($word === '' || mb_strlen($word) < 2) {
                    continue;
                }

                // so stopwords ở dạng KHÔNG DẤU để loại được cả "và" và "va"
                if (in_array(Str::ascii($word), $this->stopwords, true)) {
                    continue;
                }

                $words[] = $word;
            }
        }

        $tokens = array_values(array_unique($words));

        // Bigram lấy từ bản không dấu -> bắt cụm "may_in", "mat_khau_wifi"
        $asciiWords = array_values(array_filter(
            preg_split('/\s+/', $ascii) ?: [],
            fn (string $w) => $w !== '' && mb_strlen($w) >= 2 && ! in_array($w, $this->stopwords, true)
        ));

        for ($i = 0, $n = count($asciiWords); $i < $n - 1; $i++) {
            $tokens[] = $asciiWords[$i].'_'.$asciiWords[$i + 1];
        }

        return array_values(array_unique($tokens));
    }
}
```

**`app/Services/Classification/NaiveBayesClassifier.php` (phần train):**
```php
<?php

namespace App\Services\Classification;

use App\Models\ClassifierModel;
use App\Models\ClassifierTerm;
use Illuminate\Support\Facades\DB;

/**
 * Naive Bayes đa thức (multinomial) với Laplace smoothing α = 1.
 * Toàn bộ model lưu trong DB để có thể version hoá, đánh giá và rollback.
 */
class NaiveBayesClassifier
{
    /**
     * @param  array<int, array{tokens: array<int, string>, category_id: int}>  $samples
     */
    public function train(array $samples, string $version): ClassifierModel
    {
        $docCount  = [];   // số văn bản theo lớp
        $termCount = [];   // count(term, class)
        $termTotal = [];   // tổng số lần xuất hiện term của lớp

        foreach ($samples as $sample) {
            $category = $sample['category_id'];
            $docCount[$category]  = ($docCount[$category] ?? 0) + 1;
            $termTotal[$category] = $termTotal[$category] ?? 0;

            foreach (array_unique($sample['tokens']) as $token) {   // đếm theo kiểu "có/không" để tránh 1 từ lặp lấn át
                $termCount[$category][$token] = ($termCount[$category][$token] ?? 0) + 1;
                $termTotal[$category]++;
            }
        }

        $totalDocs = array_sum($docCount);

        $vocab = [];
        foreach ($termCount as $terms) {
            foreach (array_keys($terms) as $term) {
                $vocab[$term] = true;
            }
        }
        $vocabSize = count($vocab);

        return DB::transaction(function () use ($samples, $version, $docCount, $termCount, $termTotal, $totalDocs, $vocabSize) {
            /** @var ClassifierModel $model */
            $model = ClassifierModel::create([
                'version'    => $version,
                'algorithm'  => 'multinomial_nb',
                'samples'    => count($samples),
                'vocab_size' => $vocabSize,
                'params'     => ['alpha' => 1, 'tokenizer' => 'unicode-accent-folded+bigram'],
                'trained_at' => now(),
            ]);

            foreach ($docCount as $category => $n) {
                $model->classes()->create([
                    'category_id' => $category,
                    'doc_count'   => $n,
                    'term_total'  => $termTotal[$category],
                    'log_prior'   => log($n / max(1, $totalDocs)),
                ]);
            }

            $rows = [];
            foreach ($termCount as $category => $terms) {
                $denominator = $termTotal[$category] + $vocabSize;      // Laplace: +|V|
                foreach ($terms as $term => $count) {
                    $rows[] = [
                        'classifier_model_id' => $model->id,
                        'category_id'         => $category,
                        'term'                => $term,
                        'count'               => $count,
                        'log_prob'            => log(($count + 1) / $denominator),
                    ];
                }
            }

            foreach (array_chunk($rows, 1000) as $chunk) {              // insert theo lô cho nhanh
                ClassifierTerm::insert($chunk);
            }

            return $model;
        });
    }
}
```

**`NaiveBayesClassifier` (phần dự đoán — thêm vào class trên):**
```php
    /**
     * @param  array<int, string>  $tokens
     * @return array{category_id: int|null, confidence: float, probabilities: array<int, float>}
     */
    public function predict(ClassifierModel $model, array $tokens): array
    {
        $tokens    = array_values(array_unique($tokens));
        $vocabSize = max(1, (int) $model->vocab_size);
        $classes   = $model->classes()->get();

        if ($classes->isEmpty()) {
            return ['category_id' => null, 'confidence' => 0.0, 'probabilities' => []];
        }

        // 1 query duy nhất: lấy log_prob của các token có mặt trong văn bản
        $lookup = $model->terms()
            ->whereIn('term', $tokens)
            ->get(['category_id', 'term', 'log_prob'])
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->pluck('log_prob', 'term'));

        $logScores = [];

        foreach ($classes as $class) {
            $score      = (float) $class->log_prior;                       // log P(c)
            $termProbs  = $lookup[$class->category_id] ?? collect();
            $denominator = $class->term_total + $vocabSize;
            $unknown     = log(1 / $denominator);                          // từ chưa từng thấy trong lớp này

            foreach ($tokens as $token) {
                $score += (float) ($termProbs[$token] ?? $unknown);        // Σ log P(t|c)
            }

            $logScores[(int) $class->category_id] = $score;
        }

        // Softmax (trừ max để tránh tràn số) -> xác suất trong [0,1]
        $max = max($logScores);
        $exp = array_map(fn (float $s) => exp($s - $max), $logScores);
        $sum = array_sum($exp) ?: 1.0;

        $probabilities = [];
        foreach ($exp as $categoryId => $value) {
            $probabilities[(int) $categoryId] = $value / $sum;
        }

        arsort($probabilities);
        $topCategory = array_key_first($probabilities);

        return [
            'category_id'   => $topCategory !== null ? (int) $topCategory : null,
            'confidence'    => round((float) ($probabilities[$topCategory] ?? 0.0), 4),
            'probabilities' => $probabilities,
        ];
    }
```

**`app/Services/Classification/RuleClassifier.php` (lớp A — giải thích được):**
```php
<?php

namespace App\Services\Classification;

use App\Models\ClassificationRule;
use Illuminate\Support\Str;

/**
 * Lớp A: khớp từ khóa/regex có trọng số.
 * Trả về điểm theo danh mục + DANH SÁCH TỪ KHÓA ĐÃ KHỚP (để hiển thị "vì sao hệ thống đoán như vậy").
 */
class RuleClassifier
{
    /**
     * @return array{scores: array<int, float>, matched: array<int, array<int, string>>, boost_priority: bool, team_id: int|null}
     */
    public function classify(string $text): array
    {
        $haystack    = ' '.Str::of(Str::lower($text))->squish().' ';
        $haystackAsc = ' '.Str::of(Str::ascii(Str::lower($text)))->squish().' ';

        $scores = [];
        $matched = [];
        $boostPriority = false;
        $teamId = null;

        $rules = ClassificationRule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $weight = (float) $rule->weight;
            $hits   = [];

            foreach ((array) $rule->keywords as $keyword) {
                $needle    = Str::lower(trim((string) $keyword));
                $needleAsc = Str::ascii($needle);

                if ($needle === '') {
                    continue;
                }

                if (str_contains($haystack, ' '.$needle.' ') || str_contains($haystackAsc, ' '.$needleAsc.' ')) {
                    $hits[] = $keyword;
                }
            }

            if ($rule->regex && @preg_match('/'.$rule->regex.'/iu', $text) === 1) {
                $hits[] = "regex:{$rule->regex}";
            }

            if (! $hits) {
                continue;
            }

            $scores[$rule->category_id] = ($scores[$rule->category_id] ?? 0) + $weight * count($hits);
            $matched[$rule->category_id] = array_merge($matched[$rule->category_id] ?? [], $hits);

            if ($rule->is_urgent_signal || $rule->priority_boost > 0) {
                $boostPriority = true;
            }

            $teamId ??= $rule->team_id;
        }

        return [
            'scores'         => $scores,
            'matched'        => $matched,
            'boost_priority' => $boostPriority,
            'team_id'        => $teamId,
        ];
    }
}
```

## A.7. ClassificationService — hợp nhất 3 lớp & lưu vết dự đoán

```php
<?php
// app/Services/Classification/ClassificationService.php
namespace App\Services\Classification;

use App\Models\ClassifierModel;
use App\Models\Ticket;
use App\Models\TicketClassification;

class ClassificationService
{
    public function __construct(
        private Tokenizer $tokenizer,
        private RuleClassifier $ruleClassifier,
        private NaiveBayesClassifier $bayes,
        private LlmClassifier $llm,
    ) {}

    /**
     * @return array{
     *   category_id: int|null, confidence: float, source: string, needs_triage: bool,
     *   scores: array<int, float>, matched: array<int, string>, boost_priority: bool,
     *   team_id: int|null, model_id: int|null, model_version: string|null
     * }
     */
    public function classify(string $text, ?Ticket $ticket = null): array
    {
        $weights   = config('helpdesk.classifier.weights', ['rule' => 0.55, 'nb' => 0.45, 'llm' => 0.30]);
        $threshold = (float) config('helpdesk.classifier.threshold', 0.65);

        // ---------- Lớp A: luật từ khóa ----------
        $rule       = $this->ruleClassifier->classify($text);
        $ruleScores = $this->normalize($rule['scores']);

        // ---------- Lớp B: Naive Bayes (nếu đã có model active) ----------
        $model    = ClassifierModel::active();
        $tokens   = $this->tokenizer->tokenize($text);
        $nbScores = [];

        if ($model && $model->classes()->exists()) {
            $nbScores = $this->bayes->predict($model, $tokens)['probabilities'];
        }

        $combined = $this->combine($ruleScores, $nbScores, (float) $weights['rule'], (float) $weights['nb']);
        $source   = $nbScores ? 'hybrid' : 'rule';

        arsort($combined);
        $topId    = $combined ? (int) array_key_first($combined) : null;
        $topScore = $topId !== null ? (float) $combined[$topId] : 0.0;

        // ---------- Lớp C: LLM chỉ khi A + B chưa đủ tự tin ----------
        if ($topScore < $threshold && config('helpdesk.classifier.llm_enabled')) {
            $llmResult = $this->llm->classify($text, (string) config('helpdesk.classifier.llm_model'));

            if (! empty($llmResult['category_id'])) {
                $combined = $this->combine($combined, [$llmResult['category_id'] => 1.0], 1.0, (float) $weights['llm']);
                $source   = 'llm';
                arsort($combined);
                $topId    = (int) array_key_first($combined);
                $topScore = (float) $combined[$topId];
            }
        }

        $result = [
            'category_id'    => $topId,
            'confidence'     => round($topScore, 4),
            'source'         => $source,
            'needs_triage'   => $topScore < $threshold,
            'scores'         => array_slice($combined, 0, 5, true),      // top 5 để hiển thị gợi ý
            'matched'        => $rule['matched'][$topId] ?? [],          // giải thích "vì sao"
            'boost_priority' => (bool) $rule['boost_priority'],
            'team_id'        => $rule['team_id'],
            'model_id'       => $model?->id,
            'model_version'  => $model?->version,
        ];

        if ($ticket) {
            $this->persist($ticket, $result);
        }

        return $result;
    }

    /** Chuẩn hoá điểm rule về [0,1] (chia cho điểm cao nhất). */
    private function normalize(array $scores): array
    {
        if (! $scores) {
            return [];
        }

        $max = max($scores);

        return $max > 0 ? array_map(fn ($s) => $s / $max, $scores) : [];
    }

    /** Hợp nhất 2 phân bố theo trọng số, giữ hợp các danh mục của cả hai. */
    private function combine(array $a, array $b, float $weightA, float $weightB): array
    {
        $combined = [];

        foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $categoryId) {
            $combined[(int) $categoryId] = $weightA * (float) ($a[$categoryId] ?? 0)
                                         + $weightB * (float) ($b[$categoryId] ?? 0);
        }

        return $combined;
    }

    /** Lưu mọi dự đoán vào ticket_classifications -> nguồn dữ liệu cho đánh giá & học lại. */
    private function persist(Ticket $ticket, array $result): void
    {
        TicketClassification::create([
            'ticket_id'             => $ticket->id,
            'classifier_model_id'   => $result['model_id'],
            'predicted_category_id' => $result['category_id'],
            'confidence'            => $result['confidence'],
            'source'                => $result['source'],
            'scores'                => $result['scores'],
            'matched_keywords'      => $result['matched'],
        ]);

        $ticket->forceFill([
            'ai_confidence'  => $result['confidence'],
            'ai_category_id' => $result['category_id'],
            'needs_triage'   => $result['needs_triage'],
        ]);
    }
}
```

**`app/Models/ClassifierModel.php` (quản lý phiên bản model — hỗ trợ rollback & "chỉ kích hoạt nếu tốt hơn"):**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassifierModel extends Model
{
    protected $fillable = [
        'version', 'algorithm', 'samples', 'vocab_size', 'accuracy', 'macro_f1',
        'params', 'is_active', 'trained_at',
    ];

    protected $casts = [
        'params'     => 'array',
        'is_active'  => 'boolean',
        'trained_at' => 'datetime',
        'accuracy'   => 'float',
        'macro_f1'   => 'float',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(ClassifierClass::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(ClassifierTerm::class);
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->latest('trained_at')->first();
    }

    public static function nextVersion(): string
    {
        return 'v'.(static::query()->count() + 1);
    }

    /** Chỉ kích hoạt model mới nếu accuracy không thấp hơn model đang dùng. */
    public function activateIfBetter(): bool
    {
        $current = static::active();

        if ($current && $current->id !== $this->id && ($this->accuracy ?? 0) < ($current->accuracy ?? 0)) {
            return false;
        }

        static::query()->where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);

        return true;
    }
}
```

**`app/Services/Classification/LlmClassifier.php` (lớp C — tùy chọn, có cache & fallback an toàn):**
```php
<?php

namespace App\Services\Classification;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lớp C: gọi LLM (Gemini/OpenAI) khi lớp A+B không đủ tự tin.
 * - Chỉ chạy khi CLASSIFIER_LLM_ENABLED=true (mặc định false -> hệ thống vẫn hoạt động offline).
 * - Cache theo sha1(text) để tiết kiệm chi phí.
 * - Mọi lỗi đều trả về rỗng (không làm sập luồng tạo ticket).
 */
class LlmClassifier
{
    /**
     * @return array{category_id: int|null, confidence: float, reason: string|null}
     */
    public function classify(string $text, string $model = 'gemini-2.5-flash'): array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return ['category_id' => null, 'confidence' => 0.0, 'reason' => 'missing_api_key'];
        }

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->all();

        $cacheKey = 'llm_classify:'.sha1($model.'|'.$text.'|'.implode(',', array_keys($categories)));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($text, $model, $apiKey, $categories) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [[
                            'parts' => [[
                                'text' => $this->buildPrompt($text, $categories),
                            ]],
                        ]],
                        'generationConfig' => [
                            'temperature'      => 0.0,
                            'responseMimeType' => 'application/json',
                        ],
                    ]);

                if (! $response->successful()) {
                    return ['category_id' => null, 'confidence' => 0.0, 'reason' => 'http_'.$response->status()];
                }

                $payload = $response->json('candidates.0.content.parts.0.text');
                $json    = json_decode((string) $payload, true);

                $categoryId = isset($json['category_id']) ? (int) $json['category_id'] : null;

                if ($categoryId === null || ! array_key_exists($categoryId, $categories)) {
                    return ['category_id' => null, 'confidence' => 0.0, 'reason' => 'invalid_output'];
                }

                return [
                    'category_id' => $categoryId,
                    'confidence'  => (float) ($json['confidence'] ?? 0.6),
                    'reason'      => (string) ($json['reason'] ?? ''),
                ];
            } catch (\Throwable $e) {
                Log::warning('LlmClassifier lỗi', ['message' => $e->getMessage()]);

                return ['category_id' => null, 'confidence' => 0.0, 'reason' => 'exception'];
            }
        });
    }

    /** @param array<int, string> $categories */
    private function buildPrompt(string $text, array $categories): string
    {
        $list = collect($categories)->map(fn ($name, $id) => "{$id} = {$name}")->implode("\n");

        return <<<PROMPT
        Bạn là bộ phân loại yêu cầu hỗ trợ IT. Hãy chọn ĐÚNG MỘT danh mục phù hợp nhất cho yêu cầu dưới đây.
        Danh sách danh mục:
        {$list}

        Chỉ trả về JSON theo đúng định dạng:
        {"category_id": <id>, "confidence": <0..1>, "reason": "<lý do ngắn gọn>"}

        Yêu cầu: {$text}
        PROMPT;
    }
}
```

> Ghi chú: `config/services.php` cần thêm khoá `'gemini' => ['key' => env('GEMINI_API_KEY')]`.

## A.8. Đánh giá bộ phân loại (accuracy / macro-F1 / confusion matrix)

**`app/Services/Classification/ClassifierEvaluator.php` (tính chỉ số đánh giá):**
```php
<?php

namespace App\Services\Classification;

use App\Models\ClassifierModel;

/**
 * Tính các chỉ số đánh giá phân loại đa lớp:
 *  - accuracy tổng thể
 *  - precision / recall / F1 cho TỪNG danh mục + macro-average
 *  - confusion matrix để phát hiện các cặp danh mục hay bị lẫn nhau
 */
class ClassifierEvaluator
{
    public function __construct(
        private NaiveBayesClassifier $bayes,
        private Tokenizer $tokenizer,
    ) {}

    /**
     * @param  array<int, array{text: string, category_id: int}>  $samples
     * @return array{accuracy: float, macro_f1: float, per_class: array<int, array<string, float>>, matrix: array<int, array<int, int>>, total: int}
     */
    public function evaluate(ClassifierModel $model, array $samples): array
    {
        $labels  = [];
        $matrix  = [];
        $total   = 0;
        $correct = 0;

        foreach ($samples as $sample) {
            $actual    = (int) $sample['category_id'];
            $predicted = $this->bayes->predict($model, $this->tokenizer->tokenize($sample['text']))['category_id'];

            $labels[$actual]    = true;
            $labels[$predicted] = true;

            $matrix[$actual][$predicted] = ($matrix[$actual][$predicted] ?? 0) + 1;
            $total++;

            if ($actual === $predicted) {
                $correct++;
            }
        }

        $perClass = [];
        $f1Sum    = 0.0;

        foreach (array_keys($labels) as $categoryId) {
            $tp = $matrix[$categoryId][$categoryId] ?? 0;
            $fp = 0;
            $fn = 0;

            foreach ($matrix as $actual => $row) {
                foreach ($row as $predicted => $count) {
                    if ($predicted === $categoryId && $actual !== $categoryId) {
                        $fp += $count;                       // dự đoán vào lớp này nhưng thực tế khác
                    }
                    if ($actual === $categoryId && $predicted !== $categoryId) {
                        $fn += $count;                       // thuộc lớp này nhưng bị đoán sang lớp khác
                    }
                }
            }

            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
            $recall    = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
            $f1        = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

            $perClass[(int) $categoryId] = [
                'precision' => round($precision, 4),
                'recall'    => round($recall, 4),
                'f1'        => round($f1, 4),
                'support'   => $tp + $fn,
            ];

            $f1Sum += $f1;
        }

        return [
            'accuracy'  => $total > 0 ? round($correct / $total, 4) : 0.0,
            'macro_f1'  => $labels ? round($f1Sum / count($labels), 4) : 0.0,
            'per_class' => $perClass,
            'matrix'    => $matrix,
            'total'     => $total,
        ];
    }

    /** Báo cáo Markdown để dán thẳng vào phụ lục báo cáo. */
    public function toMarkdown(ClassifierModel $model, array $result, string $datasetName): string
    {
        $lines   = [];
        $lines[] = "# Báo cáo đánh giá bộ phân loại — {$model->version}";
        $lines[] = '';
        $lines[] = "- Tập dữ liệu: **{$datasetName}** ({$result['total']} mẫu)";
        $lines[] = "- Số mẫu train: **{$model->samples}**, kích thước từ vựng: **{$model->vocab_size}**";
        $lines[] = "- **Accuracy: {$result['accuracy']}** — **Macro-F1: {$result['macro_f1']}**";
        $lines[] = '';
        $lines[] = '| Danh mục | Precision | Recall | F1 | Support |';
        $lines[] = '|---|---|---|---|---|';

        foreach ($result['per_class'] as $categoryId => $metric) {
            $lines[] = sprintf(
                '| %d | %.4f | %.4f | %.4f | %d |',
                $categoryId,
                $metric['precision'],
                $metric['recall'],
                $metric['f1'],
                $metric['support']
            );
        }

        $predictedIds = [];
        foreach ($result['matrix'] as $actual => $row) {
            $predictedIds[] = (int) $actual;
            foreach (array_keys($row) as $predicted) {
                $predictedIds[] = (int) $predicted;
            }
        }
        $predictedIds = array_values(array_unique($predictedIds));
        sort($predictedIds);

        $lines[] = '';
        $lines[] = '## Confusion matrix (hàng = thực tế, cột = dự đoán)';
        $lines[] = '';
        $lines[] = '| Thực tế \\ Dự đoán | '.implode(' | ', $predictedIds).' |';
        $lines[] = '|'.str_repeat('---|', count($predictedIds) + 1);

        foreach ($result['matrix'] as $actual => $row) {
            $cells   = array_map(fn ($predicted) => (string) ($row[$predicted] ?? 0), $predictedIds);
            $lines[] = "| **{$actual}** | ".implode(' | ', $cells).' |';
        }

        return implode("\n", $lines);
    }
}
```

**`app/Console/Commands/TrainClassifierCommand.php`:**
```php
<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\ClassifierModel;
use App\Models\Ticket;
use App\Services\Classification\ClassifierEvaluator;
use App\Services\Classification\NaiveBayesClassifier;
use App\Services\Classification\Tokenizer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class TrainClassifierCommand extends Command
{
    protected $signature = 'classifier:train
                            {--if-enough-labels : Chỉ train khi mọi danh mục đã đủ mẫu (dùng cho scheduler)}
                            {--min-per-class=20 : Số mẫu tối thiểu mỗi danh mục}
                            {--activate : Kích hoạt model mới nếu accuracy không kém model hiện tại}';

    protected $description = 'Train lại bộ phân loại Naive Bayes từ ticket đã có NHÃN VÀNG (nhãn do người xác nhận)';

    public function handle(
        Tokenizer $tokenizer,
        NaiveBayesClassifier $bayes,
        ClassifierEvaluator $evaluator,
    ): int {
        $samples = $this->labeledSamples($tokenizer);

        if ($samples->isEmpty()) {
            $this->error('Chưa có ticket nào đủ điều kiện (đã có nhãn + đã đóng/giải quyết).');
            return self::FAILURE;
        }

        $counts      = $samples->groupBy('category_id')->map->count()->sortKeys();
        $minPerClass = (int) $this->option('min-per-class');

        $this->table(['Danh mục', 'Số mẫu'], $counts->map(fn ($c, $id) => [$id, $c])->values()->all());

        if ($counts->min() < $minPerClass) {
            $this->warn("Có danh mục chưa đủ {$minPerClass} mẫu -> model dễ đoán lệch.");

            if ($this->option('if-enough-labels')) {
                $this->info('Bỏ qua lần train này (--if-enough-labels).');
                return self::SUCCESS;
            }
        }

        $model = $bayes->train($samples->all(), ClassifierModel::nextVersion());
        $this->info("Đã train {$model->version}: {$model->samples} mẫu, vocab {$model->vocab_size} từ.");

        // Đánh giá nhanh trên 20% phần đuôi dữ liệu để có số liệu ngay
        $cut     = (int) floor($samples->count() * 0.8);
        $holdout = $samples->slice($cut)->values()->all();
        $result  = $evaluator->evaluate($model, $holdout);

        $model->update(['accuracy' => $result['accuracy'], 'macro_f1' => $result['macro_f1']]);

        $this->table(
            ['Accuracy (holdout 20%)', 'Macro-F1', 'Số mẫu đánh giá'],
            [[$result['accuracy'], $result['macro_f1'], $result['total']]]
        );

        if ($this->option('activate') && $model->activateIfBetter()) {
            $this->info("Đã kích hoạt model {$model->version}.");
        } else {
            $this->warn("Model {$model->version} chưa được kích hoạt (dùng --activate hoặc chạy classifier:evaluate).");
        }

        return self::SUCCESS;
    }

    /**
     * Nhãn vàng = final_category_id (do agent xác nhận ở màn Triage);
     * fallback về category_id với ticket lịch sử đã đóng/giải quyết.
     *
     * @return Collection<int, array{tokens: array<int, string>, category_id: int}>
     */
    private function labeledSamples(Tokenizer $tokenizer): Collection
    {
        return Ticket::query()
            ->leftJoin('ticket_classifications as tc', 'tc.ticket_id', '=', 'tickets.id')
            ->whereIn('tickets.status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->whereRaw('COALESCE(tc.final_category_id, tickets.category_id) IS NOT NULL')
            ->get([
                'tickets.subject',
                'tickets.description',
                'tickets.category_id',
                'tc.final_category_id',
            ])
            ->map(fn ($row) => [
                'tokens'      => $tokenizer->tokenize(trim($row->subject.' '.$row->description)),
                'category_id' => (int) ($row->final_category_id ?? $row->category_id),
            ])
            ->filter(fn (array $sample) => count($sample['tokens']) > 0)
            ->values();
    }
}
```

**`app/Console/Commands/EvaluateClassifierCommand.php`:**
```php
<?php

namespace App\Console\Commands;

use App\Models\ClassifierModel;
use App\Models\Ticket;
use App\Services\Classification\ClassifierEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EvaluateClassifierCommand extends Command
{
    protected $signature = 'classifier:evaluate
                            {--model=latest : latest hoặc tên version, ví dụ v3}
                            {--holdout=0.2 : Tỉ lệ mẫu giữ lại để đánh giá (bỏ qua nếu có --csv)}
                            {--csv= : Đánh giá bằng tập nhãn tay, ví dụ database/data/test_tickets.csv}';

    protected $description = 'Đánh giá bộ phân loại: accuracy, precision/recall/F1, confusion matrix';

    public function handle(ClassifierEvaluator $evaluator): int
    {
        $model = $this->resolveModel();

        if (! $model) {
            $this->error('Chưa có model. Chạy trước: php artisan classifier:train');
            return self::FAILURE;
        }

        [$samples, $datasetName] = $this->option('csv')
            ? $this->samplesFromCsv((string) $this->option('csv'))
            : $this->samplesFromHoldout((float) $this->option('holdout'));

        if (count($samples) === 0) {
            $this->error('Không có mẫu nào để đánh giá.');
            return self::FAILURE;
        }

        $result = $evaluator->evaluate($model, $samples);

        $this->table(
            ['Accuracy', 'Macro-F1', 'Số mẫu'],
            [[$result['accuracy'], $result['macro_f1'], $result['total']]]
        );

        $this->table(
            ['Danh mục', 'Precision', 'Recall', 'F1', 'Support'],
            collect($result['per_class'])->map(fn (array $m, int $id) => [
                $id, $m['precision'], $m['recall'], $m['f1'], $m['support'],
            ])->values()->all()
        );

        $path = storage_path("app/reports/classifier_{$model->version}.md");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $evaluator->toMarkdown($model, $result, $datasetName));

        $model->update(['accuracy' => $result['accuracy'], 'macro_f1' => $result['macro_f1']]);

        $this->info("Đã lưu báo cáo: {$path}");

        return $result['accuracy'] >= 0.80 ? self::SUCCESS : self::FAILURE;   // exit code dùng được trong CI/kiểm thử
    }

    private function resolveModel(): ?ClassifierModel
    {
        $option = (string) $this->option('model');

        return $option === 'latest'
            ? ClassifierModel::query()->latest('trained_at')->first()
            : ClassifierModel::query()->where('version', $option)->first();
    }

    /** @return array{0: array<int, array{text: string, category_id: int}>, 1: string} */
    private function samplesFromCsv(string $relativePath): array
    {
        $path   = base_path($relativePath);
        $rows   = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map('trim', (array) array_shift($rows));       // subject,description,category_id

        $samples = [];

        foreach ($rows as $row) {
            $data      = array_combine($header, $row);
            $samples[] = [
                'text'        => trim($data['subject'].' '.$data['description']),
                'category_id' => (int) $data['category_id'],
            ];
        }

        return [$samples, $relativePath];
    }

    /** @return array{0: array<int, array{text: string, category_id: int}>, 1: string} */
    private function samplesFromHoldout(float $ratio): array
    {
        $ratio = max(0.05, min(0.5, $ratio));

        $labeled = Ticket::query()
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->get(['subject', 'description', 'category_id']);

        $cut = (int) floor($labeled->count() * (1 - $ratio));

        // giữ phần ĐUÔI làm tập đánh giá (mô phỏng dữ liệu "tương lai" chưa từng dùng để train)
        return [
            $labeled->slice($cut)->map(fn ($t) => [
                'text'        => trim($t->subject.' '.$t->description),
                'category_id' => (int) $t->category_id,
            ])->values()->all(),
            'holdout '.round($ratio * 100).'% dữ liệu ticket trong CSDL',
        ];
    }
}
```

**Quy trình làm việc với bộ phân loại (ghi vào báo cáo):**
```bat
php artisan classifier:train --activate                    :: train + đánh giá nhanh + kích hoạt nếu tốt hơn
php artisan classifier:evaluate --csv=database/data/test_tickets.csv   :: đánh giá trên tập nhãn tay (báo cáo chính)
php artisan classifier:evaluate --model=v1 --holdout=0.3   :: so sánh các ngưỡng dữ liệu train
type storage\app\reports\classifier_v1.md                  :: mở báo cáo để đưa số liệu vào báo cáo đồ án
```

## A.9. TRỤ 2 — RoutingService & 3 chiến lược phân công

```php
<?php
// app/Services/Routing/AssignmentStrategy.php
namespace App\Services\Routing;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

interface AssignmentStrategy
{
    /**
     * @param  Collection<int, User>  $candidates
     * @return array{user: User|null, score: float, reason: string}
     */
    public function pick(Ticket $ticket, Collection $candidates): array;
}
```

```php
<?php
// app/Services/Routing/SkillWeightedStrategy.php
namespace App\Services\Routing;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * score = 0.5·skill_match + 0.3·(1 − load_ratio) + 0.2·zone_match
 *  - skill_match: mức độ khớp kỹ năng yêu cầu của danh mục (level 1..5)
 *  - load_ratio : ticket đang mở / capacity
 *  - zone_match : 1 cùng khu vực, 0.5 khu vực lân cận, 0 khác
 */
class SkillWeightedStrategy implements AssignmentStrategy
{
    public function pick(Ticket $ticket, Collection $candidates): array
    {
        $weights     = config('helpdesk.assignment.weights', ['skill' => 0.5, 'load' => 0.3, 'zone' => 0.2]);
        $required    = collect($ticket->category?->required_skills ?? []);      // ["network" => 3, "printer" => 2]
        $requiredSum = max(1, (float) $required->sum());
        $zoneOfUser  = fn (User $u) => $u->zones->pluck('id')->all();

        $best = null;

        foreach ($candidates as $user) {
            $ownedSkillSum = 0.0;

            foreach ($required as $skillCode => $needLevel) {
                $level = (int) ($user->skills->firstWhere('code', $skillCode)?->pivot->level ?? 0);
                $ownedSkillSum += min($level, (int) $needLevel);                 // không thưởng vượt mức yêu cầu
            }

            $skillMatch = $ownedSkillSum / $requiredSum;

            $capacity  = max(1, (int) ($user->pivot->capacity ?? $user->capacity ?? 8));
            $loadRatio = min(1.5, $ticket->openTicketsOf($user->id) / $capacity);

            $zoneMatch = $ticket->location_id && in_array($ticket->location_id, $zoneOfUser($user), true)
                ? 1.0
                : ($ticket->location && in_array($ticket->location->zone, $user->zones->pluck('neighbor_zone')->all(), true) ? 0.5 : 0.0);

            $score = ($weights['skill'] * $skillMatch)
                   + ($weights['load'] * (1 - min(1.0, $loadRatio)))
                   + ($weights['zone'] * $zoneMatch);

            $reason = sprintf(
                'skill %.2f (%s), load %d/%d, zone %.1f',
                $skillMatch,
                $required->keys()->implode('+') ?: '-',
                $ticket->openTicketsOf($user->id),
                $capacity,
                $zoneMatch
            );

            if (! $best || $score > $best['score']) {
                $best = ['user' => $user, 'score' => round($score, 4), 'reason' => $reason];
            }
        }

        return $best ?? ['user' => null, 'score' => 0.0, 'reason' => 'không có ứng viên'];
    }
}
```

```php
<?php
// app/Services/Routing/RoundRobinStrategy.php
namespace App\Services\Routing;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class RoundRobinStrategy implements AssignmentStrategy
{
    public function pick(Ticket $ticket, Collection $candidates): array
    {
        /** @var User|null $user */
        $user = $candidates->sortBy(fn (User $u) => $u->last_assigned_at?->timestamp ?? 0)->first();

        return [
            'user'   => $user,
            'score'  => 1.0,
            'reason' => 'round-robin theo last_assigned_at',
        ];
    }
}
```

```php
<?php
// app/Services/Routing/LeastOpenTicketsStrategy.php
namespace App\Services\Routing;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class LeastOpenTicketsStrategy implements AssignmentStrategy
{
    public function pick(Ticket $ticket, Collection $candidates): array
    {
        $scored = $candidates
            ->map(fn (User $u) => [
                'user'    => $u,
                'open'    => $ticket->openTicketsOf($u->id),
                'last'    => $u->last_assigned_at?->timestamp ?? 0,
            ])
            ->sortBy([['open', 'asc'], ['last', 'asc']])
            ->first();

        if (! $scored) {
            return ['user' => null, 'score' => 0.0, 'reason' => 'không có ứng viên'];
        }

        return [
            'user'   => $scored['user'],
            'score'  => 1 / (1 + $scored['open']),
            'reason' => "ít ticket đang mở nhất ({$scored['open']})",
        ];
    }
}
```

```php
<?php
// app/Services/Routing/RoutingService.php
namespace App\Services\Routing;

use App\Enums\TicketStatus;
use App\Models\TeamRouteRule;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Notifications\SlaWarningNotification;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoutingService
{
    /** Bước 1+2: xác định nhóm xử lý, leo thang P1, rồi gán agent. */
    public function route(Ticket $ticket): Ticket
    {
        if (! $ticket->assigned_team_id) {
            $teamId = TeamRouteRule::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $ticket->category_id))
                ->orderByRaw('(CASE WHEN category_id IS NOT NULL THEN 1 ELSE 0 END) DESC, priority ASC')
                ->value('team_id')
                ?? $ticket->category?->default_team_id;

            $ticket->forceFill(['assigned_team_id' => $teamId])->save();
        }

        if ($ticket->priority === 'P1') {                              // P1 -> thông báo ngay Lead/on-call
            $ticket->assignedTeam?->lead?->notify(new SlaWarningNotification($ticket, 'escalation'));
        }

        if ($ticket->assigned_team_id) {
            $this->assign($ticket);
        }

        return $ticket->refresh();
    }

    /**
     * Bước 3: chọn agent theo chiến lược. Có transaction + lockForUpdate để chống gán trùng.
     *
     * @return array{user: User|null, score: float, reason: string}
     */
    public function assign(Ticket $ticket, ?string $strategy = null): array
    {
        $strategy ??= $ticket->assignedTeam?->assignment_strategy ?? 'skill_weighted';

        return DB::transaction(function () use ($ticket, $strategy) {
            /** @var Ticket $fresh */
            $fresh = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($fresh->assignee_id) {
                return ['user' => $fresh->assignee, 'score' => 0.0, 'reason' => 'đã có người xử lý'];
            }

            $result = $this->strategy($strategy)->pick($fresh, $this->candidates($fresh));

            if (! $result['user']) {
                $fresh->events()->create([
                    'type' => 'assigned_failed',
                    'meta' => ['strategy' => $strategy, 'reason' => $result['reason']],
                ]);

                return $result;
            }

            $fresh->forceFill([
                'assignee_id' => $result['user']->id,
                'assigned_at' => $fresh->assigned_at ?? now(),
                'status'      => in_array($fresh->status, [TicketStatus::New->value, TicketStatus::Triage->value], true)
                    ? TicketStatus::Assigned->value
                    : $fresh->status,
            ])->save();

            $result['user']->forceFill(['last_assigned_at' => now()])->save();

            TicketAssignment::create([
                'ticket_id'   => $fresh->id,
                'to_user_id'  => $result['user']->id,
                'to_team_id'  => $fresh->assigned_team_id,
                'strategy'    => $strategy,
                'score'       => $result['score'],
                'reason'      => $result['reason'],
                'assigned_by' => auth()->id(),          // null nếu do hệ thống tự gán
            ]);

            $fresh->events()->create([
                'type' => 'assigned',
                'to'   => (string) $result['user']->id,
                'meta' => ['strategy' => $strategy, 'score' => $result['score'], 'reason' => $result['reason']],
            ]);

            $result['user']->notify(new TicketAssignedNotification($fresh));

            return $result;
        });
    }

    /** @return Collection<int, User> */
    private function candidates(Ticket $ticket): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('teams', fn ($q) => $q->where('teams.id', $ticket->assigned_team_id))
            ->with(['skills', 'zones'])
            ->get();
    }

    private function strategy(string $name): AssignmentStrategy
    {
        return match ($name) {
            'round_robin' => new RoundRobinStrategy,
            'least_open'  => new LeastOpenTicketsStrategy,
            default       => new SkillWeightedStrategy,
        };
    }
}
```

> **Tối ưu (tùy chọn):** `SkillWeightedStrategy` gọi `openTicketsOf()` cho từng ứng viên → nhóm ≤ 20 người thì chấp nhận được; muốn giảm query thì truyền sẵn map `['user_id' => open_count]` (tính bằng 1 query `groupBy`) vào strategy.

**Bổ sung quan hệ & helper vào `app/Models/Ticket.php`:**
```php
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sla(): HasOne
    {
        return $this->hasOne(TicketSla::class);
    }

    public function pauses(): HasMany
    {
        return $this->hasMany(TicketPause::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class)->latest();
    }

    public function classification(): HasOne
    {
        return $this->hasOne(TicketClassification::class)->latestOfMany();
    }

    /** Số ticket đang mở của một agent (dùng cho chiến lược cân bằng tải). */
    public function openTicketsOf(int $userId): int
    {
        return static::query()
            ->where('assignee_id', $userId)
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->count();
    }
```

## A.10. TicketObserver — "dây nối" cả 3 trụ khi ticket được tạo

```php
<?php
// app/Observers/TicketObserver.php
namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\Classification\ClassificationService;
use App\Services\Routing\RoutingService;
use App\Services\Sla\SlaService;
use App\Support\PriorityMatrix;
use App\Support\TicketCodeGenerator;

/**
 * Nhờ Observer này mà chỉ cần 1 dòng `Ticket::create([...])` là 3 trụ cùng chạy:
 *   phân loại -> định tuyến -> khởi tạo SLA. Controller không cần biết gì về 3 trụ.
 */
class TicketObserver
{
    public function __construct(
        private ClassificationService $classifier,
        private RoutingService $routing,
        private SlaService $sla,
    ) {}

    /** Sinh mã ticket + tính priority theo ma trận Impact × Urgency. */
    public function creating(Ticket $ticket): void
    {
        $ticket->code     ??= TicketCodeGenerator::next();
        $ticket->priority ??= PriorityMatrix::fromImpactUrgency(
            (int) $ticket->impact,
            (int) $ticket->urgency
        );
    }

    public function created(Ticket $ticket): void
    {
        // ---------- (1) TRỤ 1: phân loại ----------
        $result  = $this->classifier->classify("{$ticket->subject}\n{$ticket->description}", $ticket);
        $updates = [];

        if (! $ticket->category_id && $result['category_id']) {
            $updates['category_id'] = $result['category_id'];          // tự gán danh mục dự đoán
        }

        if ($result['needs_triage']) {                                  // không đủ tự tin -> hàng đợi Triage
            $updates['status'] = TicketStatus::Triage->value;
        }

        if ($result['boost_priority'] && ! in_array($ticket->priority, ['P1', 'P2'], true)) {
            $updates['priority'] = 'P2';                                // có tín hiệu khẩn cấp -> nâng mức
        }

        if ($updates) {
            $ticket->forceFill($updates)->save();
        }

        $ticket->events()->create([
            'type' => 'classified',
            'meta' => [
                'predicted_category_id' => $result['category_id'],
                'confidence'            => $result['confidence'],
                'source'                => $result['source'],
                'matched'               => $result['matched'],
                'model_version'         => $result['model_version'],
            ],
        ]);

        // ---------- (2) TRỤ 2: định tuyến (chờ Triage thì chưa gán agent) ----------
        if (! $ticket->refresh()->needs_triage) {
            $this->routing->route($ticket);
        }

        // ---------- (3) TRỤ 3: khởi tạo đồng hồ SLA ----------
        $this->sla->start($ticket->refresh());

        $ticket->events()->create(['type' => 'created', 'meta' => ['channel' => $ticket->channel]]);
    }

    /** Xác nhận/sửa danh mục ở màn Triage -> lưu NHÃN VÀNG + định tuyến + khởi tạo SLA. */
    public function confirmCategory(Ticket $ticket, int $categoryId, ?int $actorId = null): void
    {
        $ticket->classification()->update([
            'final_category_id' => $categoryId,
            'was_corrected'     => $ticket->classification?->predicted_category_id !== $categoryId,
            'corrected_by'      => $actorId,
            'corrected_at'      => now(),
        ]);

        $ticket->forceFill([
            'category_id'  => $categoryId,
            'needs_triage' => false,
            'status'       => TicketStatus::Assigned->value,
        ])->save();

        $this->routing->route($ticket);
        $this->sla->start($ticket->refresh());
    }
}
```

**Đăng ký observer + service trong `app/Providers/AppServiceProvider.php`:**
```php
use App\Models\Ticket;
use App\Observers\TicketObserver;

public function boot(): void
{
    Ticket::observe(TicketObserver::class);
}
```

## A.11. Lớp hỗ trợ nhỏ & bộ dữ liệu demo

**`app/Support/PriorityMatrix.php` (ma trận Impact × Urgency -> P1..P4):**
```php
<?php

namespace App\Support;

class PriorityMatrix
{
    /** @var array<int, array<int, string>> [impact][urgency] => priority */
    private const MATRIX = [
        3 => [1 => 'P3', 2 => 'P2', 3 => 'P1'],
        2 => [1 => 'P4', 2 => 'P3', 3 => 'P2'],
        1 => [1 => 'P4', 2 => 'P4', 3 => 'P3'],
    ];

    public static function fromImpactUrgency(int $impact, int $urgency): string
    {
        $impact  = max(1, min(3, $impact));
        $urgency = max(1, min(3, $urgency));

        return self::MATRIX[$impact][$urgency];
    }
}
```

**`app/Support/TicketCodeGenerator.php` (sinh mã `TK-2026-000123` không trùng):**
```php
<?php

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketCodeGenerator
{
    public static function next(): string
    {
        $year = now()->format('Y');

        $sequence = DB::transaction(function () use ($year) {
            $last = Ticket::query()
                ->where('code', 'like', "TK-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $number = $last ? ((int) substr((string) $last, -6)) + 1 : 1;

            return str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        });

        return "TK-{$year}-{$sequence}";
    }
}
```

> Nếu dự án phát triển tiếp (nhiều năm, nhiều tiến trình ghi đồng thời), nên chuyển sang **bảng đếm** `ticket_counters(year, last_number)` + `lockForUpdate()` thay vì quét `code`.

**Bộ dữ liệu demo (rất quan trọng để đánh giá bộ phân loại):**

| Thành phần | Số lượng | Ghi chú |
|---|---|---|
| Danh mục | 8 cha + ~20 con | Tài khoản/Mật khẩu, Mạng–Wifi, Máy in, Phần cứng, Email, Ứng dụng/ERP, Hệ thống học vụ, Bảo mật |
| Nhóm IT | 5 | Helpdesk, Hạ tầng, Thiết bị, Ứng dụng, Bảo mật |
| Kỹ năng | 10 | `network`, `printer`, `hardware`, `windows`, `email`, `erp`, `account`, `security`, `campus-system`, `backup` |
| Người dùng | 8 agent + 5 lead + 60 requester | Có phòng ban, vị trí, kỹ năng, khu vực |
| Ticket lịch sử (train) | **300–400** | Trải 90 ngày, gắn nhãn đúng, có phân bố không đều (giống thực tế: Mật khẩu ~30%, Mạng ~20%…) |
| Ticket test (nhãn tay) | **100** | `database/data/test_tickets.csv` — **không** dùng để train |
| Ticket "tươi" để demo | 20–30 | Tạo mới trong buổi demo để thấy pipeline chạy thật |

**`database/seeders/TicketSeeder.php` (rút gọn) — mẹo quan trọng:** seed dữ liệu lịch sử **tắt observer** để giữ được `created_at`/`resolved_at` trong quá khứ, rồi tự tạo `ticket_sla`, `ticket_assignments`, `ticket_events` mô phỏng:

```php
public function run(): void
{
    $templates  = require database_path('data/ticket_templates.php');   // [category_id => [[subject, description], ...]]
    $requesters = User::role('requester')->pluck('id');

    Ticket::withoutEvents(function () use ($templates, $requesters) {
        foreach ($templates as $categoryId => $samples) {
            foreach (range(1, 40) as $i) {
                [$subject, $description] = $samples[array_rand($samples)];

                $createdAt  = CarbonImmutable::now()->subDays(random_int(1, 90))->subMinutes(random_int(0, 480));
                $resolvedAt = $createdAt->addMinutes(random_int(30, 3 * 480));   // 30 phút -> ~3 ngày làm việc

                $ticket = Ticket::factory()->create([
                    'subject'      => $subject,
                    'description'  => $description,
                    'category_id'  => $categoryId,
                    'requester_id' => $requesters->random(),
                    'status'       => TicketStatus::Closed->value,
                    'priority'     => fake()->randomElement(['P2', 'P3', 'P3', 'P3', 'P4']),
                    'created_at'   => $createdAt,
                    'resolved_at'  => $resolvedAt,
                    'closed_at'    => $resolvedAt->addDay(),
                ]);

                // đồng hồ SLA mô phỏng (để báo cáo có dữ liệu ngay trước khi chạy scheduler thật)
                TicketSla::create([
                    'ticket_id'             => $ticket->id,
                    'sla_policy_id'         => SlaPolicy::query()->inRandomOrder()->value('id'),
                    'first_response_due_at' => $createdAt->addMinutes(120),
                    'resolution_due_at'     => $createdAt->addMinutes(480),
                    'warning_at'            => $createdAt->addMinutes(384),
                    'first_responded_at'    => $createdAt->addMinutes(random_int(5, 150)),
                    'resolved_at'           => $resolvedAt,
                    'first_response_met'    => fake()->boolean(85),
                    'resolution_met'        => fake()->boolean(80),
                    'state'                 => SlaState::Met->value,
                ]);
            }
        }
    });

    // 20 ticket "tươi": tạo qua pipeline thật để demo và để scheduler xử lý
    $this->call(FreshDemoTicketSeeder::class);
}
```

**Định dạng `database/data/test_tickets.csv` (tập nhãn tay để đánh giá):**
```csv
subject,description,category_id
"Không in được từ máy 305","Máy in Canon phòng 305 báo lỗi Paper Jam, đã thử mở khay giấy nhưng vẫn không in được",3
"Quên mật khẩu email","Em quên mật khẩu email công vụ, cần cấp lại để đăng nhập Outlook",1
"Wifi khu A chập chờn","Wifi khu A buổi sáng rất chậm, không tải được trang web nào",2
"Sập mạng cả phòng 402","Cả phòng 402 mất mạng hoàn toàn từ 8h, không ai làm việc được",2
```
> Lưu ý: file phải lưu **UTF-8** và đặt trong `database/data/` (không nằm trong `storage` để có thể commit vào Git).

## A.12. Kiểm thử tự động cho 3 trụ (PHPUnit)

```php
<?php
// tests/Unit/Services/Sla/BusinessTimeCalculatorTest.php
namespace Tests\Unit\Services\Sla;

use App\Models\BusinessCalendar;
use App\Models\BusinessHour;
use App\Models\Holiday;
use App\Services\Sla\BusinessTimeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private BusinessTimeCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();

        $calendar = BusinessCalendar::create(['name' => 'Giờ hành chính', 'timezone' => 'Asia/Ho_Chi_Minh']);

        foreach (range(1, 5) as $weekday) {                       // 1 = Thứ 2 ... 5 = Thứ 6
            BusinessHour::create(['business_calendar_id' => $calendar->id, 'weekday' => $weekday, 'start_time' => '08:00', 'end_time' => '12:00']);
            BusinessHour::create(['business_calendar_id' => $calendar->id, 'weekday' => $weekday, 'start_time' => '13:00', 'end_time' => '17:00']);
        }

        Holiday::create(['business_calendar_id' => $calendar->id, 'date' => '2026-10-20', 'name' => 'Ngày lễ test']);

        $this->calc = BusinessTimeCalculator::for($calendar->load(['hours', 'holidays']));
    }

    public function test_cong_phut_trong_cung_khung_gio(): void
    {
        // 14/10/2026 là thứ Tư
        $this->assertSame(
            '2026-10-14 11:00',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-14 09:00'), 120)->format('Y-m-d H:i')
        );
    }

    public function test_cong_phut_vat_qua_nghi_trua(): void
    {
        $this->assertSame(
            '2026-10-14 14:30',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-14 11:30'), 120)->format('Y-m-d H:i')
        );
    }

    public function test_ticket_tao_ngoai_gio_lam_viec_thi_bat_dau_tu_dau_khung(): void
    {
        $this->assertSame(
            '2026-10-14 09:00',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-14 06:30'), 60)->format('Y-m-d H:i')
        );
    }

    public function test_cong_phut_vat_qua_cuoi_tuan(): void
    {
        // 16/10/2026 là thứ Sáu -> còn 30' tới 17:00, 90' tiếp theo rơi vào thứ Hai 19/10
        $this->assertSame(
            '2026-10-19 09:30',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-16 16:30'), 120)->format('Y-m-d H:i')
        );
    }

    public function test_ticket_tao_vao_thu_bay(): void
    {
        $this->assertSame(
            '2026-10-19 09:00',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-17 09:00'), 60)->format('Y-m-d H:i')
        );
    }

    public function test_bo_qua_ngay_le(): void
    {
        // 19/10 16:30 + 120': 30' trong ngày 19/10, 20/10 là lễ -> tiếp tục 21/10 09:30
        $this->assertSame(
            '2026-10-21 09:30',
            $this->calc->addWorkingMinutes(CarbonImmutable::parse('2026-10-19 16:30'), 120)->format('Y-m-d H:i')
        );
    }

    public function test_dem_phut_lam_viec_giua_hai_moc(): void
    {
        $this->assertSame(180, $this->calc->workingMinutesBetween(
            CarbonImmutable::parse('2026-10-16 16:00'),      // thứ Sáu: 60'
            CarbonImmutable::parse('2026-10-19 10:00')       // thứ Hai: 120'
        ));
    }

    public function test_ngoai_gio_lam_viec_thi_bang_khong(): void
    {
        $this->assertSame(0, $this->calc->workingMinutesBetween(
            CarbonImmutable::parse('2026-10-16 17:00'),
            CarbonImmutable::parse('2026-10-19 08:00')
        ));

        $this->assertSame(0, $this->calc->workingMinutesBetween(
            CarbonImmutable::parse('2026-10-19 17:00'),
            CarbonImmutable::parse('2026-10-21 08:00')       // 20/10 là ngày lễ
        ));
    }

    public function test_kiem_tra_dang_trong_hoac_ngoai_gio_lam_viec(): void
    {
        $this->assertTrue($this->calc->isWorkingMoment(CarbonImmutable::parse('2026-10-14 10:00')));
        $this->assertFalse($this->calc->isWorkingMoment(CarbonImmutable::parse('2026-10-14 12:30')));  // nghỉ trưa
        $this->assertFalse($this->calc->isWorkingMoment(CarbonImmutable::parse('2026-10-17 10:00')));  // thứ Bảy
        $this->assertFalse($this->calc->isWorkingMoment(CarbonImmutable::parse('2026-10-20 10:00')));  // ngày lễ
    }
}
```

**Model `TicketSla` cần có (để `resume()`/`start()` truy được lịch làm việc):**
```php
<?php
// app/Models/TicketSla.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSla extends Model
{
    protected $table = 'ticket_sla';

    protected $fillable = [
        'ticket_id', 'sla_policy_id', 'first_response_due_at', 'resolution_due_at', 'warning_at',
        'first_responded_at', 'resolved_at', 'paused_minutes', 'first_response_met', 'resolution_met',
        'state', 'breached_at', 'warning_sent_at', 'computed_at',
    ];

    protected $casts = [
        'first_response_due_at' => 'datetime',
        'resolution_due_at'     => 'datetime',
        'warning_at'            => 'datetime',
        'first_responded_at'    => 'datetime',
        'resolved_at'           => 'datetime',
        'breached_at'           => 'datetime',
        'warning_sent_at'       => 'datetime',
        'computed_at'           => 'datetime',
        'first_response_met'    => 'boolean',
        'resolution_met'        => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id')->with(['hours', 'holidays']);
    }
}
```

```php
<?php
// tests/Unit/Services/Sla/SlaServiceTest.php
namespace Tests\Unit\Services\Sla;

use App\Enums\SlaState;
use App\Models\BusinessCalendar;
use App\Models\BusinessHour;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Services\Sla\SlaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCalendarAndPolicies();
    }

    public function test_start_tinh_dung_moc_due_theo_gio_lam_viec(): void
    {
        $ticket = Ticket::factory()->create(['priority' => 'P3']);

        // Thứ Sáu 16:30 -> còn 30' ; thứ Hai 19/10 làm 240' sáng + 210' chiều
        $sla = app(SlaService::class)->start($ticket, CarbonImmutable::parse('2026-10-16 16:30'));

        $this->assertSame('2026-10-19 09:30', $sla->first_response_due_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-19 16:30', $sla->resolution_due_at->format('Y-m-d H:i'));
        $this->assertSame(SlaState::Ok->value, $sla->state);
    }

    public function test_pause_va_resume_doi_moc_due_dung_so_phut_lam_viec(): void
    {
        $service = app(SlaService::class);
        $ticket  = Ticket::factory()->create(['priority' => 'P3']);

        $service->start($ticket, CarbonImmutable::parse('2026-10-14 09:00'));

        // P3 = 480 phút: 14/10 chỉ còn 180' (sáng) + 240' (chiều) = 420' -> 60' còn lại sang 15/10 09:00
        $this->assertSame('2026-10-15 09:00', $ticket->sla->resolution_due_at->format('Y-m-d H:i'));

        $this->travelTo(CarbonImmutable::parse('2026-10-14 10:00'));
        $service->pause($ticket, 'pending_user');

        $this->travelTo(CarbonImmutable::parse('2026-10-14 11:00'));       // 60 phút trong khung sáng
        $service->resume($ticket->refresh());

        $sla = $ticket->sla->refresh();

        $this->assertSame(60, $sla->paused_minutes);
        $this->assertSame(SlaState::Ok->value, $sla->state);
        $this->assertSame('2026-10-15 10:00', $sla->resolution_due_at->format('Y-m-d H:i'));  // dời thêm 60' LÀM VIỆC
    }

    public function test_danh_dau_phan_hoi_dau_tien_dung_han(): void
    {
        $service = app(SlaService::class);
        $ticket  = Ticket::factory()->create(['priority' => 'P3']);        // P3: phản hồi trong 120'

        $service->start($ticket, CarbonImmutable::parse('2026-10-14 09:00'));
        $service->markFirstResponse($ticket, CarbonImmutable::parse('2026-10-14 10:00'));

        $ticket->refresh();

        $this->assertTrue($ticket->sla->first_response_met);
        $this->assertNotNull($ticket->first_responded_at);
    }

    public function test_danh_dau_phan_hoi_dau_tien_tre_han(): void
    {
        $service = app(SlaService::class);
        $ticket  = Ticket::factory()->create(['priority' => 'P1']);        // P1: phản hồi trong 15'

        $service->start($ticket, CarbonImmutable::parse('2026-10-14 09:00'));
        $service->markFirstResponse($ticket, CarbonImmutable::parse('2026-10-14 09:30'));

        $this->assertFalse($ticket->refresh()->sla->first_response_met);
    }

    private function seedCalendarAndPolicies(): void
    {
        $calendar = BusinessCalendar::create(['name' => 'Giờ hành chính', 'timezone' => 'Asia/Ho_Chi_Minh']);

        foreach (range(1, 5) as $weekday) {
            BusinessHour::create(['business_calendar_id' => $calendar->id, 'weekday' => $weekday, 'start_time' => '08:00', 'end_time' => '12:00']);
            BusinessHour::create(['business_calendar_id' => $calendar->id, 'weekday' => $weekday, 'start_time' => '13:00', 'end_time' => '17:00']);
        }

        foreach ([['P1', 15, 120], ['P2', 30, 240], ['P3', 120, 480], ['P4', 480, 1440]] as [$priority, $frt, $resolution]) {
            SlaPolicy::create([
                'name'                   => "SLA mặc định {$priority}",
                'priority'               => $priority,
                'business_calendar_id'   => $calendar->id,
                'first_response_minutes' => $frt,
                'resolution_minutes'     => $resolution,
                'warning_percent'        => 80,
                'specificity'            => 1,
                'is_active'              => true,
            ]);
        }
    }
}
```

```php
<?php
// tests/Unit/Services/Classification/NaiveBayesTest.php
namespace Tests\Unit\Services\Classification;

use App\Services\Classification\NaiveBayesClassifier;
use App\Services\Classification\Tokenizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NaiveBayesTest extends TestCase
{
    use RefreshDatabase;

    public function test_phan_loai_dung_tren_du_lieu_nho_da_biet_truoc(): void
    {
        $tokenizer = new Tokenizer(['va', 'cua', 'khong', 'duoc']);
        $bayes     = new NaiveBayesClassifier;

        // Lớp 1: tài khoản/mật khẩu ; Lớp 2: máy in ; Lớp 3: mạng
        $corpus = [
            1 => ['quen mat khau email', 'khong dang nhap duoc tai khoan', 'cap lai mat khau outlook', 'tai khoan bi khoa'],
            2 => ['may in khong in duoc', 'may in bao loi paper jam', 'may in het muc', 'khong in duoc tu may 305'],
            3 => ['wifi cham chap', 'mat mang ca phong', 'khong vao duoc internet', 'mang lan bi ngat'],
        ];

        $samples = [];

        foreach ($corpus as $categoryId => $texts) {
            foreach ($texts as $text) {
                $samples[] = ['tokens' => $tokenizer->tokenize($text), 'category_id' => $categoryId];
            }
        }

        $model = $bayes->train($samples, 'test-v1');

        $prediction = $bayes->predict($model, $tokenizer->tokenize('may in phong 305 bi loi paper jam'));
        $this->assertSame(2, $prediction['category_id']);
        $this->assertGreaterThan(0.6, $prediction['confidence']);

        $this->assertSame(1, $bayes->predict($model, $tokenizer->tokenize('em quen mat khau dang nhap'))['category_id']);
        $this->assertSame(3, $bayes->predict($model, $tokenizer->tokenize('mat mang toan bo khu A'))['category_id']);
    }

    public function test_bat_duoc_ca_khi_nguoi_dung_go_khong_dau(): void
    {
        $tokenizer = new Tokenizer(['va', 'cua', 'khong', 'duoc']);

        $withAccent    = $tokenizer->tokenize('Mật khẩu wifi của em không đúng');
        $withoutAccent = $tokenizer->tokenize('mat khau wifi cua em khong dung');

        $this->assertContains('mat_khau', $withAccent);        // bigram từ bản bỏ dấu
        $this->assertContains('mat_khau', $withoutAccent);
        $this->assertNotEmpty(array_intersect($withAccent, $withoutAccent));
    }
}
```

```php
<?php
// tests/Feature/ClassifierAccuracyTest.php
namespace Tests\Feature;

use App\Models\ClassifierModel;
use App\Services\Classification\ClassifierEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassifierAccuracyTest extends TestCase
{
    use RefreshDatabase;

    /** Tiêu chí đánh giá của đề tài: accuracy trên tập nhãn tay >= 80% */
    public function test_do_chinh_xac_tren_tap_nhan_tay_dat_nguong_80_phan_tram(): void
    {
        $model = ClassifierModel::active();

        if (! $model) {
            $this->markTestSkipped('Chưa có model active – chạy: php artisan classifier:train --activate');
        }

        $csv = base_path('database/data/test_tickets.csv');

        if (! file_exists($csv)) {
            $this->markTestSkipped('Thiếu database/data/test_tickets.csv');
        }

        $rows   = array_map('str_getcsv', file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map('trim', array_shift($rows));

        $samples = [];

        foreach ($rows as $row) {
            $data      = array_combine($header, $row);
            $samples[] = [
                'text'        => trim($data['subject'].' '.$data['description']),
                'category_id' => (int) $data['category_id'],
            ];
        }

        $result = app(ClassifierEvaluator::class)->evaluate($model, $samples);

        $this->assertGreaterThanOrEqual(
            0.80,
            $result['accuracy'],
            "Accuracy thực tế: {$result['accuracy']} (model {$model->version}) – xem storage/app/reports"
        );
    }
}
```

```php
<?php
// tests/Feature/RoutingServiceTest.php
namespace Tests\Feature;

use App\Models\Category;
use App\Models\Skill;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Routing\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_chon_agent_khop_ky_nang_va_it_viec_nhat(): void
    {
        $team = Team::factory()->create(['assignment_strategy' => 'skill_weighted']);

        $agentGood = User::factory()->create(['last_assigned_at' => now()->subHour()]);
        $agentBusy = User::factory()->create(['last_assigned_at' => now()->subMinutes(10)]);

        foreach ([$agentGood, $agentBusy] as $agent) {
            $agent->teams()->attach($team->id, ['capacity' => 5]);
        }

        $printer = Skill::create(['code' => 'printer', 'name' => 'Máy in']);
        $agentGood->skills()->attach($printer->id, ['level' => 5]);
        $agentBusy->skills()->attach($printer->id, ['level' => 5]);

        // agentBusy đang giữ 3 ticket mở -> tải cao hơn -> điểm thấp hơn
        Ticket::factory()->count(3)->create([
            'assignee_id'      => $agentBusy->id,
            'assigned_team_id' => $team->id,
            'status'           => 'in_progress',
        ]);

        $category = Category::create([
            'code'             => 'printer',
            'name'             => 'Máy in',
            'default_team_id'  => $team->id,
            'required_skills'  => ['printer' => 3],
        ]);

        $ticket = Ticket::factory()->create([
            'category_id'      => $category->id,
            'assigned_team_id' => $team->id,
            'priority'         => 'P3',
            'status'           => 'new',
        ]);

        $result = app(RoutingService::class)->assign($ticket, 'skill_weighted');

        $this->assertSame($agentGood->id, $result['user']->id);
        $this->assertSame($agentGood->id, $ticket->refresh()->assignee_id);
        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id'  => $ticket->id,
            'to_user_id' => $agentGood->id,
            'strategy'   => 'skill_weighted',
        ]);
    }

    public function test_khong_gan_lai_ticket_da_co_nguoi_xu_ly(): void
    {
        $team   = Team::factory()->create();
        $agentA = User::factory()->create();
        $agentB = User::factory()->create();

        $agentA->teams()->attach($team->id, ['capacity' => 5]);
        $agentB->teams()->attach($team->id, ['capacity' => 5]);

        $ticket = Ticket::factory()->create([
            'assigned_team_id' => $team->id,
            'assignee_id'      => $agentA->id,
        ]);

        $result = app(RoutingService::class)->assign($ticket, 'round_robin');

        $this->assertSame($agentA->id, $result['user']->id);
        $this->assertSame('đã có người xử lý', $result['reason']);
    }
}
```

**Thứ tự triển khai mã nguồn đề xuất (bám sát sprint trong kế hoạch):**

| Bước | Nội dung | Mục trong phụ lục |
|---|---|---|
| 1 | Cấu hình `.env`, `config/helpdesk.php`, scheduler, route | A.1 |
| 2 | Migrations + Models + Factories/Seeders (danh mục, nhóm, kỹ năng, lịch làm việc) | A.2, A.11 |
| 3 | **BusinessTimeCalculator + 10 unit test (làm TRƯỚC tiên)** | A.3, A.12 |
| 4 | Ticket + vòng đời + Policy + Notification (chạy thủ công, chưa tự động) | A.2, A.10 |
| 5 | TRỤ 1: Tokenizer → RuleClassifier → NaiveBayes → ClassificationService → Triage → train/evaluate | A.6, A.7, A.8 |
| 6 | TRỤ 2: RoutingService + 3 chiến lược + claim + leo thang | A.9 |
| 7 | TRỤ 3: SlaService + CheckSlaCommand + UI đồng hồ SLA | A.4, A.5 |
| 8 | Observer nối cả 3 trụ vào luồng tạo ticket (`TicketObserver`, `TicketCommentObserver`) | A.10 |
| 9 | Dashboard, báo cáo, xuất Excel/PDF, KB, CSAT, audit log | (Sprint 6 — kế hoạch chính) |
| 10 | `php artisan test` + `vendor/bin/pint` + thu thập số liệu thực nghiệm để viết báo cáo | A.8, A.12 |

**Việc cần bổ sung khi triển khai thật (chưa đưa vào phụ lục để tránh dài):**
- `TicketFactory`, `TeamFactory`, `UserFactory` (thêm `department_id`, `location_id`, `capacity`, `last_assigned_at`).
- Enum `TicketStatus`, `TicketPriority`, `CommentType`, `AssignmentStrategy`.
- `TicketCommentObserver`: khi agent bình luận **công khai** lần đầu → gọi `SlaService::markFirstResponse($ticket)` và chuyển trạng thái sang `in_progress`.
- Màn hình Triage: hiển thị top-3 danh mục gợi ý + từ khóa khớp; nút "Đúng/Sửa" gọi `TicketObserver::confirmCategory()`.
- `TicketController` (index/store/show/update/claim/assign/reopen/rate), `ReportController` (sla/export), `SlaPolicyController`, `DashboardController`.
- Export Excel (`app/Exports/SlaReportExport.php` implements `FromView`/`FromCollection`) và PDF (Blade view + `Pdf::loadView()`).

> **Kết luận phụ lục:** tất cả mã trong tài liệu này được thiết kế để **copy trực tiếp vào dự án Laravel 13 sau khi `composer create-project`**, với 3 trụ kỹ thuật đã có đủ: (1) đường ống phân loại lai rule + Naive Bayes + LLM tùy chọn có đo lường độ chính xác; (2) bộ định tuyến & phân công 3 chiến lược có lưu vết quyết định; (3) bộ đo thời gian theo **giờ làm việc** có tạm dừng, cảnh báo 80%, vi phạm và bộ KPI đầy đủ để viết chương kết quả thực nghiệm.























