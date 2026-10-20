<x-app-layout>
    @php($isEdit = $skill->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật kỹ năng' : 'Thêm kỹ năng'"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kỹ năng', 'url' => route('admin.skills.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('admin.skills.update', $skill) : route('admin.skills.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="code" class="field-label">Mã kỹ năng <span class="text-rose-500">*</span></label>
            <input id="code" name="code" value="{{ old('code', $skill->code) }}" required class="field" placeholder="VD: network">
            <x-input-error :messages="$errors->get('code')" class="field-error" />
        </div>
        <div>
            <label for="name" class="field-label">Tên kỹ năng <span class="text-rose-500">*</span></label>
            <input id="name" name="name" value="{{ old('name', $skill->name) }}" required class="field" placeholder="VD: Mạng – Wifi">
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>
        <div>
            <label for="description" class="field-label">Mô tả</label>
            <input id="description" name="description" value="{{ old('description', $skill->description) }}" class="field">
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('admin.skills.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm kỹ năng' }}</button>
        </div>
    </form>
</x-app-layout>
