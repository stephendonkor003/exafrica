<?php

namespace App\Http\Controllers;

use App\Models\VotingPhase;
use Illuminate\Http\Request;

class VotingPhaseController extends BaseController
{
    public function index()
    {
        $phases = VotingPhase::orderBy('start_date')->get();

        return $this->successResponse($phases, 'Voting phases retrieved successfully');
    }

    public function getCurrentPhase()
    {
        $phase = VotingPhase::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (! $phase) {
            return $this->errorResponse('No active voting phase', null, 404);
        }

        return $this->successResponse($phase, 'Current voting phase retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'phase_type' => 'required|in:nomination,evaluation,voting,results|unique:voting_phases',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after:start_date',
        ]);

        $phase = VotingPhase::create($request->only([
            'name', 'description', 'phase_type', 'start_date', 'end_date',
        ]));

        return $this->successResponse($phase, 'Voting phase created successfully', 201);
    }

    public function update(Request $request, VotingPhase $phase)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'start_date' => 'nullable|date_format:Y-m-d H:i:s',
            'end_date' => 'nullable|date_format:Y-m-d H:i:s|after:start_date',
        ]);

        $phase->update($request->only([
            'name', 'description', 'start_date', 'end_date',
        ]));

        return $this->successResponse($phase, 'Voting phase updated successfully');
    }

    public function destroy(VotingPhase $phase)
    {
        $phase->delete();

        return $this->successResponse(null, 'Voting phase deleted successfully');
    }

    public function activate(VotingPhase $phase)
    {
        VotingPhase::where('is_active', true)->update(['is_active' => false]);
        $phase->update(['is_active' => true]);

        return $this->successResponse($phase, 'Voting phase activated successfully');
    }
}
