<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voter extends Model
{
    protected $fillable = ['mac_address', 'vote_count', 'last_voted_at', 'is_blocked', 'block_reason'];

    protected $casts = [
        'last_voted_at' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function isEligibleToVote(): bool
    {
        return !$this->is_blocked && $this->vote_count < 1;
    }
}
