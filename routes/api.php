<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiAuthController;
use App\Http\Controllers\API\ApiUserController;
use App\Http\Controllers\API\ApiWithdrawalController;
use App\Http\Controllers\API\ApiAppointmentController;
use App\Http\Controllers\API\ApiTeamController;
use App\Http\Controllers\API\ApiTutorialController;
use App\Http\Controllers\API\ApiTicketController;
use App\Http\Controllers\API\ApiGuideController;
use App\Http\Controllers\API\ApiRiscoinLinkController;
use App\Http\Controllers\API\ApiCalculatorController;
use App\Http\Controllers\API\ApiDashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes (no authentication required)
|--------------------------------------------------------------------------
*/
Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum token required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    // ── User Profile ──────────────────────────────────────────────────────────
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/',                  [ApiUserController::class, 'profile'])->name('profile');
        Route::put('/',                  [ApiUserController::class, 'update']);
        Route::post('/avatar',           [ApiUserController::class, 'uploadAvatar']);
        Route::put('/password',          [ApiUserController::class, 'changePassword']);
        Route::delete('/',               [ApiUserController::class, 'destroy']);
        Route::get('/notifications',     [ApiUserController::class, 'notifications']);
        Route::post('/notifications/read-all', [ApiUserController::class, 'markAllNotificationsRead']);
        Route::post('/notifications/{id}/read', [ApiUserController::class, 'markNotificationRead']);
    });

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::get('/dashboard', [ApiDashboardController::class, 'index'])->name('api.dashboard');

    // ── Leaderboard ───────────────────────────────────────────────────────────
    Route::get('/leaderboard', [ApiDashboardController::class, 'leaderboard']);

    // ── Withdrawals ───────────────────────────────────────────────────────────
    Route::prefix('withdrawals')->middleware('can:myWithdrawals.view')->group(function () {
        Route::get('/',    [ApiWithdrawalController::class, 'index']);
        Route::post('/',   [ApiWithdrawalController::class, 'store'])->middleware('can:myWithdrawals.create');
        Route::get('/{withdrawal}', [ApiWithdrawalController::class, 'show']);
    });

    // ── Appointments ──────────────────────────────────────────────────────────
    Route::prefix('appointments')->middleware('can:appointments.booking')->group(function () {
        Route::get('/',                 [ApiAppointmentController::class, 'index']);
        Route::post('/',                [ApiAppointmentController::class, 'store']);
        Route::get('/hosts',            [ApiAppointmentController::class, 'hosts']);
        Route::get('/{appointment}',    [ApiAppointmentController::class, 'show']);
        Route::patch('/{appointment}/cancel', [ApiAppointmentController::class, 'cancel']);
    });

    // ── My Team & Genealogy ───────────────────────────────────────────────────
    Route::prefix('team')->middleware('can:myTeam.access')->name('api.team.')->group(function () {
        Route::get('/',           [ApiTeamController::class, 'index'])->name('index');
        Route::get('/genealogy',  [ApiTeamController::class, 'genealogy'])->middleware('can:genealogy.view')->name('genealogy');
        Route::get('/assistant',  [ApiTeamController::class, 'assistant'])->name('assistant');
    });

    // ── Tutorials ─────────────────────────────────────────────────────────────
    Route::prefix('tutorials')->middleware('can:tutorials.access')->group(function () {
        Route::get('/',              [ApiTutorialController::class, 'index']);
        Route::get('/{tutorial}',    [ApiTutorialController::class, 'show']);
    });

    // ── Support Tickets ───────────────────────────────────────────────────────
    Route::prefix('tickets')->group(function () {
        Route::get('/',                          [ApiTicketController::class, 'index']);
        Route::post('/',                         [ApiTicketController::class, 'store']);
        Route::get('/{ticket}',                  [ApiTicketController::class, 'show']);
        Route::post('/{ticket}/comments',        [ApiTicketController::class, 'addComment']);
    });

    // ── Guides ────────────────────────────────────────────────────────────────
    Route::prefix('guides')->middleware('can:guide.access')->group(function () {
        Route::get('/',              [ApiGuideController::class, 'index']);
        Route::get('/categories',    [ApiGuideController::class, 'categories']);
        Route::get('/{guide}',       [ApiGuideController::class, 'show']);
    });

    // ── RisCoin Links ─────────────────────────────────────────────────────────
    Route::get('/riscoin-links', [ApiRiscoinLinkController::class, 'index']);

    // ── Compound Calculator ───────────────────────────────────────────────────
    Route::post('/calculator/calculate', [ApiCalculatorController::class, 'calculate'])
        ->middleware('can:calculator.access');
});

