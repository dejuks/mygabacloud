<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact form message: ' . $this->contactMessage->subject)
            ->greeting('New message from the contact form')
            ->line("From: {$this->contactMessage->name} ({$this->contactMessage->email})")
            ->line("Subject: {$this->contactMessage->subject}")
            ->line($this->contactMessage->message)
            ->line('Reply directly to their email address above — this notification does not have a reply-to link.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_message',
            'contact_message_id' => $this->contactMessage->id,
            'subject' => $this->contactMessage->subject,
        ];
    }
}
