<x-app-layout>
    <x-page-header title="Vị trí / phòng" description="Sơ đồ vị trí dùng để định tuyến ticket theo phòng."
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Vị trí']]">
        <x-slot name="actions">
            <a href="{{ route('quan-tri.vi-tri.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm vị trí</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Tên phòng...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('quan-tri.vi-tri.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($locations->isEmpty())
            <x-empty-state title="Chưa có vị trí" description="Thêm vị trí để người dùng chọn khi gửi ticket." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Vị trí</th><th>PhieuYeuCau</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr>
                                <td class="font-medium text-slate-800">{{ $location->ten }}</td>
                                <td class="text-sm">{{ $location->tickets_count }}</td>
                                <td>
                                    @if ($location->dang_hoat_dong)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('quan-tri.vi-tri.edit', $location) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('quan-tri.vi-tri.destroy', $location) }}" onsubmit="return confirm('Xoá vị trí này?')">
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
