<x-app-layout>
    <x-page-header title="Lịch làm việc" description="Khung giờ làm việc, ngày lễ dùng để tính thời hạn SLA theo giờ hành chính (Sprint 5)."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Lịch làm việc']]">
        <x-slot name="actions">
            <a href="{{ route('admin.calendars.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm lịch</a>
        </x-slot>
    </x-page-header>

    <div class="card overflow-hidden">
        @if ($calendars->isEmpty())
            <x-empty-state title="Chưa có lịch làm việc" description="Tạo lịch hành chính để bắt đầu cấu hình SLA." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr><th>Lịch</th><th>Múi giờ</th><th>Khung giờ</th><th>Ngày lễ</th><th>Nhóm sử dụng</th><th>Mặc định</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($calendars as $calendar)
                            <tr>
                                <td class="font-medium text-slate-800">{{ $calendar->name }}</td>
                                <td class="text-sm">{{ $calendar->timezone }}</td>
                                <td class="text-sm">{{ $calendar->hours_count }} khung</td>
                                <td class="text-sm">{{ $calendar->holidays_count }} ngày</td>
                                <td class="text-sm">{{ $calendar->teams_count }}</td>
                                <td>
                                    @if ($calendar->is_default)
                                        <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">Mặc định</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.calendars.edit', $calendar) }}" class="btn-secondary px-3 py-1.5 text-xs">Sửa</a>
                                        <form method="POST" action="{{ route('admin.calendars.destroy', $calendar) }}" onsubmit="return confirm('Xoá lịch làm việc này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Xoá</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $calendars->links() }}</div>
        @endif
    </div>
</x-app-layout>
