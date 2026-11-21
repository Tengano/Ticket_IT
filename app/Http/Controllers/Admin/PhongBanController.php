<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhongBan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhongBanController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $departments = PhongBan::query()
            ->withCount(['users', 'tickets'])
            ->when($search, fn ($query, string $value) => $query->where('ten', 'like', "%{$value}%")->orWhere('ma', 'like', "%{$value}%"))
            ->orderBy('ten')
            ->paginate(15)
            ->withQueryString();

        return view('quan-tri.phong-ban.index', [
            'departments' => $departments,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('quan-tri.phong-ban.form', ['department' => new PhongBan]);
    }

    public function store(Request $request): RedirectResponse
    {
        PhongBan::query()->create($this->validated($request));

        return redirect()->route('quan-tri.phong-ban.index')->with('success', 'Đã thêm phòng ban.');
    }

    public function edit(PhongBan $department): View
    {
        return view('quan-tri.phong-ban.form', ['department' => $department]);
    }

    public function update(Request $request, PhongBan $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return redirect()->route('quan-tri.phong-ban.index')->with('success', 'Đã cập nhật phòng ban.');
    }

    public function destroy(PhongBan $department): RedirectResponse
    {
        if ($department->users()->exists() || $department->tickets()->exists()) {
            return back()->with('error', 'Không thể xoá: phòng ban đang được người dùng hoặc phiếu sử dụng.');
        }

        $department->delete();

        return back()->with('success', 'Đã xoá phòng ban.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PhongBan $department = null): array
    {
        $data = $request->validate([
            'ma' => ['required', 'string', 'max:32', Rule::unique('phong_ban', 'ma')->ignore($department)],
            'ten' => ['required', 'string', 'max:255'],
            'dang_hoat_dong' => ['boolean'],
        ]);

        $data['dang_hoat_dong'] = $request->boolean('dang_hoat_dong');

        return $data;
    }
}
