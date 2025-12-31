<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->title }} - ScreenSense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
            background: #000;
            overflow: hidden;
        }
        .video-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        video {
            max-width: 100%;
            max-height: 100%;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .branding {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 12px;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .branding:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="video-container">
        @if($hlsUrl)
        <video
            id="player"
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
                const hls = new Hls();
                hls.loadSource(hlsUrl);
                hls.attachMedia(video);
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = hlsUrl;
            }
        </script>
        @else
        <video
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
    <a href="{{ $video->getFrontendShareUrl() }}" target="_blank" class="branding">
        Watch on ScreenSense
    </a>
</body>
</html>
