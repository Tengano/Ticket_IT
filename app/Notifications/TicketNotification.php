<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->code}] {$this->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line($this->message)
            ->line("Ticket: {$this->ticket->code} – {$this->ticket->subject}")
            ->action('Xem ticket', $this->url)
            ->line('Email tự động từ hệ thống IT Helpdesk, vui lòng không trả lời trực tiếp.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->code,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
