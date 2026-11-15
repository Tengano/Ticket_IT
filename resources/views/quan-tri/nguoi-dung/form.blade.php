<x-app-layout>
    @php($isEdit = $user->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật tài khoản' : 'Thêm người dùng'"
        :description="$isEdit ? 'Cập nhật thông tin, vai trò và nhóm xử lý của nhân sự.' : 'Tạo tài khoản mới và gán vai trò phù hợp.'"
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Người dùng', 'url' => route('quan-tri.nguoi-dung.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('quan-tri.nguoi-dung.update', $user) : route('quan-tri.nguoi-dung.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card grid gap-4 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="ten" class="field-label">Họ và tên <span class="text-rose-500">*</span></label>
                    <input id="ten" name="ten" value="{{ old('ten', $user->ten) }}" required class="field">
                    <x-input-error :messages="$errors->get('ten')" class="field-error" />
                </div>

                <div>
                    <label for="email" class="field-label">Email <span class="text-rose-500">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="field">
                    <x-input-error :messages="$errors->get('email')" class="field-error" />
                </div>

                <div>
                    <label for="dien_thoai" class="field-label">Số điện thoại</label>
                    <input id="dien_thoai" name="dien_thoai" value="{{ old('dien_thoai', $user->dien_thoai) }}" class="field">
                    <x-input-error :messages="$errors->get('dien_thoai')" class="field-error" />
                </div>

                <div>
                    <label for="role" class="field-label">Vai trò <span class="text-rose-500">*</span></label>
                    <select id="role" name="role" required class="field">
                        <option value="">-- Chọn vai trò --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->getRoleNames()->first()) === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="field-error" />
                </div>

                <div>
                    <label for="phong_ban_id" class="field-label">Phòng ban</label>
                    <select id="phong_ban_id" name="phong_ban_id" class="field">
                        <option value="">-- Không chọn --</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('phong_ban_id', $user->phong_ban_id) === (string) $department->id)>{{ $department->ten }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="vi_tri_id" class="field-label">Vị trí</label>
                    <select id="vi_tri_id" name="vi_tri_id" class="field">
                        <option value="">-- Không chọn --</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('vi_tri_id', $user->vi_tri_id) === (string) $location->id)>{{ $location->fullName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="password" class="field-label">Mật khẩu {{ $isEdit ? '' : '*' }}</label>
                    <input id="password" type="password" name="password" @required(! $isEdit) class="field" autocomplete="new-password">
                    @if ($isEdit)
                        <p class="mt-1 text-xs text-slate-400">Để trống nếu không đổi mật khẩu.</p>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="field-error" />
                </div>

                <div>
                    <label for="password_confirmation" class="field-label">Nhập lại mật khẩu</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="field" autocomplete="new-password">
                </div>

                <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-center sm:gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="dang_hoat_dong" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('dang_hoat_dong', $isEdit ? $user->dang_hoat_dong : true))>
                        Tài khoản đang hoạt động
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="la_vip" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('la_vip', $user->la_vip))>
                        Người dùng VIP (ưu tiên xử lý)
                    </label>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-800">Nhóm xử lý</h2>
                <p class="mt-1 text-xs text-slate-500">Nhóm quyết định hàng đợi phiếu và người phụ trách mặc định.</p>

                @php($selectedTeams = old('team_ids', $user->teams->pluck('id')->all()))

                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <p class="field-label">Nhóm xử lý</p>
                        <div class="space-y-2">
                            @forelse ($teams as $team)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="team_ids[]" value="{{ $team->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                                           @checked(in_array($team->id, array_map('intval', $selectedTeams), true))>
                                    {{ $team->ten }}
                                </label>
                            @empty
                                <p class="text-sm text-slate-400">Chưa có nhóm xử lý.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('quan-tri.nguoi-dung.index') }}" class="btn-secondary">Huỷ</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo tài khoản' }}</button>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-800">Vai trò trong hệ thống</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><span class="font-medium text-slate-800">requester</span> – Người yêu cầu: gửi phiếu, theo dõi phiếu của mình.</li>
                    <li><span class="font-medium text-slate-800">agent</span> – Kỹ thuật viên: xử lý, phân công, ghi chú nội bộ.</li>
                    <li><span class="font-medium text-slate-800">lead</span> – Trưởng nhóm IT: như agent + xem báo cáo.</li>
                    <li><span class="font-medium text-slate-800">admin</span> – Quản trị: toàn quyền cấu hình.</li>
                </ul>
            </div>

            @if ($isEdit && ! auth()->user()->is($user))
                <div class="card border-rose-200 p-5">
                    <h2 class="text-sm font-semibold text-rose-700">Xoá tài khoản</h2>
                    <p class="mt-1 text-xs text-slate-500">Chỉ xoá được khi người dùng chưa gửi hoặc phụ trách phiếu nào. Nếu không, hãy vô hiệu hoá tài khoản.</p>
                    <form method="POST" action="{{ route('quan-tri.nguoi-dung.destroy', $user) }}" class="mt-3" onsubmit="return confirm('Xoá tài khoản này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger w-full">Xoá tài khoản</button>
                    </form>
                </div>
            @endif
        </aside>
    </form>
</x-app-layout>
