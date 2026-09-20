<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Records an audit-trail entry. Wrapped in try/catch for the same reason
 * SafeNotifier wraps notifications — a logging failure (DB hiccup, whatever)
 * must never roll back or fail the actual action it's describing. The log
 * is a record of what happened, not a precondition for it happening.
 */
class ActivityLogger
{
    public static function log(string $action, ?Model $subject, string $description, ?int $forUserId = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => $forUserId ?? Auth::id(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'ip_address' => Request::ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Activity log write failed — action still completed normally.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
