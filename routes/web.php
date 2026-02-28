<?php

use App\Http\Controllers\MarkdownBlogController;
use App\Models\Video;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Privacy Policy page
Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

// About page
Route::get('/about', function () {
    return view('about');
});

// Contact page
Route::get('/contact', function () {
    return view('contact');
});

// Terms and Conditions page
Route::get('/terms', function () {
    return view('terms');
});

// Changelog page
Route::get('/changelog', function () {
    return view('changelog');
});

// Tools
Route::get('/tools/clipforge', function () {
    return view('tools.clipforge');
});

// Alternative comparison pages
Route::get('/alternative/loom', function () {
    return view('alternative.loom');
});

Route::get('/alternative/cap', function () {
    return view('alternative.cap');
});

// Blog routes (markdown file-driven from storage/app/blog/*.md)
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [MarkdownBlogController::class, 'index'])->name('index');
    Route::get('/category/{category}', [MarkdownBlogController::class, 'byCategory'])->name('category');
    Route::get('/{slug}', [MarkdownBlogController::class, 'show'])->name('show');
});

// Public video sharing page (for social media crawlers - returns OG meta tags)
Route::get('/share/video/{token}', function ($token) {
    $video = Video::where('share_token', $token)->first();

    if (! $video || ! $video->isShareLinkValid()) {
        abort(404, 'Video not found or no longer available');
    }

    $media = $video->getFirstMedia('videos');

    return view('video.share', [
        'video' => $video,
        'videoUrl' => $media ? $media->getUrl() : null,
    ]);
});

// Embeddable video player (for og:video - like Loom's embed page)
Route::get('/embed/video/{token}', function ($token) {
    $video = Video::where('share_token', $token)->first();

    if (! $video || ! $video->isShareLinkValid()) {
        abort(404, 'Video not found or no longer available');
    }

    $media = $video->getFirstMedia('videos');

    return view('video.embed', [
        'video' => $video,
        'videoUrl' => $media ? $media->getUrl() : null,
        'hlsUrl' => $video->getHlsUrl(),
    ]);
});

// Email template preview (local only)
if (app()->environment('local')) {
    Route::get('/email-preview/jira-integration', function () {
        return view('emails.jira-integration', [
            'firstName' => 'Gurpreet',
        ]);
    });

    Route::get('/email-preview/product-update', function () {
        return view('emails.product-update', [
            'badgeText' => 'Product Update 2.0',
            'headline' => 'Filter noise.',
            'subheadline' => 'Find signal.',
            'description' => "We've completely rebuilt the filtering engine. Segment your recordings by duration, user activity, and sentiment in milliseconds—not seconds.",
            'features' => [
                ['title' => 'Instant Search', 'description' => 'Queries run 10x faster with our new indexing engine.'],
                ['title' => 'Saved Views', 'description' => 'Pin your favorite filter combinations to your sidebar.'],
            ],
            'ctaText' => 'Try Smart Filters',
            'ctaUrl' => config('app.url'),
            'showVisualCard' => true,
            'previewText' => 'New filtering engine is here!',
        ]);
    });
}
