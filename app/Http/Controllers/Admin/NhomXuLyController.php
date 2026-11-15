<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NhomXuLyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;

        $teams = NhomXuLy::query()
            ->with('lead')
            ->withCount(['members', 'tickets'])
            ->when($search, fn ($query, string $value) => $query->where('ten', 'like', "%{$value}%")->orWhere('ma', 'like', "%{$value}%"))
            ->orderBy('ten')
            ->paginate(15)
            ->withQueryString();

        return view('quan-tri.nhom-xu-ly.index', [
            'teams' => $teams,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('quan-tri.nhom-xu-ly.form', array_merge($this->formData(), ['team' => new NhomXuLy]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $team = DB::transaction(function () use ($data): NhomXuLy {
            $team = NhomXuLy::query()->create($this->attributes($data));
            $this->syncMembers($team, $data);

            return $team;
        });

        return redirect()->route('quan-tri.nhom-xu-ly.index')->with('success', "Đã tạo nhóm {$team->ten}.");
    }

    public function edit(NhomXuLy $team): View
    {
        return view('quan-tri.nhom-xu-ly.form', array_merge($this->formData(), [
            'team' => $team->load('members'),
        ]));
    }

    public function update(Request $request, NhomXuLy $team): RedirectResponse
    {
        $data = $this->validated($request, $team);

        DB::transaction(function () use ($team, $data): void {
            $team->update($this->attributes($data));
            $this->syncMembers($team, $data);
        });

        return redirect()->route('quan-tri.nhom-xu-ly.index')->with('success', "Đã cập nhật nhóm {$team->ten}.");
    }

    public function destroy(NhomXuLy $team): RedirectResponse
    {
        if ($team->tickets()->whereNotIn('trang_thai', ['da_giai_quyet', 'da_dong'])->exists()) {
            return back()->with('error', 'Không thể xoá: nhóm đang xử lý phiếu chưa hoàn tất.');
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
            'staff' => NguoiDung::query()->active()->role(['agent', 'lead'])->orderBy('ten')->get(),
            'strategies' => NhomXuLy::strategies(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?NhomXuLy $team = null): array
    {
        $data = $request->validate([
            'ma' => ['required', 'string', 'max:32', Rule::unique('nhom_xu_ly', 'ma')->ignore($team)],
            'ten' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string', 'max:255'],
            'truong_nhom_id' => ['nullable', Rule::exists('nguoi_dung', 'id')->where('dang_hoat_dong', true)],
            'chien_luoc_phan_cong' => ['required', Rule::in(['khop_ky_nang', 'it_viec_nhat', 'xoay_vong'])],
            'dinh_muc' => ['required', 'integer', 'between:1,500'],
            'dang_hoat_dong' => ['boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => [Rule::exists('nguoi_dung', 'id')->where('dang_hoat_dong', true)],
        ]);

        $data['dang_hoat_dong'] = $request->boolean('dang_hoat_dong');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'ma' => $data['ma'],
            'ten' => $data['ten'],
            'mo_ta' => $data['mo_ta'] ?? null,
            'truong_nhom_id' => $data['truong_nhom_id'] ?? null,
            'chien_luoc_phan_cong' => $data['chien_luoc_phan_cong'],
            'dinh_muc' => $data['dinh_muc'],
            'dang_hoat_dong' => $data['dang_hoat_dong'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncMembers(NhomXuLy $team, array $data): void
    {
        $memberIds = collect($data['member_ids'] ?? [])->map(fn ($id) => (int) $id);
        $leadId = ! empty($data['truong_nhom_id']) ? (int) $data['truong_nhom_id'] : null;

        if ($leadId !== null && ! $memberIds->contains($leadId)) {
            $memberIds->push($leadId);
        }

        $pivot = $memberIds
            ->unique()
            ->mapWithKeys(fn (int $id) => [$id => [
                'la_truong_nhom' => $id === $leadId,
                'dinh_muc' => null,
            ]])
            ->all();

        $team->members()->sync($pivot);
    }
}
