<?php

namespace App\Jobs;

use App\Models\ProductFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Scans a newly uploaded product ZIP for malware before it can be approved.
 *
 * Default implementation shells out to ClamAV's `clamscan` if it is
 * installed on the server. This is deliberately NOT run inline on upload —
 * scanning can take seconds on a large archive, and the upload request
 * should not block on it.
 *
 * If ClamAV is not installed, the file is left in 'pending' and an admin
 * must clear it manually via the "Mark file clean" button in the review
 * queue — the file is never auto-approved just because scanning is
 * unavailable.
 */
class ScanProductFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // large ZIPs can take a while

    public function __construct(protected int $productFileId)
    {
    }

    public function handle(): void
    {
        $file = ProductFile::find($this->productFileId);

        if (! $file) {
            return;
        }

        if (! $this->clamAvAvailable()) {
            Log::warning("ClamAV not installed — file #{$file->id} left pending for manual review.");
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            $file->update(['scan_status' => 'failed']);
            return;
        }

        $localPath = $disk->path($file->path);

        $result = Process::timeout($this->timeout)->run(['clamscan', '--no-summary', $localPath]);

        // clamscan exit codes: 0 = clean, 1 = virus found, 2 = error
        $file->update([
            'scan_status' => match ($result->exitCode()) {
                0 => 'clean',
                1 => 'infected',
                default => 'failed',
            },
        ]);

        if ($result->exitCode() === 1) {
            Log::alert("MALWARE DETECTED in product file #{$file->id}: {$result->output()}");
            // In production: notify the seller and platform admins immediately,
            // and consider auto-suspending the product here.
        }
    }

    protected function clamAvAvailable(): bool
    {
        return Process::run(['which', 'clamscan'])->successful();
    }
}
