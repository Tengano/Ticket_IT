<x-app-layout>
    @php($isEdit = $team->exists)
    @php($selectedMembers = old('member_ids', $team->members->pluck('id')->all()))

    <x-page-header :title="$isEdit ? 'Cập nhật nhóm xử lý' : 'Thêm nhóm xử lý'"
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Nhóm xử lý', 'url' => route('quan-tri.nhom-xu-ly.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('quan-tri.nhom-xu-ly.update', $team) : route('quan-tri.nhom-xu-ly.store') }}" class="card max-w-3xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="ma" class="field-label">Mã nhóm <span class="text-rose-500">*</span></label>
                <input id="ma" name="ma" value="{{ old('ma', $team->ma) }}" required class="field" placeholder="VD: ho-tro-chung">
                <x-input-error :messages="$errors->get('ma')" class="field-error" />
            </div>
            <div>
                <label for="ten" class="field-label">Tên nhóm <span class="text-rose-500">*</span></label>
                <input id="ten" name="ten" value="{{ old('ten', $team->ten) }}" required class="field" placeholder="VD: Hỗ trợ chung">
                <x-input-error :messages="$errors->get('ten')" class="field-error" />
            </div>
            <div class="sm:col-span-2">
                <label for="mo_ta" class="field-label">Mô tả</label>
                <input id="mo_ta" name="mo_ta" value="{{ old('mo_ta', $team->mo_ta) }}" class="field">
            </div>
            <div>
                <label for="truong_nhom_id" class="field-label">Trưởng nhóm</label>
                <select id="truong_nhom_id" name="truong_nhom_id" class="field">
                    <option value="">-- Chưa chọn --</option>
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}" @selected((string) old('truong_nhom_id', $team->truong_nhom_id) === (string) $member->id)>{{ $member->ten }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="chien_luoc_phan_cong" class="field-label">Chiến lược phân công <span class="text-rose-500">*</span></label>
                <select id="chien_luoc_phan_cong" name="chien_luoc_phan_cong" required class="field">
                    @foreach ($strategies as $value => $label)
                        <option value="{{ $value }}" @selected(old('chien_luoc_phan_cong', $team->chien_luoc_phan_cong ?: 'khop_ky_nang') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dinh_muc" class="field-label">Định mức phiếu / người <span class="text-rose-500">*</span></label>
                <input id="dinh_muc" type="number" name="dinh_muc" min="1" max="500" value="{{ old('dinh_muc', $team->dinh_muc ?: 20) }}" required class="field">
                <x-input-error :messages="$errors->get('dinh_muc')" class="field-error" />
            </div>
        </div>

        <div>
            <p class="field-label">Thành viên nhóm</p>
            <div class="grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-2">
                @forelse ($staff as $member)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="member_ids[]" value="{{ $member->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                               @checked(in_array($member->id, array_map('intval', $selectedMembers), true))>
                        {{ $member->ten }}
                    </label>
                @empty
                    <p class="text-sm text-slate-400">Chưa có kỹ thuật viên nào.</p>
                @endforelse
            </div>
            <p class="mt-1 text-xs text-slate-400">Trưởng nhóm được tự động thêm vào danh sách thành viên.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="dang_hoat_dong" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('dang_hoat_dong', $isEdit ? $team->dang_hoat_dong : true))>
            Nhóm đang hoạt động
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('quan-tri.nhom-xu-ly.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm nhóm' }}</button>
        </div>
    </form>
</x-app-layout>
