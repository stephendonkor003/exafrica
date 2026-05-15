<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\Voter;
use App\Models\Nominee;
use App\Models\VoteStatistic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends BaseController
{
    public function index(Request $request)
    {
        $query = Vote::with('nominee', 'category', 'voter');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->vote_type) {
            $query->where('vote_type', $request->vote_type);
        }

        $votes = $query->paginate(50);
        return $this->paginatedResponse($votes, 'Votes retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nominee_id' => 'required|exists:nominees,id',
            'mac_address' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);

        $nominee = Nominee::with('category')->findOrFail($request->nominee_id);

        if ($nominee->status !== 'published') {
            return $this->errorResponse('Nominee is not published for voting', null, 403);
        }

        $voterKey = $this->resolveVoterKey($request);
        $voter = Voter::firstOrCreate(['mac_address' => $voterKey]);

        if ($voter->is_blocked) {
            return $this->errorResponse('This voter has been blocked from voting', null, 403);
        }

        $existingCategoryVote = Vote::where('voter_id', $voter->id)
            ->where('category_id', $nominee->category_id)
            ->exists();

        if ($existingCategoryVote) {
            return $this->errorResponse('You have already voted in this category', null, 403);
        }

        $vote = DB::transaction(function () use ($request, $nominee, $voter, $voterKey) {
            $vote = Vote::create([
                'nominee_id' => $nominee->id,
                'category_id' => $nominee->category_id,
                'voter_id' => $voter->id,
                'mac_address' => $voterKey,
                'vote_type' => 'public_vote',
                'ip_address' => $request->ip(),
            ]);

            $voter->increment('vote_count');
            $voter->update(['last_voted_at' => now()]);
            $nominee->increment('vote_count');

            return $vote;
        });

        $this->updateVoteStatistics($nominee->category_id);

        return $this->successResponse([
            'vote_id' => $vote->id,
            'nominee_id' => $nominee->id,
        ], 'Vote recorded successfully', 201);
    }

    private function resolveVoterKey(Request $request): string
    {
        if ($request->filled('mac_address')) {
            return $request->mac_address;
        }

        if ($request->filled('device_id')) {
            return 'device:' . hash('sha256', $request->device_id);
        }

        if ($request->user()) {
            return 'user:' . $request->user()->id;
        }

        return 'visitor:' . hash('sha256', ($request->ip() ?? 'unknown') . '|' . (string) $request->userAgent());
    }

    public function getCategoryStats($categoryId)
    {
        $nominees = Nominee::where('category_id', $categoryId)
            ->where('status', 'published')
            ->with('voteStatistic')
            ->orderBy('vote_count', 'desc')
            ->get()
            ->map(function ($nominee) {
                return [
                    'id' => $nominee->id,
                    'full_name' => $nominee->full_name,
                    'bio' => $nominee->bio,
                    'profile_image' => $nominee->profile_image,
                    'vote_count' => $nominee->vote_count,
                    'rank' => $nominee->voteStatistic?->rank,
                    'percentage' => $nominee->voteStatistic?->percentage,
                ];
            });

        return $this->successResponse([
            'category_id' => $categoryId,
            'nominees' => $nominees,
            'total_votes' => array_sum(array_map(fn($n) => $n['vote_count'], $nominees->toArray())),
        ], 'Category statistics retrieved successfully');
    }

    public function getCandidateStats(Nominee $nominee)
    {
        $stats = $nominee->voteStatistic ?? VoteStatistic::where('nominee_id', $nominee->id)->first();

        return $this->successResponse([
            'nominee_id' => $nominee->id,
            'full_name' => $nominee->full_name,
            'public_votes' => $stats?->public_votes ?? 0,
            'judge_votes' => $stats?->judge_votes ?? 0,
            'total_votes' => $nominee->vote_count,
            'percentage' => $stats?->percentage ?? 0,
            'rank' => $stats?->rank,
        ], 'Nominee statistics retrieved successfully');
    }

    public function fraudDetection()
    {
        $suspiciousVoters = Voter::where('is_blocked', false)
            ->where('vote_count', '>=', 1)
            ->get();

        $duplicateVotes = DB::table('votes')
            ->selectRaw('voter_id, nominee_id, COUNT(*) as count')
            ->groupBy('voter_id', 'nominee_id')
            ->having('count', '>', 1)
            ->get();

        $suspiciousMacs = DB::table('votes')
            ->selectRaw('mac_address, COUNT(*) as vote_count')
            ->groupBy('mac_address')
            ->having('vote_count', '>', 1)
            ->get();

        return $this->successResponse([
            'suspicious_voters_count' => count($suspiciousVoters),
            'duplicate_votes_found' => count($duplicateVotes),
            'suspicious_mac_addresses' => $suspiciousMacs,
        ], 'Fraud detection data retrieved successfully');
    }

    private function updateVoteStatistics($categoryId)
    {
        $nominees = Nominee::where('category_id', $categoryId)->get();
        $totalVotes = Vote::where('category_id', $categoryId)->count();

        foreach ($nominees as $nominee) {
            $publicVotes = $nominee->votes()->where('vote_type', 'public_vote')->count();
            $judgeVotes = $nominee->votes()->where('vote_type', 'judge_vote')->count();
            $total = $publicVotes + $judgeVotes;
            $percentage = $totalVotes > 0 ? ($total / $totalVotes) * 100 : 0;

            VoteStatistic::updateOrCreate(
                ['nominee_id' => $nominee->id],
                [
                    'category_id' => $categoryId,
                    'public_votes' => $publicVotes,
                    'judge_votes' => $judgeVotes,
                    'total_votes' => $total,
                    'percentage' => $percentage,
                ]
            );
        }

        // Calculate ranks
        $stats = VoteStatistic::where('category_id', $categoryId)
            ->orderBy('total_votes', 'desc')
            ->get();

        foreach ($stats as $index => $stat) {
            $stat->update(['rank' => $index + 1]);
        }
    }
}
