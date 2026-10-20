<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessCalendarController extends Controller
{
    public function index(): View
    {
        $calendars = BusinessCalendar::query()
            ->withCount(['hours', 'holidays', 'teams'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.calendars.index', [
            'calendars' => $calendars,
        ]);
    }

    public function create(): View
    {
        return view('admin.calendars.form', ['calendar' => new BusinessCalendar]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $calendar = DB::transaction(function () use ($data): BusinessCalendar {
            if ($data['is_default']) {
                BusinessCalendar::query()->update(['is_default' => false]);
            }

            $calendar = BusinessCalendar::query()->create([
                'name' => $data['name'],
                'timezone' => $data['timezone'],
                'is_default' => $data['is_default'],
            ]);

            $this->syncHoursAndHolidays($calendar, $data);

            return $calendar;
        });

        return redirect()->route('admin.calendars.index')->with('success', "Đã tạo lịch {$calendar->name}.");
    }

    public function edit(BusinessCalendar $calendar): View
    {
        return view('admin.calendars.form', [
            'calendar' => $calendar->load(['hours', 'holidays']),
        ]);
    }

    public function update(Request $request, BusinessCalendar $calendar): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($calendar, $data): void {
            if ($data['is_default']) {
                BusinessCalendar::query()->whereKeyNot($calendar->id)->update(['is_default' => false]);
            }

            $calendar->update([
                'name' => $data['name'],
                'timezone' => $data['timezone'],
                'is_default' => $data['is_default'],
            ]);

            $this->syncHoursAndHolidays($calendar, $data);
        });

        return redirect()->route('admin.calendars.index')->with('success', "Đã cập nhật lịch {$calendar->name}.");
    }

    public function destroy(BusinessCalendar $calendar): RedirectResponse
    {
        if ($calendar->teams()->exists()) {
            return back()->with('error', 'Không thể xoá: lịch đang được nhóm sử dụng.');
        }

        $calendar->delete();

        return back()->with('success', 'Đã xoá lịch làm việc.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $request->merge([
            'is_default' => $request->boolean('is_default'),
            'hours' => collect($request->input('hours', []))
                ->filter(fn ($hour) => ! empty($hour['weekday']) && ! empty($hour['start_time']) && ! empty($hour['end_time']))
                ->values()
                ->all(),
            'holidays' => collect($request->input('holidays', []))
                ->filter(fn ($holiday) => ! empty($holiday['date']))
                ->values()
                ->all(),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:64', Rule::in(['Asia/Ho_Chi_Minh', 'UTC'])],
            'is_default' => ['boolean'],
            'hours' => ['array'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7'],
            'hours.*.start_time' => ['required', 'date_format:H:i'],
            'hours.*.end_time' => ['required', 'date_format:H:i', 'after:hours.*.start_time'],
            'holidays' => ['array'],
            'holidays.*.date' => ['required', 'date', 'distinct'],
            'holidays.*.name' => ['nullable', 'string', 'max:255'],
        ], [
            'hours.*.end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'holidays.*.date.distinct' => 'Ngày lễ bị trùng trong danh sách.',
            'hours.*.weekday.between' => 'Thứ trong tuần không hợp lệ.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncHoursAndHolidays(BusinessCalendar $calendar, array $data): void
    {
        $calendar->hours()->delete();

        foreach ($data['hours'] as $hour) {
            $calendar->hours()->create([
                'weekday' => (int) $hour['weekday'],
                'start_time' => $hour['start_time'],
                'end_time' => $hour['end_time'],
            ]);
        }

        $calendar->holidays()->delete();

        foreach ($data['holidays'] as $holiday) {
            $calendar->holidays()->create([
                'date' => $holiday['date'],
                'name' => $holiday['name'] ?? null,
            ]);
        }
    }
}
