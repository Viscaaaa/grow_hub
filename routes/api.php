<?php

use App\Http\Controllers\Api\AdminLevelRuleController;
use App\Http\Controllers\Api\AdminPointRuleController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EngagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GrowHub API
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:30,1')->name('login');

Route::middleware(['auth:sanctum', 'track.activity'])->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/first-change-password', [AuthController::class, 'firstChangePassword']);

    // Home & Dashboard
    Route::get('/home', [DashboardController::class, 'home']);
    Route::get('/dashboard', [DashboardController::class, 'me']);
    Route::get('/leaderboard', [DashboardController::class, 'leaderboard']);

    // Knowledge Feed & Content
    Route::get('/categories', [CommunityController::class, 'categories']);
    Route::get('/contents', [ContentController::class, 'index']);
    Route::post('/contents', [ContentController::class, 'store']);
    Route::get('/contents/{content}', [ContentController::class, 'show']);
    Route::post('/contents/{content}', [ContentController::class, 'update']); // multipart (method spoofing friendly)
    Route::delete('/contents/{content}', [ContentController::class, 'destroy']);
    Route::get('/my/contents', [ContentController::class, 'mine']);
    Route::get('/my/saved', [ContentController::class, 'saved']);

    // Engagement
    Route::post('/contents/{content}/like', [EngagementController::class, 'toggleLike']);
    Route::post('/contents/{content}/save', [EngagementController::class, 'toggleSave']);
    Route::post('/contents/{content}/share', [EngagementController::class, 'share']);
    Route::get('/contents/{content}/comments', [EngagementController::class, 'comments']);
    Route::post('/contents/{content}/comments', [EngagementController::class, 'storeComment']);
    Route::delete('/comments/{comment}', [EngagementController::class, 'destroyComment']);

    // Communities
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::post('/communities', [CommunityController::class, 'store']);
    Route::get('/communities/{community:slug}', [CommunityController::class, 'show']);
    Route::post('/communities/{community}/join', [CommunityController::class, 'toggleJoin']);

    // Learning Center
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course:slug}', [CourseController::class, 'show']);
    Route::post('/courses/{course}/complete', [CourseController::class, 'complete']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // Profile
    Route::get('/users/{user}', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'avatar']);
    Route::put('/profile/password', [ProfileController::class, 'password']);

    // Help & Support
    Route::get('/support/faqs', [SupportController::class, 'faqs']);
    Route::get('/support/tickets', [SupportController::class, 'index']);
    Route::post('/support/tickets', [SupportController::class, 'store']);

    // Reviewer & Admin
    Route::middleware('reviewer')->group(function () {
        Route::prefix('review')->group(function () {
            Route::get('/contents', [ReviewController::class, 'index']);
            Route::post('/contents/{content}/approve', [ReviewController::class, 'approve']);
            Route::post('/contents/{content}/reject', [ReviewController::class, 'reject']);
        });

        Route::prefix('admin')->middleware('admin')->group(function () {
            // User management
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::put('/users/{user}', [AdminUserController::class, 'update']);
            Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

            // Gamification Point Settings & Rules
            Route::get('/point-rules', [AdminPointRuleController::class, 'index']);
            Route::put('/point-rules/batch', [AdminPointRuleController::class, 'batchUpdate']);
            Route::post('/point-rules/enable-all', [AdminPointRuleController::class, 'enableAll']);
            Route::post('/point-rules/disable-all', [AdminPointRuleController::class, 'disableAll']);
            Route::post('/point-rules/reset-defaults', [AdminPointRuleController::class, 'resetDefaults']);
            Route::put('/point-rules/{pointRule}', [AdminPointRuleController::class, 'update']);
            Route::post('/point-rules/{pointRule}/toggle', [AdminPointRuleController::class, 'toggle']);

            // Gamification Level Settings & Rules
            Route::get('/level-rules', [AdminLevelRuleController::class, 'index']);
            Route::post('/level-rules', [AdminLevelRuleController::class, 'store']);
            Route::put('/level-rules/batch', [AdminLevelRuleController::class, 'batchUpdate']);
            Route::post('/level-rules/reset-defaults', [AdminLevelRuleController::class, 'resetDefaults']);
            Route::put('/level-rules/{levelRule}', [AdminLevelRuleController::class, 'update']);
            Route::post('/level-rules/{levelRule}/toggle', [AdminLevelRuleController::class, 'toggle']);
            Route::delete('/level-rules/{levelRule}', [AdminLevelRuleController::class, 'destroy']);
        });
    });
});
