<?php

namespace App\Http\Controllers;

use App\Enums\TrangThaiPhieu;
use App\Models\PhieuYeuCau;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TongQuanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $openStatuses = collect(TrangThaiPhieu::cases())
            ->filter(fn (TrangThaiPhieu $status) => $status->isOpen())
            ->map(fn (TrangThaiPhieu $status) => $status->value)
            ->all();

        if ($user->isStaff()) {
            $baseQuery = PhieuYeuCau::query();
            $stats = [
                'open' => (clone $baseQuery)->whereIn('trang_thai', $openStatuses)->count(),
                'unassigned' => (clone $baseQuery)->whereNull('nguoi_xu_ly_id')->whereIn('trang_thai', $openStatuses)->count(),
                'mine' => (clone $baseQuery)->where('nguoi_xu_ly_id', $user->id)->whereIn('trang_thai', $openStatuses)->count(),
                'critical' => (clone $baseQuery)->where('uu_tien', 'P1')->whereIn('trang_thai', $openStatuses)->count(),
            ];

            $recentTickets = PhieuYeuCau::query()
                ->with(['requester', 'assignee', 'category'])
                ->latest()
                ->limit(8)
                ->get();

            $queue = PhieuYeuCau::query()
                ->with(['requester', 'category'])
                ->whereNull('nguoi_xu_ly_id')
                ->whereIn('trang_thai', $openStatuses)
                ->oldest()
                ->limit(5)
                ->get();
        } else {
            $baseQuery = PhieuYeuCau::query()->where('nguoi_yeu_cau_id', $user->id);
            $stats = [
                'open' => (clone $baseQuery)->whereIn('trang_thai', $openStatuses)->count(),
                'da_giai_quyet' => (clone $baseQuery)->where('trang_thai', TrangThaiPhieu::DaGiaiQuyet->value)->count(),
                'da_dong' => (clone $baseQuery)->where('trang_thai', TrangThaiPhieu::DaDong->value)->count(),
                'total' => (clone $baseQuery)->count(),
            ];

            $recentTickets = PhieuYeuCau::query()
                ->with(['assignee', 'category'])
                ->where('nguoi_yeu_cau_id', $user->id)
                ->latest()
                ->limit(8)
                ->get();

            $queue = collect();
        }

        return view('tong-quan', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'queue' => $queue,
        ]);
    }
}
