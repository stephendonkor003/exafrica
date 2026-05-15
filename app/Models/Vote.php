<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = ['nominee_id', 'category_id', 'voter_id', 'mac_address', 'vote_type', 'judge_id', 'ip_address'];

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function scopePublicVotes($query)
    {
        return $query->where('vote_type', 'public_vote');
    }

    public function scopeJudgeVotes($query)
    {
        return $query->where('vote_type', 'judge_vote');
    }
}
