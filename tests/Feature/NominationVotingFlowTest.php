<?php

namespace Tests\Feature;

use App\Models\Nomination;
use App\Models\Nominee;
use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\NomineeController;
use App\Http\Controllers\VoteController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use ReflectionMethod;
use Tests\TestCase;

class NominationVotingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_nominate_and_vote_end_to_end(): void
    {
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

        $nominationId = $this->controllerResponse(NominationController::class, 'store', [
                'full_name' => 'Ama Mensah',
                'bio' => 'Built a regional clean water platform.',
                'email' => 'ama@example.com',
                'category_id' => $categoryId,
                'nomination_reason' => 'Her work expanded access to safe water.',
            ], $voter)
            ->assertCreated()
            ->assertJsonPath('data.evaluation_status', 'pending')
            ->json('data.id');

        $nomineeId = Nomination::findOrFail($nominationId)->nominee_id;

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
        ?PersonalAccessToken $token = null
    ): TestResponse
    {
        $request = Request::create('/test', 'POST', $data);
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
