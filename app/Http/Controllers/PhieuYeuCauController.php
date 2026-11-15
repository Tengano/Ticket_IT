<?php

namespace App\Http\Controllers;

use App\Enums\LoaiSuKienPhieu;
use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use App\Http\Requests\CapNhatPhieuYeuCauRequest;
use App\Http\Requests\LuuPhieuYeuCauRequest;
use App\Models\DanhMuc;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Models\PhongBan;
use App\Models\ViTri;
use App\Services\Phieu\DichVuPhieuYeuCau;
use App\Services\Phieu\DichVuTepDinhKem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhieuYeuCauController extends Controller
{
    public function __construct(
        private readonly DichVuPhieuYeuCau $tickets,
        private readonly DichVuTepDinhKem $attachments,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'trang_thai' => ['nullable', Rule::enum(TrangThaiPhieu::class)],
            'uu_tien' => ['nullable', Rule::enum(UuTienPhieu::class)],
            'danh_muc_id' => ['nullable', 'integer', 'exists:danh_muc,id'],
            'nhom_xu_ly_id' => ['nullable', 'integer', 'exists:nhom_xu_ly,id'],
            'nguoi_xu_ly' => ['nullable', 'string', 'max:32'],
        ]);

        $tickets = PhieuYeuCau::query()
            ->visibleTo($user)
            ->with(['requester', 'assignee', 'category.parent', 'assignedTeam', 'department'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('ma', 'like', "%{$search}%")
                        ->orWhere('tieu_de', 'like', "%{$search}%");
                });
            })
            ->when($filters['trang_thai'] ?? null, fn ($query, string $status) => $query->where('trang_thai', $status))
            ->when($filters['uu_tien'] ?? null, fn ($query, string $priority) => $query->where('uu_tien', $priority))
            ->when($filters['danh_muc_id'] ?? null, function ($query, int $categoryId): void {
                $query->whereHas('category', function ($query) use ($categoryId): void {
                    $query->whereKey($categoryId)->orWhere('danh_muc_cha_id', $categoryId);
                });
            })
            ->when($filters['nhom_xu_ly_id'] ?? null, fn ($query, int $teamId) => $query->where('nhom_xu_ly_id', $teamId))
            ->when(($filters['nguoi_xu_ly'] ?? null) === 'me', fn ($query) => $query->where('nguoi_xu_ly_id', $user->id))
            ->when(($filters['nguoi_xu_ly'] ?? null) === 'unassigned', fn ($query) => $query->whereNull('nguoi_xu_ly_id'))
            ->when(ctype_digit($filters['nguoi_xu_ly'] ?? ''), fn ($query) => $query->where('nguoi_xu_ly_id', (int) $filters['nguoi_xu_ly']))
            ->latest()
            ->paginate(config('ho_tro.phieu.so_ban_ghi_moi_trang'))
            ->withQueryString();

        return view('phieu-yeu-cau.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'categories' => DanhMuc::query()->roots()->with('children')->get(),
            'teams' => NhomXuLy::query()->where('dang_hoat_dong', true)->orderBy('ten')->get(),
            'staff' => NguoiDung::query()->active()->role(['agent', 'lead'])->orderBy('ten')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PhieuYeuCau::class);

        return view('phieu-yeu-cau.create', [
            'categories' => DanhMuc::query()->roots()->with(['children' => fn ($query) => $query->active()])->active()->get(),
            'departments' => PhongBan::query()->where('dang_hoat_dong', true)->orderBy('ten')->get(),
            'locations' => ViTri::query()->where('dang_hoat_dong', true)->orderBy('ten')->get(),
            'defaultDepartmentId' => $request->user()->phong_ban_id,
            'defaultLocationId' => $request->user()->vi_tri_id,
        ]);
    }

    public function store(LuuPhieuYeuCauRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create(
            $request->user(),
            $request->safe()->except(['attachments']),
        );

        if ($request->hasFile('attachments')) {
            $created = $this->attachments->store($ticket, $request->file('attachments'), $request->user());

            foreach ($created as $attachment) {
                $this->tickets->recordEvent($ticket, $request->user(), LoaiSuKienPhieu::DinhKemTep, null, null, [
                    'ten' => $attachment->ten_goc,
                    'attachment_id' => $attachment->id,
                ]);
            }
        }

        return redirect()
            ->route('phieu.show', $ticket)
            ->with('success', "Đã tạo phiếu {$ticket->ma} thành công.");
    }

    public function show(Request $request, PhieuYeuCau $ticket): View
    {
        $this->authorize('view', $ticket);

        $user = $request->user();
        $isStaff = $user->isStaff();

        $ticket->load([
            'requester', 'assignee', 'assignedTeam', 'category.parent', 'department', 'location', 'watchers',
        ]);

        $comments = $ticket->comments()
            ->with(['author', 'attachments.uploader'])
            ->when(! $isStaff, fn ($query) => $query->where('loai', 'cong_khai'))
            ->oldest()
            ->get();

        $attachments = $ticket->attachments()
            ->with(['uploader', 'comment'])
            ->when(! $isStaff, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('binh_luan_id')
                        ->orWhereHas('comment', fn ($commentQuery) => $commentQuery->where('loai', 'cong_khai'));
                });
            })
            ->latest()
            ->get();

        $events = $ticket->events()
            ->with('actor')
            ->when(! $isStaff, fn ($query) => $query->where('loai', '!=', LoaiSuKienPhieu::GhiChuNoiBo->value))
            ->latest()
            ->get();

        $user->unreadNotifications->each(function ($notification) use ($ticket): void {
            if (($notification->data['phieu_yeu_cau_id'] ?? null) === $ticket->id) {
                $notification->markAsRead();
            }
        });

        return view('phieu-yeu-cau.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'attachments' => $attachments,
            'events' => $events,
            'availableStatuses' => collect($ticket->availableTransitions()),
            'staff' => NguoiDung::query()->active()->role(['agent', 'lead'])->orderBy('ten')->get(),
            'teams' => NhomXuLy::query()->where('dang_hoat_dong', true)->orderBy('ten')->get(),
        ]);
    }

    public function update(CapNhatPhieuYeuCauRequest $request, PhieuYeuCau $ticket): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        if (! empty($data['trang_thai'])) {
            $ticket = $this->tickets->changeStatus(
                $ticket,
                TrangThaiPhieu::from($data['trang_thai']),
                $actor,
                $data['ly_do'] ?? null,
            );
        }

        if (! empty($data['uu_tien'])) {
            $ticket = $this->tickets->changePriority($ticket, UuTienPhieu::from($data['uu_tien']), $actor);
        }

        if (array_key_exists('nguoi_xu_ly_id', $data) || array_key_exists('nhom_xu_ly_id', $data)) {
            $this->authorize('assign', $ticket);

            $assignee = ! empty($data['nguoi_xu_ly_id']) ? NguoiDung::query()->find($data['nguoi_xu_ly_id']) : null;
            $team = ! empty($data['nhom_xu_ly_id']) ? NhomXuLy::query()->find($data['nhom_xu_ly_id']) : null;

            $this->tickets->assign($ticket, $assignee, $team, $actor);
        }

        return back()->with('success', 'Đã cập nhật phiếu.');
    }

    public function close(Request $request, PhieuYeuCau $ticket): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $reason = $request->validate([
            'ly_do' => ['nullable', 'string', 'max:255'],
        ])['ly_do'] ?? null;

        $this->tickets->close($ticket, $request->user(), $reason);

        return back()->with('success', 'Đã đóng phiếu.');
    }

    public function reopen(Request $request, PhieuYeuCau $ticket): RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $reason = $request->validate([
            'ly_do' => ['nullable', 'string', 'max:255'],
        ])['ly_do'] ?? null;

        $this->tickets->reopen($ticket, $request->user(), $reason);

        return back()->with('success', 'Đã mở lại phiếu.');
    }

    public function assign(Request $request, PhieuYeuCau $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $data = $request->validate([
            'nguoi_xu_ly_id' => ['nullable', Rule::exists('nguoi_dung', 'id')->where('dang_hoat_dong', true)],
            'nhom_xu_ly_id' => ['nullable', Rule::exists('nhom_xu_ly', 'id')->where('dang_hoat_dong', true)],
        ]);

        $assignee = ! empty($data['nguoi_xu_ly_id']) ? NguoiDung::query()->find($data['nguoi_xu_ly_id']) : null;
        $team = ! empty($data['nhom_xu_ly_id']) ? NhomXuLy::query()->find($data['nhom_xu_ly_id']) : null;

        $this->tickets->assign($ticket, $assignee, $team, $request->user());

        return back()->with('success', 'Đã cập nhật phân công phiếu.');
    }
}
