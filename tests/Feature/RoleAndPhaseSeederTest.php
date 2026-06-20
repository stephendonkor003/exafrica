<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Models\VotingPhase;
use Database\Seeders\RoleAndPhaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndPhaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_runs_without_initial_super_admin_credentials(): void
    {
        config([
            'security.initial_super_admin.name' => '',
            'security.initial_super_admin.email' => '',
            'security.initial_super_admin.password' => '',
            'security.initial_super_admin.system_email' => 'system-seeder@example.invalid',
        ]);

        $this->seed(RoleAndPhaseSeeder::class);

        $systemUser = User::where('email', 'system-seeder@example.invalid')->firstOrFail();

        $this->assertFalse($systemUser->is_active);
        $this->assertSame('super_admin', $systemUser->role->slug);
        $this->assertSame(6, Role::count());
        $this->assertSame(9, Category::count());
        $this->assertSame(4, VotingPhase::count());
        $this->assertTrue(Category::query()->where('created_by', $systemUser->id)->exists());
    }
}
