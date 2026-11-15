<x-app-layout>
    <x-page-header title="Tạo phiếu hỗ trợ" description="Mô tả càng rõ sự cố, kỹ thuật viên càng xử lý nhanh. Mức ưu tiên được tính tự động từ ảnh hưởng × khẩn cấp."
        :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Phiếu', 'url' => route('phieu.index')], ['label' => 'Tạo mới']]" />

    <form method="POST" action="{{ route('phieu.store') }}" enctype="multipart/form-data"
          x-data="{
              anh_huong: {{ (int) old('anh_huong', 2) }},
              khan_cap: {{ (int) old('khan_cap', 2) }},
              uu_tien() {
                  const i = this.anh_huong, u = this.khan_cap;
                  if (i === 3 && u === 3) return 'P1 – Nghiêm trọng';
                  if ((i === 3 && u === 2) || (i === 2 && u === 3)) return 'P2 – Cao';
                  if ((i === 3 && u === 1) || (i === 2 && u === 2) || (i === 1 && u === 3)) return 'P3 – Trung bình';
                  return 'P4 – Thấp';
              }
          }"
          class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-5">
                <div>
                    <label for="tieu_de" class="field-label">Tiêu đề sự cố <span class="text-rose-500">*</span></label>
                    <input id="tieu_de" name="tieu_de" value="{{ old('tieu_de') }}" required maxlength="180" class="field"
                           placeholder="Ví dụ: Không in được từ máy 305">
                    <x-input-error :messages="$errors->get('tieu_de')" class="field-error" />
                </div>

                <div>
                    <label for="danh_muc_id" class="field-label">Danh mục <span class="text-rose-500">*</span></label>
                    <select id="danh_muc_id" name="danh_muc_id" required class="field">
                        <option value="">-- Chọn danh mục sự cố --</option>
                        @foreach ($categories as $parent)
                            <optgroup label="{{ $parent->ten }}">
                                @foreach ($parent->children as $child)
                                    <option value="{{ $child->id }}" @selected((string) old('danh_muc_id') === (string) $child->id)>
                                        {{ $child->ten }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('danh_muc_id')" class="field-error" />
                    <p class="mt-1 text-xs text-slate-400">Chọn danh mục con sát với sự cố nhất để phiếu được chuyển đúng nhóm xử lý.</p>
                </div>

                <div>
                    <label for="mo_ta" class="field-label">Mô tả chi tiết <span class="text-rose-500">*</span></label>
                    <textarea id="mo_ta" name="mo_ta" rows="6" required class="field"
                              placeholder="Thời điểm xảy ra, thiết bị/phần mềm liên quan, thông báo lỗi, số người bị ảnh hưởng...">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="field-error" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phong_ban_id" class="field-label">Phòng ban</label>
                        <select id="phong_ban_id" name="phong_ban_id" class="field">
                            <option value="">-- Không chọn --</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('phong_ban_id', $defaultDepartmentId) === (string) $department->id)>
                                    {{ $department->ten }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('phong_ban_id')" class="field-error" />
                    </div>

                    <div>
                        <label for="vi_tri_id" class="field-label">Vị trí / phòng</label>
                        <select id="vi_tri_id" name="vi_tri_id" class="field">
                            <option value="">-- Không chọn --</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('vi_tri_id', $defaultLocationId) === (string) $location->id)>
                                    {{ $location->fullName() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vi_tri_id')" class="field-error" />
                    </div>
                </div>

                <div>
                    <label for="attachments" class="field-label">Tệp đính kèm</label>
                    <input id="attachments" type="file" name="attachments[]" multiple class="field file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700">
                    <p class="mt-1 text-xs text-slate-400">Tối đa 5 tệp, mỗi tệp không quá {{ config('ho_tro.tep_dinh_kem.kich_thuoc_toi_da_kb') / 1024 }} MB (ảnh, PDF, Word, Excel, ZIP).</p>
                    <x-input-error :messages="$errors->get('attachments')" class="field-error" />
                    <x-input-error :messages="$errors->get('attachments.*')" class="field-error" />
                </div>
            </div>

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-800">Mức ảnh hưởng & độ khẩn cấp</h2>
                <p class="mt-1 text-xs text-slate-500">Hệ thống dùng ma trận Ảnh hưởng × Khẩn cấp để chọn mức ưu tiên P1–P4.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="field-label">Mức ảnh hưởng</p>
                        <div class="space-y-2">
                            @foreach (\App\Enums\MucAnhHuong::cases() as $option)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                       :class="anh_huong === {{ $option->value }} ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-200' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="anh_huong" value="{{ $option->value }}" x-model.number="anh_huong" class="mt-0.5 h-4 w-4 text-indigo-600">
                                    <span>
                                        <span class="font-medium text-slate-800">{{ $option === \App\Enums\MucAnhHuong::Thap ? 'Thấp' : ($option === \App\Enums\MucAnhHuong::TrungBinh ? 'Trung bình' : 'Cao') }}</span>
                                        <span class="block text-xs text-slate-500">{{ $option->label() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="field-label">Độ khẩn cấp</p>
                        <div class="space-y-2">
                            @foreach (\App\Enums\MucKhanCap::cases() as $option)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                       :class="khan_cap === {{ $option->value }} ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-200' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="khan_cap" value="{{ $option->value }}" x-model.number="khan_cap" class="mt-0.5 h-4 w-4 text-indigo-600">
                                    <span>
                                        <span class="font-medium text-slate-800">{{ $option === \App\Enums\MucKhanCap::Thap ? 'Thấp' : ($option === \App\Enums\MucKhanCap::TrungBinh ? 'Trung bình' : 'Cao') }}</span>
                                        <span class="block text-xs text-slate-500">{{ $option->label() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('anh_huong')" class="field-error" />
                <x-input-error :messages="$errors->get('khan_cap')" class="field-error" />
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('phieu.index') }}" class="btn-secondary">Huỷ</a>
                <button type="submit" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" /> Gửi phiếu
                </button>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Mức ưu tiên dự kiến</p>
                <p class="mt-2 text-lg font-bold text-indigo-700" x-text="uu_tien()">{{ old('anh_huong') && old('khan_cap') ? '' : 'P3 – Trung bình' }}</p>
                <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    Công thức: Ảnh hưởng × Khẩn cấp. P1 chỉ dành cho sự cố ảnh hưởng hệ thống dùng chung và đang gián đoạn công việc.
                </div>
            </div>

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-slate-800">Mẹo mô tả sự cố</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-indigo-500">•</span> Nêu rõ thời điểm bắt đầu và thiết bị/phần mềm liên quan.</li>
                    <li class="flex gap-2"><span class="text-indigo-500">•</span> Ghi nguyên văn thông báo lỗi nếu có.</li>
                    <li class="flex gap-2"><span class="text-indigo-500">•</span> Cho biết số người/phòng ban đang bị ảnh hưởng.</li>
                    <li class="flex gap-2"><span class="text-indigo-500">•</span> Đính kèm ảnh chụp màn hình để IT chẩn đoán nhanh hơn.</li>
                </ul>
            </div>
        </aside>
    </form>
</x-app-layout>
