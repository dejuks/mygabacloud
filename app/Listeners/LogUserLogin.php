<?php

namespace App\Listeners;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Events\Attributes\AsEventListener;

class LogUserLogin
{
    #[AsEventListener]
    public function handle(Login $event): void
    {
        ActivityLogger::log('user.login', $event->user, "{$event->user->name} logged in.");
    }
}
