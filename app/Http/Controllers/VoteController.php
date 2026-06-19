<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\Voter;
use App\Models\Nominee;
use App\Models\VoteStatistic;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends BaseController
{
    private const VOTABLE_NOMINEE_STATUSES = ['approved', 'published'];

    public function index(Request $request)
    {
        $query = Vote::with('nominee', 'category', 'voter', 'accountUser', 'judge')
            ->latest();

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->vote_type) {
            $query->where('vote_type', $request->vote_type);
        }

        $votes = $query->paginate(50);
        $votes->getCollection()->transform(fn (Vote $vote) => $this->voteAuditPayload($vote));

        return $this->paginatedResponse($votes, 'Votes retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nominee_id' => 'required|exists:nominees,id',
            'mac_address' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:1000',
        ]);

        $nominee = Nominee::with('category')->findOrFail($request->nominee_id);

        if (! in_array($nominee->status, self::VOTABLE_NOMINEE_STATUSES, true)) {
            return $this->errorResponse('Nominee is not approved for voting', null, 403);
        }

        $accountUser = $this->resolveOptionalAuthenticatedUser($request);
        $voterKey = $this->resolveVoterKey($request);
        $voter = Voter::firstOrCreate(['mac_address' => $voterKey]);

        if ($voter->is_blocked) {
            return $this->errorResponse('This voter has been blocked from voting', null, 403);
        }

        $existingCategoryVote = Vote::where(function ($query) use ($request, $voter, $voterKey) {
            $query->where('voter_id', $voter->id)
                ->orWhere('mac_address', $voterKey);

            if ($request->ip()) {
                $query->orWhere('ip_address', $request->ip());
            }
        })
            ->where('category_id', $nominee->category_id)
            ->exists();

        if ($existingCategoryVote) {
            return $this->errorResponse('You have already voted in this category', null, 403);
        }

        $vote = DB::transaction(function () use ($request, $nominee, $voter, $voterKey, $accountUser) {
            $vote = Vote::create([
                'nominee_id' => $nominee->id,
                'category_id' => $nominee->category_id,
                'voter_id' => $voter->id,
                'account_user_id' => $accountUser?->id,
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

    private function resolveOptionalAuthenticatedUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }

        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return null;
        }

        $token = PersonalAccessToken::with('user.role')
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if (! $token?->user?->is_active) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token->user;
    }

    private function voteAuditPayload(Vote $vote): array
    {
        return [
            'id' => $vote->id,
            'vote_type' => $vote->vote_type,
            'nominee_id' => $vote->nominee_id,
            'nominee' => $vote->nominee ? [
                'id' => $vote->nominee->id,
                'full_name' => $vote->nominee->full_name,
                'country' => $vote->nominee->country,
            ] : null,
            'category_id' => $vote->category_id,
            'category' => $vote->category ? [
                'id' => $vote->category->id,
                'name' => $vote->category->name,
            ] : null,
            'voter_id' => $vote->voter_id,
            'voter_record' => $vote->voter ? [
                'id' => $vote->voter->id,
                'vote_count' => $vote->voter->vote_count,
                'is_blocked' => $vote->voter->is_blocked,
            ] : null,
            'account_user_id' => $vote->account_user_id,
            'account_user' => $vote->accountUser ? [
                'id' => $vote->accountUser->id,
                'name' => $vote->accountUser->name,
                'email' => $vote->accountUser->email,
                'role' => $vote->accountUser->role?->name,
            ] : null,
            'judge_id' => $vote->judge_id,
            'judge' => $vote->judge ? [
                'id' => $vote->judge->id,
                'name' => $vote->judge->name,
                'email' => $vote->judge->email,
            ] : null,
            'ip_address' => $vote->ip_address,
            'location' => $this->locationLabelForIp($vote->ip_address),
            'mac_address' => $vote->mac_address,
            'device_key' => $vote->mac_address,
            'created_at' => $vote->created_at,
        ];
    }

    private function locationLabelForIp(?string $ipAddress): string
    {
        if (blank($ipAddress)) {
            return 'Unknown';
        }

        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return 'Unknown';
        }

        $isPublic = filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $isPublic
            ? 'Public IP (location lookup not configured)'
            : 'Private or reserved network';
    }

    public function getCategoryStats($categoryId)
    {
        $nominees = Nominee::where('category_id', $categoryId)
            ->whereIn('status', self::VOTABLE_NOMINEE_STATUSES)
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
