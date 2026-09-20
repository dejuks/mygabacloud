<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'disk', 'path', 'original_name', 'size_bytes',
        'version', 'checksum', 'scan_status',
        'integrity_status', 'duplicate_of_file_id', 'ai_review_notes', 'analyzed_at',
    ];

    protected function casts(): array
    {
        return ['analyzed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(ProductFile::class, 'duplicate_of_file_id');
    }

    public function isDownloadable(): bool
    {
        return $this->scan_status === 'clean';
    }
}
