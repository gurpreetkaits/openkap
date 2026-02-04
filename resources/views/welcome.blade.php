@extends('layouts.app')

@section('title', 'ScreenSense - Screen Recording Software | Loom Alternative')
@section('meta_description', 'ScreenSense is async video messaging for remote teams. Record your screen, share a link, and communicate without scheduling meetings. Free to start.')
@section('meta_keywords', 'screen recording software, loom alternative, async video, screen recorder, video messaging, remote team communication, screen capture, video sharing')

@section('og_title', 'ScreenSense - Async Video for Remote Teams')
@section('og_description', 'Record your screen, share a link, skip the meeting. Free screen recording software for teams.')

@push('styles')
<style>
    .hero-gradient {
        background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(249, 115, 22, 0.15), transparent);
    }
    .feature-card {
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .feature-card:hover {
        transform: translateY(-2px);
        border-color: rgba(255, 255, 255, 0.15);
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
        box-shadow: 0 0 30px -5px rgba(249, 115, 22, 0.4);
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
    <section class="hero-gradient relative">
        <div class="max-w-6xl mx-auto px-6 pt-16 pb-24 md:pt-24 md:pb-32">
            <div class="max-w-3xl mx-auto text-center">
                {{-- Tagline --}}
                <p class="text-brand-500 font-medium text-sm tracking-wide uppercase mb-6">
                    Async video messaging
                </p>

                {{-- Main headline - SEO optimized --}}
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-semibold text-white leading-tight tracking-tight mb-6">
                    Say it with a video,<br class="hidden sm:block">
                    <span class="text-neutral-400">not another meeting</span>
                </h1>

                {{-- Subheadline --}}
                <p class="text-lg md:text-xl text-neutral-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Record your screen and camera, get a shareable link in seconds. Your team watches on their own time. No scheduling, no calendar tetris.
                </p>

                {{-- CTA buttons --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-6">
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary w-full sm:w-auto inline-flex items-center justify-center h-12 px-8 rounded-lg bg-brand-600 text-white font-medium hover:bg-brand-500">
                        Record Now
                    </a>
                    <a href="{{ config('app.chrome_extension_url', '#') }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center h-12 px-8 rounded-lg border border-white/10 text-white font-medium hover:bg-white/5 transition-colors">
                        <svg class="size-5 mr-2" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.21 0 4.831 1.757 2.632 4.501l3.953 6.848A5.454 5.454 0 0 1 12 6.545h10.691A12 12 0 0 0 12 0zM1.931 5.47A11.943 11.943 0 0 0 0 12c0 6.012 4.42 10.991 10.189 11.864l3.953-6.847a5.45 5.45 0 0 1-6.865-2.29zm13.342 2.166a5.446 5.446 0 0 1 1.819 7.533l-3.954 6.847c.538.054 1.085.084 1.638.084C20.09 22.1 24 17.627 24 12.203V12c0-.338-.014-.672-.041-1.003zM12 16.364a4.364 4.364 0 1 0 0-8.728 4.364 4.364 0 0 0 0 8.728z"/></svg>
                        Add to Chrome
                    </a>
                    <a href="#how-it-works" class="w-full sm:w-auto inline-flex items-center justify-center h-12 px-8 rounded-lg border border-white/10 text-white font-medium hover:bg-white/5 transition-colors">
                        <i data-lucide="play-circle" class="size-5 mr-2"></i>
                        See how it works
                    </a>
                </div>

                <p class="text-sm text-neutral-500">
                    Free forever for individuals. No credit card required.
                </p>
            </div>

            {{-- Product Screenshot --}}
            <div class="mt-16 md:mt-20 max-w-5xl mx-auto">
                <div class="relative rounded-xl overflow-hidden border border-white/10 shadow-2xl shadow-black/50">
                    {{-- Browser chrome --}}
                    <div class="h-10 bg-neutral-900 border-b border-white/5 flex items-center px-4 gap-2">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-[#FF5F57]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#FEBC2E]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#28C840]"></div>
                        </div>
                        <div class="flex-1 flex justify-center">
                            <div class="px-4 py-1 rounded-md bg-neutral-800 text-xs text-neutral-400 flex items-center gap-2">
                                <i data-lucide="lock" class="size-3"></i>
                                app.screensense.in
                            </div>
                        </div>
                        <div class="w-12"></div>
                    </div>
                    <img src="/demo/images/dashboard-with-recording-dialog.png" alt="ScreenSense screen recording interface showing recording options" class="w-full h-auto block bg-neutral-950">
                </div>
            </div>
        </div>
    </section>

    {{-- Social Proof - Company logos --}}
    <section class="border-t border-white/5 py-12 md:py-16">
        <div class="max-w-5xl mx-auto px-6">
            <p class="text-center text-sm text-neutral-500 mb-8">Trusted by remote teams at</p>
            <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6 opacity-50">
                {{-- Placeholder logos - replace with actual customer logos --}}
                <div class="text-neutral-400 font-semibold tracking-tight text-lg">Startup Co</div>
                <div class="text-neutral-400 font-semibold tracking-tight text-lg">DevAgency</div>
                <div class="text-neutral-400 font-semibold tracking-tight text-lg">RemoteFirst</div>
                <div class="text-neutral-400 font-semibold tracking-tight text-lg">TechFlow</div>
                <div class="text-neutral-400 font-semibold tracking-tight text-lg">BuildLabs</div>
            </div>
        </div>
    </section>

    {{-- How it Works Section --}}
    <section id="how-it-works" class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-semibold text-white mb-4">How ScreenSense works</h2>
                <p class="text-neutral-400 text-lg max-w-2xl mx-auto">Three steps to replace your next unnecessary meeting</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 md:gap-12">
                {{-- Step 1 --}}
                <div class="text-center md:text-left">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-500/10 text-brand-500 font-semibold text-sm mb-5">1</div>
                    <h3 class="text-xl font-medium text-white mb-3">Record your screen</h3>
                    <p class="text-neutral-400 leading-relaxed">Click record, select a window or your whole screen. Add your microphone to explain what you're showing. Done in seconds.</p>
                </div>

                {{-- Step 2 --}}
                <div class="text-center md:text-left">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-500/10 text-brand-500 font-semibold text-sm mb-5">2</div>
                    <h3 class="text-xl font-medium text-white mb-3">Get a shareable link</h3>
                    <p class="text-neutral-400 leading-relaxed">The moment you stop recording, you get a link. No waiting for uploads. Paste it anywhere — Slack, email, Notion, Linear.</p>
                </div>

                {{-- Step 3 --}}
                <div class="text-center md:text-left">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-500/10 text-brand-500 font-semibold text-sm mb-5">3</div>
                    <h3 class="text-xl font-medium text-white mb-3">They watch when ready</h3>
                    <p class="text-neutral-400 leading-relaxed">No account needed to view. Your video plays instantly in any browser. They watch at 1.5x, skip ahead, or replay the tricky parts.</p>
                </div>
            </div>

            {{-- Demo GIF section --}}
            <div class="mt-20 grid lg:grid-cols-2 gap-8 items-start">
                <div class="space-y-4">
                    <button onclick="showDemo('record')" id="demo-btn-record" class="w-full text-left p-5 rounded-xl border border-brand-500/30 bg-brand-500/5 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center text-brand-500">
                                <i data-lucide="video" class="size-5"></i>
                            </div>
                            <div>
                                <div class="font-medium text-white">Recording a video</div>
                                <p class="text-sm text-neutral-400">Select source, hit record, explain your point</p>
                            </div>
                        </div>
                    </button>

                    <button onclick="showDemo('share')" id="demo-btn-share" class="w-full text-left p-5 rounded-xl border border-white/5 hover:border-white/10 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center text-neutral-400">
                                <i data-lucide="share" class="size-5"></i>
                            </div>
                            <div>
                                <div class="font-medium text-white">Sharing with your team</div>
                                <p class="text-sm text-neutral-400">Copy link, paste anywhere, done</p>
                            </div>
                        </div>
                    </button>
                </div>

                <div class="rounded-xl overflow-hidden border border-white/10 bg-neutral-900">
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

    {{-- Features Section --}}
    <section id="features" class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-semibold text-white mb-4">Everything you need to communicate async</h2>
                <p class="text-neutral-400 text-lg max-w-2xl mx-auto">No bloated feature list. Just the essentials, done well.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Feature 1 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="zap" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">Instant links</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">Your video link is ready the moment you stop recording. Share it before you even close the tab.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="mic" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">Screen + audio</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">Record your screen with microphone audio. Show and tell at the same time — way clearer than screenshots.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="user-x" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">No viewer signup</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">Anyone with the link can watch. No login walls, no "create an account to view" nonsense.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="monitor" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">HD recording</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">1080p capture so every detail is visible. Code, designs, spreadsheets — all crystal clear.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="play" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">Fast playback</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">HLS adaptive streaming means videos start playing immediately, even on slow connections.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="feature-card p-6 rounded-xl border border-white/5 bg-white/[0.02]">
                    <div class="w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center mb-4">
                        <i data-lucide="sparkles" class="size-5 text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">No watermarks</h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">Your videos look professional. No logos stamped on your content, even on the free plan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Chrome Extension Banner --}}
    <section class="py-12 md:py-16">
        <div class="max-w-4xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8 p-8 rounded-2xl border border-white/10 bg-gradient-to-r from-neutral-900 to-neutral-900/50">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center shrink-0">
                        <i data-lucide="chrome" class="size-7 text-neutral-900"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-white mb-1">Chrome extension</h3>
                        <p class="text-neutral-400">Record from your browser toolbar. One click to start.</p>
                    </div>
                </div>
                <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="shrink-0 inline-flex items-center gap-2 h-11 px-6 bg-white text-neutral-900 rounded-lg font-medium hover:bg-neutral-100 transition-colors">
                    Add to Chrome
                    <i data-lucide="external-link" class="size-4"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Pricing Section --}}
    <section id="pricing" class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-semibold text-white mb-4">Simple pricing</h2>
                <p class="text-neutral-400 text-lg">Start free. Upgrade when your team grows.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Free Plan --}}
                <div class="pricing-card p-8 rounded-2xl border border-white/10 bg-neutral-900/30">
                    <div class="mb-6">
                        <h3 class="text-xl font-medium text-white mb-1">Free</h3>
                        <p class="text-sm text-neutral-500">For individuals getting started</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-4xl font-semibold text-white">$0</span>
                        <span class="text-neutral-500">/month</span>
                    </div>
                    <ul class="space-y-3 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            10 videos
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            5 minutes per recording
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            1 GB storage
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            No watermarks
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="block w-full py-3 rounded-lg border border-white/10 text-center font-medium text-white hover:bg-white/5 transition-colors">
                        Get started
                    </a>
                </div>

                {{-- Pro Plan --}}
                <div class="pricing-card p-8 rounded-2xl border border-brand-500/30 bg-neutral-900/50 relative">
                    <div class="absolute -top-3 left-6 px-3 py-1 rounded-full bg-brand-600 text-xs font-medium text-white">
                        Most popular
                    </div>
                    <div class="mb-6">
                        <h3 class="text-xl font-medium text-white mb-1">Pro</h3>
                        <p class="text-sm text-neutral-400">For power users and creators</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-4xl font-semibold text-white">$8</span>
                        <span class="text-neutral-500">/month</span>
                    </div>
                    <ul class="space-y-3 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-white">
                            <i data-lucide="check" class="size-4 text-brand-500"></i>
                            Unlimited videos
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <i data-lucide="check" class="size-4 text-brand-500"></i>
                            Unlimited recording length
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <i data-lucide="check" class="size-4 text-brand-500"></i>
                            50 GB storage
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <i data-lucide="check" class="size-4 text-brand-500"></i>
                            HLS adaptive streaming
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <i data-lucide="check" class="size-4 text-brand-500"></i>
                            Priority support
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary block w-full py-3 rounded-lg bg-brand-600 text-center font-medium text-white hover:bg-brand-500 transition-colors">
                        Upgrade to Pro
                    </a>
                </div>

                {{-- Team Plan --}}
                <div class="pricing-card p-8 rounded-2xl border border-white/10 bg-neutral-900/30">
                    <div class="mb-6">
                        <h3 class="text-xl font-medium text-white mb-1">Team</h3>
                        <p class="text-sm text-neutral-500">For growing teams</p>
                    </div>
                    <div class="mb-6">
                        <span class="text-4xl font-semibold text-white">$39</span>
                        <span class="text-neutral-500">/month</span>
                    </div>
                    <ul class="space-y-3 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            Up to 5 team members
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            100 GB shared storage
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            Shared video library
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            Team workspaces
                        </li>
                        <li class="flex items-center gap-3 text-neutral-300">
                            <i data-lucide="check" class="size-4 text-neutral-500"></i>
                            Admin controls
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="block w-full py-3 rounded-lg border border-white/10 text-center font-medium text-white hover:bg-white/5 transition-colors">
                        Get Team
                    </a>
                </div>
            </div>

            <p class="text-center text-neutral-500 text-sm mt-10">
                Need more seats or custom features? <a href="mailto:contact@gurpreetkait.in" class="text-white hover:text-brand-400 transition-colors">Talk to us</a>
            </p>

            <div class="text-center mt-12">
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary inline-flex items-center justify-center h-12 px-8 rounded-lg bg-brand-600 text-white font-medium hover:bg-brand-500">
                    Record Now
                </a>
            </div>
        </div>
    </section>

    {{-- Use Cases Section --}}
    <section class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-semibold text-white mb-4">Built for async-first teams</h2>
                <p class="text-neutral-400 text-lg max-w-2xl mx-auto">Replace meetings, not productivity</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="p-8 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <h3 class="text-lg font-medium text-white mb-3">Engineering teams</h3>
                    <p class="text-neutral-400 leading-relaxed">Record PR walkthroughs, explain architecture decisions, or demo a bug fix. Your teammates review when they're in the zone, not when your calendar says so.</p>
                </div>

                <div class="p-8 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <h3 class="text-lg font-medium text-white mb-3">Design reviews</h3>
                    <p class="text-neutral-400 leading-relaxed">Walk through your Figma files with context. Stakeholders watch the full explanation instead of asking "what does this button do?" in comments.</p>
                </div>

                <div class="p-8 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <h3 class="text-lg font-medium text-white mb-3">Customer support</h3>
                    <p class="text-neutral-400 leading-relaxed">Show customers exactly how to fix their issue. A 30-second video beats a 500-word email every time.</p>
                </div>

                <div class="p-8 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <h3 class="text-lg font-medium text-white mb-3">Remote onboarding</h3>
                    <p class="text-neutral-400 leading-relaxed">Create reusable training videos for new hires. They watch at their own pace and can replay the tricky parts.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-3xl mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-semibold text-white mb-12 text-center">Frequently asked questions</h2>

            <div class="space-y-8">
                <div>
                    <h3 class="text-lg font-medium text-white mb-2">Is ScreenSense really free?</h3>
                    <p class="text-neutral-400 leading-relaxed">Yes. The free plan includes 10 recordings with no watermarks. After that, Pro is $8/month for unlimited videos. We don't do the "free trial then surprise you with a paywall" thing.</p>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-white mb-2">Do people need an account to watch my videos?</h3>
                    <p class="text-neutral-400 leading-relaxed">No. Anyone with the link can watch immediately in their browser. No signup, no app download, no friction.</p>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-white mb-2">How long can my recordings be?</h3>
                    <p class="text-neutral-400 leading-relaxed">Free plan: 5 minutes per video. Pro and Team: no limit. Most async updates are under 3 minutes anyway.</p>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-white mb-2">What browsers are supported?</h3>
                    <p class="text-neutral-400 leading-relaxed">Chrome, Firefox, Safari, and Edge. We also have a <a href="https://chromewebstore.google.com/detail/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener noreferrer" class="text-white underline hover:text-brand-400">Chrome extension</a> for one-click recording.</p>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-white mb-2">How is this different from Loom?</h3>
                    <p class="text-neutral-400 leading-relaxed">Simpler pricing, no aggressive upsells, and a free tier that actually lets you use the product. We're focused on doing one thing well: helping you share quick video explanations with your team.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-20 md:py-28 border-t border-white/5">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-semibold text-white mb-6">Ready to skip your next meeting?</h2>
            <p class="text-lg text-neutral-400 mb-10">Record your first video in under a minute. Free, no credit card.</p>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary inline-flex items-center justify-center h-14 px-10 rounded-lg bg-brand-600 text-white text-lg font-medium hover:bg-brand-500">
                Start recording — it's free
            </a>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    function showDemo(type) {
        const recordBtn = document.getElementById('demo-btn-record');
        const shareBtn = document.getElementById('demo-btn-share');
        const recordDemo = document.getElementById('demo-record');
        const shareDemo = document.getElementById('demo-share');

        const activeClasses = 'w-full text-left p-5 rounded-xl border border-brand-500/30 bg-brand-500/5 transition-all';
        const inactiveClasses = 'w-full text-left p-5 rounded-xl border border-white/5 hover:border-white/10 transition-all';

        if (type === 'record') {
            recordBtn.className = activeClasses;
            shareBtn.className = inactiveClasses;
            recordBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center text-brand-500';
            shareBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center text-neutral-400';
            recordDemo.classList.remove('hidden');
            shareDemo.classList.add('hidden');
        } else {
            shareBtn.className = activeClasses;
            recordBtn.className = inactiveClasses;
            shareBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-brand-500/10 flex items-center justify-center text-brand-500';
            recordBtn.querySelector('.w-10').className = 'w-10 h-10 rounded-lg bg-neutral-800 flex items-center justify-center text-neutral-400';
            shareDemo.classList.remove('hidden');
            recordDemo.classList.add('hidden');
        }
    }
</script>
@endpush
