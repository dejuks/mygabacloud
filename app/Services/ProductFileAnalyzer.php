<?php

namespace App\Services;

use App\Models\ProductFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Three genuinely different capabilities, deliberately not blurred together:
 *
 * 1. checkIntegrity() — deterministic, always reliable. Can the file even be
 *    opened as what it claims to be? Catches a truncated upload, a renamed
 *    non-ZIP file pretending to be a .docx, a corrupted PDF.
 *
 * 2. findExactDuplicate() — deterministic, reliable, but narrow. Catches
 *    only byte-for-byte identical re-uploads via checksum comparison. It
 *    will NOT catch a re-packaged, renamed, or lightly edited copy of
 *    someone else's work — that's a fundamentally harder problem this
 *    does not claim to solve.
 *
 * 3. extractTextSnippet() + AiReviewer — best-effort and optional. Feeds a
 *    short text excerpt to an LLM for advisory flags only (obvious
 *    copyright-notice mismatches, placeholder/lorem-ipsum content). This is
 *    NOT a copyright determination and must never be presented as one —
 *    the admin's judgment is still the actual check.
 */
class ProductFileAnalyzer
{
    public function checkIntegrity(ProductFile $file): string
    {
        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            return 'corrupted';
        }

        $localPath = $disk->path($file->path);
        $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));

        try {
            if (in_array($extension, ['zip', 'docx', 'pptx', 'xlsx'], true)) {
                // docx/pptx/xlsx ARE zip files internally — this genuinely
                // verifies the archive isn't truncated or corrupted.
                $zip = new ZipArchive();
                $result = $zip->open($localPath, ZipArchive::CHECKCONS);
                if ($result === true) {
                    $zip->close();
                    return 'valid';
                }
                return 'corrupted';
            }

            if ($extension === 'pdf') {
                // A real PDF parser would be more thorough, but checking
                // for the header and trailer markers catches the common
                // failure mode (truncated upload) without adding a
                // dependency just for this check.
                $handle = fopen($localPath, 'rb');
                $header = fread($handle, 8);
                fseek($handle, -32, SEEK_END);
                $tail = fread($handle, 32);
                fclose($handle);

                $hasHeader = str_starts_with($header, '%PDF-');
                $hasTrailer = str_contains($tail, '%%EOF');

                return ($hasHeader && $hasTrailer) ? 'valid' : 'corrupted';
            }

            // .doc/.xls/.ppt (legacy binary Office formats) and .rar don't
            // have a cheap dependency-free integrity check worth adding —
            // presence + non-zero size is the honest limit here.
            return $disk->size($file->path) > 0 ? 'valid' : 'corrupted';
        } catch (\Throwable $e) {
            Log::warning("Integrity check failed for product_file #{$file->id}: " . $e->getMessage());
            return 'corrupted';
        }
    }

    /**
     * Exact-match only. Searches every OTHER product file's checksum,
     * across every seller — not just this seller's own uploads — since the
     * scenario worth catching is someone re-uploading a file that was
     * already submitted (by them or someone else) under a different title.
     */
    public function findExactDuplicate(ProductFile $file): ?ProductFile
    {
        if (! $file->checksum) {
            return null;
        }

        return ProductFile::where('checksum', $file->checksum)
            ->where('id', '!=', $file->id)
            ->oldest()
            ->first();
    }

    /**
     * Best-effort plain-text extraction, capped to a short excerpt — this
     * feeds the optional AI advisory step, not a full-document indexer.
     * Returns null (not an exception) for anything unsupported or unreadable
     * so the caller can just skip the AI step gracefully.
     */
    public function extractTextSnippet(ProductFile $file, int $maxChars = 3000): ?string
    {
        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            return null;
        }

        $localPath = $disk->path($file->path);
        $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));

        try {
            return match ($extension) {
                'docx' => $this->extractFromOoxmlZip($localPath, 'word/document.xml', $maxChars),
                'pptx' => $this->extractFromOoxmlZip($localPath, 'ppt/slides/slide1.xml', $maxChars),
                'xlsx' => $this->extractFromOoxmlZip($localPath, 'xl/sharedStrings.xml', $maxChars),
                'pdf' => $this->extractFromPdf($localPath, $maxChars),
                default => null, // .zip, .doc, .xls, .ppt, .rar: no dependency-free extraction available
            };
        } catch (\Throwable $e) {
            Log::info("Text extraction skipped for product_file #{$file->id}: " . $e->getMessage());
            return null;
        }
    }

    protected function extractFromOoxmlZip(string $path, string $innerFile, int $maxChars): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName($innerFile);
        $zip->close();

        if (! $xml) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($xml)));

        return $text ? mb_substr($text, 0, $maxChars) : null;
    }

    protected function extractFromPdf(string $path, int $maxChars): ?string
    {
        // Requires composer require smalot/pdfparser — if it's not
        // installed, this returns null and the AI step just skips PDFs
        // rather than erroring.
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return null;
        }

        $parser = new \Smalot\PdfParser\Parser();
        $text = trim(preg_replace('/\s+/', ' ', $parser->parseFile($path)->getText()));

        return $text ? mb_substr($text, 0, $maxChars) : null;
    }
}
