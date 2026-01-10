<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HlsController;
use App\Http\Controllers\MarkdownBlogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoViewController;
use App\Http\Middleware\CheckSubscriptionLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication routes (rate limited to prevent abuse)
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [GoogleAuthController::class, 'logout']);
        Route::get('/me', [GoogleAuthController::class, 'user']);
    });
});

// Test route to verify API is working
Route::get('/test', function () {
    return response()->json([
        'message' => 'ScreenSense API is working!',
        'timestamp' => now(),
    ]);
});

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

// Polar webhook handled by laravel-polar package at: POST /polar/webhook

// Public video sharing - anyone can watch
Route::get('/share/video/{token}', [VideoController::class, 'viewShared']);
Route::get('/share/video/{token}/stream', [VideoController::class, 'streamShared']); // Public streaming for shared videos
Route::get('/share/video/{token}/comments', [CommentController::class, 'indexByToken']);
Route::get('/share/video/{token}/commenters', [CommentController::class, 'commentersByToken']);
Route::post('/share/video/{token}/view', [VideoViewController::class, 'recordSharedView']);

// HLS streaming with CORS support (for cross-origin playback)
Route::get('/share/video/{token}/hls/master.m3u8', [HlsController::class, 'masterPlaylist']);
Route::get('/share/video/{token}/hls/{variant}.m3u8', [HlsController::class, 'variantPlaylist']);
Route::get('/share/video/{token}/hls/{segment}.ts', [HlsController::class, 'segment']);

// Blog routes - public (markdown file-driven)
Route::prefix('blogs')->group(function () {
    Route::get('/', [MarkdownBlogController::class, 'apiIndex']);
    Route::get('/recent', [MarkdownBlogController::class, 'recent']);
    Route::get('/category/{category}', [MarkdownBlogController::class, 'apiByCategory']);
    Route::get('/{slug}', [MarkdownBlogController::class, 'apiShow']);
});
Route::get('/share/video/{token}/reactions', [ReactionController::class, 'indexByToken']);
Route::post('/share/video/{token}/reactions', [ReactionController::class, 'storeByToken']); // Reactions don't require auth

// App settings - public (subscription prices, limits, etc.)
Route::get('/settings', [SettingController::class, 'publicSettings']);

// ============================================
// PROTECTED ROUTES (Authentication required)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Commenting on shared videos requires auth
    Route::post('/share/video/{token}/comments', [CommentController::class, 'storeByToken']);

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    // Subscription management routes
    Route::prefix('subscription')->group(function () {
        Route::get('/status', [SubscriptionController::class, 'status']);
        Route::get('/history', [SubscriptionController::class, 'history']);
        Route::post('/checkout', [SubscriptionController::class, 'createCheckout']);
        Route::get('/checkout-url', [SubscriptionController::class, 'getCheckoutUrl']);
        Route::post('/checkout/success', [SubscriptionController::class, 'handleCheckoutSuccess']);
        Route::post('/cancel', [SubscriptionController::class, 'cancel']);
        Route::get('/portal', [SubscriptionController::class, 'getPortalUrl']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Feedback routes (rate limited: 3 per hour)
    Route::prefix('feedback')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);
        Route::post('/', [FeedbackController::class, 'store']);
    });

    // Video routes - all require authentication
    Route::prefix('videos')->group(function () {
        Route::get('/', [VideoController::class, 'index']);
        Route::get('/favourites', [VideoController::class, 'favourites']);

        // Video upload requires subscription limit check
        Route::post('/', [VideoController::class, 'store'])
            ->middleware(CheckSubscriptionLimit::class);

        Route::get('/{id}', [VideoController::class, 'show']);
        Route::get('/{id}/stream', [VideoController::class, 'stream']);
        Route::put('/{id}', [VideoController::class, 'update']);
        Route::delete('/{id}', [VideoController::class, 'destroy']);
        Route::post('/{id}/toggle-sharing', [VideoController::class, 'toggleSharing']);
        Route::post('/{id}/toggle-favourite', [VideoController::class, 'toggleFavourite']);
        Route::post('/{id}/regenerate-token', [VideoController::class, 'regenerateShareToken']);
        Route::post('/{id}/trim', [VideoController::class, 'trim']);
        Route::get('/{id}/conversion-status', [VideoController::class, 'conversionStatus']);

        // Comments
        Route::get('/{id}/comments', [CommentController::class, 'index']);
        Route::get('/{id}/commenters', [CommentController::class, 'commenters']);
        Route::post('/{id}/comments', [CommentController::class, 'store']);
        Route::put('/{id}/comments/{commentId}', [CommentController::class, 'update']);
        Route::delete('/{id}/comments/{commentId}', [CommentController::class, 'destroy']);

        // Reactions
        Route::get('/{id}/reactions', [ReactionController::class, 'index']);
        Route::post('/{id}/reactions', [ReactionController::class, 'store']);
        Route::get('/{id}/reactions/user', [ReactionController::class, 'userReactions']);

        // Views tracking
        Route::post('/{id}/view', [VideoViewController::class, 'recordView']);
        Route::get('/{id}/stats', [VideoViewController::class, 'getStats']);

        // Transcription and summary
        Route::post('/{id}/transcription', [VideoController::class, 'requestTranscription']);
        Route::get('/{id}/transcription', [VideoController::class, 'getTranscription']);
        Route::get('/{id}/transcription/status', [VideoController::class, 'transcriptionStatus']);
        Route::post('/{id}/summary', [VideoController::class, 'requestSummary']);
        Route::get('/{id}/summary', [VideoController::class, 'getSummary']);
    });
});

// Legacy recording routes (deprecated - use /videos instead)
Route::prefix('recordings')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'recordings' => [],
            'message' => 'Use /api/videos endpoint instead',
        ]);
    });

    Route::post('/', function (Request $request) {
        return response()->json([
            'message' => 'Use /api/videos endpoint instead',
            'id' => uniqid(),
        ]);
    });
});

// ============================================
// STREAMING UPLOAD ROUTES (Chunked upload during recording)
// ============================================
Route::middleware('auth:sanctum')->prefix('stream')->group(function () {
    // Starting a new upload requires subscription limit check
    Route::post('/start', [\App\Http\Controllers\StreamVideoController::class, 'startUpload'])
        ->middleware(CheckSubscriptionLimit::class);

    Route::post('/{sessionId}/chunk', [\App\Http\Controllers\StreamVideoController::class, 'uploadChunk']);
    Route::post('/{sessionId}/complete', [\App\Http\Controllers\StreamVideoController::class, 'completeUpload']);
    Route::post('/{sessionId}/cancel', [\App\Http\Controllers\StreamVideoController::class, 'cancelUpload']);
    Route::get('/{sessionId}/status', [\App\Http\Controllers\StreamVideoController::class, 'getStatus']);
});
