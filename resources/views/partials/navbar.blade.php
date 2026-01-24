<!-- Navigation -->
<nav class="fixed top-0 w-full z-50 border-b border-white/5 bg-brand-950/80 backdrop-blur-xl transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2 group cursor-pointer">
            <img src="/logo.png" alt="ScreenSense" class="size-8 rounded-lg shadow-[0_0_15px_-3px_rgba(249,115,22,0.4)] group-hover:shadow-[0_0_25px_-5px_rgba(249,115,22,0.6)] transition-all duration-300">
            <span class="text-lg font-medium tracking-tight text-white">ScreenSense</span>
        </a>

        <!-- Desktop Nav Links -->
        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-neutral-400">
            <a href="/#how-it-works" class="hover:text-white transition-colors">How it works</a>
            <a href="/#features" class="hover:text-white transition-colors">Features</a>
            <a href="/#pricing" class="hover:text-white transition-colors">Pricing</a>
            <!-- Compare Dropdown -->
            <div class="relative group" id="compare-dropdown">
                <button type="button" class="flex items-center gap-1 hover:text-white transition-colors {{ request()->is('compare/*') ? 'text-white' : '' }}">
                    Compare
                    <i data-lucide="chevron-down" class="size-3.5 transition-transform group-hover:rotate-180"></i>
                </button>
                <div class="absolute top-full left-0 pt-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                    <div class="py-2 bg-brand-950 border border-white/10 rounded-lg shadow-xl">
                        <a href="/compare/loom" class="block px-4 py-2 text-sm text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('compare/loom') ? 'text-white bg-white/5' : '' }}">
                            vs Loom
                        </a>
                        <a href="/compare/cap" class="block px-4 py-2 text-sm text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('compare/cap') ? 'text-white bg-white/5' : '' }}">
                            vs Cap.so
                        </a>
                    </div>
                </div>
            </div>
            <!-- Resources Dropdown -->
            <div class="relative group">
                <button type="button" class="flex items-center gap-1 hover:text-white transition-colors {{ request()->is('blog*') || request()->is('changelog') ? 'text-white' : '' }}">
                    Resources
                    <i data-lucide="chevron-down" class="size-3.5 transition-transform group-hover:rotate-180"></i>
                </button>
                <div class="absolute top-full left-0 pt-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                    <div class="py-2 bg-brand-950 border border-white/10 rounded-lg shadow-xl">
                        <a href="/blog" class="block px-4 py-2 text-sm text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('blog*') ? 'text-white bg-white/5' : '' }}">
                            Blog
                        </a>
                        <a href="/changelog" class="block px-4 py-2 text-sm text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('changelog') ? 'text-white bg-white/5' : '' }}">
                            Changelog
                        </a>
                    </div>
                </div>
            </div>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/feedback" class="hover:text-white transition-colors">Feedback</a>
        </div>

        <div class="flex items-center gap-3">
            <!-- Sign In Button -->
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="hidden sm:block text-sm font-medium bg-white text-black px-4 py-2 rounded-full hover:bg-neutral-200 transition-colors shadow-[0_0_15px_-3px_rgba(255,255,255,0.3)]">
                Sign In
            </a>

            <!-- Mobile Hamburger Button -->
            <button id="mobile-menu-btn" class="md:hidden flex items-center justify-center size-10 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                <i data-lucide="menu" class="size-5 text-white" id="menu-icon"></i>
                <i data-lucide="x" class="size-5 text-white hidden" id="close-icon"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="md:hidden hidden border-t border-white/5 bg-brand-950/95 backdrop-blur-xl">
        <div class="px-6 py-4 space-y-1">
            <a href="/#how-it-works" class="block px-4 py-3 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors">
                How it works
            </a>
            <a href="/#features" class="block px-4 py-3 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors">
                Features
            </a>
            <a href="/#pricing" class="block px-4 py-3 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors">
                Pricing
            </a>
            <!-- Compare Section -->
            <div class="py-2">
                <div class="px-4 py-2 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Compare</div>
                <a href="/compare/loom" class="block px-4 py-2 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('compare/loom') ? 'text-white bg-white/5' : '' }}">
                    vs Loom
                </a>
                <a href="/compare/cap" class="block px-4 py-2 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('compare/cap') ? 'text-white bg-white/5' : '' }}">
                    vs Cap.so
                </a>
            </div>
            <!-- Resources Section -->
            <div class="py-2">
                <div class="px-4 py-2 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Resources</div>
                <a href="/blog" class="block px-4 py-2 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('blog*') ? 'text-white bg-white/5' : '' }}">
                    Blog
                </a>
                <a href="/changelog" class="block px-4 py-2 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors {{ request()->is('changelog') ? 'text-white bg-white/5' : '' }}">
                    Changelog
                </a>
            </div>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/feedback" class="block px-4 py-3 rounded-lg text-sm font-medium text-neutral-300 hover:text-white hover:bg-white/5 transition-colors">
                Feedback
            </a>

            <div class="pt-4 mt-4 border-t border-white/5 space-y-3">
                <!-- Sign In Mobile -->
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="flex items-center justify-center w-full px-4 py-3 rounded-lg text-sm font-medium bg-white text-black hover:bg-neutral-200 transition-colors">
                    Sign In
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                menuIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });

            // Close menu when clicking a link
            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                });
            });
        }

    });
</script>
