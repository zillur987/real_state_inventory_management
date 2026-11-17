<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PropertyImage extends Model
{
    protected $fillable = ['property_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
