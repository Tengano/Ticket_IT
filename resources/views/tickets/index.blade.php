<x-app-layout>
    <x-page-header title="Danh sách ticket" description="Theo dõi và lọc yêu cầu hỗ trợ theo trạng thái, ưu tiên, danh mục và người phụ trách." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Tickets']]">
        <x-slot name="actions">
            <a href="{{ route('tickets.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Tạo ticket
            </a>
        </x-slot>
    </x-page-header>

    <form method="GET" action="{{ route('tickets.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="q" class="field-label">Tìm kiếm</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="field" placeholder="Mã ticket hoặc tiêu đề...">
            </div>

            <div>
                <label for="status" class="field-label">Trạng thái</label>
                <select id="status" name="status" class="field">
                    <option value="">Tất cả</option>
                    @foreach (\App\Enums\TicketStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="priority" class="field-label">Ưu tiên</label>
                <select id="priority" name="priority" class="field">
                    <option value="">Tất cả</option>
                    @foreach (\App\Enums\TicketPriority::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="category_id" class="field-label">Danh mục</label>
                <select id="category_id" name="category_id" class="field">
                    <option value="">Tất cả</option>
                    @foreach ($categories as $parent)
                        <optgroup label="{{ $parent->name }}">
                            <option value="{{ $parent->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $parent->id)>
                                Tất cả {{ $parent->name }}
                            </option>
                            @foreach ($parent->children as $child)
                                <option value="{{ $child->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $child->id)>
                                    {{ $child->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="assignee" class="field-label">Người phụ trách</label>
                <select id="assignee" name="assignee" class="field">
                    <option value="">Tất cả</option>
                    @if (auth()->user()->isStaff())
                        <option value="me" @selected(($filters['assignee'] ?? '') === 'me')>Giao cho tôi</option>
                        <option value="unassigned" @selected(($filters['assignee'] ?? '') === 'unassigned')>Chưa phân công</option>
                    @endif
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}" @selected((string) ($filters['assignee'] ?? '') === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('tickets.index') }}" class="btn-secondary">Xoá lọc</a>
            <button type="submit" class="btn-primary">
                <x-icon name="search" class="h-4 w-4" /> Lọc
            </button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <p class="text-sm font-semibold text-slate-800">{{ $tickets->total() }} ticket</p>
            <p class="text-xs text-slate-400">Trang {{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</p>
        </div>

        @if ($tickets->isEmpty())
            <x-empty-state title="Không tìm thấy ticket" description="Thử thay đổi bộ lọc hoặc tạo ticket mới để bắt đầu.">
                <x-slot name="action">
                    <a href="{{ route('tickets.create') }}" class="btn-primary">Tạo ticket</a>
                </x-slot>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Mã / Tiêu đề</th>
                            <th>Người gửi</th>
                            <th>Danh mục</th>
                            <th>Phụ trách</th>
                            <th>Ưu tiên</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-mono text-xs font-medium text-indigo-600 hover:underline">{{ $ticket->code }}</a>
                                    <a href="{{ route('tickets.show', $ticket) }}" class="mt-0.5 block max-w-xs truncate font-medium text-slate-800 hover:text-indigo-600">{{ $ticket->subject }}</a>
                                </td>
                                <td>
                                    <p class="text-sm text-slate-700">{{ $ticket->requester->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $ticket->department?->name ?? '—' }}</p>
                                </td>
                                <td class="text-sm">{{ $ticket->category?->name ?? '—' }}</td>
                                <td>
                                    @if ($ticket->assignee)
                                        <p class="text-sm text-slate-700">{{ $ticket->assignee->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $ticket->assignedTeam?->name ?? '—' }}</p>
                                    @else
                                        <span class="text-xs font-medium text-amber-600">Chưa phân công</span>
                                    @endif
                                </td>
                                <td><x-priority-badge :priority="$ticket->priority" /></td>
                                <td><x-status-badge :status="$ticket->status" /></td>
                                <td class="text-xs text-slate-500">
                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    <span class="block text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
