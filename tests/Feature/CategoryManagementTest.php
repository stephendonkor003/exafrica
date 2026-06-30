<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_office_can_load_all_requested_categories_and_public_reads_updates(): void
    {
        $admin = $this->createSuperAdmin();
        $token = $admin->createApiToken();

        for ($position = 1; $position <= 20; $position++) {
            Category::create([
                'name' => 'Category '.$position,
                'description' => 'Description '.$position,
                'icon' => 'fa-star',
                'max_nominees' => 10,
                'position' => $position,
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
        }

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/categories?per_page=100')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 100)
            ->assertJsonPath('pagination.total', 20)
            ->assertJsonCount(20, 'data');

        $target = Category::where('name', 'Category 20')->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/categories/'.$target->id, [
                'name' => 'Updated Public Category',
                'description' => 'Shown on nomination and voting pages.',
                'icon' => 'fa-layer-group',
                'max_nominees' => 12,
                'position' => 1,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Public Category')
            ->assertJsonPath('data.description', 'Shown on nomination and voting pages.')
            ->assertJsonPath('data.max_nominees', 12);

        $publicNames = collect(
            $this->getJson('/api/v1/public/categories?per_page=100')
                ->assertOk()
                ->json('data')
        )->pluck('name');

        $this->assertTrue($publicNames->contains('Updated Public Category'));
        $this->assertFalse($publicNames->contains('Category 20'));
    }

    private function createSuperAdmin(): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super_admin',
        ]);

        return User::create([
            'name' => 'Back Office Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
