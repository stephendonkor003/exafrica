<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    public function roles()
    {
        return $this->successResponse(Role::orderBy('name')->get(), 'Roles retrieved successfully');
    }

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $users = $query->paginate(20);
        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string',
            'bio' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'bio' => $request->bio,
            'is_active' => true,
        ]);

        return $this->successResponse($user->load('role'), 'User created successfully', 201);
    }

    public function show(User $user)
    {
        return $this->successResponse($user->load('role'), 'User retrieved successfully');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'bio' => 'nullable|string',
            'profile_image' => 'nullable|url',
            'role_id' => 'nullable|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user->update($request->only([
            'name', 'email', 'phone', 'bio', 'profile_image', 'role_id', 'is_active'
        ]));

        return $this->successResponse($user->load('role'), 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->successResponse(null, 'User deleted successfully');
    }
}
