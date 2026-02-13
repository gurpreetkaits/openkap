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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'DM Sans', sans-serif; }
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
                        sans: ['DM Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    @stack('styles')

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-BV8921YS6Q"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-BV8921YS6Q');
    </script>

    <!-- PostHog Analytics - Lightweight -->
    @if(config('services.posthog.key'))
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);

        posthog.init('{{ config('services.posthog.key') }}', {
            api_host: '{{ config('services.posthog.host', 'https://us.i.posthog.com') }}',
            person_profiles: 'anonymous',
            autocapture: true,
            capture_pageview: true,
            capture_pageleave: true,
            disable_session_recording: true,
            enable_heatmaps: false,
        });

        // Track JS errors only
        window.addEventListener('error', function(e) {
            posthog.capture('js_error', { message: e.message, source: e.filename, line: e.lineno });
        });
    </script>
    @endif
</head>
<body class="@yield('body_class', 'bg-brand-950 text-neutral-300 selection:bg-brand-500/30 selection:text-brand-100') antialiased flex flex-col min-h-screen overflow-x-hidden">

    @include('partials.navbar', ['lightMode' => View::hasSection('light_mode')])

    <!-- Main Content -->
    <main class="relative flex-grow pt-16 overflow-x-hidden">
        @yield('content')
    </main>

    @include('partials.footer', ['lightMode' => View::hasSection('light_mode')])

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Fetch GitHub stars
        fetch('https://api.github.com/repos/gurpreetkaits/screensense')
            .then(response => response.json())
            .then(data => {
                const stars = data.stargazers_count || 0;
                document.querySelectorAll('.js-github-stars').forEach(el => el.textContent = stars);
            })
            .catch(() => {
                document.querySelectorAll('.js-github-stars').forEach(el => el.textContent = '0');
            });
    </script>

    @stack('scripts')
</body>
</html>
