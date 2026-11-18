<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Owner extends Model
{
    use SoftDeletes;

   protected $fillable = [
        'owner_code',
        'name',
        'phone',
        'email',
        'profession',
        'detailed_address',
        'legal_status_note',
        'expected_price',
        'relationship_manager_id',
        'owner_type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_price' => 'decimal:2',
            'final_negotiated_price' => 'decimal:2',
        ];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function relationshipManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'relationship_manager_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OwnerActivityLog::class)->latest();
    }

    public function contactRequests(): HasMany
    {
        return $this->hasMany(OwnerContactRequest::class)->latest();
    }
}
