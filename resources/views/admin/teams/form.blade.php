<x-app-layout>
    @php($isEdit = $team->exists)
    @php($selectedMembers = old('member_ids', $team->members->pluck('id')->all()))

    <x-page-header :title="$isEdit ? 'Cập nhật nhóm xử lý' : 'Thêm nhóm xử lý'"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Nhóm xử lý', 'url' => route('admin.teams.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('admin.teams.update', $team) : route('admin.teams.store') }}" class="card max-w-3xl space-y-4 p-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="code" class="field-label">Mã nhóm <span class="text-rose-500">*</span></label>
                <input id="code" name="code" value="{{ old('code', $team->code) }}" required class="field" placeholder="VD: helpdesk">
                <x-input-error :messages="$errors->get('code')" class="field-error" />
            </div>
            <div>
                <label for="name" class="field-label">Tên nhóm <span class="text-rose-500">*</span></label>
                <input id="name" name="name" value="{{ old('name', $team->name) }}" required class="field" placeholder="VD: Hỗ trợ chung">
                <x-input-error :messages="$errors->get('name')" class="field-error" />
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="field-label">Mô tả</label>
                <input id="description" name="description" value="{{ old('description', $team->description) }}" class="field">
            </div>
            <div>
                <label for="lead_id" class="field-label">Trưởng nhóm</label>
                <select id="lead_id" name="lead_id" class="field">
                    <option value="">-- Chưa chọn --</option>
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}" @selected((string) old('lead_id', $team->lead_id) === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="business_calendar_id" class="field-label">Lịch làm việc</label>
                <select id="business_calendar_id" name="business_calendar_id" class="field">
                    <option value="">-- Chưa chọn --</option>
                    @foreach ($calendars as $calendar)
                        <option value="{{ $calendar->id }}" @selected((string) old('business_calendar_id', $team->business_calendar_id) === (string) $calendar->id)>{{ $calendar->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assignment_strategy" class="field-label">Chiến lược phân công <span class="text-rose-500">*</span></label>
                <select id="assignment_strategy" name="assignment_strategy" required class="field">
                    @foreach ($strategies as $value => $label)
                        <option value="{{ $value }}" @selected(old('assignment_strategy', $team->assignment_strategy ?: 'skill_weighted') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="capacity" class="field-label">Định mức ticket / người <span class="text-rose-500">*</span></label>
                <input id="capacity" type="number" name="capacity" min="1" max="500" value="{{ old('capacity', $team->capacity ?: 20) }}" required class="field">
                <x-input-error :messages="$errors->get('capacity')" class="field-error" />
            </div>
        </div>

        <div>
            <p class="field-label">Thành viên nhóm</p>
            <div class="grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-2">
                @forelse ($staff as $member)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="member_ids[]" value="{{ $member->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                               @checked(in_array($member->id, array_map('intval', $selectedMembers), true))>
                        {{ $member->name }}
                    </label>
                @empty
                    <p class="text-sm text-slate-400">Chưa có kỹ thuật viên nào.</p>
                @endforelse
            </div>
            <p class="mt-1 text-xs text-slate-400">Trưởng nhóm được tự động thêm vào danh sách thành viên.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('is_active', $isEdit ? $team->is_active : true))>
            Nhóm đang hoạt động
        </label>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('admin.teams.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Thêm nhóm' }}</button>
        </div>
    </form>
</x-app-layout>
