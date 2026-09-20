<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PayoutRequest $payout) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your payout has been sent')
            ->greeting('Payout complete')
            ->line("We have sent \${$this->payout->amount} via {$this->payout->method}.")
            ->action('View payouts', route('seller.payouts.index'));
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'payout', 'amount' => $this->payout->amount];
    }
}
