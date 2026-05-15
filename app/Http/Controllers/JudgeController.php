<?php

namespace App\Http\Controllers;

use App\Models\Judge;
use App\Models\User;
use Illuminate\Http\Request;

class JudgeController extends BaseController
{
    public function index(Request $request)
    {
        $query = Judge::with('user');

        if ($request->published_only) {
            $query->where('is_published', true);
        }

        $judges = $query->paginate(20);
        return $this->paginatedResponse($judges, 'Judges retrieved successfully');
    }

    public function show(Judge $judge)
    {
        return $this->successResponse($judge->load('user'), 'Judge retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:judges',
            'background' => 'nullable|string',
            'profile_image' => 'nullable|url',
            'specialization' => 'nullable|string',
        ]);

        $judge = Judge::create($request->only(['user_id', 'background', 'profile_image', 'specialization']));

        return $this->successResponse($judge->load('user'), 'Judge created successfully', 201);
    }

    public function update(Request $request, Judge $judge)
    {
        $request->validate([
            'background' => 'nullable|string',
            'profile_image' => 'nullable|url',
            'specialization' => 'nullable|string',
        ]);

        $judge->update($request->only(['background', 'profile_image', 'specialization']));

        return $this->successResponse($judge->load('user'), 'Judge updated successfully');
    }

    public function destroy(Judge $judge)
    {
        $judge->delete();
        return $this->successResponse(null, 'Judge deleted successfully');
    }

    public function publish(Judge $judge)
    {
        $judge->update(['is_published' => true]);
        return $this->successResponse($judge->load('user'), 'Judge published successfully');
    }

    public function unpublish(Judge $judge)
    {
        $judge->update(['is_published' => false]);
        return $this->successResponse($judge->load('user'), 'Judge unpublished successfully');
    }
}
