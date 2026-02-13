@extends('layouts.app')

@section('title', 'ScreenSense - Screen Recording Software | Loom Alternative')
@section('meta_description', 'ScreenSense is async video messaging for remote teams. Record your screen, share a link, and communicate without scheduling meetings. Free to start.')
@section('meta_keywords', 'screen recording software, loom alternative, async video, screen recorder, video messaging, remote team communication, screen capture, video sharing')

@section('og_title', 'ScreenSense - Async Video for Remote Teams')
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
                        Async video messaging for teams
                    </div>

                    {{-- Main headline --}}
                    <h1 class="text-4xl md:text-5xl lg:text-[3.25rem] font-bold text-gray-900 leading-[1.1] tracking-tight mb-6">
                        Say it with a video,<br>
                        <span class="text-gray-300 line-through decoration-gray-300/60">not another meeting</span>
                    </h1>

                    {{-- Subheadline --}}
                    <p class="text-lg text-gray-500 mb-10 leading-relaxed">
                        Record your screen and camera, get a shareable link in seconds. Your team watches on their own time. No scheduling, no calendar tetris.
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

                    <p class="text-sm text-gray-400">
                        Free forever for individuals &middot; No credit card required
                    </p>
                </div>

                {{-- RIGHT: Dummy App UI --}}
                <div class="flex-1 w-full max-w-2xl lg:max-w-none relative">
                    {{-- Glow behind UI --}}
                    <div class="absolute -inset-4 bg-gradient-to-br from-orange-200/20 via-orange-100/10 to-transparent rounded-3xl blur-2xl pointer-events-none"></div>

                    <div class="relative rounded-2xl overflow-hidden border border-gray-200/80 shadow-2xl shadow-gray-900/10 ring-1 ring-gray-900/5 bg-white">

                        {{-- Browser Chrome --}}
                        <div class="h-8 bg-gray-50 border-b border-gray-200/80 flex items-center px-3 gap-2">
                            <div class="flex gap-1">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#FF5F57]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#FEBC2E]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#28C840]"></div>
                            </div>
                            <div class="flex-1 flex justify-center">
                                <div class="px-3 py-0.5 rounded bg-white border border-gray-200 text-[10px] text-gray-400 flex items-center gap-1">
                                    <i data-lucide="lock" class="size-2.5"></i>
                                    app.screensense.in
                                </div>
                            </div>
                            <div class="w-10"></div>
                        </div>

                        {{-- App Layout --}}
                        <div class="flex h-[340px] md:h-[400px]">

                            {{-- Sidebar --}}
                            <div class="w-12 md:w-44 bg-gray-50/80 border-r border-gray-200/60 flex flex-col py-3 shrink-0">
                                {{-- Logo --}}
                                <div class="px-3 mb-4 hidden md:flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-orange-600 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-[11px] font-semibold text-gray-900">ScreenSense</span>
                                </div>
                                {{-- Nav Items --}}
                                <div class="space-y-0.5 px-2">
                                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-md bg-orange-50 text-orange-600">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <span class="text-[11px] font-medium hidden md:block">My Videos</span>
                                    </div>
                                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-400 hover:bg-gray-100">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        <span class="text-[11px] font-medium hidden md:block">Dashboard</span>
                                    </div>
                                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-400 hover:bg-gray-100">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="text-[11px] font-medium hidden md:block">Shared with me</span>
                                    </div>
                                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-md text-gray-400 hover:bg-gray-100">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="text-[11px] font-medium hidden md:block">Settings</span>
                                    </div>
                                </div>
                                {{-- Record Button --}}
                                <div class="mt-auto px-2">
                                    <div class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg bg-orange-600 text-white cursor-default">
                                        <div class="w-2 h-2 rounded-full bg-white animate-pulse"></div>
                                        <span class="text-[10px] font-semibold hidden md:block">New Recording</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Main Content --}}
                            <div class="flex-1 flex flex-col overflow-hidden relative">
                                {{-- Top Bar --}}
                                <div class="h-10 border-b border-gray-100 flex items-center justify-between px-4 shrink-0">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-semibold text-gray-900">My Videos</span>
                                        <span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">12 videos</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-32 h-6 rounded-md border border-gray-200 bg-gray-50 flex items-center px-2">
                                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            <span class="text-[10px] text-gray-300 ml-1">Search...</span>
                                        </div>
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-[9px] font-bold">G</div>
                                    </div>
                                </div>

                                {{-- Video Grid --}}
                                <div class="flex-1 p-4 overflow-hidden">
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        {{-- Video Card 1 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">2:34</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">Sprint review walkthrough</p>
                                            <p class="text-[9px] text-gray-400">2 hours ago</p>
                                        </div>
                                        {{-- Video Card 2 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">4:12</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">Bug fix demo #482</p>
                                            <p class="text-[9px] text-gray-400">5 hours ago</p>
                                        </div>
                                        {{-- Video Card 3 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-amber-100 to-orange-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">1:45</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">API design proposal</p>
                                            <p class="text-[9px] text-gray-400">Yesterday</p>
                                        </div>
                                        {{-- Video Card 4 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-purple-100 to-pink-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">3:08</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">Onboarding flow update</p>
                                            <p class="text-[9px] text-gray-400">2 days ago</p>
                                        </div>
                                        {{-- Video Card 5 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-rose-100 to-red-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">0:58</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">Quick feedback for Sarah</p>
                                            <p class="text-[9px] text-gray-400">3 days ago</p>
                                        </div>
                                        {{-- Video Card 6 --}}
                                        <div class="group cursor-default">
                                            <div class="aspect-video rounded-lg bg-gradient-to-br from-cyan-100 to-blue-100 border border-gray-200/60 relative overflow-hidden">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <div class="w-6 h-6 rounded-full bg-white/90 shadow flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-700 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1 rounded">5:21</div>
                                            </div>
                                            <p class="text-[10px] font-medium text-gray-700 mt-1.5 truncate">Deployment pipeline setup</p>
                                            <p class="text-[9px] text-gray-400">Last week</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Recording Bottom Bar --}}
                                <div class="h-10 bg-gray-900 flex items-center justify-between px-4 shrink-0">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></div>
                                        <span class="text-[11px] font-medium text-white">Recording</span>
                                        <span class="text-[11px] font-mono text-red-400">00:32</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1 text-[10px] text-gray-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                            Mic on
                                        </div>
                                        <div class="w-px h-4 bg-gray-700"></div>
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button class="bg-red-600 text-white text-[10px] font-semibold px-3 py-1 rounded-md hover:bg-red-500">
                                            Stop
                                        </button>
                                    </div>
                                </div>

                                {{-- Recording Popup (floating) --}}
                                <div class="absolute top-14 right-4 w-48 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-10">
                                    <div class="p-3">
                                        <div class="flex items-center gap-2 mb-3">
                                            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                            <span class="text-[11px] font-semibold text-gray-900">Recording Screen</span>
                                        </div>
                                        {{-- Webcam bubble preview --}}
                                        <div class="w-full aspect-video rounded-lg bg-gradient-to-br from-gray-700 to-gray-900 mb-3 relative overflow-hidden">
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-sm font-bold">G</div>
                                            </div>
                                            <div class="absolute top-1.5 right-1.5 bg-red-500 text-white text-[7px] font-bold px-1 py-0.5 rounded flex items-center gap-0.5">
                                                <div class="w-1 h-1 rounded-full bg-white animate-pulse"></div>
                                                REC
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-[18px] font-mono font-semibold text-gray-900">0:32</span>
                                            <div class="flex items-center gap-1.5">
                                                <button class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </button>
                                                <button class="w-7 h-7 rounded-full bg-red-600 flex items-center justify-center text-white hover:bg-red-500">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Source indicator --}}
                                    <div class="border-t border-gray-100 px-3 py-2 bg-gray-50/50 flex items-center gap-2">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span class="text-[9px] text-gray-400">Entire Screen &middot; Mic on</span>
                                    </div>
                                </div>

                            </div>
                        </div>
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

            {{-- Trust Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <div class="text-center p-4 rounded-xl bg-white border border-gray-100">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <i data-lucide="github" class="size-4 text-gray-900"></i>
                        <span class="text-2xl font-bold text-gray-900 js-github-stars">-</span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium">GitHub Stars</p>
                </div>
                <div class="text-center p-4 rounded-xl bg-white border border-gray-100">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <i data-lucide="code-2" class="size-4 text-gray-900"></i>
                        <span class="text-2xl font-bold text-gray-900">100%</span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium">Open Source</p>
                </div>
                <div class="text-center p-4 rounded-xl bg-white border border-gray-100">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <i data-lucide="ban" class="size-4 text-gray-900"></i>
                        <span class="text-2xl font-bold text-gray-900">Zero</span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium">Watermarks</p>
                </div>
                <div class="text-center p-4 rounded-xl bg-white border border-gray-100">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <i data-lucide="credit-card" class="size-4 text-gray-900"></i>
                        <span class="text-2xl font-bold text-gray-900">$0</span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium">To Get Started</p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it Works Section --}}
    <section id="how-it-works" class="py-20 md:py-28">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">How it works</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Three steps. Zero meetings.</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">Replace your next unnecessary meeting with an async video</p>
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
                    <p class="text-gray-500 leading-relaxed">Click record, select a window or your whole screen. Add your microphone to explain what you're showing.</p>
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
                    <p class="text-gray-500 leading-relaxed">The moment you stop recording, your link is ready. Paste it anywhere — Slack, email, Notion, Linear.</p>
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
                    <p class="text-gray-500 leading-relaxed">No account needed. Plays instantly in any browser. They watch at 1.5x, skip ahead, or replay the tricky parts.</p>
                </div>
            </div>

            {{-- Demo GIF section --}}
            <div class="mt-20 grid lg:grid-cols-2 gap-8 items-start">
                <div class="space-y-4">
                    <button onclick="showDemo('record')" id="demo-btn-record" class="w-full text-left p-5 rounded-xl border border-orange-200 bg-orange-50/50 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                                <i data-lucide="video" class="size-5"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Recording a video</div>
                                <p class="text-sm text-gray-500">Select source, hit record, explain your point</p>
                            </div>
                        </div>
                    </button>

                    <button onclick="showDemo('share')" id="demo-btn-share" class="w-full text-left p-5 rounded-xl border border-gray-200 hover:border-gray-300 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                <i data-lucide="share" class="size-5"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Sharing with your team</div>
                                <p class="text-sm text-gray-500">Copy link, paste anywhere, done</p>
                            </div>
                        </div>
                    </button>
                </div>

                <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50 shadow-lg shadow-gray-200/50">
                    <div id="demo-record" class="block">
                        <img src="/demo/how-to-record-screen.gif" alt="How to record your screen with ScreenSense" class="w-full h-auto">
                    </div>
                    <div id="demo-share" class="hidden">
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
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">No bloated feature list. Just the essentials, done well.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- Feature 1 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center mb-4">
                        <i data-lucide="zap" class="size-5 text-orange-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Instant links</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Your video link is ready the moment you stop recording. Share it before you even close the tab.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                        <i data-lucide="mic" class="size-5 text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Screen + audio</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Record your screen with microphone audio. Show and tell — way clearer than screenshots.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
                        <i data-lucide="user-x" class="size-5 text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No viewer signup</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Anyone with the link can watch. No login walls, no "create an account" nonsense.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center mb-4">
                        <i data-lucide="monitor" class="size-5 text-violet-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">HD recording</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">1080p capture so every detail is visible. Code, designs, spreadsheets — crystal clear.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-sky-50 flex items-center justify-center mb-4">
                        <i data-lucide="play" class="size-5 text-sky-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Fast playback</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">HLS adaptive streaming means videos start playing immediately, even on slow connections.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="feature-card p-7 rounded-2xl border border-gray-200 bg-white group hover:border-gray-300 hover:shadow-lg hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-4">
                        <i data-lucide="sparkles" class="size-5 text-amber-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No watermarks</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Your videos look professional. No logos stamped on your content, even on the free plan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Chrome Extension Banner --}}
    <section class="py-14 md:py-20">
        <div class="max-w-4xl mx-auto px-6">
            <div class="relative flex flex-col md:flex-row items-center justify-between gap-8 p-8 md:p-10 rounded-2xl bg-gray-900 overflow-hidden">
                {{-- Background decoration --}}
                <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900"></div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-orange-600/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
                        <svg class="size-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.21 0 4.831 1.757 2.632 4.501l3.953 6.848A5.454 5.454 0 0 1 12 6.545h10.691A12 12 0 0 0 12 0zM1.931 5.47A11.943 11.943 0 0 0 0 12c0 6.012 4.42 10.991 10.189 11.864l3.953-6.847a5.45 5.45 0 0 1-6.865-2.29zm13.342 2.166a5.446 5.446 0 0 1 1.819 7.533l-3.954 6.847c.538.054 1.085.084 1.638.084C20.09 22.1 24 17.627 24 12.203V12c0-.338-.014-.672-.041-1.003zM12 16.364a4.364 4.364 0 1 0 0-8.728 4.364 4.364 0 0 0 0 8.728z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-white mb-1">Chrome extension</h3>
                        <p class="text-gray-400 text-sm">Record from your browser toolbar. One click to start recording.</p>
                    </div>
                </div>
                <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="relative shrink-0 inline-flex items-center gap-2 h-11 px-6 bg-white text-gray-900 rounded-xl font-semibold hover:bg-gray-100 transition-colors shadow-lg">
                    Add to Chrome
                    <i data-lucide="external-link" class="size-4"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <section id="pricing" class="py-20 md:py-28 border-t border-gray-100">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Pricing</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Simple, transparent pricing</h2>
                <p class="text-gray-500 text-lg">Start free. Upgrade when your team grows.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-5 items-start">
                {{-- Free Plan --}}
                <div class="pricing-card p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center mb-4">
                            <i data-lucide="user" class="size-5 text-gray-500"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-1">Free</h3>
                        <p class="text-sm text-gray-400">For individuals getting started</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$0</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <ul class="space-y-3.5 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-gray-500"></i>
                            </div>
                            10 videos
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-gray-500"></i>
                            </div>
                            5 minutes per recording
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-gray-500"></i>
                            </div>
                            1 GB storage
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-gray-500"></i>
                            </div>
                            No watermarks
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="block w-full py-3 rounded-xl border border-gray-200 text-center font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Get started free
                    </a>
                </div>

                {{-- Pro Plan --}}
                <div class="pricing-card p-8 rounded-2xl border-2 border-orange-500 bg-white relative shadow-xl shadow-orange-100/50 md:-mt-4 md:mb-4">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-orange-600 text-xs font-semibold text-white shadow-lg shadow-orange-600/30">
                        Most popular
                    </div>
                    <div class="mb-6">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center mb-4">
                            <i data-lucide="rocket" class="size-5 text-orange-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-1">Pro</h3>
                        <p class="text-sm text-gray-500">For power users and creators</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$8</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <ul class="space-y-3.5 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-gray-700">
                            <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-orange-600"></i>
                            </div>
                            Unlimited videos
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-orange-600"></i>
                            </div>
                            Unlimited recording length
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-orange-600"></i>
                            </div>
                            50 GB storage
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-orange-600"></i>
                            </div>
                            HLS adaptive streaming
                        </li>
                        <li class="flex items-center gap-3 text-gray-700">
                            <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-orange-600"></i>
                            </div>
                            Priority support
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary block w-full py-3 rounded-xl bg-orange-600 text-center font-semibold text-white hover:bg-orange-500 transition-colors shadow-lg shadow-orange-600/20">
                        Upgrade to Pro
                    </a>
                </div>

                {{-- Team Plan --}}
                <div class="pricing-card p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300">
                    <div class="mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                            <i data-lucide="users" class="size-5 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-1">Team</h3>
                        <p class="text-sm text-gray-400">For growing teams</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">$39</span>
                        <span class="text-gray-400 text-sm ml-1">/month</span>
                    </div>
                    <ul class="space-y-3.5 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-blue-600"></i>
                            </div>
                            Up to 5 team members
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-blue-600"></i>
                            </div>
                            100 GB shared storage
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-blue-600"></i>
                            </div>
                            Shared video library
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-blue-600"></i>
                            </div>
                            Team workspaces
                        </li>
                        <li class="flex items-center gap-3 text-gray-600">
                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="size-3 text-blue-600"></i>
                            </div>
                            Admin controls
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="block w-full py-3 rounded-xl border border-gray-200 text-center font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Get Team
                    </a>
                </div>
            </div>

            <p class="text-center text-gray-400 text-sm mt-10">
                Need more seats or custom features? <a href="mailto:contact@gurpreetkait.in" class="text-gray-900 font-medium hover:text-orange-600 transition-colors underline decoration-gray-300 hover:decoration-orange-300">Talk to us</a>
            </p>
        </div>
    </section>

    {{-- Use Cases Section --}}
    <section class="py-20 md:py-28 bg-gray-50/60 border-y border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Use cases</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Built for async-first teams</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">Replace meetings, not productivity</p>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div class="group p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 hover:border-gray-300 transition-all duration-300">
                    <div class="flex items-start gap-5">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                            <i data-lucide="code-2" class="size-6 text-violet-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Engineering teams</h3>
                            <p class="text-gray-500 leading-relaxed text-sm">Record PR walkthroughs, explain architecture decisions, or demo a bug fix. Your teammates review when they're in the zone, not when your calendar says so.</p>
                        </div>
                    </div>
                </div>

                <div class="group p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 hover:border-gray-300 transition-all duration-300">
                    <div class="flex items-start gap-5">
                        <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center shrink-0">
                            <i data-lucide="palette" class="size-6 text-pink-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Design reviews</h3>
                            <p class="text-gray-500 leading-relaxed text-sm">Walk through your Figma files with context. Stakeholders watch the full explanation instead of asking "what does this button do?" in comments.</p>
                        </div>
                    </div>
                </div>

                <div class="group p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 hover:border-gray-300 transition-all duration-300">
                    <div class="flex items-start gap-5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                            <i data-lucide="headphones" class="size-6 text-emerald-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Customer support</h3>
                            <p class="text-gray-500 leading-relaxed text-sm">Show customers exactly how to fix their issue. A 30-second video beats a 500-word email every time.</p>
                        </div>
                    </div>
                </div>

                <div class="group p-8 rounded-2xl border border-gray-200 bg-white hover:shadow-lg hover:shadow-gray-100/80 hover:border-gray-300 transition-all duration-300">
                    <div class="flex items-start gap-5">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                            <i data-lucide="graduation-cap" class="size-6 text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Remote onboarding</h3>
                            <p class="text-gray-500 leading-relaxed text-sm">Create reusable training videos for new hires. They watch at their own pace and can replay the tricky parts.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials Section --}}
    <section class="py-20 md:py-28">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 border border-orange-100 text-orange-600 text-xs font-semibold uppercase tracking-wider mb-4">Testimonials</div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Loved by developers and teams</h2>
                <p class="text-gray-500 text-lg">Honest feedback from people who switched</p>
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
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">"Finally, a screen recorder that doesn't nag me to upgrade every 5 seconds. It just works. I replaced Loom for my entire dev team."</p>
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
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">"I send 3-4 quick videos to clients daily. Saves me hours of back-and-forth emails. The instant link sharing is a game changer."</p>
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
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">"Simple pricing, no bloat. My whole team switched over in a day. Way better than paying Loom's enterprise tax."</p>
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
                        <p class="text-xs text-gray-500 leading-relaxed">Inspect every line of code. No hidden trackers, no black boxes. MIT licensed.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-check" class="size-5 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">Your data stays yours</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Videos are encrypted in transit. No selling your data. Delete anytime, it's actually gone.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                        <i data-lucide="lock" class="size-5 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">No vendor lock-in</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Export your videos anytime. Self-host if you want. You're never trapped.</p>
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
                        <p class="text-gray-500 leading-relaxed text-sm">Yes. The free plan includes 10 recordings with no watermarks. After that, Pro is $8/month for unlimited videos. We don't do the "free trial then surprise you with a paywall" thing.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">Do people need an account to watch my videos?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-relaxed text-sm">No. Anyone with the link can watch immediately in their browser. No signup, no app download, no friction.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">How long can my recordings be?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-relaxed text-sm">Free plan: 5 minutes per video. Pro and Team: no limit. Most async updates are under 3 minutes anyway.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">What browsers are supported?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-relaxed text-sm">Chrome, Firefox, Safari, and Edge. We also have a <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="text-orange-600 font-medium hover:text-orange-500 underline decoration-orange-200">Chrome extension</a> for one-click recording.</p>
                    </div>
                </div>

                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-gray-300 transition-colors">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left">
                        <span class="text-base font-semibold text-gray-900 pr-4">How is this different from Loom?</span>
                        <i data-lucide="plus" class="size-5 text-gray-400 shrink-0 faq-icon transition-transform duration-200"></i>
                    </button>
                    <div class="faq-content hidden px-5 pb-5 -mt-1">
                        <p class="text-gray-500 leading-relaxed text-sm">Simpler pricing, no aggressive upsells, and a free tier that actually lets you use the product. We're focused on doing one thing well: helping you share quick video explanations with your team.</p>
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
            <p class="text-lg text-gray-500 mb-10 max-w-lg mx-auto">Record your first video in under a minute. Free, no credit card required.</p>
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
