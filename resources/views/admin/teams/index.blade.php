<x-app-layout>
    @php($strategyLabels = ['skill_weighted' => 'Khớp kỹ năng', 'least_open' => 'Ít việc nhất', 'round_robin' => 'Xoay vòng'])

    <x-page-header title="Nhóm xử lý" description="Nhóm tiếp nhận ticket theo danh mục, có trưởng nhóm và chiến lược phân công riêng."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Nhóm xử lý']]">
        <x-slot name="actions">
            <a href="{{ route('admin.teams.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm nhóm</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Mã hoặc tên nhóm...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('admin.teams.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($teams->isEmpty())
            <x-empty-state title="Chưa có nhóm xử lý" description="Thêm nhóm để định tuyến ticket theo danh mục." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Nhóm</th><th>Trưởng nhóm</th><th>Lịch làm việc</th><th>Chiến lược</th><th>Thành viên</th><th>Ticket</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($teams as $team)
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $team->name }}</p>
                                    <p class="font-mono text-xs text-slate-400">{{ $team->code }}</p>
                                </td>
                                <td class="text-sm">{{ $team->lead?->name ?? '—' }}</td>
                                <td class="text-sm">{{ $team->businessCalendar?->name ?? '—' }}</td>
                                <td class="text-sm">{{ $strategyLabels[$team->assignment_strategy] ?? $team->assignment_strategy }}</td>
                                <td class="text-sm">{{ $team->members_count }} / {{ $team->capacity }}</td>
                                <td class="text-sm">{{ $team->tickets_count }}</td>
                                <td>
                                    @if ($team->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.teams.edit', $team) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" onsubmit="return confirm('Xoá nhóm này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Xoá</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $teams->links() }}</div>
        @endif
    </div>
</x-app-layout>
