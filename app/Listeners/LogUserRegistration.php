<?php

namespace App\Listeners;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Attributes\AsEventListener;

class LogUserRegistration
{
    #[AsEventListener]
    public function handle(Registered $event): void
    {
        ActivityLogger::log('user.registered', $event->user, "{$event->user->name} ({$event->user->email}) created an account.");
    }
}
