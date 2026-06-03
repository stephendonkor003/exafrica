<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nominee extends Model
{
    protected $fillable = [
        'full_name', 'bio', 'email', 'phone', 'country', 'profile_image',
        'category_id', 'status', 'vote_count', 'rejection_reason'
    ];

    protected $casts = [
        'vote_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nomination()
    {
        return $this->hasOne(Nomination::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function voteStatistic()
    {
        return $this->hasOne(VoteStatistic::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
