<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PropertyVerification extends Model
{
    protected $fillable = ['property_id', 'actor_id', 'stage', 'status', 'checklist', 'notes', 'acted_at'];

    protected function casts(): array
    {
        return ['checklist' => 'array', 'acted_at' => 'datetime'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
