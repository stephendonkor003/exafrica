<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteStatistic extends Model
{
    protected $fillable = ['nominee_id', 'category_id', 'public_votes', 'judge_votes', 'total_votes', 'percentage', 'rank'];

    protected $casts = [
        'percentage' => 'float',
    ];

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
