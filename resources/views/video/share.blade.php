@php
    // Use thumbnail for social previews (fallback for platforms that don't support video)
    $thumbnailUrl = $video->getThumbnailUrl();
    if ($thumbnailUrl && !str_starts_with($thumbnailUrl, 'http')) {
        $thumbnailUrl = url($thumbnailUrl);
    }

    // Embed URL for og:video (HTML page with video player - like Loom)
    $embedUrl = $video->getEmbedUrl();

    // Canonical URL for OG tags (backend URL - this page)
    $shareUrl = $video->getShareUrl();

    // Frontend URL for JS redirect (where users actually watch videos)
    $frontendUrl = $video->getFrontendShareUrl();

    $description = $video->description ?: 'Watch this screen recording on OpenKap';
    $duration = $video->duration ?? 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->title }} - OpenKap</title>
    <meta name="description" content="{{ $description }}">

    <!-- Open Graph / Facebook / LinkedIn / Discord / Slack -->
    <meta property="og:title" content="{{ $video->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:site_name" content="OpenKap">

    <!-- OG Image (fallback for platforms that don't support video embeds) -->
    @if($thumbnailUrl)
    <meta property="og:image" content="{{ $thumbnailUrl }}">
    <meta property="og:image:width" content="1280">
    <meta property="og:image:height" content="720">
    <meta property="og:image:alt" content="{{ $video->title }}">
    @endif

    <!-- OG Video (HTML embed page - like Loom) -->
    <meta property="og:video" content="{{ $embedUrl }}">
    <meta property="og:video:secure_url" content="{{ $embedUrl }}">
    <meta property="og:video:type" content="text/html">
    <meta property="og:video:width" content="1280">
    <meta property="og:video:height" content="720">
    @if($duration > 0)
    <meta property="video:duration" content="{{ $duration }}">
    @endif

    <!-- Twitter Player Card (embeddable video player) -->
    <meta name="twitter:card" content="player">
    <meta name="twitter:title" content="{{ $video->title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($thumbnailUrl)
    <meta name="twitter:image" content="{{ $thumbnailUrl }}">
    @endif
    <meta name="twitter:player" content="{{ $embedUrl }}">
    <meta name="twitter:player:width" content="1280">
    <meta name="twitter:player:height" content="720">

    <!-- Additional meta for iMessage / Apple -->
    <meta name="apple-mobile-web-app-title" content="OpenKap">
    @if($thumbnailUrl)
    <link rel="image_src" href="{{ $thumbnailUrl }}">
    @endif

    <!-- Redirect to frontend for full-featured player -->
    <script>
        // Redirect to the Vue frontend for the full video player experience
        // Social media crawlers will get the meta tags above before JS runs
        window.location.replace(@json($frontendUrl));
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ $frontendUrl }}">
    </noscript>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Fallback content shown briefly before redirect, or if JS disabled -->
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="text-center text-white">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-900 border-t-transparent mx-auto mb-4"></div>
            <p class="text-gray-400">Redirecting to video player...</p>
            <p class="text-gray-500 text-sm mt-2">
                If you are not redirected, <a href="{{ $frontendUrl }}" class="text-orange-500 hover:text-orange-400">click here</a>
            </p>
        </div>
    </div>
</body>
</html>
