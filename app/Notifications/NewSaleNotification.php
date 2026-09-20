<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrderItem $item) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You made a sale: {$this->item->product->title}")
            ->greeting('Good news!')
            ->line("{$this->item->product->title} just sold for \${$this->item->price}.")
            ->line("Platform commission ({$this->item->commission_rate}%): \${$this->item->commission_amount}")
            ->line("Your earning: \${$this->item->seller_earning}")
            ->action('View your dashboard', route('seller.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sale',
            'product' => $this->item->product->title,
            'earning' => $this->item->seller_earning,
        ];
    }
}
