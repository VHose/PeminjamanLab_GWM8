<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorAccountApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Visitor Telah Disetujui - Peminjaman Laboratorium')
            ->greeting("Halo, {$this->user->name}!")
            ->line('Kabar baik! Akun pendaftaran visitor Anda telah disetujui oleh pengelola laboratorium.')
            ->line("Instansi / Organisasi: {$this->user->pic_or_instansi}")
            ->line('Sekarang Anda sudah dapat mengajukan permohonan peminjaman ruangan laboratorium melalui sistem.')
            ->action('Ajukan Peminjaman Lab', url(route('bookings.create')))
            ->salutation("Salam hormat,\n" . config('app.name'));
    }
}
