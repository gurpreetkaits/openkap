@extends('layouts.app')

@section('title', 'ScreenSense - Screen Recording | Loom Alternative')
@section('meta_description', 'ScreenSense, the best Loom alternative. Record your screen with audio and share instantly. No account required.')
@section('meta_keywords', 'screen recording, loom alternative, screen recorder, screen capture tool, video recording software, share screen recordings, loom free alternative, async video')

@section('og_title', 'ScreenSense - Loom Alternative')
@section('og_description', 'The best screen recording tool. Record your screen, capture audio, and share instantly.')

@push('styles')
<style>
    @keyframes float-up {
        0% { transform: translateY(10px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 15px -3px rgba(234, 88, 12, 0.4); }
        50% { box-shadow: 0 0 25px -5px rgba(234, 88, 12, 0.6); }
    }
    @keyframes cursorMove {
        0% { transform: translate(0, 0); }
        25% { transform: translate(100px, 50px); }
        50% { transform: translate(100px, 50px) scale(0.9); }
        75% { transform: translate(20px, 120px); }
        100% { transform: translate(0, 0); }
    }

    .animate-float-up { animation: float-up 0.6s ease-out forwards; }
    .animate-pulse-glow { animation: pulse-glow 2s infinite; }
    .animate-cursor { animation: cursorMove 4s infinite ease-in-out; }

    .faq-content {
        transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
    }
    .faq-content.active {
        opacity: 1;
    }
</style>
@endpush

@section('content')
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] max-w-[100vw] h-[400px] bg-brand-600/10 blur-[100px] rounded-full pointer-events-none -z-10 opacity-50"></div>

    <!-- Hero Section -->
    <div class="max-w-5xl mx-auto px-6 flex flex-col items-center text-center relative z-10 py-20 pt-8 overflow-hidden">

        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-brand-500/20 bg-brand-500/5 text-brand-400 text-xs font-medium mb-8">
            <i data-lucide="video" class="size-3.5"></i>
            10 Free Recordings — No Credit Card
        </div>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-medium tracking-tight text-white mb-6 leading-[1.1]">
            Stop scheduling meetings<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-b from-neutral-100 to-neutral-500">just to explain something.</span>
        </h1>

        <p class="text-xl text-neutral-300 max-w-2xl mx-auto mb-3 leading-relaxed">
            Record your screen, share a link, done. Your teammates watch when they have time.
        </p>
        <p class="text-base text-neutral-400 max-w-xl mx-auto mb-8 leading-relaxed">
            No account needed to view. No watermarks. No surprise paywalls.
        </p>

        <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto mb-4">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full sm:w-auto group relative inline-flex h-14 items-center justify-center overflow-hidden rounded-full bg-brand-600 px-10 font-medium text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[0_0_20px_-5px_rgba(234,88,12,0.4)] active:scale-95">
                <span class="mr-2 text-lg">Start Recording</span>
                <i data-lucide="arrow-right" class="size-5 transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>
        <p class="text-sm text-neutral-500 flex items-center gap-2">
            <i data-lucide="credit-card" class="size-4"></i>
            No credit card required
        </p>

        <!-- UI Visualization / Screenshots -->
        <div class="relative w-full max-w-6xl mx-auto h-[350px] sm:h-[450px] md:h-[550px] lg:h-[650px] mt-16 overflow-hidden" style="perspective: 2000px;">

            <!-- Glow behind images -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[60%] h-[60%] bg-brand-500/20 blur-[100px] rounded-full"></div>

            <!-- Left Card (Faded Background) -->
            <div class="absolute top-10 left-1/2 w-[95%] md:w-[70%] rounded-xl border border-white/5 bg-[#0a0a0a] shadow-2xl opacity-40 z-0 hidden sm:block overflow-hidden" style="transform: translateX(-50%) translateX(-15%) rotate(-6deg);">
                <!-- Browser Header -->
                <div class="h-10 border-b border-white/5 bg-[#111] flex items-center px-4 gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-red-500/20"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/20"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-green-500/20"></div>
                </div>
                <!-- Screenshot -->
                <img src="/demo/images/dashboard.png" alt="ScreenSense Dashboard" class="w-full h-auto block">
            </div>

            <!-- Right Card (Faded Background) -->
            <div class="absolute top-10 left-1/2 w-[95%] md:w-[70%] rounded-xl border border-white/5 bg-[#0a0a0a] shadow-2xl opacity-40 z-0 hidden sm:block overflow-hidden" style="transform: translateX(-50%) translateX(15%) rotate(6deg);">
                <!-- Browser Header -->
                <div class="h-10 border-b border-white/5 bg-[#111] flex items-center px-4 gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-red-500/20"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/20"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-green-500/20"></div>
                </div>
                <!-- Screenshot -->
                <img src="/demo/images/single-video-page.png" alt="ScreenSense Video Page" class="w-full h-auto block">
            </div>

            <!-- Center Main Card -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[98%] md:w-[85%] lg:w-[80%] rounded-xl border border-white/10 bg-[#0c0c0c] shadow-[0_0_50px_-12px_rgba(0,0,0,0.8)] z-10 overflow-hidden transition-transform duration-500 hover:scale-[1.02]">
                <!-- Window Header -->
                <div class="h-10 border-b border-white/5 bg-[#141414] flex items-center justify-between px-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-[#FF5F57]"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-[#FEBC2E]"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-[#28C840]"></div>
                    </div>
                    <div class="px-3 py-1 bg-[#1f1f1f] rounded text-[10px] text-neutral-500 font-medium border border-white/5 flex items-center gap-2">
                        <i data-lucide="lock" class="w-2.5 h-2.5"></i>
                        record.screensense.in
                    </div>
                    <div class="w-10"></div>
                </div>
                <!-- Main Screenshot -->
                <img src="/demo/images/dashboard-with-recording-dialog.png" alt="ScreenSense Recording" class="w-full h-auto block">
            </div>

            <!-- Bottom Fade Gradient -->
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-brand-950 to-transparent z-20"></div>
        </div>

    </div>

    <!-- Browser Extension Promotion Banner -->
    <section class="max-w-5xl mx-auto px-6 py-8">
        <div class="relative overflow-hidden rounded-2xl border border-brand-500/20 bg-gradient-to-br from-brand-900/20 via-neutral-900/40 to-neutral-900/20 p-8 md:p-10">
            <!-- Glow Effect -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-brand-500/10 blur-[100px] rounded-full"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                <!-- Left: Icon & Text -->
                <div class="flex-1 text-center md:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-xs font-medium mb-4">
                        <i data-lucide="sparkles" class="size-3"></i>
                        Now Available
                    </div>
                    <h3 class="text-2xl md:text-3xl font-semibold text-white mb-3">
                        Record with One Click
                    </h3>
                    <p class="text-neutral-300 mb-6 max-w-md">
                        Install our Chrome extension and start recording instantly from your browser. No app download needed.
                    </p>
                    
                    <!-- Extension CTA -->
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="group inline-flex items-center gap-3 px-6 py-3 bg-white hover:bg-gray-50 text-gray-900 rounded-lg font-medium transition-all shadow-lg hover:shadow-xl hover:scale-105 active:scale-95">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C10.6868 2 9.38642 2.25866 8.17317 2.7612C6.95991 3.26375 5.85752 4.00035 4.92893 4.92893C3.05357 6.8043 2 9.34784 2 12C2 14.6522 3.05357 17.1957 4.92893 19.0711C5.85752 19.9997 6.95991 20.7362 8.17317 21.2388C9.38642 21.7413 10.6868 22 12 22C14.6522 22 17.1957 20.9464 19.0711 19.0711C20.9464 17.1957 22 14.6522 22 12C22 10.6868 21.7413 9.38642 21.2388 8.17317C20.7362 6.95991 19.9997 5.85752 19.0711 4.92893C18.1425 4.00035 17.0401 3.26375 15.8268 2.7612C14.6136 2.25866 13.3132 2 12 2Z" fill="#4285F4"/>
                                <path d="M12 2V12L19.0711 19.0711C20.9464 17.1957 22 14.6522 22 12C22 10.6868 21.7413 9.38642 21.2388 8.17317L12 2Z" fill="#34A853"/>
                                <path d="M12 12L4.92893 19.0711C6.8043 20.9464 9.34784 22 12 22C14.6522 22 17.1957 20.9464 19.0711 19.0711L12 12Z" fill="#FBBC04"/>
                                <path d="M12 2L2 12H12V2Z" fill="#EA4335"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-xs text-gray-500 leading-none mb-0.5">Add to</div>
                                <div class="text-sm font-semibold leading-none">Chrome</div>
                            </div>
                            <i data-lucide="external-link" class="size-4 text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                        </a>
                        
                        <div class="flex items-center gap-2 text-sm text-neutral-400">
                            <div class="flex items-center">
                                <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                                <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                                <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                                <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                                <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                            </div>
                            <span class="text-neutral-500">Free</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Visual/Screenshot -->
                <div class="relative shrink-0">
                    <div class="w-48 h-48 md:w-56 md:h-56 rounded-2xl bg-gradient-to-br from-neutral-800 to-neutral-900 border border-white/10 p-6 flex items-center justify-center shadow-2xl">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/20">
                                <i data-lucide="chrome" class="size-8 text-white"></i>
                            </div>
                            <div class="text-white font-medium mb-1">ScreenSense</div>
                            <div class="text-xs text-neutral-400">Chrome Extension</div>
                            <div class="mt-4 inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-500/10 border border-green-500/20">
                                <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                <span class="text-[10px] text-green-400 font-medium">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="max-w-5xl mx-auto px-6 py-16">
        <!-- Trust Indicators -->
        <div class="flex flex-wrap justify-center items-center gap-6 md:gap-12 mb-12 text-neutral-400">
            <div class="flex items-center gap-2">
                <i data-lucide="video" class="size-5"></i>
                <span class="text-sm">10 Free Videos</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="lock" class="size-5"></i>
                <span class="text-sm">Secure Sharing</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="zap" class="size-5"></i>
                <span class="text-sm">Instant Links</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="ban" class="size-5"></i>
                <span class="text-sm">No Watermarks</span>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="text-center mb-8">
            <h3 class="text-xl font-medium text-white mb-2">People who switched from Loom</h3>
            <p class="text-sm text-neutral-500">Honest feedback from developers and founders</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Testimonial 1 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="flex items-center gap-1 mb-4">
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"Finally, a screen recorder that doesn't nag me to upgrade every 5 seconds. It just works."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-[10px] font-medium text-white">SK</div>
                    <div>
                        <p class="text-sm text-white font-medium">Sanjay K.</p>
                        <p class="text-xs text-neutral-500">Full-stack Developer</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="flex items-center gap-1 mb-4">
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"I send 3-4 quick videos to clients daily. Saves me hours of back-and-forth emails."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-[10px] font-medium text-white">MR</div>
                    <div>
                        <p class="text-sm text-white font-medium">Maria R.</p>
                        <p class="text-xs text-neutral-500">Freelance Designer</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="flex items-center gap-1 mb-4">
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                    <i data-lucide="star" class="size-4 text-yellow-500 fill-yellow-500"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"Simple pricing, no bloat. My whole team switched over in a day. Way better than paying Loom's enterprise tax."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-[10px] font-medium text-white">AT</div>
                    <div>
                        <p class="text-sm text-white font-medium">Alex T.</p>
                        <p class="text-xs text-neutral-500">Startup CTO</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Workflow Simplified Section -->
    <section id="how-it-works" class="max-w-6xl mx-auto px-6 py-24 border-t border-white/5">
        <div class="mb-16 text-center">
            <h2 class="text-3xl font-medium text-white mb-4">Workflow simplified</h2>
            <p class="text-neutral-400">Designed for speed, built for clarity.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Sidebar Controls -->
            <div class="lg:col-span-4 flex flex-col gap-3">

                <!-- Step 1: Capture -->
                <button onclick="switchTab('capture')" id="btn-capture" class="group text-left p-6 rounded-xl border border-brand-500/20 bg-brand-900/10 transition-all duration-300 ring-1 ring-brand-500/50 relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-2 relative z-10">
                        <div id="icon-capture" class="size-8 rounded-lg bg-brand-500/20 text-brand-500 flex items-center justify-center">
                            <i data-lucide="video" class="size-4"></i>
                        </div>
                        <h3 class="font-medium text-white">1. Capture</h3>
                    </div>
                    <p class="text-sm text-neutral-400 pl-11 relative z-10">Select window or region. Recording starts instantly.</p>
                </button>

                <!-- Step 2: Share -->
                <button onclick="switchTab('share')" id="btn-share" class="group text-left p-6 rounded-xl border border-transparent hover:bg-white/5 transition-all duration-300">
                    <div class="flex items-center gap-3 mb-2">
                        <div id="icon-share" class="size-8 rounded-lg bg-neutral-800 group-hover:bg-neutral-700 transition-colors flex items-center justify-center text-neutral-300">
                            <i data-lucide="send" class="size-4"></i>
                        </div>
                        <h3 class="font-medium text-white">2. Share</h3>
                    </div>
                    <p class="text-sm text-neutral-400 pl-11">Get a secure link immediately. No upload wait times.</p>
                </button>
            </div>

            <!-- Visual Demo Container -->
            <div class="lg:col-span-8 h-[500px] bg-[#0A0A0A] border border-white/10 rounded-2xl shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:32px_32px]"></div>

                <!-- Header -->
                <div class="absolute top-0 w-full h-12 border-b border-white/5 bg-white/5 flex items-center px-4 justify-between z-20 backdrop-blur-md">
                    <div class="flex gap-2">
                        <div class="size-2.5 rounded-full bg-neutral-700"></div>
                        <div class="size-2.5 rounded-full bg-neutral-700"></div>
                    </div>
                    <div class="text-[10px] font-mono text-neutral-600 uppercase tracking-widest">Preview</div>
                </div>

                <!-- CAPTURE VIEW -->
                <div id="tab-capture" class="absolute inset-0 pt-12 flex items-center justify-center transition-all duration-500">
                    <img src="/demo/how-to-record-screen.gif" alt="How to record screen" class="w-full h-full object-cover">
                </div>

                <!-- SHARE VIEW -->
                <div id="tab-share" class="absolute inset-0 pt-12 flex items-center justify-center transition-all duration-500 opacity-0 pointer-events-none translate-y-4">
                    <img src="/demo/copy-share-link.gif" alt="Copy and share link" class="w-full h-full object-cover">
                </div>

            </div>
        </div>

        <!-- CTA after Workflow -->
        <div class="mt-16 text-center">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="group relative inline-flex h-12 items-center justify-center overflow-hidden rounded-full bg-brand-600 px-8 font-medium text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[0_0_20px_-5px_rgba(234,88,12,0.4)] active:scale-95">
                <span class="mr-2">Get Started Free</span>
                <i data-lucide="arrow-right" class="size-4 transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>
    </section>

    <!-- Features Bento Grid -->
    <section id="features" class="py-24 relative border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-16 md:text-center max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-5xl font-medium tracking-tight text-white mb-6">Designed for speed and clarity.</h2>
                <p class="text-lg text-neutral-400">Everything you need to communicate effectively, without the bloat. Optimized for modern workflows.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Feature 1: Instant Sharing -->
                <div class="md:col-span-2 group relative overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/50 hover:bg-neutral-900/80 transition-all duration-500">
                    <div class="absolute top-0 right-0 p-12 opacity-20 group-hover:opacity-10 transition-opacity">
                        <div class="size-64 bg-brand-500 blur-[100px] rounded-full"></div>
                    </div>
                    <div class="p-8 md:p-10 h-full flex flex-col justify-between relative z-10">
                        <div class="mb-8">
                            <div class="inline-flex items-center justify-center size-12 rounded-lg bg-neutral-800 border border-white/10 mb-6 text-brand-500">
                                <i data-lucide="zap" class="size-6"></i>
                            </div>
                            <h3 class="text-xl font-medium text-white mb-2">Instant Sharing</h3>
                            <p class="text-neutral-400 text-lg">Links are generated instantly as you record. Paste into Slack, Linear, or Notion before the video even finishes processing.</p>
                        </div>

                        <!-- Mockup of Link UI -->
                        <div class="bg-black/50 border border-white/10 rounded-lg p-4 flex items-center justify-between gap-4 max-w-md backdrop-blur-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i data-lucide="link" class="size-4 text-neutral-500 shrink-0"></i>
                                <span class="text-sm text-neutral-300 truncate font-mono">screensense.com/v/8x29a...</span>
                            </div>
                            <button class="text-xs font-medium bg-brand-600 text-white px-3 py-1.5 rounded hover:bg-brand-500 transition-colors">Copy</button>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Audio Clarity -->
                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/50 hover:bg-neutral-900/80 transition-all duration-500">
                    <div class="p-8 md:p-10 h-full relative z-10">
                        <div class="inline-flex items-center justify-center size-12 rounded-lg bg-neutral-800 border border-white/10 mb-6 text-brand-500">
                            <i data-lucide="mic" class="size-6"></i>
                        </div>
                        <h3 class="text-xl font-medium text-white mb-2">Crystal Clear Audio</h3>
                        <p class="text-neutral-400 mb-8 text-lg">Capture your voice alongside screen recording with high-quality audio.</p>

                        <!-- Audio Visualizer Mockup -->
                        <div class="flex items-center justify-center gap-1 h-12">
                            <div class="w-1.5 bg-brand-500/40 rounded-full h-4 animate-pulse"></div>
                            <div class="w-1.5 bg-brand-500/70 rounded-full h-8 animate-pulse" style="animation-delay: 0.1s"></div>
                            <div class="w-1.5 bg-brand-500 rounded-full h-10 animate-pulse" style="animation-delay: 0.2s"></div>
                            <div class="w-1.5 bg-brand-500 rounded-full h-6 animate-pulse" style="animation-delay: 0.3s"></div>
                            <div class="w-1.5 bg-brand-500/60 rounded-full h-9 animate-pulse" style="animation-delay: 0.15s"></div>
                            <div class="w-1.5 bg-brand-500/30 rounded-full h-4 animate-pulse" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Browser Extension -->
                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/50 hover:bg-neutral-900/80 transition-all duration-500">
                    <div class="p-8 md:p-10 h-full relative z-10">
                        <div class="absolute top-8 right-8 px-2 py-0.5 rounded-full bg-green-500/10 border border-green-500/20 text-[10px] font-medium text-green-400 uppercase tracking-wider">Available Now</div>
                        <div class="inline-flex items-center justify-center size-12 rounded-lg bg-neutral-800 border border-white/10 mb-6 text-brand-500">
                            <i data-lucide="puzzle" class="size-6"></i>
                        </div>
                        <h3 class="text-xl font-medium text-white mb-2">Browser Extension</h3>
                        <p class="text-neutral-400 mb-8 text-lg">Record directly from Chrome with one click. No app needed.</p>

                        <!-- Extension Preview -->
                        <div class="space-y-3">
                            <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between p-3 rounded bg-black/40 border border-white/5 hover:bg-black/60 transition-colors">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="chrome" class="size-4 text-neutral-400"></i>
                                    <span class="text-sm text-neutral-300">Chrome Extension</span>
                                </div>
                                <span class="text-xs text-brand-400 font-medium">Install</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: High Res -->
                <div class="md:col-span-2 group relative overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/50 hover:bg-neutral-900/80 transition-all duration-500">
                    <div class="absolute bottom-0 left-0 p-12 opacity-20 group-hover:opacity-10 transition-opacity">
                        <div class="size-64 bg-blue-500 blur-[100px] rounded-full"></div>
                    </div>
                    <div class="p-8 md:p-10 h-full flex flex-col md:flex-row items-center gap-10 relative z-10">
                        <div class="flex-1">
                            <div class="inline-flex items-center justify-center size-12 rounded-lg bg-neutral-800 border border-white/10 mb-6 text-brand-500">
                                <i data-lucide="monitor" class="size-6"></i>
                            </div>
                            <h3 class="text-xl font-medium text-white mb-2">High Quality Recording</h3>
                            <p class="text-neutral-400 text-lg">Capture every pixel. ScreenSense supports high resolution recording for crystal clear demos and tutorials.</p>
                        </div>

                        <div class="relative shrink-0">
                            <div class="bg-black border border-white/10 rounded-lg p-2 flex gap-2">
                                <span class="px-2 py-1 rounded bg-neutral-800 text-neutral-400 text-xs font-mono">720p</span>
                                <span class="px-2 py-1 rounded bg-brand-600 text-white text-xs font-mono shadow-lg shadow-brand-500/20">1080p</span>
                                <span class="px-2 py-1 rounded bg-neutral-800 text-neutral-400 text-xs font-mono">4K</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CTA after Features -->
            <div class="mt-16 text-center">
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="group relative inline-flex h-12 items-center justify-center overflow-hidden rounded-full bg-brand-600 px-8 font-medium text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[0_0_20px_-5px_rgba(234,88,12,0.4)] active:scale-95">
                    <span class="mr-2">Get Started Free</span>
                    <i data-lucide="arrow-right" class="size-4 transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 border-t border-white/5 relative overflow-hidden">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-brand-900/5 blur-[120px] rounded-full pointer-events-none -z-10"></div>
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-medium text-white mb-4">Simple, Transparent Pricing</h2>
                <p class="text-neutral-400">Start free, upgrade when you need more.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- Free Tier -->
                <div class="p-8 rounded-2xl border border-white/10 bg-neutral-900/40 hover:bg-neutral-900/60 transition-colors flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Free</h3>
                        <p class="text-sm text-neutral-500 mt-1">Perfect for getting started</p>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$0</span>
                            <span class="text-neutral-500">/month</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-neutral-500"></i>
                                10 videos
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-neutral-500"></i>
                                1 GB storage
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-neutral-500"></i>
                                5 min recordings
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-neutral-500"></i>
                                Basic playback
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg border border-white/10 bg-white/5 text-sm font-medium text-white hover:bg-white/10 transition-colors text-center">
                        Get Started
                    </a>
                </div>

                <!-- Pro Plan -->
                <div class="p-8 pt-12 rounded-2xl border border-brand-500/30 bg-neutral-900 relative flex flex-col shadow-[0_0_50px_-15px_rgba(249,115,22,0.15)]">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 text-[10px] font-semibold text-white uppercase tracking-wider shadow-lg">Popular</span>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Pro</h3>
                        <p class="text-sm text-neutral-400 mt-1">For individuals & creators</p>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$8</span>
                            <span class="text-neutral-500">/month</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-white">
                                <div class="size-5 rounded-full bg-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-brand-400"></i>
                                </div>
                                Unlimited videos
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <div class="size-5 rounded-full bg-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-brand-400"></i>
                                </div>
                                50 GB storage
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <div class="size-5 rounded-full bg-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-brand-400"></i>
                                </div>
                                Unlimited recording length
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <div class="size-5 rounded-full bg-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-brand-400"></i>
                                </div>
                                HLS adaptive streaming
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <div class="size-5 rounded-full bg-brand-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-brand-400"></i>
                                </div>
                                Priority support
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg bg-brand-600 text-sm font-medium text-white hover:bg-brand-500 transition-colors text-center shadow-lg shadow-brand-900/20">
                        Upgrade to Pro
                    </a>
                </div>

                <!-- Team Plan -->
                <div class="p-8 pt-12 rounded-2xl border border-white/10 bg-neutral-900/40 hover:bg-neutral-900/60 transition-colors flex flex-col relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-[10px] font-semibold text-white uppercase tracking-wider shadow-lg">For Teams</span>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Team</h3>
                        <p class="text-sm text-neutral-500 mt-1">For collaborative teams</p>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$39</span>
                            <span class="text-neutral-500">/month</span>
                        </div>
                        <p class="text-xs text-neutral-500 mt-1">Up to 5 team members</p>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                5 team members included
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                100 GB shared storage
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                Unlimited recording length
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                Team workspaces
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                Shared video library
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <div class="size-5 rounded-full bg-indigo-500/20 flex items-center justify-center">
                                    <i data-lucide="check" class="size-3 text-indigo-400"></i>
                                </div>
                                Admin controls
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-500 transition-colors text-center">
                        Get Team
                    </a>
                </div>
            </div>

            <!-- Enterprise Contact -->
            <div class="mt-12 text-center p-6 bg-white/[0.02] rounded-xl border border-white/5 max-w-2xl mx-auto">
                <h4 class="text-sm font-semibold text-white mb-1">Need a custom enterprise plan?</h4>
                <p class="text-sm text-neutral-500 mb-3">For large organizations with specific security, SSO, and compliance needs.</p>
                <a href="mailto:contact@gurpreetkait.in" class="text-sm font-medium text-white border-b border-neutral-600 hover:border-white transition-colors">
                    Contact Sales
                </a>
            </div>

            <!-- CTA after Pricing -->
            <div class="mt-16 text-center">
                <p class="text-neutral-400 mb-4">Ready to get started?</p>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="group relative inline-flex h-12 items-center justify-center overflow-hidden rounded-full bg-brand-600 px-8 font-medium text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[0_0_20px_-5px_rgba(234,88,12,0.4)] active:scale-95">
                    <span class="mr-2">Get Started Free</span>
                    <i data-lucide="arrow-right" class="size-4 transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 border-t border-white/5 bg-neutral-950">
        <div class="max-w-3xl mx-auto px-6">
            <h2 class="text-3xl font-medium text-white mb-12 text-center">Frequently asked questions</h2>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="border border-white/5 rounded-lg bg-white/[0.02] overflow-hidden">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-white/[0.02] transition-colors">
                        <span class="text-sm font-medium text-neutral-200">Is ScreenSense really free?</span>
                        <i data-lucide="chevron-down" class="size-4 text-neutral-500 transition-transform duration-300"></i>
                    </button>
                    <div class="faq-content">
                        <div class="px-6 pb-4 text-sm text-neutral-400">
                            Yes! The free plan includes 10 video recordings with full screen and audio capture. It's perfect for trying out ScreenSense before upgrading.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="border border-white/5 rounded-lg bg-white/[0.02] overflow-hidden">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-white/[0.02] transition-colors">
                        <span class="text-sm font-medium text-neutral-200">How do shareable links work?</span>
                        <i data-lucide="chevron-down" class="size-4 text-neutral-500 transition-transform duration-300"></i>
                    </button>
                    <div class="faq-content">
                        <div class="px-6 pb-4 text-sm text-neutral-400">
                            After recording, you get a secure link with token-based access. Anyone with the link can view your video without needing an account.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="border border-white/5 rounded-lg bg-white/[0.02] overflow-hidden">
                    <button onclick="toggleFaq(this)" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-white/[0.02] transition-colors">
                        <span class="text-sm font-medium text-neutral-200">What browsers are supported?</span>
                        <i data-lucide="chevron-down" class="size-4 text-neutral-500 transition-transform duration-300"></i>
                    </button>
                    <div class="faq-content">
                        <div class="px-6 pb-4 text-sm text-neutral-400">
                            ScreenSense works on all modern browsers including Chrome, Firefox, Safari, and Edge. Our Chrome extension is now available on the <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="text-brand-400 hover:text-brand-300 underline">Chrome Web Store</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    // Workflow Tab Switching
    function switchTab(tabName) {
        const btnCapture = document.getElementById('btn-capture');
        const btnShare = document.getElementById('btn-share');
        const iconCapture = document.getElementById('icon-capture');
        const iconShare = document.getElementById('icon-share');
        const tabCapture = document.getElementById('tab-capture');
        const tabShare = document.getElementById('tab-share');

        const inactiveClass = "group text-left p-6 rounded-xl border border-transparent hover:bg-white/5 transition-all duration-300";
        const activeClass = "group text-left p-6 rounded-xl border border-brand-500/20 bg-brand-900/10 transition-all duration-300 ring-1 ring-brand-500/50 relative overflow-hidden";
        const iconInactiveClass = "size-8 rounded-lg bg-neutral-800 group-hover:bg-neutral-700 transition-colors flex items-center justify-center text-neutral-300";
        const iconActiveClass = "size-8 rounded-lg bg-brand-500/20 text-brand-500 flex items-center justify-center";

        if (tabName === 'capture') {
            btnCapture.className = activeClass;
            iconCapture.className = iconActiveClass;
            btnShare.className = inactiveClass;
            iconShare.className = iconInactiveClass;

            tabCapture.style.opacity = '1';
            tabCapture.style.transform = 'translateY(0)';
            tabCapture.style.pointerEvents = 'auto';
            tabShare.style.opacity = '0';
            tabShare.style.pointerEvents = 'none';
            tabShare.style.transform = 'translateY(10px)';

        } else if (tabName === 'share') {
            btnShare.className = activeClass;
            iconShare.className = iconActiveClass;
            btnCapture.className = inactiveClass;
            iconCapture.className = iconInactiveClass;

            tabShare.style.opacity = '1';
            tabShare.style.pointerEvents = 'auto';
            tabShare.style.transform = 'translateY(0)';
            tabCapture.style.opacity = '0';
            tabCapture.style.pointerEvents = 'none';
            tabCapture.style.transform = 'translateY(-10px)';
        }
    }

    // FAQ Toggle
    function toggleFaq(button) {
        const content = button.nextElementSibling;
        const icon = button.querySelector('i');

        if (content.style.maxHeight) {
            content.style.maxHeight = null;
            content.classList.remove('active');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.style.maxHeight = content.scrollHeight + "px";
            content.classList.add('active');
            icon.style.transform = 'rotate(180deg)';
        }
    }

</script>
@endpush
