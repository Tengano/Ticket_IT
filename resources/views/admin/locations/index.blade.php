<x-app-layout>
    <x-page-header title="Vị trí / phòng" description="Sơ đồ vị trí dùng để định tuyến ticket theo khu vực."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Vị trí']]">
        <x-slot name="actions">
            <a href="{{ route('admin.locations.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm vị trí</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Tên phòng, tòa nhà, khu vực...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('admin.locations.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($locations->isEmpty())
            <x-empty-state title="Chưa có vị trí" description="Thêm vị trí để người dùng chọn khi gửi ticket." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Vị trí</th><th>Tòa nhà</th><th>Tầng</th><th>Khu vực</th><th>Ticket</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr>
                                <td class="font-medium text-slate-800">{{ $location->name }}</td>
                                <td class="text-sm">{{ $location->building ?? '—' }}</td>
                                <td class="text-sm">{{ $location->floor ?? '—' }}</td>
                                <td class="text-sm">{{ $location->zone ?? '—' }}</td>
                                <td class="text-sm">{{ $location->tickets_count }}</td>
                                <td>
                                    @if ($location->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.locations.edit', $location) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('admin.locations.destroy', $location) }}" onsubmit="return confirm('Xoá vị trí này?')">
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
            <div class="border-t border-slate-100 px-5 py-4">{{ $locations->links() }}</div>
        @endif
    </div>
</x-app-layout>
