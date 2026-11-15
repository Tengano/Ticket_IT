<x-app-layout>
    @php($isEdit = $location->exists)

    <x-page-header :title="$isEdit ? 'Cập nhật vị trí' : 'Thêm vị trí'"
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Vị trí', 'url' => route('quan-tri.vi-tri.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('quan-tri.vi-tri.update', $location) : route('quan-tri.vi-tri.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="ten" class="field-label">Tên vị trí <span class="text-rose-500">*</span></label>
                <input id="ten" name="ten" value="{{ old('ten', $location->ten) }}" required class="field" placeholder="VD: Phòng 305">
                <x-input-error :messages="$errors->get('name')" class="field-error" />
            </div>
            <div>
                <label for="toa_nha" class="field-label">Tòa nhà</label>
                <input id="toa_nha" name="toa_nha" value="{{ old('toa_nha', $location->toa_nha) }}" class="field" placeholder="VD: Tòa A">
            </div>
            <div>
                <label for="tang" class="field-label">Tầng</label>
                <input id="tang" name="tang" value="{{ old('tang', $location->tang) }}" class="field" placeholder="VD: Tầng 3">
            </div>
            <div>
                <label for="khu_vuc" class="field-label">Khu vực</label>
                <input id="khu_vuc" name="khu_vuc" value="{{ old('khu_vuc', $location->khu_vuc) }}" class="field" placeholder="VD: Khu A">
            </div>
            <div class="sm:col-span-2">
                <label for="mo_ta" class="field-label">Mô tả</label>
                <input id="mo_ta" name="mo_ta" value="{{ old('mo_ta', $location->mo_ta) }}" class="field">
            </div>
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
