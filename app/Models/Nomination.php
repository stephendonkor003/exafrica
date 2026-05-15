<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nomination extends Model
{
    protected $fillable = [
        'nominee_id', 'category_id', 'nominated_by', 'nomination_reason',
        'evaluator_notes', 'evaluated_by', 'evaluation_status', 'evaluated_at'
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function scopePending($query)
    {
        return $query->where('evaluation_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('evaluation_status', 'approved');
    }
}
