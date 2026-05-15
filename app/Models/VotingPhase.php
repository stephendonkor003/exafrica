<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingPhase extends Model
{
    protected $fillable = ['name', 'description', 'phase_type', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('phase_type', $type);
    }

    public function isCurrentPhase(): bool
    {
        return $this->is_active && now()->between($this->start_date, $this->end_date);
    }

    public function isPastPhase(): bool
    {
        return now()->isAfter($this->end_date);
    }

    public function isFuturePhase(): bool
    {
        return now()->isBefore($this->start_date);
    }
}
