<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product, public bool $approved) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject("Approved: {$this->product->title}")
                ->greeting('Your product is live!')
                ->line("\"{$this->product->title}\" has been approved and is now listed.")
                ->action('View listing', route('products.show', $this->product));
        }

        return (new MailMessage)
            ->subject("Changes needed: {$this->product->title}")
            ->greeting('Your product needs some changes')
            ->line("\"{$this->product->title}\" was not approved for the following reason:")
            ->line($this->product->rejection_reason)
            ->action('Edit product', route('seller.products.edit', $this->product))
            ->line('Fix the issues and resubmit — we will review it again.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_reviewed',
            'product_id' => $this->product->id,
            'approved' => $this->approved,
        ];
    }
}
