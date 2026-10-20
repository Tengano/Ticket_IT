<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $skills = Skill::query()
            ->withCount('users')
            ->when($search, fn ($query, string $value) => $query->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.skills.index', [
            'skills' => $skills,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.skills.form', ['skill' => new Skill]);
    }

    public function store(Request $request): RedirectResponse
    {
        Skill::query()->create($this->validated($request));

        return redirect()->route('admin.skills.index')->with('success', 'Đã thêm kỹ năng.');
    }

    public function edit(Skill $skill): View
    {
        return view('admin.skills.form', ['skill' => $skill]);
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $skill->update($this->validated($request, $skill));

        return redirect()->route('admin.skills.index')->with('success', 'Đã cập nhật kỹ năng.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return back()->with('success', 'Đã xoá kỹ năng.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Skill $skill = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('skills', 'code')->ignore($skill)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
