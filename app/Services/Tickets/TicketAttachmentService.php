<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TicketAttachmentService
{
    /**
     * Lưu danh sách tệp đính kèm vào storage/app/private/tickets/{id}.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, TicketAttachment>
     */
    public function store(Ticket $ticket, array $files, User $uploader, ?TicketComment $comment = null): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("tickets/{$ticket->id}", 'local');

            if ($path === false) {
                continue;
            }

            $attachments[] = TicketAttachment::query()->create([
                'ticket_id' => $ticket->id,
                'ticket_comment_id' => $comment?->id,
                'uploaded_by' => $uploader->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $attachments;
    }

    public function delete(TicketAttachment $attachment): void
    {
        try {
            Storage::disk($attachment->disk)->delete($attachment->path);
        } catch (Throwable $exception) {
            report($exception);
        }

        $attachment->delete();
    }
}
