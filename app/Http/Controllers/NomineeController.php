<?php

namespace App\Http\Controllers;

use App\Models\Nominee;
use App\Models\Category;
use Illuminate\Http\Request;

class NomineeController extends BaseController
{
    public function index(Request $request)
    {
        $query = Nominee::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $nominees = $query->paginate(20);
        return $this->paginatedResponse($nominees, 'Nominees retrieved successfully');
    }

    public function show(Nominee $nominee)
    {
        return $this->successResponse($nominee->load('category', 'votes'), 'Nominee retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'profile_image' => 'nullable|url',
            'category_id' => 'required|exists:categories,id',
        ]);

        $nominee = Nominee::create([
            'full_name' => $request->full_name,
            'bio' => $request->bio,
            'email' => $request->email,
            'phone' => $request->phone,
            'profile_image' => $request->profile_image,
            'category_id' => $request->category_id,
            'status' => 'pending',
        ]);

        return $this->successResponse($nominee->load('category'), 'Nominee created successfully', 201);
    }

    public function update(Request $request, Nominee $nominee)
    {
        $request->validate([
            'full_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'profile_image' => 'nullable|url',
        ]);

        $nominee->update($request->only(['full_name', 'bio', 'email', 'phone', 'profile_image']));

        return $this->successResponse($nominee->load('category'), 'Nominee updated successfully');
    }

    public function destroy(Nominee $nominee)
    {
        $nominee->delete();
        return $this->successResponse(null, 'Nominee deleted successfully');
    }

    public function approve(Nominee $nominee)
    {
        $nominee->update(['status' => 'approved']);
        return $this->successResponse($nominee, 'Nominee approved successfully');
    }

    public function reject(Request $request, Nominee $nominee)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        
        $nominee->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return $this->successResponse($nominee, 'Nominee rejected successfully');
    }

    public function publish(Nominee $nominee)
    {
        $nominee->update(['status' => 'published']);
        return $this->successResponse($nominee, 'Nominee published successfully');
    }
}
