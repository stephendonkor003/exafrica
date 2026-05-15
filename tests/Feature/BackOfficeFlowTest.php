<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPhaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackOfficeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_back_office_workflows(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => Role::where('slug', 'super_admin')->value('id'),
                'is_active' => true,
            ]
        );

        $token = $admin->createApiToken();

        $this->withToken($token)
            ->getJson('/api/v1/dashboard/admin')
            ->assertOk()
            ->assertJsonPath('data.summary.total_categories', 9);

        $roles = $this->withToken($token)
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->json('data');

        $categoryId = $this->withToken($token)
            ->postJson('/api/v1/categories', [
                'name' => 'Back Office Test Category',
                'description' => 'Created by test',
                'max_nominees' => 3,
                'position' => 99,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->putJson("/api/v1/categories/{$categoryId}", [
                'description' => 'Updated by test',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $phaseId = $this->withToken($token)
            ->getJson('/api/v1/voting-phases')
            ->assertOk()
            ->json('data.2.id');

        $this->withToken($token)
            ->postJson("/api/v1/voting-phases/{$phaseId}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->withToken($token)
            ->postJson('/api/v1/users', [
                'name' => 'Back Office User',
                'email' => 'back-office-user@example.com',
                'password' => 'password123',
                'role_id' => $roles[0]['id'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'back-office-user@example.com');
    }
}
