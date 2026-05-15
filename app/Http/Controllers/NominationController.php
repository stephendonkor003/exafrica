<?php

namespace App\Http\Controllers;

use App\Models\Nomination;
use App\Models\Nominee;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NominationController extends BaseController
{
    public function index(Request $request)
    {
        $query = Nomination::with('nominee', 'category', 'nominatedBy', 'evaluatedBy');

        if ($request->evaluation_status) {
            $query->where('evaluation_status', $request->evaluation_status);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $nominations = $query->paginate(20);
        return $this->paginatedResponse($nominations, 'Nominations retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nominee_id' => 'nullable|exists:nominees,id|required_without:full_name',
            'full_name' => 'nullable|string|max:255|required_without:nominee_id',
            'bio' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'profile_image' => 'nullable|url',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'nomination_reason' => 'required|string',
        ]);

        $category = Category::findOrFail($request->category_id);

        $nomination = DB::transaction(function () use ($request, $category) {
            $nominee = $request->nominee_id
                ? Nominee::lockForUpdate()->findOrFail($request->nominee_id)
                : Nominee::create([
                    'full_name' => $request->full_name,
                    'bio' => $request->bio,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'profile_image' => $request->profile_image,
                    'category_id' => $category->id,
                    'status' => 'pending',
                ]);

            if ((int) $nominee->category_id !== (int) $category->id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Nominee does not belong to the selected category',
                    'errors' => null,
                ], 422));
            }

            $existingNomination = Nomination::where('nominee_id', $nominee->id)
                ->where('category_id', $category->id)
                ->first();

            if ($existingNomination) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'This nominee has already been nominated in this category',
                    'errors' => null,
                ], 409));
            }

            return Nomination::create([
                'nominee_id' => $nominee->id,
                'category_id' => $category->id,
                'nominated_by' => auth()->id(),
                'nomination_reason' => $request->nomination_reason,
                'evaluation_status' => 'pending',
            ]);
        });

        return $this->successResponse(
            $nomination->load('nominee', 'category', 'nominatedBy'),
            'Nomination created successfully',
            201
        );
    }

    public function update(Request $request, Nomination $nomination)
    {
        $request->validate([
            'nomination_reason' => 'nullable|string',
        ]);

        if ($nomination->evaluation_status !== 'pending') {
            return $this->errorResponse('Cannot update evaluated nominations', null, 403);
        }

        $nomination->update($request->only(['nomination_reason']));

        return $this->successResponse($nomination->load('nominee', 'category'), 'Nomination updated successfully');
    }

    public function evaluate(Request $request, Nomination $nomination)
    {
        $request->validate([
            'evaluation_status' => 'required|in:approved,rejected',
            'evaluator_notes' => 'nullable|string',
        ]);

        $nomination->update([
            'evaluation_status' => $request->evaluation_status,
            'evaluator_notes' => $request->evaluator_notes,
            'evaluated_by' => auth()->id(),
            'evaluated_at' => now(),
        ]);

        if ($request->evaluation_status === 'approved') {
            $nomination->nominee->update(['status' => 'approved']);
        } else {
            $nomination->nominee->update([
                'status' => 'rejected',
                'rejection_reason' => $request->evaluator_notes,
            ]);
        }

        return $this->successResponse($nomination->load('nominee'), 'Nomination evaluated successfully');
    }

    public function approve(Nomination $nomination)
    {
        if ($nomination->evaluation_status === 'pending') {
            $nomination->update([
                'evaluation_status' => 'approved',
                'evaluated_by' => auth()->id(),
                'evaluated_at' => now(),
            ]);
            $nomination->nominee->update(['status' => 'approved']);
        }

        return $this->successResponse($nomination, 'Nomination approved successfully');
    }
}
