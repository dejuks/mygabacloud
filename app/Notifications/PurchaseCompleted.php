<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Your order {$this->order->order_number} is complete")
            ->greeting("Thanks for your purchase!")
            ->line("Order {$this->order->order_number} — total \${$this->order->grand_total}");

        foreach ($this->order->items as $item) {
            $mail->line("• {$item->product->title} ({$item->license_type} license)");
        }

        return $mail
            ->action('Go to your library', route('library.index'))
            ->line('Your license keys and downloads are available any time from your library.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->grand_total,
        ];
    }
}
