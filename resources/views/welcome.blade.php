@extends('layouts.app')

@section('title', 'ScreenSense - Affordable Loom Alternative for Individuals and Small Teams')
@section('meta_description', 'ScreenSense is async video messaging for remote teams. Record your screen, share a link, and communicate without scheduling meetings. Free to start.')
@section('meta_keywords', 'screen recording software, loom alternative, async video, screen recorder, video messaging, remote team communication, screen capture, video sharing')

@section('og_title', 'ScreenSense - Affordable Loom Alternative for Individuals and Small Teams')
@section('og_description', 'Record your screen, share a link, skip the meeting. Free screen recording software for teams.')

@section('body_class', 'bg-white text-gray-900 selection:bg-orange-100 selection:text-orange-700')
@section('light_mode', true)

@push('styles')
<style>
    .feature-card {
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .feature-card:hover {
        transform: translateY(-2px);
        border-color: rgba(0, 0, 0, 0.1);
    }
    .pricing-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .pricing-card:hover {
        transform: translateY(-4px);
    }
    .btn-primary {
        transition: all 0.2s ease;
    }
    .btn-primary:hover {
        box-shadow: 0 8px 30px -5px rgba(249, 115, 22, 0.35);
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }
    .screenshot-float {
        animation: float 6s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script type="application/ld+json">
@verbatim
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "ScreenSense",
    "applicationCategory": "MultimediaApplication",
    "operatingSystem": "Web Browser",
    "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    },
    "description": "Screen recording software for async video messaging. Record your screen with audio and share instantly.",
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "150"
    }
}
@endverbatim
</script>
@endpush

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden">
        {{-- Background layers --}}
        <div class="absolute inset-0 bg-gradient-to-b from-orange-50/60 via-white to-white"></div>
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0 / 0.04) 1px, transparent 0); background-size: 32px 32px;"></div>

        {{-- Decorative gradient orb --}}
        <div class="absolute top-0 left-1/4 -translate-y-1/3 w-[600px] h-[400px] bg-gradient-to-br from-orange-200/40 to-amber-100/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-6 pt-20 pb-16 md:pt-28 md:pb-24">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">

                {{-- LEFT: Text Content --}}
                <div class="flex-1 text-center lg:text-left max-w-xl">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-50 border border-orange-200/60 text-orange-600 text-sm font-medium mb-8">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                        </span>
                        Say it with a video, not another meeting
                    </div>

                    {{-- Main headline --}}
                    <h1 class="text-4xl md:text-5xl lg:text-[3.25rem] font-bold text-gray-900 leading-[1.1] tracking-tight mb-6">
                        Affordable Loom Alternative for Individuals
                    </h1>

                    {{-- Subheadline --}}
                    <p class="text-lg text-gray-500 mb-10 leading-[1.7]">
                        Record your screen and camera, get a shareable link. Everything as Loom but better at pricing and continue to evolve more in features.
                    </p>

                    {{-- CTA buttons --}}
                    <div class="flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-3 mb-6">
                        <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary w-full sm:w-auto inline-flex items-center justify-center h-12 px-8 rounded-xl bg-orange-600 text-white font-semibold hover:bg-orange-500 shadow-lg shadow-orange-600/25 transition-all">
                            Record Now
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center h-12 px-8 rounded-xl bg-gray-900 text-white font-medium hover:bg-gray-800 transition-colors">
                            <svg class="size-5 mr-2" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.21 0 4.831 1.757 2.632 4.501l3.953 6.848A5.454 5.454 0 0 1 12 6.545h10.691A12 12 0 0 0 12 0zM1.931 5.47A11.943 11.943 0 0 0 0 12c0 6.012 4.42 10.991 10.189 11.864l3.953-6.847a5.45 5.45 0 0 1-6.865-2.29zm13.342 2.166a5.446 5.446 0 0 1 1.819 7.533l-3.954 6.847c.538.054 1.085.084 1.638.084C20.09 22.1 24 17.627 24 12.203V12c0-.338-.014-.672-.041-1.003zM12 16.364a4.364 4.364 0 1 0 0-8.728 4.364 4.364 0 0 0 0 8.728z"/></svg>
                            Add to Chrome
                        </a>
                    </div>

                </div>

                {{-- RIGHT: App Preview Video --}}
                <div class="flex-1 w-full max-w-2xl lg:max-w-none relative">
                    {{-- Glow behind video --}}
                    <div class="absolute -inset-4 bg-gradient-to-br from-orange-200/20 via-orange-100/10 to-transparent rounded-3xl blur-2xl pointer-events-none"></div>

                    <div class="relative rounded-2xl overflow-hidden border border-gray-200/80 shadow-2xl shadow-gray-900/10 ring-1 ring-gray-900/5 bg-gray-900">
                        <video
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            class="w-full h-auto block"
                        >
                            <source src="{{ asset('app-preview-screensense.mp4') }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Trust Bar: Launch Badges + Stats --}}
    <section class="py-12 md:py-14 bg-gray-50/60 border-y border-gray-100">
        <div class="max-w-5xl mx-auto px-6">
            {{-- Launch Badges --}}
            <div class="flex flex-wrap justify-center items-center gap-5 mb-10">
                <a href="https://www.producthunt.com/products/screensense-2?embed=true&utm_source=badge-featured&utm_medium=badge&utm_campaign=badge-screensense-2" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity">
                    <img src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1058090&theme=light&t=1767617780370" alt="ScreenSense on Product Hunt" width="250" height="54" loading="lazy">
                </a>
                <a href="https://peerlist.io/gurpreet/project/screensense--open-source-screen-recording" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity">
                    <img src="https://peerlist.io/api/v1/projects/embed/PRJH9OBGM78Q7867EC6ADDNQGK799G?showUpvote=false&theme=light" alt="ScreenSense on Peerlist" style="width: auto; height: 54px;" loading="lazy">
                </a>
            </div>

        </div>
    </section>

    {{-- How it Works Section --}}
    <section id="how-it-works" class="py-20 md:py-28">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">How it works</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Three steps. Zero meetings.</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto leading-[1.7]">Replace your next unnecessary meeting with an async video</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                {{-- Step 1 --}}
                <div class="relative p-8 rounded-2xl bg-gradient-to-b from-orange-50/80 to-white border border-orange-100/60 group hover:shadow-lg hover:shadow-orange-100/40 transition-all duration-300">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-orange-600 text-white flex items-center justify-center font-bold text-sm shadow-lg shadow-orange-600/20">1</div>
                        <div class="hidden md:block flex-1 h-px bg-gradient-to-r from-orange-200 to-transparent"></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center mb-4">
                        <i data-lucide="video" class="size-6 text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Record your screen</h3>
                    <p class="text-gray-500 leading-[1.7]">Click record, select a window or your whole screen. Add your microphone to explain what you're showing.</p>
                </div>

                {{-- Step 2 --}}
                <div class="relative p-8 rounded-2xl bg-gradient-to-b from-blue-50/80 to-white border border-blue-100/60 group hover:shadow-lg hover:shadow-blue-100/40 transition-all duration-300">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-lg shadow-blue-600/20">2</div>
                        <div class="hidden md:block flex-1 h-px bg-gradient-to-r from-blue-200 to-transparent"></div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mb-4">
                        <i data-lucide="link" class="size-6 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Get a shareable link</h3>
                    <p class="text-gray-500 leading-[1.7]">The moment you stop recording, your link is ready. Paste it anywhere — Slack, email, Notion, Linear.</p>
                </div>

                {{-- Step 3 --}}
                <div class="relative p-8 rounded-2xl bg-gradient-to-b from-emerald-50/80 to-white border border-emerald-100/60 group hover:shadow-lg hover:shadow-emerald-100/40 transition-all duration-300">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-lg shadow-emerald-600/20">3</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4">
                        <i data-lucide="play-circle" class="size-6 text-emerald-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">They watch when ready</h3>
                    <p class="text-gray-500 leading-[1.7]">No account needed. Plays instantly in any browser. They watch at 1.5x, skip ahead, or replay the tricky parts.</p>
                </div>
            </div>

            {{-- Demo GIF section --}}
            <div class="mt-20 grid md:grid-cols-2 gap-6">
                {{-- Record a Video --}}
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                            <i data-lucide="video" class="size-5"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Record a Video</div>
                            <p class="text-sm text-gray-500">Select source, hit record, explain your point</p>
                        </div>
                    </div>
                    <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 shadow-lg shadow-gray-200/50">
                        <img src="/demo/how-to-record-screen.gif" alt="How to record your screen with ScreenSense" class="w-full h-auto">
                    </div>
                </div>

                {{-- Sharing With Your Friends --}}
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                            <i data-lucide="share" class="size-5"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Sharing With Your Friends</div>
                            <p class="text-sm text-gray-500">Copy link, paste anywhere, done</p>
                        </div>
                    </div>
                    <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 shadow-lg shadow-gray-200/50">
                        <img src="/demo/copy-share-link.gif" alt="How to share your screen recording" class="w-full h-auto">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section - Bento Grid --}}
    <section id="features" class="py-20 md:py-28 bg-gray-50/60 border-y border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Features</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Everything you need, nothing you don't</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto leading-[1.7]">No bloated feature list. Just the essentials, done well.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- Feature 1 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center mb-4">
                        <i data-lucide="zap" class="size-5 text-orange-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Instant links</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">Your video link is ready the moment you stop recording. Share it before you even close the tab.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                        <i data-lucide="mic" class="size-5 text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Screen + audio</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">Record your screen with microphone audio. Show and tell — way clearer than screenshots.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
                        <i data-lucide="user-x" class="size-5 text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No viewer signup</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">Anyone with the link can watch. No login walls, no "create an account" nonsense.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center mb-4">
                        <i data-lucide="monitor" class="size-5 text-violet-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">HD recording</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">1080p capture so every detail is visible. Code, designs, spreadsheets — crystal clear.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-sky-50 flex items-center justify-center mb-4">
                        <i data-lucide="play" class="size-5 text-sky-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Fast playback</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">HLS adaptive streaming means videos start playing immediately, even on slow connections.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-4">
                        <i data-lucide="sparkles" class="size-5 text-amber-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No watermarks</h3>
                    <p class="text-gray-500 text-sm leading-[1.7]">Your videos look professional. No logos stamped on your content, even on the free plan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <section id="pricing" class="py-20 md:py-28 border-t border-gray-100">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Pricing</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Simple, transparent pricing</h2>
                <p class="text-gray-500 text-lg leading-[1.7]">Start free. Upgrade when your team grows.</p>
            </div>

            {{-- Single Card with Plan Switcher --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6" id="pricing-card">
                {{-- Header + Switch --}}
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-semibold text-gray-900">Choose Your Plan</h3>
                    <div class="flex items-center gap-0.5 bg-gray-100 rounded-lg p-0.5">
                        <button onclick="switchPlan('free')" id="plan-btn-free" class="px-3 py-1.5 text-xs font-medium rounded-md transition-all text-gray-500 hover:text-gray-700">Free</button>
                        <button onclick="switchPlan('pro')" id="plan-btn-pro" class="px-3 py-1.5 text-xs font-medium rounded-md transition-all bg-white text-gray-900 shadow-sm">Pro</button>
                    </div>
                </div>

                {{-- Plan Title + Price (centered) --}}
                <div class="text-center py-6">
                    {{-- Free --}}
                    <div id="plan-free" class="hidden">
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Free</h4>
                        <p class="text-xs text-gray-400 mb-4">For getting started</p>
                        <div>
                            <span class="text-4xl font-bold text-gray-900">$0</span>
                            <span class="text-gray-400 text-sm">/mo</span>
                        </div>
                    </div>

                    {{-- Pro --}}
                    <div id="plan-pro" class="">
                        <h4 class="text-lg font-bold text-gray-900 mb-1">Pro</h4>
                        <p class="text-xs text-gray-400 mb-3">For individuals & creators</p>
                        {{-- Billing cycle toggle --}}
                        <div class="flex items-center justify-center mb-4">
                            <div class="flex items-center gap-0.5 bg-gray-100 rounded-md p-0.5">
                                <button onclick="switchBilling('monthly')" id="billing-btn-monthly" class="px-2.5 py-1 text-[11px] font-medium rounded transition-all bg-white text-gray-900 shadow-sm">Monthly</button>
                                <button onclick="switchBilling('yearly')" id="billing-btn-yearly" class="px-2.5 py-1 text-[11px] font-medium rounded transition-all text-gray-500 hover:text-gray-700">Yearly</button>
                            </div>
                        </div>
                        <div id="price-monthly" class="">
                            <span class="text-4xl font-bold text-gray-900">$8</span>
                            <span class="text-gray-400 text-sm">/mo</span>
                        </div>
                        <div id="price-yearly" class="hidden">
                            <span class="text-4xl font-bold text-gray-900">$80</span>
                            <span class="text-gray-400 text-sm">/yr</span>
                            <div class="mt-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-medium">Save 17%</span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Benefits Grid --}}
                <div class="py-5 border-b border-gray-100">
                    {{-- Free benefits --}}
                    <ul id="benefits-free" class="hidden grid grid-cols-3 gap-x-6 gap-y-3 text-sm text-gray-700">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            5 videos
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            5 min per recording
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            No watermarks
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Shareable links
                        </li>
                    </ul>

                    {{-- Pro benefits --}}
                    <ul id="benefits-pro" class="grid grid-cols-3 gap-x-6 gap-y-3 text-sm text-gray-700">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Unlimited videos
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Unlimited recording
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            HLS streaming
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Priority support
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            No watermarks
                        </li>
                    </ul>

                    {{-- Team benefits --}}
                </div>

                {{-- Action Button --}}
                <div class="pt-5 flex justify-center">
                    <a id="cta-free" href="{{ config('app.frontend_url', config('app.url')) }}/login" class="hidden w-full max-w-[280px] py-2.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors text-center">
                        Get started free
                    </a>
                    <a id="cta-pro" href="{{ config('app.frontend_url', config('app.url')) }}/login" class="flex w-full max-w-[280px] py-2.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-xs font-medium text-white transition-all shadow-sm text-center items-center justify-center gap-1.5">
                        Upgrade to Pro
                        <svg class="w-3.5 h-3.5 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            <p class="text-center text-gray-400 text-sm mt-10">
                Need more seats or custom features? <a href="mailto:contact@gurpreetkait.in" class="text-gray-900 font-medium hover:text-orange-600 transition-colors underline decoration-gray-300 hover:decoration-orange-300">Talk to us</a>
            </p>
        </div>
    </section>

    {{-- Testimonials Section --}}
    <section class="py-20 md:py-28">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Testimonials</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Loved by developers and teams</h2>
                <p class="text-gray-500 text-lg leading-[1.7]">Honest feedback from people who switched</p>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                {{-- Testimonial 1 --}}
                <div class="p-7 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="flex items-center gap-1 mb-4">
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                    </div>
                    <p class="text-gray-600 text-sm leading-[1.7] mb-5">"Finally, a screen recorder that doesn't nag me to upgrade every 5 seconds. It just works. I replaced Loom for my entire dev team."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-xs font-bold text-white">SK</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Sanjay K.</p>
                            <p class="text-xs text-gray-400">Full-stack Developer</p>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 2 --}}
                <div class="p-7 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="flex items-center gap-1 mb-4">
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                    </div>
                    <p class="text-gray-600 text-sm leading-[1.7] mb-5">"I send 3-4 quick videos to clients daily. Saves me hours of back-and-forth emails. The instant link sharing is a game changer."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-xs font-bold text-white">MR</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Maria R.</p>
                            <p class="text-xs text-gray-400">Freelance Designer</p>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 3 --}}
                <div class="p-7 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="flex items-center gap-1 mb-4">
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                        <i data-lucide="star" class="size-4 text-amber-400 fill-amber-400"></i>
                    </div>
                    <p class="text-gray-600 text-sm leading-[1.7] mb-5">"Simple pricing, no bloat. My whole team switched over in a day. Way better than paying Loom's enterprise tax."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-400 to-violet-600 flex items-center justify-center text-xs font-bold text-white">AT</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Alex T.</p>
                            <p class="text-xs text-gray-400">Startup Founder</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Open Source + Security Trust Strip --}}
    <section class="py-14 md:py-16 bg-gray-50/60 border-y border-gray-100">
        <div class="max-w-5xl mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                        <i data-lucide="github" class="size-5 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">Fully open source</h4>
                        <p class="text-xs text-gray-500 leading-[1.7]">Inspect every line of code. No hidden trackers, no black boxes. MIT licensed.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-check" class="size-5 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">Your data stays yours</h4>
                        <p class="text-xs text-gray-500 leading-[1.7]">Videos are encrypted in transit. No selling your data. Delete anytime, it's actually gone.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                        <i data-lucide="lock" class="size-5 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">No vendor lock-in</h4>
                        <p class="text-xs text-gray-500 leading-[1.7]">Export your videos anytime. Self-host if you want. You're never trapped.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="py-20 md:py-28">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">FAQ</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Frequently asked questions</h2>
            </div>

            <div class="space-y-3" id="faq-accordion">
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">Is ScreenSense really free?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-[1.7] text-sm">Yes. The free plan includes 5 recordings with no watermarks. After that, Pro is $8/month for unlimited videos. We don't do the "free trial then surprise you with a paywall" thing.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">Do people need an account to watch my videos?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-[1.7] text-sm">No. Anyone with the link can watch immediately in their browser. No signup, no app download, no friction.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">How long can my recordings be?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-[1.7] text-sm">Free plan: 5 minutes per video. Pro and Team: no limit. Most async updates are under 3 minutes anyway.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">What browsers are supported?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-[1.7] text-sm">Chrome, Firefox, Safari, and Edge. We also have a <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="text-orange-600 font-medium hover:text-orange-500 underline decoration-orange-200">Chrome extension</a> for one-click recording.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">How is this different from Loom?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-[1.7] text-sm">Simpler pricing, no aggressive upsells, and a free tier that actually lets you use the product. We're focused on doing one thing well: helping you share quick video explanations with your team.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-20 md:py-28 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-white via-orange-50/40 to-orange-50/80"></div>
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgb(249 115 22 / 0.06) 1px, transparent 0); background-size: 24px 24px;"></div>
        {{-- Decorative blobs --}}
        <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-orange-200/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/4 w-56 h-56 bg-amber-200/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative max-w-2xl mx-auto px-6 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-100 border border-orange-200 text-orange-700 text-xs font-semibold mb-6">
                <i data-lucide="sparkles" class="size-3"></i>
                Free to get started
            </div>
            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">Ready to skip your<br class="hidden sm:block"> next meeting?</h2>
            <p class="text-lg text-gray-500 mb-10 max-w-lg mx-auto leading-[1.7]">Record your first video in under a minute. Free, no credit card required.</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary inline-flex items-center justify-center h-14 px-10 rounded-xl bg-orange-600 text-white text-lg font-semibold hover:bg-orange-500 shadow-xl shadow-orange-600/25 transition-all">
                    Start recording
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center h-14 px-8 rounded-xl border border-gray-200 bg-white text-gray-700 font-medium hover:bg-gray-50 hover:border-gray-300 transition-all">
                    <svg class="size-5 mr-2 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.21 0 4.831 1.757 2.632 4.501l3.953 6.848A5.454 5.454 0 0 1 12 6.545h10.691A12 12 0 0 0 12 0zM1.931 5.47A11.943 11.943 0 0 0 0 12c0 6.012 4.42 10.991 10.189 11.864l3.953-6.847a5.45 5.45 0 0 1-6.865-2.29zm13.342 2.166a5.446 5.446 0 0 1 1.819 7.533l-3.954 6.847c.538.054 1.085.084 1.638.084C20.09 22.1 24 17.627 24 12.203V12c0-.338-.014-.672-.041-1.003zM12 16.364a4.364 4.364 0 1 0 0-8.728 4.364 4.364 0 0 0 0 8.728z"/></svg>
                    Add to Chrome
                </a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        const content = item.querySelector('.faq-content');
        const icon = item.querySelector('.faq-icon');
        const isOpen = !content.classList.contains('hidden');

        // Close all
        document.querySelectorAll('.faq-item').forEach(function(el) {
            el.querySelector('.faq-content').classList.add('hidden');
            el.querySelector('.faq-icon').style.transform = '';
            el.classList.remove('border-orange-200', 'bg-orange-50/30');
            el.classList.add('border-gray-200', 'bg-white');
        });

        // Open clicked (if it was closed)
        if (!isOpen) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(45deg)';
            item.classList.remove('border-gray-200', 'bg-white');
            item.classList.add('border-orange-200', 'bg-orange-50/30');
        }

        lucide.createIcons();
    }

    function switchPlan(plan) {
        var plans = ['free', 'pro'];
        plans.forEach(function(p) {
            document.getElementById('plan-' + p).classList.add('hidden');
            document.getElementById('benefits-' + p).classList.add('hidden');
            document.getElementById('cta-' + p).classList.add('hidden');
            document.getElementById('cta-' + p).classList.remove('flex');
            var btn = document.getElementById('plan-btn-' + p);
            btn.className = 'px-3 py-1.5 text-xs font-medium rounded-md transition-all text-gray-500 hover:text-gray-700';
        });
        document.getElementById('plan-' + plan).classList.remove('hidden');
        document.getElementById('benefits-' + plan).classList.remove('hidden');
        document.getElementById('cta-' + plan).classList.remove('hidden');
        if (plan !== 'free') document.getElementById('cta-' + plan).classList.add('flex');
        document.getElementById('plan-btn-' + plan).className = 'px-3 py-1.5 text-xs font-medium rounded-md transition-all bg-white text-gray-900 shadow-sm';
    }

    function switchBilling(cycle) {
        var monthly = document.getElementById('price-monthly');
        var yearly = document.getElementById('price-yearly');
        var btnMonthly = document.getElementById('billing-btn-monthly');
        var btnYearly = document.getElementById('billing-btn-yearly');
        var active = 'px-2.5 py-1 text-[11px] font-medium rounded transition-all bg-white text-gray-900 shadow-sm';
        var inactive = 'px-2.5 py-1 text-[11px] font-medium rounded transition-all text-gray-500 hover:text-gray-700';
        if (cycle === 'yearly') {
            monthly.classList.add('hidden');
            yearly.classList.remove('hidden');
            btnYearly.className = active;
            btnMonthly.className = inactive;
        } else {
            yearly.classList.add('hidden');
            monthly.classList.remove('hidden');
            btnMonthly.className = active;
            btnYearly.className = inactive;
        }
    }

    function showDemo(type) {
        const recordBtn = document.getElementById('demo-btn-record');
        const shareBtn = document.getElementById('demo-btn-share');
        const recordDemo = document.getElementById('demo-record');
        const shareDemo = document.getElementById('demo-share');

        const activeClasses = 'w-full text-left p-5 rounded-xl border border-orange-200 bg-orange-50/50 transition-all';
        const inactiveClasses = 'w-full text-left p-5 rounded-xl border border-gray-200 hover:border-gray-300 transition-all';

        if (type === 'record') {
            recordBtn.className = activeClasses;
            shareBtn.className = inactiveClasses;
            recordBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600';
            shareBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400';
            recordDemo.classList.remove('hidden');
            shareDemo.classList.add('hidden');
        } else {
            shareBtn.className = activeClasses;
            recordBtn.className = inactiveClasses;
            shareBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600';
            recordBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400';
            shareDemo.classList.remove('hidden');
            recordDemo.classList.add('hidden');
        }
    }
</script>
@endpush
