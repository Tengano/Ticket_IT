<x-app-layout>
    @php($isEdit = $location->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật vị trí' : 'Thêm vị trí'"
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Vị trí', 'url' => route('quan-tri.vi-tri.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('quan-tri.vi-tri.update', $location) : route('quan-tri.vi-tri.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="ten" class="field-label">Tên vị trí <span class="text-rose-500">*</span></label>
            <input id="ten" name="ten" value="{{ old('ten', $location->ten) }}" required class="field" placeholder="VD: Phòng 305">
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="dang_hoat_dong" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('dang_hoat_dong', $isEdit ? $location->dang_hoat_dong : true))>
            Đang sử dụng
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('quan-tri.vi-tri.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm vị trí' }}</button>
        </div>
    </form>
</x-app-layout>
