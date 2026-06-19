<?php

namespace App\Http\Controllers;

use App\Models\Nominee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NomineeController extends BaseController
{
    private const PUBLIC_STATUSES = ['approved', 'published'];

    public function publicIndex(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Nominee::with(['category', 'nomination:id,nominee_id,nomination_reason'])
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->orderBy('vote_count', 'desc')
            ->orderBy('full_name');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $nominees = $query->paginate($perPage);
        $nominees->getCollection()->transform(fn (Nominee $nominee) => $this->publicNomineePayload($nominee));

        return $this->paginatedResponse($nominees, 'Nominees retrieved successfully');
    }

    public function publicShow(Nominee $nominee)
    {
        if (! in_array($nominee->status, self::PUBLIC_STATUSES, true)) {
            return $this->errorResponse('You do not have permission to access this resource', null, 403);
        }

        $nominee->load(['category', 'nomination:id,nominee_id,nomination_reason']);

        return $this->successResponse($this->publicNomineePayload($nominee), 'Nominee retrieved successfully');
    }

    public function index(Request $request)
    {
        $query = Nominee::with('category');
        $canManageNominees = in_array($request->user()?->role?->slug, ['super_admin', 'evaluator'], true);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if (! $canManageNominees) {
            $query->where('status', 'published');
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        $nominees = $query->paginate(20);

        return $this->paginatedResponse($nominees, 'Nominees retrieved successfully');
    }

    public function show(Request $request, Nominee $nominee)
    {
        $canManageNominees = in_array($request->user()?->role?->slug, ['super_admin', 'evaluator'], true);

        if (! $canManageNominees && $nominee->status !== 'published') {
            return $this->errorResponse('You do not have permission to access this resource', null, 403);
        }

        return $this->successResponse($nominee->load('category', 'votes'), 'Nominee retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => ['nullable', 'string', Rule::in($this->africanCountryNames())],
            'profile_image' => 'nullable|url:http,https|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        $nominee = Nominee::create([
            'full_name' => $request->full_name,
            'bio' => $request->bio,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
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
            'bio' => 'nullable|string|max:5000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => ['nullable', 'string', Rule::in($this->africanCountryNames())],
            'profile_image' => 'nullable|url:http,https|max:2048',
        ]);

        $nominee->update($request->only(['full_name', 'bio', 'email', 'phone', 'country', 'profile_image']));

        return $this->successResponse($nominee->load('category'), 'Nominee updated successfully');
    }

    private function africanCountryNames(): array
    {
        return array_column(config('african_countries', []), 'name');
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
        $request->validate(['rejection_reason' => 'required|string|max:5000']);

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

    private function publicNomineePayload(Nominee $nominee): array
    {
        return [
            'id' => $nominee->id,
            'full_name' => $nominee->full_name,
            'bio' => $nominee->bio,
            'country' => $nominee->country,
            'profile_image' => $nominee->profile_image,
            'category_id' => $nominee->category_id,
            'status' => $nominee->status,
            'vote_count' => $nominee->vote_count,
            'category' => $nominee->category ? [
                'id' => $nominee->category->id,
                'name' => $nominee->category->name,
                'description' => $nominee->category->description,
            ] : null,
            'nomination_reason' => $nominee->nomination?->nomination_reason,
        ];
    }
}
