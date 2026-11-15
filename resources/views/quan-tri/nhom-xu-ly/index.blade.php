<x-app-layout>
    @php($strategyLabels = ['skill_weighted' => 'Khớp kỹ năng', 'least_open' => 'Ít việc nhất', 'round_robin' => 'Xoay vòng'])

    <x-page-header title="Nhóm xử lý" description="Nhóm tiếp nhận ticket theo danh mục, có trưởng nhóm và chiến lược phân công riêng."
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Nhóm xử lý']]">
        <x-slot name="actions">
            <a href="{{ route('quan-tri.nhom-xu-ly.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm nhóm</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Mã hoặc tên nhóm...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('quan-tri.nhom-xu-ly.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($teams->isEmpty())
            <x-empty-state title="Chưa có nhóm xử lý" description="Thêm nhóm để định tuyến ticket theo danh mục." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Nhóm</th><th>Trưởng nhóm</th><th>Lịch làm việc</th><th>Chiến lược</th><th>Thành viên</th><th>PhieuYeuCau</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($teams as $team)
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $team->ten }}</p>
                                    <p class="font-mono text-xs text-slate-400">{{ $team->ma }}</p>
                                </td>
                                <td class="text-sm">{{ $team->lead?->ten ?? '—' }}</td>
                                <td class="text-sm">{{ $team->businessCalendar?->ten ?? '—' }}</td>
                                <td class="text-sm">{{ $strategyLabels[$team->chien_luoc_phan_cong] ?? $team->chien_luoc_phan_cong }}</td>
                                <td class="text-sm">{{ $team->members_count }} / {{ $team->dinh_muc }}</td>
                                <td class="text-sm">{{ $team->tickets_count }}</td>
                                <td>
                                    @if ($team->dang_hoat_dong)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('quan-tri.nhom-xu-ly.edit', $team) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('quan-tri.nhom-xu-ly.destroy', $team) }}" onsubmit="return confirm('Xoá nhóm này?')">
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
