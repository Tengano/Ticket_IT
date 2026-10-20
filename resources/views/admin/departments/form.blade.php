<x-app-layout>
    @php($isEdit = $department->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật phòng ban' : 'Thêm phòng ban'"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Phòng ban', 'url' => route('admin.departments.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('admin.departments.update', $department) : route('admin.departments.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="code" class="field-label">Mã phòng ban <span class="text-rose-500">*</span></label>
            <input id="code" name="code" value="{{ old('code', $department->code) }}" required class="field" placeholder="VD: CNTT">
            <x-input-error :messages="$errors->get('code')" class="field-error" />
        </div>

        <div>
            <label for="name" class="field-label">Tên phòng ban <span class="text-rose-500">*</span></label>
            <input id="name" name="name" value="{{ old('name', $department->name) }}" required class="field" placeholder="VD: Phòng Công nghệ thông tin">
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>

        <div>
            <label for="description" class="field-label">Mô tả</label>
            <input id="description" name="description" value="{{ old('description', $department->description) }}" class="field">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('is_active', $isEdit ? $department->is_active : true))>
            Đang sử dụng
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('admin.departments.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm phòng ban' }}</button>
        </div>
    </form>
</x-app-layout>
