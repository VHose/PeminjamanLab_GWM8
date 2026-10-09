<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Pengajuan Peminjaman Lab Baru #' . $this->booking->id)
            ->greeting('Yth. Kepala Program Studi,')
            ->line('Ada pengajuan peminjaman laboratorium baru yang memerlukan persetujuan Anda:')
            ->line('Peminjam: ' . $this->booking->requester_name)
            ->line('Tujuan: ' . $this->booking->purpose)
            ->line('Daftar Ruangan & Waktu:');

        foreach ($this->booking->details as $detail) {
            $message->line("- {$detail->room->code} ({$detail->start_datetime->format('d/m/Y H:i')} - {$detail->end_datetime->format('H:i')})");
        }

        return $message->action('Tinjau Pengajuan', url(route('approvals.index')));
    }
}
