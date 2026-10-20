<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCalendar;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $teams = Team::query()
            ->with(['lead', 'businessCalendar'])
            ->withCount(['members', 'tickets'])
            ->when($search, fn ($query, string $value) => $query->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teams.index', [
            'teams' => $teams,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.teams.form', array_merge($this->formData(), ['team' => new Team]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $team = DB::transaction(function () use ($data): Team {
            $team = Team::query()->create($this->attributes($data));
            $this->syncMembers($team, $data);

            return $team;
        });

        return redirect()->route('admin.teams.index')->with('success', "Đã tạo nhóm {$team->name}.");
    }

    public function edit(Team $team): View
    {
        return view('admin.teams.form', array_merge($this->formData(), [
            'team' => $team->load('members'),
        ]));
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validated($request, $team);

        DB::transaction(function () use ($team, $data): void {
            $team->update($this->attributes($data));
            $this->syncMembers($team, $data);
        });

        return redirect()->route('admin.teams.index')->with('success', "Đã cập nhật nhóm {$team->name}.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        if ($team->tickets()->whereNotIn('status', ['resolved', 'closed'])->exists()) {
            return back()->with('error', 'Không thể xoá: nhóm đang xử lý ticket chưa hoàn tất.');
        }

        $team->delete();

        return back()->with('success', 'Đã xoá nhóm.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'staff' => User::query()->active()->role(['agent', 'lead'])->orderBy('name')->get(),
            'calendars' => BusinessCalendar::query()->orderBy('name')->get(),
            'strategies' => [
                'skill_weighted' => 'Khớp kỹ năng (khuyến nghị)',
                'least_open' => 'Ít ticket đang mở nhất',
                'round_robin' => 'Xoay vòng',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Team $team = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('teams', 'code')->ignore($team)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'lead_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'business_calendar_id' => ['nullable', Rule::exists('business_calendars', 'id')],
            'assignment_strategy' => ['required', Rule::in(['skill_weighted', 'least_open', 'round_robin'])],
            'capacity' => ['required', 'integer', 'between:1,500'],
            'is_active' => ['boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => [Rule::exists('users', 'id')->where('is_active', true)],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'business_calendar_id' => $data['business_calendar_id'] ?? null,
            'assignment_strategy' => $data['assignment_strategy'],
            'capacity' => $data['capacity'],
            'is_active' => $data['is_active'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncMembers(Team $team, array $data): void
    {
        $memberIds = collect($data['member_ids'] ?? [])->map(fn ($id) => (int) $id);
        $leadId = ! empty($data['lead_id']) ? (int) $data['lead_id'] : null;

        if ($leadId !== null && ! $memberIds->contains($leadId)) {
            $memberIds->push($leadId);
        }

        $pivot = $memberIds
            ->unique()
            ->mapWithKeys(fn (int $id) => [$id => [
                'is_lead' => $id === $leadId,
                'capacity' => null,
            ]])
            ->all();

        $team->members()->sync($pivot);
    }
}
