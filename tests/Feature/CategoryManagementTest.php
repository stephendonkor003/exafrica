<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Nominee;
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

        $hiddenCategory = Category::create([
            'name' => 'Hidden Public Category',
            'description' => 'This disabled category should not show publicly.',
            'icon' => 'fa-eye-slash',
            'max_nominees' => 10,
            'position' => 21,
            'is_active' => false,
            'created_by' => $admin->id,
        ]);

        $hiddenNominee = Nominee::create([
            'full_name' => 'Inactive Category Nominee',
            'bio' => 'This nominee belongs to a disabled category.',
            'country' => 'Kenya',
            'profile_image' => 'https://example.com/hidden.jpg',
            'category_id' => $hiddenCategory->id,
            'status' => 'approved',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/categories?per_page=100')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 100)
            ->assertJsonPath('pagination.total', 21)
            ->assertJsonCount(21, 'data');

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

        $publicCategoryResponse = $this->getJson('/api/v1/public/categories?per_page=100')
                ->assertOk();

        $this->assertStringContainsString('no-store', $publicCategoryResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $publicCategoryResponse->headers->get('Cache-Control'));

        $publicNames = collect($publicCategoryResponse->json('data'))->pluck('name');

        $this->assertTrue($publicNames->contains('Updated Public Category'));
        $this->assertFalse($publicNames->contains('Category 20'));
        $this->assertFalse($publicNames->contains('Hidden Public Category'));

        $this->get('/?section=voting')
            ->assertOk()
            ->assertSee('Updated Public Category')
            ->assertSee('Shown on nomination and voting pages.')
            ->assertDontSee('Gender and Women Empowerment')
            ->assertDontSee('Hidden Public Category');

        $this->getJson('/api/v1/public/nominees?per_page=100')
            ->assertOk()
            ->assertJsonMissing(['full_name' => 'Inactive Category Nominee']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])
            ->postJson('/api/v1/public/votes', [
                'nominee_id' => $hiddenNominee->id,
                'device_id' => 'inactive-category-device',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'This category is not open for voting');
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
