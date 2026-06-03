<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NomineeController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\VotingPhaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;

Route::prefix('v1')->group(function () {
    // Auth routes (no authentication required)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth.api')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Categories
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'show']);
        Route::middleware('role:super_admin')->group(function () {
            Route::post('categories', [CategoryController::class, 'store']);
            Route::put('categories/{category}', [CategoryController::class, 'update']);
            Route::patch('categories/{category}', [CategoryController::class, 'update']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        });

        // Nominees
        Route::get('nominees', [NomineeController::class, 'index']);
        Route::get('nominees/{nominee}', [NomineeController::class, 'show']);
        Route::middleware('role:super_admin,evaluator')->group(function () {
            Route::post('nominees', [NomineeController::class, 'store']);
            Route::put('nominees/{nominee}', [NomineeController::class, 'update']);
            Route::delete('nominees/{nominee}', [NomineeController::class, 'destroy']);
            Route::post('nominees/{nominee}/approve', [NomineeController::class, 'approve']);
            Route::post('nominees/{nominee}/reject', [NomineeController::class, 'reject']);
            Route::post('nominees/{nominee}/publish', [NomineeController::class, 'publish']);
        });

        // Nominations
        Route::post('nominations', [NominationController::class, 'store']);
        Route::middleware('role:super_admin,evaluator')->group(function () {
            Route::get('nominations', [NominationController::class, 'index']);
            Route::put('nominations/{nomination}', [NominationController::class, 'update']);
            Route::post('nominations/{nomination}/evaluate', [NominationController::class, 'evaluate']);
            Route::post('nominations/{nomination}/approve', [NominationController::class, 'approve']);
        });

        // Voting & Votes
        Route::post('votes', [VoteController::class, 'store']);
        Route::get('votes/stats/{category}', [VoteController::class, 'getCategoryStats']);
        Route::get('votes/candidate/{nominee}', [VoteController::class, 'getCandidateStats']);
        Route::middleware('role:super_admin,voting_analyst')->group(function () {
            Route::get('votes', [VoteController::class, 'index']);
            Route::get('votes/fraud-detection', [VoteController::class, 'fraudDetection']);
        });

        // Judges
        Route::get('judges', [JudgeController::class, 'index']);
        Route::get('judges/{judge}', [JudgeController::class, 'show']);
        Route::middleware('role:super_admin')->group(function () {
            Route::post('judges', [JudgeController::class, 'store']);
            Route::put('judges/{judge}', [JudgeController::class, 'update']);
            Route::delete('judges/{judge}', [JudgeController::class, 'destroy']);
            Route::post('judges/{judge}/publish', [JudgeController::class, 'publish']);
            Route::post('judges/{judge}/unpublish', [JudgeController::class, 'unpublish']);
        });

        // Voting Phases
        Route::get('voting-phases/current', [VotingPhaseController::class, 'getCurrentPhase']);
        Route::middleware('role:super_admin')->group(function () {
            Route::get('voting-phases', [VotingPhaseController::class, 'index']);
            Route::post('voting-phases', [VotingPhaseController::class, 'store']);
            Route::put('voting-phases/{phase}', [VotingPhaseController::class, 'update']);
            Route::delete('voting-phases/{phase}', [VotingPhaseController::class, 'destroy']);
            Route::post('voting-phases/{phase}/activate', [VotingPhaseController::class, 'activate']);
        });

        // Dashboards
        Route::get('dashboard/admin', [DashboardController::class, 'adminDashboard'])->middleware('role:super_admin');
        Route::get('dashboard/evaluator', [DashboardController::class, 'evaluatorDashboard'])->middleware('role:evaluator');
        Route::get('dashboard/analyst', [DashboardController::class, 'analystDashboard'])->middleware('role:voting_analyst');
        Route::get('dashboard/judge', [DashboardController::class, 'judgeDashboard'])->middleware('role:judge');
        Route::get('dashboard/voter', [DashboardController::class, 'voterDashboard']);

        // User Management
        Route::middleware('role:super_admin')->group(function () {
            Route::get('roles', [UserController::class, 'roles']);
            Route::apiResource('users', UserController::class);
        });
    });
});
