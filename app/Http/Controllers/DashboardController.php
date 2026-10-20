<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $openStatuses = collect(TicketStatus::cases())
            ->filter(fn (TicketStatus $status) => $status->isOpen())
            ->map(fn (TicketStatus $status) => $status->value)
            ->all();

        if ($user->isStaff()) {
            $baseQuery = Ticket::query();
            $stats = [
                'open' => (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
                'unassigned' => (clone $baseQuery)->whereNull('assignee_id')->whereIn('status', $openStatuses)->count(),
                'mine' => (clone $baseQuery)->where('assignee_id', $user->id)->whereIn('status', $openStatuses)->count(),
                'critical' => (clone $baseQuery)->where('priority', 'P1')->whereIn('status', $openStatuses)->count(),
            ];

            $recentTickets = Ticket::query()
                ->with(['requester', 'assignee', 'category'])
                ->latest()
                ->limit(8)
                ->get();

            $queue = Ticket::query()
                ->with(['requester', 'category'])
                ->whereNull('assignee_id')
                ->whereIn('status', $openStatuses)
                ->oldest()
                ->limit(5)
                ->get();
        } else {
            $baseQuery = Ticket::query()->where('requester_id', $user->id);
            $stats = [
                'open' => (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
                'resolved' => (clone $baseQuery)->where('status', TicketStatus::Resolved->value)->count(),
                'closed' => (clone $baseQuery)->where('status', TicketStatus::Closed->value)->count(),
                'total' => (clone $baseQuery)->count(),
            ];

            $recentTickets = Ticket::query()
                ->with(['assignee', 'category'])
                ->where('requester_id', $user->id)
                ->latest()
                ->limit(8)
                ->get();

            $queue = collect();
        }

        return view('dashboard', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'queue' => $queue,
        ]);
    }
}
