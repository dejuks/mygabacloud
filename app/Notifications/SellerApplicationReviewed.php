<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerApplicationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $approved, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Your seller account is approved')
                ->greeting('Welcome aboard!')
                ->line('Your seller application has been approved. You can start uploading products now.')
                ->action('Open seller dashboard', route('seller.dashboard'));
        }

        return (new MailMessage)
            ->subject('About your seller application')
            ->line('Unfortunately we could not approve your seller application at this time.')
            ->line($this->reason ?: 'You are welcome to apply again in the future.');
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'seller_application', 'approved' => $this->approved];
    }
}
