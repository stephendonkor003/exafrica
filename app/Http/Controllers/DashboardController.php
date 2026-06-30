<?php

namespace App\Http\Controllers;

use App\Models\Nominee;
use App\Models\Category;
use App\Models\Vote;
use App\Models\VoteStatistic;
use App\Models\VotingPhase;
use App\Models\Nomination;
use App\Models\Judge;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function adminDashboard()
    {
        $totalNominees = Nominee::count();
        $totalVotes = Vote::count();
        $totalCategories = Category::count();
        $activePhase = VotingPhase::where('is_active', true)->first();

        $votesByCategory = Category::withCount(['votes' => function ($query) {
            $query->where('vote_type', 'public_vote');
        }])->get();

        $categoryVoteStats = Category::withCount([
            'nominees',
            'votes as public_votes_count' => function ($query) {
                $query->where('vote_type', 'public_vote');
            },
            'votes as judge_votes_count' => function ($query) {
                $query->where('vote_type', 'judge_vote');
            },
            'votes as total_votes_count',
        ])
            ->orderBy('position')
            ->get();

        $topNominees = Nominee::orderBy('vote_count', 'desc')
            ->limit(10)
            ->get(['id', 'full_name', 'vote_count', 'category_id']);

        $nomineeVoteStats = Nominee::with('category:id,name')
            ->withCount([
                'votes as public_votes_count' => function ($query) {
                    $query->where('vote_type', 'public_vote');
                },
                'votes as judge_votes_count' => function ($query) {
                    $query->where('vote_type', 'judge_vote');
                },
                'votes as total_votes_count',
            ])
            ->orderByDesc('vote_count')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'country', 'category_id', 'status', 'vote_count']);

        $phaseStatus = $this->getPhaseStatus();

        return $this->successResponse([
            'summary' => [
                'total_nominees' => $totalNominees,
                'total_votes' => $totalVotes,
                'total_categories' => $totalCategories,
                'active_phase' => $activePhase?->name,
            ],
            'votes_by_category' => $votesByCategory->map(fn($c) => [
                'category' => $c->name,
                'vote_count' => $c->votes_count,
            ]),
            'top_nominees' => $topNominees,
            'category_vote_stats' => $categoryVoteStats->map(fn($category) => [
                'id' => $category->id,
                'category' => $category->name,
                'nominee_count' => $category->nominees_count,
                'public_votes' => $category->public_votes_count,
                'judge_votes' => $category->judge_votes_count,
                'total_votes' => $category->total_votes_count,
            ]),
            'nominee_vote_stats' => $nomineeVoteStats->map(fn($nominee) => [
                'id' => $nominee->id,
                'full_name' => $nominee->full_name,
                'country' => $nominee->country,
                'status' => $nominee->status,
                'category_id' => $nominee->category_id,
                'category' => $nominee->category?->name,
                'public_votes' => $nominee->public_votes_count,
                'judge_votes' => $nominee->judge_votes_count,
                'total_votes' => $nominee->total_votes_count,
            ]),
            'phase_status' => $phaseStatus,
        ], 'Admin dashboard retrieved successfully');
    }

    public function evaluatorDashboard()
    {
        $pendingNominations = Nomination::where('evaluation_status', 'pending')
            ->with('nominee', 'category')
            ->count();

        $evaluatedNominations = Nomination::where('evaluated_by', auth()->id())
            ->with('nominee')
            ->get();

        $approvedNominees = Nominee::where('status', 'approved')->count();

        return $this->successResponse([
            'pending_evaluations' => $pendingNominations,
            'evaluated_count' => count($evaluatedNominations),
            'approved_nominees' => $approvedNominees,
            'recent_evaluations' => $evaluatedNominations->take(10),
        ], 'Evaluator dashboard retrieved successfully');
    }

    public function analystDashboard()
    {
        $totalVotes = Vote::count();
        $publicVotes = Vote::where('vote_type', 'public_vote')->count();
        $judgeVotes = Vote::where('vote_type', 'judge_vote')->count();

        $voteDistribution = Vote::selectRaw('category_id, COUNT(*) as vote_count')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $uniqueVoters = DB::table('votes')->distinct('voter_id')->count();

        // Fraud metrics
        $duplicateAttempts = DB::table('votes')
            ->selectRaw('voter_id, COUNT(*) as attempt_count')
            ->groupBy('voter_id')
            ->having('attempt_count', '>', 1)
            ->count();

        return $this->successResponse([
            'vote_summary' => [
                'total_votes' => $totalVotes,
                'public_votes' => $publicVotes,
                'judge_votes' => $judgeVotes,
            ],
            'vote_distribution' => $voteDistribution,
            'unique_voters' => $uniqueVoters,
            'fraud_metrics' => [
                'duplicate_attempts' => $duplicateAttempts,
            ],
        ], 'Analyst dashboard retrieved successfully');
    }

    public function judgeDashboard()
    {
        $judge = Judge::where('user_id', auth()->id())->first();

        if (!$judge) {
            return $this->errorResponse('You are not registered as a judge', null, 403);
        }

        $judgeVotes = Vote::where('judge_id', auth()->id())->count();

        $categories = Category::where('is_active', true)
            ->with(['nominees' => function ($query) {
            $query->where('status', 'published');
            }])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $this->successResponse([
            'judge_info' => [
                'id' => $judge->id,
                'name' => auth()->user()->name,
                'specialization' => $judge->specialization,
                'vote_count' => $judgeVotes,
                'is_published' => $judge->is_published,
            ],
            'categories' => $categories,
        ], 'Judge dashboard retrieved successfully');
    }

    public function voterDashboard()
    {
        $activePhase = VotingPhase::where('is_active', true)->first();

        if (!$activePhase || $activePhase->phase_type !== 'voting') {
            return $this->errorResponse('Voting is not currently active', null, 403);
        }

        $categories = Category::where('is_active', true)
            ->with(['nominees' => function ($query) {
            $query->where('status', 'published')
                ->with('voteStatistic')
                ->orderBy('vote_count', 'desc');
            }])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $publishedJudges = Judge::where('is_published', true)
            ->with('user')
            ->get(['id', 'user_id', 'background', 'profile_image', 'specialization']);

        return $this->successResponse([
            'active_phase' => [
                'name' => $activePhase->name,
                'start_date' => $activePhase->start_date,
                'end_date' => $activePhase->end_date,
            ],
            'categories' => $categories,
            'judges' => $publishedJudges,
        ], 'Voter dashboard retrieved successfully');
    }

    private function getPhaseStatus()
    {
        $phases = VotingPhase::orderBy('start_date')->get();

        return $phases->map(fn($phase) => [
            'name' => $phase->name,
            'type' => $phase->phase_type,
            'status' => $this->determinePhaseStatus($phase),
            'start_date' => $phase->start_date,
            'end_date' => $phase->end_date,
        ]);
    }

    private function determinePhaseStatus($phase)
    {
        if ($phase->is_active && now()->between($phase->start_date, $phase->end_date)) {
            return 'active';
        } elseif (now()->isBefore($phase->start_date)) {
            return 'upcoming';
        }
        return 'completed';
    }
}
