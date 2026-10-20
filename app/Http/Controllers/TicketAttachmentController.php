<?php

namespace App\Http\Controllers;

use App\Enums\TicketEventType;
use App\Http\Requests\StoreTicketAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketNotifier;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function __construct(private readonly TicketAttachmentService $attachments) {}

    public function store(StoreTicketAttachmentRequest $request, Ticket $ticket, TicketService $tickets, TicketNotifier $notifier): RedirectResponse
    {
        $created = $this->attachments->store($ticket, [$request->file('file')], $request->user());

        foreach ($created as $attachment) {
            $tickets->recordEvent($ticket, $request->user(), TicketEventType::AttachmentAdded, null, null, [
                'name' => $attachment->original_name,
                'attachment_id' => $attachment->id,
            ]);
            $notifier->attachmentAdded($ticket, $request->user(), $attachment->original_name);
        }

        return back()->with('success', 'Đã tải lên tệp đính kèm.');
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404, 'Tệp không còn tồn tại.');

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
