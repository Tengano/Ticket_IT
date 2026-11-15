<?php

namespace App\Notifications;

use App\Models\PhieuYeuCau;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThongBaoPhieu extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PhieuYeuCau $ticket,
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
            ->subject("[{$this->ticket->ma}] {$this->title}")
            ->greeting("Xin chào {$notifiable->ten},")
            ->line($this->message)
            ->line("Phiếu: {$this->ticket->ma} – {$this->ticket->tieu_de}")
            ->action('Xem phiếu', $this->url)
            ->line('Email tự động từ hệ thống hỗ trợ IT, vui lòng không trả lời trực tiếp.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'phieu_yeu_cau_id' => $this->ticket->id,
            'ma_phieu' => $this->ticket->ma,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
