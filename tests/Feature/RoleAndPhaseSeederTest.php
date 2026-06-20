<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Models\VotingPhase;
use Database\Seeders\RoleAndPhaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAndPhaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_canonical_super_admin_and_deactivates_other_admins(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $admin = User::where('email', 'donkors@africanunion.org')->firstOrFail();

        $otherAdmin = User::create([
            'name' => 'Old Admin',
            'email' => 'old-admin@example.com',
            'password' => Hash::make('OldAdminPass123!'),
            'role_id' => $admin->role_id,
            'is_active' => true,
        ]);
        Category::firstOrFail()->update(['created_by' => $otherAdmin->id]);

        $this->seed(RoleAndPhaseSeeder::class);

        $admin->refresh();
        $otherAdmin->refresh();

        $this->assertSame('African Union Super Admin', $admin->name);
        $this->assertTrue(Hash::check('Amodon@2063', $admin->password));
        $this->assertTrue($admin->is_active);
        $this->assertSame('super_admin', $admin->role->slug);
        $this->assertFalse($otherAdmin->is_active);
        $this->assertSame(6, Role::count());
        $this->assertSame(9, Category::count());
        $this->assertSame(4, VotingPhase::count());
        $this->assertTrue(Category::query()->where('created_by', $admin->id)->exists());
        $this->assertFalse(Category::query()->where('created_by', $otherAdmin->id)->exists());
    }
}
