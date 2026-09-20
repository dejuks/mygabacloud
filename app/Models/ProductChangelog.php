<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChangelog extends Model
{
    use HasFactory;

    protected $table = 'product_changelogs';

    protected $fillable = ['product_id', 'version', 'notes'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
