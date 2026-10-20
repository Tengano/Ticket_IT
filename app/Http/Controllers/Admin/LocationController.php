<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $locations = Location::query()
            ->withCount(['tickets'])
            ->when($search, fn ($query, string $value) => $query->where('name', 'like', "%{$value}%")
                ->orWhere('building', 'like', "%{$value}%")
                ->orWhere('zone', 'like', "%{$value}%"))
            ->orderBy('building')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.locations.index', [
            'locations' => $locations,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.locations.form', ['location' => new Location]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::query()->create($this->validated($request));

        return redirect()->route('admin.locations.index')->with('success', 'Đã thêm vị trí.');
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.form', ['location' => $location]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $location->update($this->validated($request));

        return redirect()->route('admin.locations.index')->with('success', 'Đã cập nhật vị trí.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->tickets()->exists() || $location->agents()->exists()) {
            return back()->with('error', 'Không thể xoá: vị trí đang được ticket hoặc kỹ thuật viên sử dụng.');
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
            'name' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:32'],
            'zone' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
