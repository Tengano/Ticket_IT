<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\Location;
use App\Models\Skill;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $users = User::query()
            ->with(['roles', 'department', 'teams'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', array_merge($this->formData(), ['user' => new User]));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'department_id' => $data['department_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_vip' => $data['is_vip'] ?? false,
            ]);

            $user->syncRoles([$data['role']]);
            $this->syncRelations($user, $data);

            return $user;
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Đã tạo tài khoản {$user->name}.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', array_merge($this->formData(), [
            'user' => $user->load(['roles', 'teams', 'skills']),
        ]));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $attributes = Arr::only($data, [
                'name', 'email', 'phone', 'department_id', 'location_id', 'is_active', 'is_vip',
            ]);

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);
            $user->syncRoles([$data['role']]);
            $this->syncRelations($user, $data);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Đã cập nhật tài khoản {$user->name}.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Bạn không thể tự vô hiệu hoá tài khoản của mình.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "Đã kích hoạt tài khoản {$user->name}."
            : "Đã vô hiệu hoá tài khoản {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Bạn không thể xoá tài khoản của mình.');

        if ($user->requestedTickets()->exists() || $user->assignedTickets()->exists()) {
            return back()->with('error', 'Không thể xoá: người dùng đã gửi hoặc đang phụ trách ticket. Hãy vô hiệu hoá tài khoản thay vì xoá.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Đã xoá tài khoản {$user->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'departments' => Department::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(User $user, array $data): void
    {
        $user->teams()->sync($data['team_ids'] ?? []);

        $skills = collect($data['skill_ids'] ?? [])
            ->mapWithKeys(fn ($skillId) => [(int) $skillId => ['level' => 3]])
            ->all();

        $user->skills()->sync($skills);
    }
}
