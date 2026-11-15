<x-app-layout>
    @php($isEdit = $department->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật phòng ban' : 'Thêm phòng ban'"
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Phòng ban', 'url' => route('quan-tri.phong-ban.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('quan-tri.phong-ban.update', $department) : route('quan-tri.phong-ban.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="ma" class="field-label">Mã phòng ban <span class="text-rose-500">*</span></label>
            <input id="ma" name="ma" value="{{ old('ma', $department->ma) }}" required class="field" placeholder="VD: CNTT">
            <x-input-error :messages="$errors->get('ma')" class="field-error" />
        </div>

        <div>
            <label for="ten" class="field-label">Tên phòng ban <span class="text-rose-500">*</span></label>
            <input id="ten" name="ten" value="{{ old('ten', $department->ten) }}" required class="field" placeholder="VD: Phòng Công nghệ thông tin">
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>

        <div>
            <label for="mo_ta" class="field-label">Mô tả</label>
            <input id="mo_ta" name="mo_ta" value="{{ old('mo_ta', $department->mo_ta) }}" class="field">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="dang_hoat_dong" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('dang_hoat_dong', $isEdit ? $department->dang_hoat_dong : true))>
            Đang sử dụng
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('quan-tri.phong-ban.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm phòng ban' }}</button>
        </div>
    </form>
</x-app-layout>
