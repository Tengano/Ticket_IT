<x-app-layout>
    <x-page-header title="Phòng ban" description="Danh mục phòng ban dùng cho hồ sơ người dùng và phân loại ticket."
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Phòng ban']]">
        <x-slot name="actions">
            <a href="{{ route('quan-tri.phong-ban.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm phòng ban</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Mã hoặc tên phòng ban...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('quan-tri.phong-ban.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($departments->isEmpty())
            <x-empty-state title="Chưa có phòng ban" description="Thêm phòng ban để gán cho người dùng và ticket." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Mã</th><th>Tên phòng ban</th><th>Người dùng</th><th>PhieuYeuCau</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td class="font-mono text-xs">{{ $department->ma }}</td>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $department->ten }}</p>
                                </td>
                                <td class="text-sm">{{ $department->users_count }}</td>
                                <td class="text-sm">{{ $department->tickets_count }}</td>
                                <td>
                                    @if ($department->dang_hoat_dong)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('quan-tri.phong-ban.edit', $department) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('quan-tri.phong-ban.destroy', $department) }}" onsubmit="return confirm('Xoá phòng ban này?')">
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
            <div class="border-t border-slate-100 px-5 py-4">{{ $departments->links() }}</div>
        @endif
    </div>
</x-app-layout>
