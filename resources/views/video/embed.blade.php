<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->title }} - OpenKap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }

        /* Custom video controls styling */
        video::-webkit-media-controls-panel { background: linear-gradient(transparent, rgba(0,0,0,0.7)); }
        video::-webkit-media-controls-play-button { filter: invert(1); }
        video::-webkit-media-controls-timeline { filter: hue-rotate(180deg); }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Main Container -->
    <div class="w-full h-full flex flex-col">
        <!-- Header Bar -->
        <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <a href="{{ $video->getFrontendShareUrl() }}" target="_blank" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                    <div class="w-7 h-7 bg-gradient-to-br from-gray-800 to-gray-900 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900 text-sm">OpenKap</span>
                </a>
                <div class="w-px h-5 bg-gray-200"></div>
                <span class="text-gray-700 text-sm font-medium truncate max-w-[200px] md:max-w-[400px]">{{ $video->title }}</span>
            </div>
            <a
                href="{{ $video->getFrontendShareUrl() }}"
                target="_blank"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-medium transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Open
            </a>
        </div>

        <!-- Video Container -->
        <div class="flex-1 bg-black flex items-center justify-center overflow-hidden">
            @if($hlsUrl)
            <video
                id="player"
                class="w-full h-full object-contain"
                controls
                autoplay
                playsinline
                poster="{{ $video->getThumbnailUrl() }}"
            >
                <source src="{{ $hlsUrl }}" type="application/x-mpegURL">
                @if($videoUrl)
                <source src="{{ $videoUrl }}" type="video/webm">
                @endif
            </video>
            <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
            <script>
                const video = document.getElementById('player');
                const hlsUrl = @json($hlsUrl);
                if (Hls.isSupported()) {
                    const hls = new Hls({
                        enableWorker: true,
                        lowLatencyMode: false,
                    });
                    hls.loadSource(hlsUrl);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, () => {
                        // Set to highest quality
                        if (hls.levels.length > 0) {
                            hls.currentLevel = hls.levels.length - 1;
                        }
                    });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = hlsUrl;
                }
            </script>
            @else
            <video
                class="w-full h-full object-contain"
                controls
                autoplay
                playsinline
                poster="{{ $video->getThumbnailUrl() }}"
            >
                <source src="{{ $videoUrl }}" type="video/webm">
                Your browser does not support the video tag.
            </video>
            @endif
        </div>

        <!-- Footer Bar -->
        <div class="bg-white border-t border-gray-200 px-4 py-2 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-4 text-xs text-gray-500">
                @if($video->duration)
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ gmdate($video->duration >= 3600 ? 'H:i:s' : 'i:s', $video->duration) }}
                </span>
                @endif
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ $video->views_count ?? 0 }} views
                </span>
            </div>
            <a
                href="{{ $video->getFrontendShareUrl() }}"
                target="_blank"
                class="text-xs text-gray-500 hover:text-gray-900 transition-colors"
            >
                Powered by OpenKap
            </a>
        </div>
    </div>
</body>
</html>
