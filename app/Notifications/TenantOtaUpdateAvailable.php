<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantOtaUpdateAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $releaseTag,
        private readonly ?string $releaseUrl,
        private readonly ?string $tenantName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('BrewCloud platform update available: ' . $this->releaseTag)
            ->greeting('Hello' . ($this->tenantName ? ' ' . $this->tenantName : '') . ',')
            ->line('A new BrewCloud platform release is available for your tenant environment.')
            ->line('Latest release: ' . $this->releaseTag)
            ->line('This update has been queued by the platform and tenant update metadata has been refreshed.');

        if ($this->releaseUrl) {
            $mail->action('View release details', $this->releaseUrl);
        }

        return $mail->line('No action is required unless your shop has custom deployment hooks.');
    }
}
