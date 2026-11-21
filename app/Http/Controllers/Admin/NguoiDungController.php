<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CapNhatNguoiDungRequest;
use App\Http\Requests\Admin\LuuNguoiDungRequest;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhongBan;
use App\Models\ViTri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class NguoiDungController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:32'],
            'trang_thai' => ['nullable', 'in:active,inactive'],
        ]);

        $users = NguoiDung::query()
            ->with(['roles', 'department', 'teams'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('ten', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when(($filters['trang_thai'] ?? null) === 'active', fn ($query) => $query->where('dang_hoat_dong', true))
            ->when(($filters['trang_thai'] ?? null) === 'inactive', fn ($query) => $query->where('dang_hoat_dong', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quan-tri.nguoi-dung.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('quan-tri.nguoi-dung.form', array_merge($this->formData(), ['user' => new NguoiDung]));
    }

    public function store(LuuNguoiDungRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): NguoiDung {
            $user = NguoiDung::query()->create([
                'ten' => $data['ten'],
                'email' => $data['email'],
                'dien_thoai' => $data['dien_thoai'] ?? null,
                'password' => $data['password'],
                'phong_ban_id' => $data['phong_ban_id'] ?? null,
                'vi_tri_id' => $data['vi_tri_id'] ?? null,
                'dang_hoat_dong' => $data['dang_hoat_dong'] ?? true,
            ]);

            $user->syncRoles([$data['role']]);
            $this->syncRelations($user, $data);

            return $user;
        });

        return redirect()
            ->route('quan-tri.nguoi-dung.index')
            ->with('success', "Đã tạo tài khoản {$user->ten}.");
    }

    public function edit(NguoiDung $user): View
    {
        return view('quan-tri.nguoi-dung.form', array_merge($this->formData(), [
            'user' => $user->load(['roles', 'teams']),
        ]));
    }

    public function update(CapNhatNguoiDungRequest $request, NguoiDung $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $attributes = Arr::only($data, [
                'ten', 'email', 'dien_thoai', 'phong_ban_id', 'vi_tri_id', 'dang_hoat_dong',
            ]);

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);
            $user->syncRoles([$data['role']]);
            $this->syncRelations($user, $data);
        });

        return redirect()
            ->route('quan-tri.nguoi-dung.index')
            ->with('success', "Đã cập nhật tài khoản {$user->ten}.");
    }

    public function toggle(Request $request, NguoiDung $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Bạn không thể tự vô hiệu hoá tài khoản của mình.');

        $user->update(['dang_hoat_dong' => ! $user->dang_hoat_dong]);

        return back()->with('success', $user->dang_hoat_dong
            ? "Đã kích hoạt tài khoản {$user->ten}."
            : "Đã vô hiệu hoá tài khoản {$user->ten}.");
    }

    public function destroy(Request $request, NguoiDung $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Bạn không thể xoá tài khoản của mình.');

        if ($user->requestedTickets()->exists() || $user->assignedTickets()->exists()) {
            return back()->with('error', 'Không thể xoá: người dùng đã gửi hoặc đang phụ trách phiếu. Hãy vô hiệu hoá tài khoản thay vì xoá.');
        }

        $user->delete();

        return redirect()
            ->route('quan-tri.nguoi-dung.index')
            ->with('success', "Đã xoá tài khoản {$user->ten}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'departments' => PhongBan::query()->orderBy('ten')->get(),
            'locations' => ViTri::query()->orderBy('ten')->get(),
            'teams' => NhomXuLy::query()->where('dang_hoat_dong', true)->orderBy('ten')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(NguoiDung $user, array $data): void
    {
        $user->teams()->sync($data['team_ids'] ?? []);
    }
}
