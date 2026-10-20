<?php

namespace App\Models;

use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'actor_id', 'type', 'from', 'to', 'meta'])]
class TicketEvent extends Model
{
    /** @use HasFactory<TicketEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketEventType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function summary(): string
    {
        return match ($this->type) {
            TicketEventType::Created => 'đã tạo ticket',
            TicketEventType::Updated => 'đã cập nhật thông tin ticket',
            TicketEventType::StatusChanged => "đã chuyển trạng thái từ \"{$this->statusLabel($this->from)}\" sang \"{$this->statusLabel($this->to)}\"",
            TicketEventType::PriorityChanged => "đã đổi mức ưu tiên từ {$this->priorityLabel($this->from)} sang {$this->priorityLabel($this->to)}",
            TicketEventType::Assigned => $this->assignmentSummary(),
            TicketEventType::Commented => 'đã thêm bình luận công khai',
            TicketEventType::InternalNoteAdded => 'đã thêm ghi chú nội bộ',
            TicketEventType::AttachmentAdded => 'đã đính kèm tệp "'.($this->meta['name'] ?? 'tệp tin').'"',
            TicketEventType::Reopened => 'đã mở lại ticket',
            TicketEventType::Closed => 'đã đóng ticket',
            TicketEventType::WatcherAdded => 'đã thêm người theo dõi',
        };
    }

    private function assignmentSummary(): string
    {
        $assignee = $this->meta['assignee'] ?? null;
        $team = $this->meta['team'] ?? null;

        return match (true) {
            $assignee !== null && $team !== null => "đã phân công ticket cho {$assignee} (nhóm {$team})",
            $assignee !== null => "đã phân công ticket cho {$assignee}",
            $team !== null => "đã chuyển ticket sang nhóm {$team}",
            default => 'đã cập nhật phân công',
        };
    }

    private function statusLabel(?string $value): string
    {
        return $value ? (TicketStatus::tryFrom($value)?->label() ?? $value) : '—';
    }

    private function priorityLabel(?string $value): string
    {
        return $value ? (TicketPriority::tryFrom($value)?->shortLabel() ?? $value) : '—';
    }
}
