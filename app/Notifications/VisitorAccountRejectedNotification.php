<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorAccountRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public ?string $reason = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Pemberitahuan Status Akun Visitor - Peminjaman Laboratorium')
            ->greeting("Halo, {$this->user->name}!")
            ->line('Mohon maaf, pengajuan pendaftaran akun visitor Anda belum dapat disetujui oleh pengelola laboratorium.');

        if ($this->reason) {
            $mail->line("Alasan: {$this->reason}");
        }

        $mail->line('Silakan hubungi staf atau pengelola laboratorium jika Anda memerlukan klarifikasi lebih lanjut.')
            ->salutation("Salam hormat,\n" . config('app.name'));

        return $mail;
    }
}
