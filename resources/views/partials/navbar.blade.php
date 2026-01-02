<!-- Navigation -->
<nav class="fixed top-0 w-full z-50 border-b border-white/5 bg-brand-950/80 backdrop-blur-xl transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2 group cursor-pointer">
            <img src="/logo.png" alt="ScreenSense" class="size-8 rounded-lg shadow-[0_0_15px_-3px_rgba(249,115,22,0.4)] group-hover:shadow-[0_0_25px_-5px_rgba(249,115,22,0.6)] transition-all duration-300">
            <span class="text-lg font-medium tracking-tight text-white">ScreenSense</span>
        </a>

        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-neutral-400">
            <a href="/#how-it-works" class="hover:text-white transition-colors">How it works</a>
            <a href="/#features" class="hover:text-white transition-colors">Features</a>
            <a href="/#pricing" class="hover:text-white transition-colors">Pricing</a>
            <a href="/blog" class="hover:text-white transition-colors {{ request()->is('blog*') ? 'text-white' : '' }}">Blog</a>
        </div>

        <div class="flex items-center gap-4">
            <!-- GitHub Button -->
            <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer" class="hidden sm:flex items-center gap-2 text-xs font-medium bg-white/5 border border-white/10 px-3 py-1.5 rounded-full hover:bg-white/10 transition-colors group">
                <i data-lucide="github" class="size-4 text-neutral-400 group-hover:text-white transition-colors"></i>
                <span class="text-neutral-300 group-hover:text-white">Star</span>
                <div class="w-px h-3 bg-white/10 mx-1"></div>
                <span id="github-stars-nav" class="text-neutral-500 group-hover:text-brand-400 transition-colors">--</span>
            </a>

            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="text-sm font-medium bg-white text-black px-4 py-2 rounded-full hover:bg-neutral-200 transition-colors shadow-[0_0_15px_-3px_rgba(255,255,255,0.3)]">
                Sign In
            </a>
        </div>
    </div>
</nav>
