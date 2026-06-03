<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\NomineeController;
use App\Http\Controllers\VoteController;
use App\Models\Category;
use App\Models\Nomination;
use App\Models\Nominee;
use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use ReflectionMethod;
use Tests\TestCase;

class NominationVotingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_nominate_and_vote_end_to_end(): void
    {
        Storage::fake('public');
        $this->createRoles();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'super_admin')->value('id'),
            'is_active' => true,
        ]);

        $adminToken = $admin->createApiToken();

        $voter = User::create([
            'name' => 'Public Voter',
            'email' => 'voter@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $voterToken = $voter->createApiToken();

        $categoryId = $this->controllerResponse(CategoryController::class, 'store', [
            'name' => 'Innovation',
            'description' => 'Builders changing Africa',
            'max_nominees' => 5,
        ], $admin)
            ->assertCreated()
            ->json('data.id');

        $nominationResponse = $this->controllerResponse(NominationController::class, 'store', [
            'full_name' => 'Ama Mensah',
            'bio' => 'Built a regional clean water platform.',
            'country' => 'Ghana',
            'category_id' => $categoryId,
            'nomination_reason' => 'Her work expanded access to safe water.',
            'achievement_links' => ['https://example.com/ama-achievement'],
        ], $voter, files: [
            'profile_image_file' => UploadedFile::fake()->image('ama-profile.jpg'),
            'achievement_documents' => [
                UploadedFile::fake()->create('ama-award.pdf', 100, 'application/pdf'),
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.evaluation_status', 'pending')
            ->assertJsonPath('data.nominee.country', 'Ghana')
            ->assertJsonPath('data.achievement_links.0', 'https://example.com/ama-achievement')
            ->assertJsonPath('data.achievement_documents.0.name', 'ama-award.pdf')
            ->assertJsonPath('message', 'Nomination created successfully');

        $nominationId = $nominationResponse->json('data.id');
        $referenceCode = $nominationResponse->json('data.reference_code');

        $this->assertMatchesRegularExpression('/^(?=.*[A-Z])(?=.*[0-9])[A-Z0-9]{7}$/', $referenceCode);

        $nomineeId = Nomination::findOrFail($nominationId)->nominee_id;
        $this->assertNotNull(Nominee::findOrFail($nomineeId)->profile_image);

        $this->controllerResponse(NominationController::class, 'approve', [], $admin, [
            Nomination::findOrFail($nominationId),
        ])
            ->assertOk()
            ->assertJsonPath('data.evaluation_status', 'approved');

        $this->controllerResponse(NomineeController::class, 'publish', [], $admin, [
            Nominee::findOrFail($nomineeId),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->controllerResponse(VoteController::class, 'store', [
            'nominee_id' => $nomineeId,
        ], $voter)
            ->assertCreated()
            ->assertJsonPath('data.nominee_id', $nomineeId);

        $this->assertSame(1, Nominee::findOrFail($nomineeId)->vote_count);

        $this->controllerResponse(VoteController::class, 'store', [
            'nominee_id' => $nomineeId,
        ], $voter)
            ->assertForbidden()
            ->assertJsonPath('message', 'You have already voted in this category');

        $this->controllerResponse(VoteController::class, 'getCategoryStats', [], $voter, [$categoryId])
            ->assertOk()
            ->assertJsonPath('data.total_votes', 1)
            ->assertJsonPath('data.nominees.0.rank', 1);
    }

    public function test_user_can_only_submit_one_nomination(): void
    {
        Storage::fake('public');
        $this->createRoles();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-one-nomination@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'super_admin')->value('id'),
            'is_active' => true,
        ]);

        $voter = User::create([
            'name' => 'Public Voter',
            'email' => 'one-nomination@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Innovation',
            'description' => 'Builders changing Africa',
            'max_nominees' => 5,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->controllerResponse(NominationController::class, 'store', [
            'full_name' => 'Ama Mensah',
            'country' => 'Ghana',
            'category_id' => $category->id,
            'nomination_reason' => 'Her work expanded access to safe water.',
            'achievement_links' => ['https://example.com/water-impact'],
        ], $voter, files: [
            'profile_image_file' => UploadedFile::fake()->image('one-nomination-profile.jpg'),
        ])
            ->assertCreated()
            ->assertJson(fn ($json) => $json
                ->whereType('data.reference_code', 'string')
                ->etc()
            );

        $this->controllerResponse(NominationController::class, 'store', [
            'full_name' => 'Kojo Mensah',
            'country' => 'Ghana',
            'category_id' => $category->id,
            'nomination_reason' => 'He built a regional education platform.',
            'achievement_links' => ['https://example.com/education-impact'],
        ], $voter, files: [
            'profile_image_file' => UploadedFile::fake()->image('duplicate-profile.jpg'),
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already submitted a nomination');

        $this->assertSame(1, Nomination::count());
    }

    public function test_different_account_cannot_nominate_from_same_device_or_ip(): void
    {
        Storage::fake('public');
        $this->createRoles();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-device@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'super_admin')->value('id'),
            'is_active' => true,
        ]);

        $firstVoter = User::create([
            'name' => 'First Voter',
            'email' => 'first-device@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $secondVoter = User::create([
            'name' => 'Second Voter',
            'email' => 'second-device@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Innovation',
            'description' => 'Builders changing Africa',
            'max_nominees' => 5,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $server = [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Nomination Test Browser',
        ];

        $this->controllerResponse(NominationController::class, 'store', [
            'full_name' => 'Ama Mensah',
            'country' => 'Ghana',
            'category_id' => $category->id,
            'nomination_reason' => 'Her work expanded access to safe water.',
            'achievement_links' => ['https://example.com/safe-water'],
            'device_fingerprint' => 'same-browser-device',
        ], $firstVoter, files: [
            'profile_image_file' => UploadedFile::fake()->image('first-profile.jpg'),
        ], server: $server)
            ->assertCreated();

        $this->controllerResponse(NominationController::class, 'store', [
            'full_name' => 'Kojo Mensah',
            'country' => 'Kenya',
            'category_id' => $category->id,
            'nomination_reason' => 'He built a regional education platform.',
            'achievement_links' => ['https://example.com/education'],
            'device_fingerprint' => 'same-browser-device',
        ], $secondVoter, files: [
            'profile_image_file' => UploadedFile::fake()->image('second-profile.jpg'),
        ], server: $server)
            ->assertStatus(409)
            ->assertJsonPath('message', 'This device has already been used to nominate someone already');

        $this->assertSame(1, Nomination::count());
    }

    public function test_logout_invalidates_api_token(): void
    {
        $this->createRoles();

        $user = User::create([
            'name' => 'Public Voter',
            'email' => 'logout@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'voter')->value('id'),
            'is_active' => true,
        ]);

        $token = $user->createApiToken();

        $apiToken = PersonalAccessToken::where('token', hash('sha256', $token))->firstOrFail();

        $this->controllerResponse(AuthController::class, 'logout', [], $user, [], $apiToken)->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $apiToken->id,
        ]);
    }

    private function createRoles(): void
    {
        foreach ([
            ['name' => 'Super Admin', 'slug' => 'super_admin'],
            ['name' => 'Voter', 'slug' => 'voter'],
        ] as $role) {
            Role::create($role);
        }
    }

    private function controllerResponse(
        string $controller,
        string $method,
        array $data = [],
        ?User $user = null,
        array $arguments = [],
        ?PersonalAccessToken $token = null,
        array $files = [],
        array $server = []
    ): TestResponse {
        $request = Request::create('/test', 'POST', $data, [], $files, $server);
        $request->headers->set('Accept', 'application/json');

        if ($user) {
            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
        }

        if ($token) {
            $request->attributes->set('api_token', $token);
        }

        $reflection = new ReflectionMethod($controller, $method);
        $parameters = $reflection->getParameters();
        $expectsRequest = isset($parameters[0])
            && $parameters[0]->getType()
            && is_a((string) $parameters[0]->getType(), Request::class, true);

        $response = $expectsRequest
            ? app($controller)->{$method}($request, ...$arguments)
            : app($controller)->{$method}(...$arguments);

        return TestResponse::fromBaseResponse($response);
    }
}
