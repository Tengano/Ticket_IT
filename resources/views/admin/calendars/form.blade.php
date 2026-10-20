<x-app-layout>
    @php($isEdit = $calendar->exists)
    @php($initialHours = old('hours', $calendar->exists ? $calendar->hours->map(fn ($hour) => [
        'weekday' => (string) $hour->weekday,
        'start_time' => substr($hour->start_time, 0, 5),
        'end_time' => substr($hour->end_time, 0, 5),
    ])->values()->all() : []))
    @php($initialHolidays = old('holidays', $calendar->exists ? $calendar->holidays->map(fn ($holiday) => [
        'date' => $holiday->date->format('Y-m-d'),
        'name' => $holiday->name,
    ])->values()->all() : []))

    <x-page-header :title="$isEdit ? 'Cập nhật lịch làm việc' : 'Thêm lịch làm việc'"
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Lịch làm việc', 'url' => route('admin.calendars.index')], ['label' => $isEdit ? 'Cập nhật' : 'Thêm mới']]" />

    <form method="POST" action="{{ $isEdit ? route('admin.calendars.update', $calendar) : route('admin.calendars.store') }}"
          x-data="{
              hours: @js($initialHours),
              holidays: @js($initialHolidays),
              addHour() { this.hours.push({ weekday: '1', start_time: '08:00', end_time: '17:00' }); },
              removeHour(index) { this.hours.splice(index, 1); },
              addHoliday() { this.holidays.push({ date: '', name: '' }); },
              removeHoliday(index) { this.holidays.splice(index, 1); }
          }"
          class="max-w-4xl space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="card grid gap-4 p-5 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="name" class="field-label">Tên lịch <span class="text-rose-500">*</span></label>
                <input id="name" name="name" value="{{ old('name', $calendar->name) }}" required class="field" placeholder="VD: Lịch hành chính">
                <x-input-error :messages="$errors->get('name')" class="field-error" />
            </div>
            <div>
                <label for="timezone" class="field-label">Múi giờ</label>
                <select id="timezone" name="timezone" class="field">
                    <option value="Asia/Ho_Chi_Minh" @selected(old('timezone', $calendar->timezone ?: 'Asia/Ho_Chi_Minh') === 'Asia/Ho_Chi_Minh')>Asia/Ho_Chi_Minh</option>
                    <option value="UTC" @selected(old('timezone', $calendar->timezone) === 'UTC')>UTC</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-3">
                <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(old('is_default', $calendar->is_default))>
                Đặt làm lịch mặc định
            </label>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Khung giờ làm việc</h2>
                    <p class="text-xs text-slate-500">Có thể có nhiều khung/ngày, ví dụ 08:00–12:00 và 13:00–17:00.</p>
                </div>
                <button type="button" class="btn-secondary px-3 py-1.5 text-xs" @click="addHour()">+ Thêm khung giờ</button>
            </div>

            <div class="mt-4 space-y-2">
                <template x-for="(hour, index) in hours" :key="index">
                    <div class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3">
                        <div class="min-w-32 flex-1">
                            <label class="field-label">Thứ</label>
                            <select :name="'hours[' + index + '][weekday]'" x-model="hour.weekday" class="field">
                                <option value="1">Thứ 2</option>
                                <option value="2">Thứ 3</option>
                                <option value="3">Thứ 4</option>
                                <option value="4">Thứ 5</option>
                                <option value="5">Thứ 6</option>
                                <option value="6">Thứ 7</option>
                                <option value="7">Chủ nhật</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Từ</label>
                            <input type="time" :name="'hours[' + index + '][start_time]'" x-model="hour.start_time" class="field">
                        </div>
                        <div>
                            <label class="field-label">Đến</label>
                            <input type="time" :name="'hours[' + index + '][end_time]'" x-model="hour.end_time" class="field">
                        </div>
                        <button type="button" class="btn-secondary px-3 py-2 text-xs text-rose-600" @click="removeHour(index)">Xoá</button>
                    </div>
                </template>

                <p x-show="hours.length === 0" class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-400">
                    Chưa có khung giờ. Nhấn “Thêm khung giờ” để bắt đầu.
                </p>
            </div>

            <x-input-error :messages="$errors->get('hours.*')" class="field-error" />
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Ngày lễ / nghỉ</h2>
                    <p class="text-xs text-slate-500">Các ngày này được bỏ qua khi tính thời hạn SLA.</p>
                </div>
                <button type="button" class="btn-secondary px-3 py-1.5 text-xs" @click="addHoliday()">+ Thêm ngày lễ</button>
            </div>

            <div class="mt-4 space-y-2">
                <template x-for="(holiday, index) in holidays" :key="index">
                    <div class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3">
                        <div>
                            <label class="field-label">Ngày</label>
                            <input type="date" :name="'holidays[' + index + '][date]'" x-model="holiday.date" class="field">
                        </div>
                        <div class="min-w-48 flex-1">
                            <label class="field-label">Tên ngày lễ</label>
                            <input type="text" :name="'holidays[' + index + '][name]'" x-model="holiday.name" class="field" placeholder="VD: Tết Dương lịch">
                        </div>
                        <button type="button" class="btn-secondary px-3 py-2 text-xs text-rose-600" @click="removeHoliday(index)">Xoá</button>
                    </div>
                </template>

                <p x-show="holidays.length === 0" class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-400">
                    Chưa cấu hình ngày lễ.
                </p>
            </div>

            <x-input-error :messages="$errors->get('holidays.*')" class="field-error" />
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.calendars.index') }}" class="btn-secondary">Huỷ</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo lịch' }}</button>
        </div>
    </form>
</x-app-layout>
