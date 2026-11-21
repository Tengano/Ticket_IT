<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ViTri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViTriController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $locations = ViTri::query()
            ->withCount(['tickets'])
            ->when($search, fn ($query, string $value) => $query->where('ten', 'like', "%{$value}%"))
            ->orderBy('ten')
            ->paginate(15)
            ->withQueryString();

        return view('quan-tri.vi-tri.index', [
            'locations' => $locations,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('quan-tri.vi-tri.form', ['location' => new ViTri]);
    }

    public function store(Request $request): RedirectResponse
    {
        ViTri::query()->create($this->validated($request));

        return redirect()->route('quan-tri.vi-tri.index')->with('success', 'Đã thêm vị trí.');
    }

    public function edit(ViTri $location): View
    {
        return view('quan-tri.vi-tri.form', ['location' => $location]);
    }

    public function update(Request $request, ViTri $location): RedirectResponse
    {
        $location->update($this->validated($request));

        return redirect()->route('quan-tri.vi-tri.index')->with('success', 'Đã cập nhật vị trí.');
    }

    public function destroy(ViTri $location): RedirectResponse
    {
        if ($location->tickets()->exists() || $location->users()->exists()) {
            return back()->with('error', 'Không thể xoá: vị trí đang được phiếu hoặc người dùng sử dụng.');
        }

        $location->delete();

        return back()->with('success', 'Đã xoá vị trí.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:255'],
            'dang_hoat_dong' => ['boolean'],
        ]);

        $data['dang_hoat_dong'] = $request->boolean('dang_hoat_dong');

        return $data;
    }
}
