<?php

use App\Http\Controllers\AdminDashboardController;
// use App\Http\Controllers\BunnyVideoController; // Bunny disabled - encoding costs too high
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClipForgeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\HlsController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MarkdownBlogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ScreenshotController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoViewController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckSubscriptionLimit;
use App\Http\Middleware\OptionalSanctumAuthMiddleware;
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
        'message' => 'OpenKap API is working!',
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
Route::get('/share/video/{token}/captions.vtt', [VideoController::class, 'sharedCaptions']); // Public WebVTT captions
Route::get('/share/video/{token}/comments', [CommentController::class, 'indexByToken']);
Route::get('/share/video/{token}/commenters', [CommentController::class, 'commentersByToken']);
Route::post('/share/video/{token}/view', [VideoViewController::class, 'recordSharedView'])
    ->middleware([OptionalSanctumAuthMiddleware::class, 'throttle:30,1']);

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
Route::post('/share/video/{token}/reactions', [ReactionController::class, 'storeByToken'])
    ->middleware('throttle:30,1');

// App settings - public (subscription prices, limits, etc.)
Route::get('/settings', [SettingController::class, 'publicSettings']);

// Integration OAuth callbacks (validated via encrypted state param, no auth middleware)
Route::get('/integrations/{provider}/callback', [IntegrationController::class, 'callback']);

// Public playlist sharing - anyone can view
Route::get('/share/playlist/{token}', [PlaylistController::class, 'showShared']);

// Public screenshot sharing - anyone can view
Route::get('/share/screenshot/{token}', [ScreenshotController::class, 'viewShared']);

// ============================================
// PROTECTED ROUTES (Authentication required)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Onboarding
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete']);

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

    // User settings routes (using /user/settings to avoid conflict with public /settings)
    Route::prefix('user/settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::post('/reset', [SettingsController::class, 'reset']);
        Route::post('/logo', [SettingsController::class, 'uploadLogo']);
        Route::delete('/logo', [SettingsController::class, 'removeLogo']);
    });

    // Feedback routes (rate limited: 3 per hour)
    Route::prefix('feedback')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);
        Route::post('/', [FeedbackController::class, 'store']);
        Route::delete('/{id}', [FeedbackController::class, 'destroy']);
    });

    // Workspace routes
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index']);
        Route::post('/', [WorkspaceController::class, 'store']);
        Route::get('/{slug}', [WorkspaceController::class, 'show']);
        Route::patch('/{slug}', [WorkspaceController::class, 'update']);
        Route::delete('/{slug}', [WorkspaceController::class, 'destroy']);
        Route::post('/{slug}/leave', [WorkspaceController::class, 'leave']);
        Route::get('/{slug}/videos', [WorkspaceController::class, 'videos']);

        // Member management
        Route::get('/{slug}/members', [WorkspaceMemberController::class, 'index']);
        Route::post('/{slug}/members/invite', [WorkspaceMemberController::class, 'invite']);
        Route::patch('/{slug}/members/{userId}', [WorkspaceMemberController::class, 'update']);
        Route::delete('/{slug}/members/{userId}', [WorkspaceMemberController::class, 'destroy']);

        // Invitation management within workspace
        Route::get('/{slug}/invitations', [WorkspaceInvitationController::class, 'index']);
        Route::post('/{slug}/invitations', [WorkspaceInvitationController::class, 'store']);
        Route::delete('/{slug}/invitations/{invitationId}', [WorkspaceInvitationController::class, 'destroy']);
        Route::post('/{slug}/invitations/{invitationId}/resend', [WorkspaceInvitationController::class, 'resend']);
    });

    // Invitation routes (accessed by token, for accepting invitations)
    Route::get('/invitations/{token}', [WorkspaceInvitationController::class, 'show'])->withoutMiddleware('auth:sanctum');
    Route::post('/invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept']);

    // Video routes - all require authentication
    Route::prefix('videos')->group(function () {
        Route::get('/', [VideoController::class, 'index']);
        Route::get('/favourites', [VideoController::class, 'favourites']);

        // Video upload requires subscription limit check
        Route::post('/', [VideoController::class, 'store'])
            ->middleware(CheckSubscriptionLimit::class);

        // Bulk operations
        Route::post('/bulk-delete', [VideoController::class, 'bulkDestroy']);
        Route::post('/bulk-favourite', [VideoController::class, 'bulkAddToFavourites']);
        Route::delete('/bulk-favourite', [VideoController::class, 'bulkRemoveFromFavourites']);

        Route::get('/{id}', [VideoController::class, 'show']);
        Route::get('/{id}/stream', [VideoController::class, 'stream']);
        Route::put('/{id}', [VideoController::class, 'update']);
        Route::delete('/{id}', [VideoController::class, 'destroy']);
        Route::post('/{id}/toggle-sharing', [VideoController::class, 'toggleSharing']);
        Route::post('/{id}/toggle-favourite', [VideoController::class, 'toggleFavourite']);
        Route::post('/{id}/regenerate-token', [VideoController::class, 'regenerateShareToken']);
        Route::post('/{id}/trim', [VideoController::class, 'trim']);
        Route::post('/{id}/blur', [VideoController::class, 'applyBlur']);
        Route::get('/{id}/blur-status', [VideoController::class, 'blurStatus']);
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

        // Zoom effects
        Route::put('/{id}/zoom-settings', [VideoController::class, 'updateZoomSettings']);
        Route::get('/{id}/zoom-events', [VideoController::class, 'getZoomEvents']);
        Route::put('/{id}/zoom-events', [VideoController::class, 'updateZoomEvents']);
        Route::get('/{id}/zoom-status', [VideoController::class, 'getZoomStatus']);

        // Transcription editing
        Route::put('/{id}/transcription', [VideoController::class, 'updateTranscription']);

        // Transcript AI chat (5 questions/day limit)
        Route::post('/{id}/transcript-chat', [VideoController::class, 'transcriptChat']);

        // Video editor
        Route::post('/{id}/apply-edits', [VideoController::class, 'applyEdits']);
        Route::get('/{id}/edit-status', [VideoController::class, 'editStatus']);

        // MP4 download
        Route::post('/{id}/request-download-mp4', [VideoController::class, 'requestDownloadMp4']);
        Route::get('/{id}/download-mp4', [VideoController::class, 'downloadMp4']);
    });

    // Folder routes
    Route::prefix('folders')->group(function () {
        Route::get('/', [FolderController::class, 'index']);
        Route::post('/', [FolderController::class, 'store']);
        Route::patch('/{id}', [FolderController::class, 'update']);
        Route::delete('/{id}', [FolderController::class, 'destroy']);
        Route::get('/{id}/videos', [FolderController::class, 'videos']);
        Route::post('/{id}/videos', [FolderController::class, 'addVideos']);
        Route::delete('/{id}/videos/{videoId}', [FolderController::class, 'removeVideo']);
    });

    // Playlist routes
    Route::prefix('playlists')->group(function () {
        Route::get('/', [PlaylistController::class, 'index']);
        Route::post('/', [PlaylistController::class, 'store']);
        Route::get('/{id}', [PlaylistController::class, 'show']);
        Route::put('/{id}', [PlaylistController::class, 'update']);
        Route::delete('/{id}', [PlaylistController::class, 'destroy']);
        Route::post('/{id}/toggle-sharing', [PlaylistController::class, 'toggleSharing']);
        Route::put('/{id}/password', [PlaylistController::class, 'setPassword']);
        Route::put('/{id}/sort-by', [PlaylistController::class, 'updateSortBy']);
        Route::post('/{id}/videos', [PlaylistController::class, 'addVideo']);
        Route::post('/{id}/bulk-add-videos', [PlaylistController::class, 'bulkAddVideos']);
        Route::delete('/{id}/videos/{videoId}', [PlaylistController::class, 'removeVideo']);
        Route::put('/{id}/reorder', [PlaylistController::class, 'reorder']);
    });

    // Screenshot routes
    Route::prefix('screenshots')->group(function () {
        Route::get('/', [ScreenshotController::class, 'index']);
        Route::post('/', [ScreenshotController::class, 'store']);
        Route::get('/{id}', [ScreenshotController::class, 'show']);
        Route::put('/{id}', [ScreenshotController::class, 'update']);
        Route::delete('/{id}', [ScreenshotController::class, 'destroy']);
        Route::post('/{id}/toggle-sharing', [ScreenshotController::class, 'toggleSharing']);
    });

    // Integration routes
    Route::prefix('integrations')->group(function () {
        Route::get('/', [IntegrationController::class, 'index']);
        Route::get('/providers', [IntegrationController::class, 'availableProviders']);
        Route::get('/{provider}/connect', [IntegrationController::class, 'connect']);
        Route::delete('/{provider}', [IntegrationController::class, 'disconnect']);
        Route::get('/{provider}/targets', [IntegrationController::class, 'targets']);
        Route::post('/{provider}/videos/{videoId}/share', [IntegrationController::class, 'shareVideo']);
        Route::post('/{provider}/videos/{videoId}/bug', [IntegrationController::class, 'createBug']);
        Route::get('/videos/{videoId}/history', [IntegrationController::class, 'shareHistory']);
    });

    // Chat routes
    Route::prefix('chat')->middleware('throttle:30,1')->group(function () {
        Route::get('/conversations', [ChatController::class, 'conversations']);
        Route::post('/conversations', [ChatController::class, 'createConversation']);
        Route::get('/conversations/{id}/messages', [ChatController::class, 'messages']);
        Route::delete('/conversations/{id}', [ChatController::class, 'destroyConversation']);
        Route::post('/send', [ChatController::class, 'send']);
    });
});

// ============================================
// ADMIN ROUTES (Admin access required)
// ============================================
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    });

// Legacy recording routes (deprecated - use /videos instead)
Route::prefix('recordings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'recordings' => [],
            'message' => 'Use /api/videos endpoint instead',
        ]);
    });

    Route::post('/', function (Request $request) {
        return response()->json([
            'message' => 'Use /api/videos endpoint instead',
        ]);
    });
});

// ============================================
// STREAMING UPLOAD ROUTES (Chunked upload during recording)
// ============================================
Route::middleware('auth:sanctum')->prefix('stream')->group(function () {
    // Starting a new upload requires subscription limit check + rate limit
    Route::post('/start', [\App\Http\Controllers\StreamVideoController::class, 'startUpload'])
        ->middleware([CheckSubscriptionLimit::class, 'throttle:5,1']);

    Route::post('/{sessionId}/chunk', [\App\Http\Controllers\StreamVideoController::class, 'uploadChunk']);
    Route::post('/{sessionId}/complete', [\App\Http\Controllers\StreamVideoController::class, 'completeUpload'])
        ->middleware(CheckSubscriptionLimit::class);
    Route::post('/{sessionId}/cancel', [\App\Http\Controllers\StreamVideoController::class, 'cancelUpload'])
        ->middleware('throttle:5,1');
    Route::get('/{sessionId}/status', [\App\Http\Controllers\StreamVideoController::class, 'getStatus'])
        ->middleware('throttle:30,1');
});

// ============================================
// BUNNY STREAM ROUTES (Disabled - encoding costs too high)
// ============================================
// // Bunny webhook (no auth - Bunny sends these automatically)
// Route::post('/webhooks/bunny', [\App\Http\Controllers\BunnyWebhookController::class, 'handle']);
//
// // Public routes for shared video playback
// Route::get('/bunny/share/{token}/playback', [BunnyVideoController::class, 'sharedPlayback']);
//
// // Protected routes for video management
// Route::middleware('auth:sanctum')->prefix('bunny/videos')->group(function () {
//     // Create video and get upload credentials (requires subscription check)
//     Route::post('/create', [BunnyVideoController::class, 'create'])
//         ->middleware(CheckSubscriptionLimit::class);
//
//     // Mark upload as complete
//     Route::post('/{id}/complete', [BunnyVideoController::class, 'complete']);
//
//     // Get video processing status
//     Route::get('/{id}/status', [BunnyVideoController::class, 'status']);
//
//     // Get signed playback URLs
//     Route::get('/{id}/playback', [BunnyVideoController::class, 'playback']);
//
//     // Delete video
//     Route::delete('/{id}', [BunnyVideoController::class, 'destroy']);
// });

// ClipForge - Video clip extraction tool (public, per-endpoint rate limits)
Route::prefix('clipforge')->group(function () {
    // Expensive operations: tight limits
    Route::post('/youtube', [ClipForgeController::class, 'youtube'])->middleware('throttle:5,10');   // 5 per 10 min
    Route::post('/upload', [ClipForgeController::class, 'upload'])->middleware('throttle:5,10');      // 5 per 10 min
    Route::post('/clip', [ClipForgeController::class, 'clip'])->middleware('throttle:15,10');         // 15 per 10 min

    // File serving: generous (browser makes many range requests)
    Route::get('/video/{filename}', [ClipForgeController::class, 'video'])->middleware('throttle:120,1');
    Route::get('/download/{filename}', [ClipForgeController::class, 'download'])->middleware('throttle:30,1');
});
