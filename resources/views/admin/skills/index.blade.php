<x-app-layout>
    <x-page-header title="Kỹ năng" description="Danh mục kỹ năng chuyên môn dùng cho phân công kỹ thuật viên."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kỹ năng']]">
        <x-slot name="actions">
            <a href="{{ route('admin.skills.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm kỹ năng</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-56 flex-1">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $search }}" class="field" placeholder="Mã hoặc tên kỹ năng...">
        </div>
        <button type="submit" class="btn-primary">Lọc</button>
        <a href="{{ route('admin.skills.index') }}" class="btn-secondary">Xoá lọc</a>
    </form>

    <div class="card overflow-hidden">
        @if ($skills->isEmpty())
            <x-empty-state title="Chưa có kỹ năng" description="Thêm kỹ năng để gán cho kỹ thuật viên." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Mã</th><th>Kỹ năng</th><th>Mô tả</th><th>Kỹ thuật viên</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($skills as $skill)
                            <tr>
                                <td class="font-mono text-xs">{{ $skill->code }}</td>
                                <td class="font-medium text-slate-800">{{ $skill->name }}</td>
                                <td class="text-sm text-slate-500">{{ $skill->description ?? '—' }}</td>
                                <td class="text-sm">{{ $skill->users_count }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.skills.edit', $skill) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}" onsubmit="return confirm('Xoá kỹ năng này?')">
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
            <div class="border-t border-slate-100 px-5 py-4">{{ $skills->links() }}</div>
        @endif
    </div>
</x-app-layout>
