<?php

namespace App\Http\Controllers;

use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly TicketAttachmentService $attachments,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'assignee' => ['nullable', 'string', 'max:32'],
        ]);

        $tickets = Ticket::query()
            ->visibleTo($user)
            ->with(['requester', 'assignee', 'category.parent', 'assignedTeam', 'department'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($filters['category_id'] ?? null, function ($query, int $categoryId): void {
                $query->whereHas('category', function ($query) use ($categoryId): void {
                    $query->whereKey($categoryId)->orWhere('parent_id', $categoryId);
                });
            })
            ->when($filters['team_id'] ?? null, fn ($query, int $teamId) => $query->where('assigned_team_id', $teamId))
            ->when(($filters['assignee'] ?? null) === 'me', fn ($query) => $query->where('assignee_id', $user->id))
            ->when(($filters['assignee'] ?? null) === 'unassigned', fn ($query) => $query->whereNull('assignee_id'))
            ->when(ctype_digit($filters['assignee'] ?? ''), fn ($query) => $query->where('assignee_id', (int) $filters['assignee']))
            ->latest()
            ->paginate(config('helpdesk.tickets.per_page'))
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'categories' => Category::query()->roots()->with('children')->get(),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(),
            'staff' => User::query()->active()->role(['agent', 'lead'])->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::query()->roots()->with(['children' => fn ($query) => $query->active()])->active()->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'defaultDepartmentId' => $request->user()->department_id,
            'defaultLocationId' => $request->user()->location_id,
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create(
            $request->user(),
            $request->safe()->except(['attachments']),
        );

        if ($request->hasFile('attachments')) {
            $created = $this->attachments->store($ticket, $request->file('attachments'), $request->user());

            foreach ($created as $attachment) {
                $this->tickets->recordEvent($ticket, $request->user(), TicketEventType::AttachmentAdded, null, null, [
                    'name' => $attachment->original_name,
                    'attachment_id' => $attachment->id,
                ]);
            }
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Đã tạo ticket {$ticket->code} thành công.");
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $user = $request->user();
        $isStaff = $user->isStaff();

        $ticket->load([
            'requester', 'assignee', 'assignedTeam', 'category.parent', 'department', 'location', 'watchers',
        ]);

        $comments = $ticket->comments()
            ->with(['author', 'attachments.uploader'])
            ->when(! $isStaff, fn ($query) => $query->where('type', 'public'))
            ->oldest()
            ->get();

        $attachments = $ticket->attachments()
            ->with(['uploader', 'comment'])
            ->when(! $isStaff, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('ticket_comment_id')
                        ->orWhereHas('comment', fn ($commentQuery) => $commentQuery->where('type', 'public'));
                });
            })
            ->latest()
            ->get();

        $events = $ticket->events()
            ->with('actor')
            ->when(! $isStaff, fn ($query) => $query->where('type', '!=', TicketEventType::InternalNoteAdded->value))
            ->latest()
            ->get();

        $user->unreadNotifications->each(function ($notification) use ($ticket): void {
            if (($notification->data['ticket_id'] ?? null) === $ticket->id) {
                $notification->markAsRead();
            }
        });

        return view('tickets.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'attachments' => $attachments,
            'events' => $events,
            'availableStatuses' => collect($ticket->availableTransitions()),
            'staff' => User::query()->active()->role(['agent', 'lead'])->orderBy('name')->get(),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        if (! empty($data['status'])) {
            $ticket = $this->tickets->changeStatus(
                $ticket,
                TicketStatus::from($data['status']),
                $actor,
                $data['reason'] ?? null,
            );
        }

        if (! empty($data['priority'])) {
            $ticket = $this->tickets->changePriority($ticket, TicketPriority::from($data['priority']), $actor);
        }

        if (array_key_exists('assignee_id', $data) || array_key_exists('assigned_team_id', $data)) {
            $this->authorize('assign', $ticket);

            $assignee = ! empty($data['assignee_id']) ? User::query()->find($data['assignee_id']) : null;
            $team = ! empty($data['assigned_team_id']) ? Team::query()->find($data['assigned_team_id']) : null;

            $this->tickets->assign($ticket, $assignee, $team, $actor);
        }

        return back()->with('success', 'Đã cập nhật ticket.');
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $reason = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ])['reason'] ?? null;

        $this->tickets->close($ticket, $request->user(), $reason);

        return back()->with('success', 'Đã đóng ticket.');
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $reason = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ])['reason'] ?? null;

        $this->tickets->reopen($ticket, $request->user(), $reason);

        return back()->with('success', 'Đã mở lại ticket.');
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $data = $request->validate([
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'assigned_team_id' => ['nullable', Rule::exists('teams', 'id')->where('is_active', true)],
        ]);

        $assignee = ! empty($data['assignee_id']) ? User::query()->find($data['assignee_id']) : null;
        $team = ! empty($data['assigned_team_id']) ? Team::query()->find($data['assigned_team_id']) : null;

        $this->tickets->assign($ticket, $assignee, $team, $request->user());

        return back()->with('success', 'Đã cập nhật phân công ticket.');
    }
}
