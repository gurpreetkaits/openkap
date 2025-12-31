<?php

use App\Http\Controllers\BlogController;
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

// Blog routes (SEO-friendly server-rendered pages)
Route::get('/blog', [BlogController::class, 'webIndex'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'webShow'])->name('blog.show');

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
