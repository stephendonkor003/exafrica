<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role_id', 'is_active', 'phone', 'bio', 'profile_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function nominationsCreated(): HasMany
    {
        return $this->hasMany(Nomination::class, 'nominated_by');
    }

    public function nominationsEvaluated(): HasMany
    {
        return $this->hasMany(Nomination::class, 'evaluated_by');
    }

    public function categoriesCreated(): HasMany
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    public function judgeProfile()
    {
        return $this->hasOne(Judge::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    public function createApiToken(string $name = 'auth_token'): string
    {
        $plainTextToken = Str::random(80);

        $this->apiTokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
        ]);

        return $plainTextToken;
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role?->name ?? 'Unknown';
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role?->slug === Str::slug($roleName, '_');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isJudge(): bool
    {
        return $this->hasRole('judge');
    }
}
