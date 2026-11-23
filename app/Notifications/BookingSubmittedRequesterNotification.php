<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSubmittedRequesterNotification extends Notification implements ShouldQueue
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
            BookingStatus::PENDING_KAPRODI, BookingStatus::PENDING_PRODI => 'Menunggu Persetujuan Kaprodi (Tahap 1)',
            BookingStatus::PENDING_KALAB, BookingStatus::PENDING_LAB => 'Menunggu Persetujuan Kepala Lab (Tahap 2)',
            default => $this->booking->status?->label() ?? 'Sedang Diproses',
        };

        $isReschedule = (int) ($this->booking->type ?? 0) === 1;
        $subject = $isReschedule ? "Bukti Pengajuan Ubah Jadwal Lab #{$this->booking->id}" : "Bukti Pengajuan Peminjaman Lab #{$this->booking->id}";
        $intro = $isReschedule
            ? 'Pengajuan perubahan jadwal/ruangan laboratorium Anda telah berhasil terkirim ke sistem dan langsung menunggu persetujuan Kepala Lab.'
            : 'Pengajuan peminjaman laboratorium Anda telah berhasil terkirim ke sistem dan saat ini sedang menunggu proses persetujuan.';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$this->booking->requester_name},")
            ->line($intro)
            ->line('Berikut rincian peminjaman Anda:')
            ->line("• ID Pengajuan: #{$this->booking->id}")
            ->line("• Tujuan Kegiatan: {$this->booking->purpose}")
            ->line("• Status Saat Ini: {$statusText}")
            ->line('• Rincian Ruangan & Waktu:');

        foreach ($this->booking->details as $detail) {
            $roomName = $detail->room ? ($detail->room->name ?? $detail->room->code) : 'Ruangan';
            $message->line("   - {$roomName} ({$detail->start_datetime->format('d/m/Y H:i')} - {$detail->end_datetime->format('H:i')})");
        }

        return $message
            ->action('Lihat Status Pengajuan', url(route('bookings.show', $this->booking)))
            ->line('Anda akan menerima email pemberitahuan kembali setelah pengajuan ini disetujui atau ditolak.');
    }
}
