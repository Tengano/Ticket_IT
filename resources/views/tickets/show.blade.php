<x-app-layout>
    <x-page-header :title="$ticket->subject"
        :description="$ticket->code.' · '.$ticket->created_at->format('d/m/Y H:i')"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Tickets', 'url' => route('tickets.index')], ['label' => $ticket->code]]">
        <x-slot name="actions">
            <a href="{{ route('tickets.index') }}" class="btn-secondary">Quay lại</a>

            @can('close', $ticket)
                <form method="POST" action="{{ route('tickets.close', $ticket) }}" onsubmit="return confirm('Xác nhận đóng ticket này?')">
                    @csrf
                    <button type="submit" class="btn-danger">
                        <x-icon name="check" class="h-4 w-4" /> Đóng ticket
                    </button>
                </form>
            @endcan

            @can('reopen', $ticket)
                <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" onsubmit="return confirm('Mở lại ticket này?')">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <x-icon name="clock" class="h-4 w-4" /> Mở lại
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-status-badge :status="$ticket->status" />
        <x-priority-badge :priority="$ticket->priority" :with-label="true" />

        @if ($ticket->category)
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 ring-inset">
                {{ $ticket->category->fullName() }}
            </span>
        @endif

        @if ($ticket->assignedTeam)
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 ring-inset">
                Nhóm {{ $ticket->assignedTeam->name }}
            </span>
        @endif

        @if ($ticket->reopened_count > 0)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200 ring-inset">
                Đã mở lại {{ $ticket->reopened_count }} lần
            </span>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                        {{ $ticket->requester->initials() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">{{ $ticket->requester->name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $ticket->requester->roleLabel() }} · {{ $ticket->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $ticket->description }}</div>

                <div class="mt-5 flex flex-wrap gap-x-6 gap-y-1 border-t border-slate-100 pt-4 text-xs text-slate-500">
                    <span>Phòng ban: <span class="font-medium text-slate-700">{{ $ticket->department?->name ?? '—' }}</span></span>
                    <span>Vị trí: <span class="font-medium text-slate-700">{{ $ticket->location?->fullName() ?? '—' }}</span></span>
                    <span>Kênh: <span class="font-medium text-slate-700">{{ $ticket->channel }}</span></span>
                </div>
            </div>

            @if ($attachments->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-800">Tệp đính kèm ({{ $attachments->count() }})</h2>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($attachments as $attachment)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <x-icon name="paperclip" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-700">{{ $attachment->original_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $attachment->humanSize() }} · {{ $attachment->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}" class="btn-secondary px-3 py-1.5 text-xs">
                                    <x-icon name="download" class="h-3.5 w-3.5" /> Tải
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Trao đổi ({{ $comments->count() }})</h2>
                    <p class="text-xs text-slate-400">Ghi chú nội bộ chỉ hiển thị với kỹ thuật viên</p>
                </div>

                @forelse ($comments as $comment)
                    <div id="comment-{{ $comment->id }}" class="border-b border-slate-100 px-5 py-4 {{ $comment->isInternal() ? 'bg-amber-50/60' : '' }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $comment->isInternal() ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }} text-xs font-bold">
                                {{ $comment->author->initials() }}
                            </span>
                            <span class="text-sm font-semibold text-slate-800">{{ $comment->author->name }}</span>
                            <span class="text-xs text-slate-400">{{ $comment->author->roleLabel() }}</span>
                            <span class="text-xs text-slate-300">·</span>
                            <span class="text-xs text-slate-400">{{ $comment->created_at->format('d/m/Y H:i') }}</span>

                            @if ($comment->isInternal())
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                    <x-icon name="lock" class="h-3 w-3" /> Ghi chú nội bộ
                                </span>
                            @endif

                            @if ($comment->is_first_response)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Phản hồi đầu tiên</span>
                            @endif
                        </div>

                        <div class="mt-2 pl-10 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $comment->body }}</div>

                        @if ($comment->attachments->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2 pl-10">
                                @foreach ($comment->attachments as $attachment)
                                    <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                       class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:border-indigo-300 hover:text-indigo-600">
                                        <x-icon name="paperclip" class="h-3 w-3" /> {{ $attachment->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <x-empty-state title="Chưa có trao đổi" description="Bình luận công khai hoặc ghi chú nội bộ sẽ xuất hiện tại đây." />
                @endforelse

                @can('comment', $ticket)
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data"
                          x-data="{ type: 'public' }" class="space-y-3 border-t border-slate-100 bg-slate-50/70 p-5">
                        @csrf

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-700">Thêm phản hồi</h3>

                            @if (auth()->user()->can('internalNote', $ticket))
                                <select name="type" x-model="type" class="field sm:w-56">
                                    <option value="public">Bình luận công khai</option>
                                    <option value="internal">Ghi chú nội bộ</option>
                                </select>
                            @else
                                <input type="hidden" name="type" value="public">
                            @endif
                        </div>

                        <textarea name="body" rows="4" required class="field" :class="type === 'internal' ? 'border-amber-300 bg-amber-50' : ''"
                                  placeholder="Nhập nội dung phản hồi...">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="field-error" />

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <input type="file" name="attachments[]" multiple class="field max-w-sm py-1.5 text-xs">
                            <button type="submit" class="btn-primary">
                                <x-icon name="paperclip" class="h-4 w-4" /> Gửi phản hồi
                            </button>
                        </div>
                    </form>
                @else
                    <div class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-500">
                        Ticket đã kết thúc. Kỹ thuật viên vẫn có thể bổ sung ghi chú nội bộ khi cần.
                    </div>
                @endcan
            </div>
        </div>

        <aside class="space-y-6">

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-800">Thông tin ticket</h2>

                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Người yêu cầu</dt>
                        <dd class="text-right">
                            <p class="font-medium text-slate-800">{{ $ticket->requester->name }}</p>
                            <p class="text-xs text-slate-400">{{ $ticket->requester->email }}</p>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Người phụ trách</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->assignee?->name ?? 'Chưa phân công' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Nhóm xử lý</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->assignedTeam?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Danh mục</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->category?->fullName() ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Ngày tạo</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Phản hồi đầu tiên</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->first_responded_at?->format('d/m/Y H:i') ?? 'Chưa phản hồi' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Giải quyết lúc</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->resolved_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-2">
                        <dt class="text-slate-500">Đóng lúc</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $ticket->closed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            @can('update', $ticket)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Cập nhật xử lý</h2>
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="field-label">Trạng thái</label>
                            <select id="status" name="status" class="field">
                                <option value="">Giữ nguyên</option>
                                @foreach ($availableStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="priority" class="field-label">Mức ưu tiên</label>
                            <select id="priority" name="priority" class="field">
                                <option value="">Giữ nguyên</option>
                                @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                    <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="reason" class="field-label">Lý do / ghi chú</label>
                            <input id="reason" name="reason" class="field" placeholder="Ví dụ: đã liên hệ người dùng">
                        </div>

                        <button type="submit" class="btn-primary w-full">Lưu thay đổi</button>
                    </form>
                </div>
            @endcan

            @can('assign', $ticket)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Phân công</h2>
                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="mt-3 space-y-3">
                        @csrf

                        <div>
                            <label for="assigned_team_id" class="field-label">Nhóm xử lý</label>
                            <select id="assigned_team_id" name="assigned_team_id" class="field">
                                <option value="">-- Giữ nguyên --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected($ticket->assigned_team_id === $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assignee_id" class="field-label">Kỹ thuật viên</label>
                            <select id="assignee_id" name="assignee_id" class="field">
                                <option value="">-- Chưa phân công --</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" @selected($ticket->assignee_id === $member->id)>{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn-primary w-full">Cập nhật phân công</button>
                    </form>
                </div>
            @endcan

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Lịch sử hoạt động</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $events->count() }}</span>
                </div>

                <ol class="mt-4 space-y-4">
                    @forelse ($events as $event)
                        <li class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500">
                                {{ $event->type->icon() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-700">
                                    <span class="font-semibold text-slate-800">{{ $event->actor?->name ?? 'Hệ thống' }}</span>
                                    {{ $event->summary() }}
                                </p>
                                <p class="text-xs text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có hoạt động nào.</li>
                    @endforelse
                </ol>
            </div>
        </aside>
    </div>
</x-app-layout>
