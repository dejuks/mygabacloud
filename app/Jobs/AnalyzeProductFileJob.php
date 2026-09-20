<?php

namespace App\Jobs;

use App\Models\ProductFile;
use App\Services\AiContentReviewer;
use App\Services\ProductFileAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs on every product file upload, independently of malware scanning
 * (ScanProductFileJob) and independently of AUTO_CLEAN_UPLOADS — integrity
 * and duplicate checks are cheap, deterministic, and don't need ClamAV.
 */
class AnalyzeProductFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(protected int $productFileId)
    {
    }

    public function handle(ProductFileAnalyzer $analyzer, AiContentReviewer $aiReviewer): void
    {
        $file = ProductFile::with('product.seller.sellerProfile')->find($this->productFileId);

        if (! $file) {
            return;
        }

        $integrityStatus = $analyzer->checkIntegrity($file);
        $duplicate = $analyzer->findExactDuplicate($file);

        $aiNotes = null;

        if ($integrityStatus === 'valid') {
            $snippet = $analyzer->extractTextSnippet($file);
            if ($snippet) {
                $aiNotes = $aiReviewer->review($snippet, $file->product);
            }
        }

        $file->update([
            'integrity_status' => $integrityStatus,
            'duplicate_of_file_id' => $duplicate?->id,
            'ai_review_notes' => $aiNotes,
            'analyzed_at' => now(),
        ]);
    }
}
