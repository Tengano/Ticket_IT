<x-app-layout>
    @php($isEdit = $location->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật vị trí' : 'Thêm vị trí'"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Vị trí', 'url' => route('admin.locations.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('admin.locations.update', $location) : route('admin.locations.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="field-label">Tên vị trí <span class="text-rose-500">*</span></label>
                <input id="name" name="name" value="{{ old('name', $location->name) }}" required class="field" placeholder="VD: Phòng 305">
                <x-input-error :messages="$errors->get('name')" class="field-error" />
            </div>
            <div>
                <label for="building" class="field-label">Tòa nhà</label>
                <input id="building" name="building" value="{{ old('building', $location->building) }}" class="field" placeholder="VD: Tòa A">
            </div>
            <div>
                <label for="floor" class="field-label">Tầng</label>
                <input id="floor" name="floor" value="{{ old('floor', $location->floor) }}" class="field" placeholder="VD: Tầng 3">
            </div>
            <div>
                <label for="zone" class="field-label">Khu vực</label>
                <input id="zone" name="zone" value="{{ old('zone', $location->zone) }}" class="field" placeholder="VD: Khu A">
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="field-label">Mô tả</label>
                <input id="description" name="description" value="{{ old('description', $location->description) }}" class="field">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('is_active', $isEdit ? $location->is_active : true))>
            Đang sử dụng
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('admin.locations.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm vị trí' }}</button>
        </div>
    </form>
</x-app-layout>
