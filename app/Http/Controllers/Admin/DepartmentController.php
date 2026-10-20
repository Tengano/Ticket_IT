<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $departments = Department::query()
            ->withCount(['users', 'tickets'])
            ->when($search, fn ($query, string $value) => $query->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.departments.index', [
            'departments' => $departments,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.departments.form', ['department' => new Department]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::query()->create($this->validated($request));

        return redirect()->route('admin.departments.index')->with('success', 'Đã thêm phòng ban.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', ['department' => $department]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return redirect()->route('admin.departments.index')->with('success', 'Đã cập nhật phòng ban.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->exists() || $department->tickets()->exists()) {
            return back()->with('error', 'Không thể xoá: phòng ban đang được người dùng hoặc ticket sử dụng.');
        }

        $department->delete();

        return back()->with('success', 'Đã xoá phòng ban.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
