<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nomination extends Model
{
    protected $fillable = [
        'reference_code', 'nominee_id', 'category_id', 'nominated_by', 'nominator_ip',
        'nominator_device_hash', 'nominator_user_agent', 'nomination_reason',
        'achievement_documents', 'achievement_links',
        'evaluator_notes', 'evaluated_by', 'evaluation_status', 'evaluated_at',
    ];

    protected $casts = [
        'achievement_documents' => 'array',
        'achievement_links' => 'array',
        'evaluated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Nomination $nomination): void {
            if (blank($nomination->reference_code)) {
                $nomination->reference_code = self::generateReferenceCode();
            }
        });
    }

    private static function generateReferenceCode(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $characters = $letters.$numbers;

        do {
            $code = $letters[random_int(0, strlen($letters) - 1)]
                .$numbers[random_int(0, strlen($numbers) - 1)];

            for ($i = 0; $i < 5; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }

            $code = str_shuffle($code);
        } while (static::where('reference_code', $code)->exists());

        return $code;
    }

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
