<x-app-layout>
    <x-page-header title="Quản lý người dùng" description="Tạo tài khoản, gán vai trò, nhóm xử lý và kỹ năng cho từng nhân sự."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Người dùng']]">
        <x-slot name="actions">
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Thêm người dùng
            </a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="field-label">Tìm kiếm</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="field" placeholder="Họ tên hoặc email...">
        </div>
        <div>
            <label for="role" class="field-label">Vai trò</label>
            <select id="role" name="role" class="field">
                <option value="">Tất cả</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="field-label">Trạng thái</label>
            <div class="flex gap-2">
                <select id="status" name="status" class="field">
                    <option value="">Tất cả</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Đã vô hiệu hoá</option>
                </select>
                <button type="submit" class="btn-primary shrink-0">Lọc</button>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        @if ($users->isEmpty())
            <x-empty-state title="Không có người dùng" description="Thêm tài khoản đầu tiên hoặc thay đổi điều kiện lọc." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Vai trò</th>
                            <th>Phòng ban</th>
                            <th>Nhóm xử lý</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $member->initials() }}</span>
                                        <div>
                                            <p class="font-medium text-slate-800">{{ $member->name }}</p>
                                            <p class="text-xs text-slate-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-sm text-slate-600">{{ $member->roleLabel() }}</span></td>
                                <td class="text-sm">{{ $member->department?->name ?? '—' }}</td>
                                <td class="text-sm">
                                    {{ $member->teams->pluck('name')->join(', ') ?: '—' }}
                                </td>
                                <td>
                                    @if ($member->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Hoạt động</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Vô hiệu hoá</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $member) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>

                                        @unless (auth()->user()->is($member))
                                            <form method="POST" action="{{ route('admin.users.toggle', $member) }}">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium {{ $member->is_active ? 'text-rose-600 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                                    {{ $member->is_active ? 'Vô hiệu hoá' : 'Kích hoạt' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
