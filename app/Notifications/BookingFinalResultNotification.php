<?php

namespace App\Notifications;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingFinalResultNotification extends Notification implements ShouldQueue
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
        $statusText = match ($this->booking->status) {
            BookingStatus::APPROVED => 'Disetujui',
            BookingStatus::REJECTED => 'Ditolak',
            BookingStatus::CANCELLED => 'Dibatalkan',
            default => 'Selesai Diproses',
        };

        $message = (new MailMessage)
            ->subject("Hasil Pengajuan Peminjaman Lab #{$this->booking->id} - {$statusText}")
            ->greeting("Halo {$this->booking->requester_name},")
            ->line("Pengajuan peminjaman laboratorium Anda (ID: #{$this->booking->id}) telah selesai diproses.")
            ->line("Status Keseluruhan: {$statusText}");

        if ($this->booking->notes) {
            $message->line("Catatan / Alasan: {$this->booking->notes}");
        }

        $message->line('Detail per Ruangan:');
        foreach ($this->booking->details as $detail) {
            $dStatus = match ($detail->status) {
                BookingDetailStatus::APPROVED => 'Disetujui',
                BookingDetailStatus::REJECTED => 'Ditolak' . ($detail->notes ? " (Alasan: {$detail->notes})" : ''),
                BookingDetailStatus::CANCELLED => 'Dibatalkan',
                default => 'Menunggu',
            };
            $message->line("- Ruangan {$detail->room->code} ({$detail->start_datetime->format('d/m/Y H:i')} - {$detail->end_datetime->format('H:i')}): {$dStatus}");
        }

        return $message->action('Lihat Detail Peminjaman', url(route('bookings.show', $this->booking)));
    }
}
