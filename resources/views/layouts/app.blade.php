<!DOCTYPE html>
<html lang="en" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ScreenSense - Screen Recording Made Simple')</title>
    <meta name="description" content="@yield('meta_description', 'Free open source screen recording tool. The best Loom alternative for capturing your screen with audio. Record, share instantly - no account needed.')">
    <meta name="keywords" content="@yield('meta_keywords', 'screen recording, loom alternative, open source screen recording, open source loom alternative, free screen recorder, screen capture, video recording, share screen recordings')">
    <meta name="robots" content="index, follow">
    <meta name="author" content="ScreenSense">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', 'ScreenSense - Free Open Source Loom Alternative')">
    <meta property="og:description" content="@yield('og_description', 'Free open source screen recording tool. The best Loom alternative - record your screen, capture audio, and share instantly.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', url('/demo/website-preview.png'))">
    <meta property="og:image:alt" content="ScreenSense - Open Source Screen Recording Tool">
    <meta property="og:site_name" content="ScreenSense">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'ScreenSense - Free Open Source Loom Alternative')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Free open source screen recording tool. The best Loom alternative - record your screen, capture audio, and share instantly.')">
    <meta name="twitter:image" content="@yield('twitter_image', url('/demo/website-preview.png'))">
    <meta name="twitter:image:alt" content="ScreenSense - Open Source Screen Recording Tool">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }

        /* Utility classes */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
    </style>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74',
                            400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c',
                            800: '#9a3412', 900: '#7c2d12', 950: '#0a0a0a',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    @stack('styles')
</head>
<body class="bg-brand-950 text-neutral-300 antialiased selection:bg-brand-500/30 selection:text-brand-100 flex flex-col min-h-screen overflow-x-hidden">

    @include('partials.navbar')

    <!-- Main Content -->
    <main class="relative flex-grow pt-16 overflow-x-hidden">
        @yield('content')
    </main>

    @include('partials.footer')

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Fetch GitHub stars
        fetch('https://api.github.com/repos/gurpreetkaits/screensense')
            .then(response => response.json())
            .then(data => {
                const stars = data.stargazers_count || 0;
                const navEl = document.getElementById('github-stars-nav');
                if (navEl) navEl.textContent = stars;
                const heroEl = document.getElementById('github-stars');
                if (heroEl) heroEl.textContent = stars;
            })
            .catch(() => {
                const navEl = document.getElementById('github-stars-nav');
                if (navEl) navEl.textContent = '0';
                const heroEl = document.getElementById('github-stars');
                if (heroEl) heroEl.textContent = '0';
            });
    </script>

    @stack('scripts')
</body>
</html>
