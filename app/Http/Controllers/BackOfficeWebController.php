<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\Nomination;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BackOfficeWebController extends Controller
{
    public function loginForm()
    {
        if (session()->has('backoffice_user_id') && session()->has('backoffice_api_token')) {
            return redirect()->route('backoffice.dashboard');
        }

        return view('backoffice.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('role')
            ->where('email', $credentials['email'])
            ->first();

        if (! $user
            || ! Hash::check($credentials['password'], $user->password)
            || ! $user->is_active
            || $user->role?->slug !== 'super_admin') {
            return back()
                ->withErrors(['email' => 'Back Office access requires a valid Super Admin account.'])
                ->onlyInput('email');
        }

        $this->forgetBackOfficeSession($request);

        $plainTextToken = Str::random(80);
        $token = $user->apiTokens()->create([
            'name' => 'back_office_web',
            'token' => hash('sha256', $plainTextToken),
        ]);

        $request->session()->regenerate();
        $request->session()->put([
            'backoffice_user_id' => $user->id,
            'backoffice_api_token' => $plainTextToken,
            'backoffice_api_token_id' => $token->id,
        ]);

        return redirect()->route('backoffice.dashboard');
    }

    public function dashboard(Request $request)
    {
        $user = $this->backOfficeUser($request);

        if (! $user) {
            return redirect()->route('backoffice.login');
        }

        return view('backoffice.dashboard', [
            'adminUser' => $user,
            'apiToken' => $request->session()->get('backoffice_api_token'),
        ]);
    }

    public function showNomination(Request $request, Nomination $nomination)
    {
        $user = $this->backOfficeUser($request);

        if (! $user) {
            return redirect()->route('backoffice.login');
        }

        $nomination->load([
            'nominee.category',
            'category',
            'nominatedBy.role',
            'evaluatedBy.role',
        ]);

        return view('backoffice.nomination-show', [
            'adminUser' => $user,
            'nomination' => $nomination,
        ]);
    }

    public function logout(Request $request)
    {
        $this->forgetBackOfficeSession($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('backoffice.login');
    }

    private function backOfficeUser(Request $request): ?User
    {
        $userId = $request->session()->get('backoffice_user_id');

        $tokenId = $request->session()->get('backoffice_api_token_id');

        if (! $userId
            || ! $tokenId
            || ! $request->session()->has('backoffice_api_token')
            || ! PersonalAccessToken::whereKey($tokenId)->exists()) {
            return null;
        }

        $user = User::with('role')->find($userId);

        if (! $user || ! $user->is_active || $user->role?->slug !== 'super_admin') {
            $this->forgetBackOfficeSession($request);
            return null;
        }

        return $user;
    }

    private function forgetBackOfficeSession(Request $request): void
    {
        $tokenId = $request->session()->get('backoffice_api_token_id');

        if ($tokenId) {
            PersonalAccessToken::whereKey($tokenId)->delete();
        }

        $request->session()->forget([
            'backoffice_user_id',
            'backoffice_api_token',
            'backoffice_api_token_id',
        ]);
    }
}
