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
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
            </span>
            Free to Start
        </div>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-medium tracking-tight text-white mb-8 leading-[1.1]">
            Record & share async videos<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-b from-neutral-100 to-neutral-500">in seconds — no meetings, no wait.</span>
        </h1>

        <p class="text-xl text-neutral-300 max-w-xl mx-auto mb-4 leading-relaxed font-medium">
            Unlimited recordings. Fair pricing. No lock-in.
        </p>
        <p class="text-base text-neutral-500 max-w-xl mx-auto mb-10 leading-relaxed font-light">
            Built for developers, founders, and small teams.
        </p>

        <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full sm:w-auto group relative inline-flex h-12 items-center justify-center overflow-hidden rounded-full bg-brand-600 px-8 font-medium text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[0_0_20px_-5px_rgba(234,88,12,0.4)] active:scale-95">
                <span class="mr-2">Get Started Free</span>
                <i data-lucide="arrow-right" class="size-4 transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>

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

        <!-- Launch Badges -->
        <div class="flex flex-wrap justify-center items-center gap-4 mt-8">
            <a href="https://peerlist.io/gurpreet/project/screensense--open-source-screen-recording" target="_blank" rel="noreferrer">
                <img
                    src="https://peerlist.io/api/v1/projects/embed/PRJH9OBGM78Q7867EC6ADDNQGK799G?showUpvote=false&theme=light"
                    alt="ScreenSense - Open Source Screen Recording"
                    style="width: auto; height: 72px;"
                />
            </a>
            <a href="https://www.producthunt.com/products/screensense-2?embed=true&utm_source=badge-featured&utm_medium=badge&utm_campaign=badge-screensense-2" target="_blank" rel="noopener noreferrer">
                <img
                    src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1058090&theme=light&t=1767617780370"
                    alt="ScreenSense - Open Source Loom Alternative | Product Hunt"
                    width="250"
                    height="54"
                />
            </a>
        </div>
    </div>

    <!-- Social Proof Section -->
    <section class="max-w-5xl mx-auto px-6 py-16">
        <!-- Users Stat -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-full border border-white/10 bg-white/5">
                <div class="flex -space-x-2">
                    <div class="size-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 border-2 border-neutral-900 flex items-center justify-center text-[10px] font-medium text-white">JD</div>
                    <div class="size-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 border-2 border-neutral-900 flex items-center justify-center text-[10px] font-medium text-white">AK</div>
                    <div class="size-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 border-2 border-neutral-900 flex items-center justify-center text-[10px] font-medium text-white">MR</div>
                    <div class="size-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 border-2 border-neutral-900 flex items-center justify-center text-[10px] font-medium text-white">+</div>
                </div>
                <span class="text-sm text-neutral-300"><span class="text-white font-medium">30+</span> active users loved by teams globally</span>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="text-center mb-8">
            <h3 class="text-xl font-medium text-white mb-2">What early users are saying</h3>
            <p class="text-sm text-neutral-500">Real feedback from real teams</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Testimonial 1 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="mb-4">
                    <i data-lucide="quote" class="size-5 text-brand-500/50"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"Way easier than explaining things over Slack."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-[10px] font-medium text-white">FD</div>
                    <div>
                        <p class="text-xs text-neutral-400">Frontend Developer</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="mb-4">
                    <i data-lucide="quote" class="size-5 text-brand-500/50"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"Perfect for quick walkthroughs without jumping on calls."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-[10px] font-medium text-white">IF</div>
                    <div>
                        <p class="text-xs text-neutral-400">Indie Founder</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="p-6 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors">
                <div class="mb-4">
                    <i data-lucide="quote" class="size-5 text-brand-500/50"></i>
                </div>
                <p class="text-neutral-300 text-sm leading-relaxed mb-4">"Simple, fast, and doesn't feel bloated like other tools."</p>
                <div class="flex items-center gap-3">
                    <div class="size-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-[10px] font-medium text-white">RT</div>
                    <div>
                        <p class="text-xs text-neutral-400">Small Remote Team</p>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <!-- Free Tier -->
                <div class="p-8 rounded-2xl border border-white/10 bg-neutral-900/40 hover:bg-neutral-900/60 transition-colors flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Free</h3>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$0</span>
                        </div>
                        <p class="mt-2 text-sm text-neutral-400">Perfect for trying out ScreenSense</p>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                10 video recordings
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Screen + audio capture
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Shareable links
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg border border-white/10 bg-white/5 text-sm font-medium text-white hover:bg-white/10 transition-colors text-center">
                        Get Started
                    </a>
                </div>

                <!-- Pro Monthly -->
                <div class="p-8 rounded-2xl border border-brand-500/40 bg-brand-950/50 relative flex flex-col shadow-[0_0_50px_-15px_rgba(249,115,22,0.2)]">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-brand-600 text-[10px] font-semibold text-white uppercase tracking-wider">Most Popular</span>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Pro Monthly</h3>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$7</span>
                            <span class="text-neutral-500">/month</span>
                        </div>
                        <p class="mt-2 text-sm text-brand-200/80">For creators who need more</p>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-white">
                                <i data-lucide="check" class="size-4 text-brand-500"></i>
                                Unlimited recordings
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <i data-lucide="check" class="size-4 text-brand-500"></i>
                                Screen + audio capture
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <i data-lucide="check" class="size-4 text-brand-500"></i>
                                Shareable links
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white">
                                <i data-lucide="check" class="size-4 text-brand-500"></i>
                                Priority support
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg bg-brand-600 text-sm font-medium text-white hover:bg-brand-500 transition-colors text-center">
                        Get Started
                    </a>
                </div>

                <!-- Pro Yearly -->
                <div class="p-8 rounded-2xl border border-white/10 bg-neutral-900/40 hover:bg-neutral-900/60 transition-colors flex flex-col">
                    <span class="inline-block w-fit px-2 py-0.5 rounded-full bg-green-500/10 border border-green-500/20 text-[10px] font-medium text-green-400 uppercase tracking-wider mb-3">Save $4</span>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-white">Pro Yearly</h3>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-4xl font-semibold text-white">$80</span>
                            <span class="text-neutral-500">/year</span>
                        </div>
                        <p class="mt-2 text-sm text-neutral-400">Best value for long-term use</p>
                    </div>
                    <div class="flex-1">
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Unlimited recordings
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Screen + audio capture
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Shareable links
                            </li>
                            <li class="flex items-center gap-3 text-sm text-neutral-300">
                                <i data-lucide="check" class="size-4 text-green-500"></i>
                                Priority support
                            </li>
                        </ul>
                    </div>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="w-full py-3 rounded-lg border border-white/10 bg-white/5 text-sm font-medium text-white hover:bg-white/10 transition-colors text-center">
                        Get Started
                    </a>
                </div>
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
