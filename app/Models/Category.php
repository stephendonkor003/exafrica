<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'max_nominees', 'position', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(Nominee::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    public function voteStatistics(): HasMany
    {
        return $this->hasMany(VoteStatistic::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
