<?php

namespace App\Http\Controllers;

use App\Enums\CommentType;
use App\Enums\TicketEventType;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketNotifier;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TicketCommentController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly TicketAttachmentService $attachments,
        private readonly TicketNotifier $notifier,
    ) {}

    public function store(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $data = $request->validated();
        $type = CommentType::from($data['type']);
        $user = $request->user();

        if ($type->isInternal()) {
            $this->authorize('internalNote', $ticket);
        }

        $comment = DB::transaction(function () use ($request, $ticket, $data, $type, $user) {
            $isFirstResponse = false;

            if (! $type->isInternal() && $user->isStaff() && $ticket->first_responded_at === null) {
                $isFirstResponse = true;
                $ticket->update(['first_responded_at' => now()]);
            }

            $comment = $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $data['body'],
                'type' => $type,
                'is_first_response' => $isFirstResponse,
            ]);

            if ($request->hasFile('attachments')) {
                $this->attachments->store($ticket, $request->file('attachments'), $user, $comment);
            }

            $this->tickets->recordEvent(
                $ticket,
                $user,
                $type->isInternal() ? TicketEventType::InternalNoteAdded : TicketEventType::Commented,
                null,
                null,
                ['comment_id' => $comment->id],
            );

            return $comment;
        });

        $this->notifier->commentAdded($comment->load(['ticket', 'author']));

        $message = $type->isInternal() ? 'Đã thêm ghi chú nội bộ.' : 'Đã gửi phản hồi.';

        return redirect()
            ->to(route('tickets.show', $ticket).'#comment-'.$comment->id)
            ->with('success', $message);
    }
}
