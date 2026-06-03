<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Nomination;
use App\Models\Nominee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPhaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackOfficeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_office_uses_separate_portal_and_super_admin_login(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('section-backoffice', false)
            ->assertDontSee('data-section="backoffice"', false);

        $this->get('/back-office')
            ->assertRedirect(route('backoffice.login'));

        $this->get('/back-office/login')
            ->assertOk()
            ->assertSee('Operations Access')
            ->assertSee('fa-earth-africa', false)
            ->assertSee('Enter Back Office');

        $this->post('/back-office/login', [
            'email' => 'donkors@africanunion.org',
            'password' => 'Amodon@2063',
        ])->assertRedirect(route('backoffice.dashboard'));

        $this->get('/back-office')
            ->assertOk()
            ->assertSee('Operations Console')
            ->assertSee('Agenda 2063 Back Office')
            ->assertSee('boCategoryVoteChart', false)
            ->assertSee('data-bo-search="nominations"', false)
            ->assertSee('nominationRecordBaseUrl', false)
            ->assertSee('Dashboard')
            ->assertSee('Nominations')
            ->assertSee('Users');

        $this->post('/back-office/logout')
            ->assertRedirect(route('backoffice.login'));

        $this->get('/back-office')
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_voter_cannot_login_to_back_office_portal(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        User::create([
            'name' => 'Regular Portal Voter',
            'email' => 'portal-voter@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $this->post('/back-office/login', [
            'email' => 'portal-voter@example.com',
            'password' => 'password123',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->get('/back-office')
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_super_admin_can_open_full_nomination_record_page(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $category = Category::firstOrFail();
        $nominator = User::create([
            'name' => 'Record Nominator',
            'email' => 'record-nominator@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);
        $nominee = Nominee::create([
            'full_name' => 'Full Record Nominee',
            'bio' => 'Complete nominee biography.',
            'email' => 'nominee-record@example.com',
            'phone' => '+233555000000',
            'country' => 'Ghana',
            'profile_image' => '/storage/nominees/profile-images/record-profile.jpg',
            'category_id' => $category->id,
            'status' => 'pending',
        ]);
        $nomination = Nomination::create([
            'nominee_id' => $nominee->id,
            'category_id' => $category->id,
            'nominated_by' => $nominator->id,
            'nominator_ip' => '192.0.2.10',
            'nominator_device_hash' => str_repeat('a', 64),
            'nominator_user_agent' => 'Feature Test Browser',
            'nomination_reason' => 'This record should show the entire nomination data.',
            'achievement_documents' => [[
                'name' => 'impact-report.pdf',
                'url' => '/storage/nominations/achievement-documents/impact-report.pdf',
                'mime_type' => 'application/pdf',
                'size' => 1000,
            ]],
            'achievement_links' => ['https://example.com/record-achievement'],
            'evaluation_status' => 'pending',
        ]);

        $this->get(route('backoffice.nominations.show', $nomination))
            ->assertRedirect(route('backoffice.login'));

        $this->post('/back-office/login', [
            'email' => 'donkors@africanunion.org',
            'password' => 'Amodon@2063',
        ])->assertRedirect(route('backoffice.dashboard'));

        $this->get(route('backoffice.nominations.show', $nomination))
            ->assertOk()
            ->assertSee('Nomination Record')
            ->assertSee($nomination->reference_code)
            ->assertSee('Full Record Nominee')
            ->assertSee('Ghana')
            ->assertSee('Record Nominator')
            ->assertSee('192.0.2.10')
            ->assertSee(str_repeat('a', 64))
            ->assertSee('Feature Test Browser')
            ->assertSee('impact-report.pdf')
            ->assertSee('https://example.com/record-achievement')
            ->assertSee('This record should show the entire nomination data.');
    }

    public function test_super_admin_can_manage_back_office_workflows(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $admin = User::where('email', 'donkors@africanunion.org')->firstOrFail();

        $this->assertTrue(Hash::check('Amodon@2063', $admin->password));
        $this->assertSame('super_admin', $admin->role->slug);
        $this->assertTrue($admin->is_active);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'donkors@africanunion.org',
            'password' => 'Amodon@2063',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'Super Admin')
            ->json('data.token');

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

    public function test_registered_voter_cannot_access_back_office_resources(): void
    {
        $this->seed(RoleAndPhaseSeeder::class);

        $voter = User::create([
            'name' => 'Regular Voter',
            'email' => 'regular-voter@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $category = Category::firstOrFail();

        $pendingNominee = Nominee::create([
            'full_name' => 'Pending Nominee',
            'category_id' => $category->id,
            'status' => 'pending',
        ]);

        Nominee::create([
            'full_name' => 'Published Nominee',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'regular-voter@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'Voter')
            ->json('data.token');

        foreach ([
            '/api/v1/dashboard/admin',
            '/api/v1/nominations',
            '/api/v1/voting-phases',
            '/api/v1/roles',
            '/api/v1/users',
        ] as $endpoint) {
            $this->withToken($token)
                ->getJson($endpoint)
                ->assertForbidden()
                ->assertJsonPath('message', 'You do not have permission to access this resource');
        }

        $this->withToken($token)
            ->postJson('/api/v1/nominees', [
                'full_name' => 'Unauthorized Nominee',
                'category_id' => $category->id,
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->getJson('/api/v1/nominees?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Published Nominee');

        $this->withToken($token)
            ->getJson("/api/v1/nominees/{$pendingNominee->id}")
            ->assertForbidden();
    }
}
