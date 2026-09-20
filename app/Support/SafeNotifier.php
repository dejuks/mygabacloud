<?php

namespace App\Support;

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a notification without letting a delivery failure (bounced email,
 * SMTP rejection, DNS lookup failure, etc.) bubble up and fail the request
 * that triggered it.
 *
 * This matters because notifications are fired from inside real business
 * transactions — approving a payment, fulfilling an order, approving a
 * payout — and none of those should roll back or 500 just because a
 * confirmation email couldn't be delivered. The email is a side effect of
 * the transaction succeeding, not a precondition for it.
 *
 * Usage: SafeNotifier::send($user, new SomeNotification(...));
 */
class SafeNotifier
{
    public static function send(mixed $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Notification delivery failed — business transaction still completed normally.', [
                'notification' => get_class($notification),
                'notifiable' => method_exists($notifiable, 'getKey')
                    ? get_class($notifiable) . '#' . $notifiable->getKey()
                    : get_class($notifiable),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
