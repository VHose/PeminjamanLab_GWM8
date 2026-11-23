<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Verifikasi Alamat Email - Peminjaman Laboratorium')
                ->greeting('Halo, ' . $notifiable->name . '!')
                ->line('Terima kasih telah mendaftar akun di Sistem Peminjaman Laboratorium.')
                ->line('Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda:')
                ->action('Verifikasi Alamat Email', $url)
                ->line('Setelah email diverifikasi, akun Anda akan ditinjau oleh Kepala Lab / Staf Lab untuk disetujui.')
                ->line('Jika Anda tidak merasa mendaftar di sistem kami, silakan abaikan email ini.')
                ->salutation("Salam hormat,\n" . config('app.name'));
        });
    }
}

