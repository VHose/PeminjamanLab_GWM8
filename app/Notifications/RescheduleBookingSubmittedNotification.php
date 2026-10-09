<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RescheduleBookingSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject('Permohonan Perubahan Jadwal Lab #' . $this->booking->id)
            ->greeting('Yth. Kepala Laboratorium,')
            ->line('Terdapat permohonan perubahan jadwal peminjaman ruangan yang memerlukan persetujuan Anda:')
            ->line('Peminjam: ' . $this->booking->requester_name)
            ->line('Alasan Perubahan: ' . ($this->booking->notes ?? '-'))
            ->line('Daftar Ruangan & Jadwal Baru:');

        foreach ($this->booking->details as $detail) {
            $message->line("- {$detail->room->code} ({$detail->start_datetime->format('d/m/Y H:i')} - {$detail->end_datetime->format('H:i')})");
        }

        return $message->action('Tinjau Permohonan', url(route('approvals.index')));
    }
}
