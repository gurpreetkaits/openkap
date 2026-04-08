@php $light = $lightMode ?? false; @endphp

<footer class="border-t {{ $light ? 'border-gray-200/60 bg-white' : 'border-white/10 bg-neutral-950' }} mt-auto">
    <div class="max-w-7xl mx-auto px-6 py-14">

        {{-- Top row: brand left, link columns right --}}
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-12 mb-12">

            {{-- Left: logo + tagline + creator + socials --}}
            <div class="flex flex-col gap-4 max-w-xs">
                <div class="flex items-center gap-2.5">
                    <img src="/logo.png" alt="OpenKap" class="size-6 rounded">
                    <span class="text-sm font-semibold {{ $light ? 'text-gray-900' : 'text-white' }}">OpenKap</span>
                </div>
                <p class="text-xs leading-relaxed {{ $light ? 'text-gray-400' : 'text-neutral-500' }}">
                    Record it, share it, move on.<br>Screen recording for async teams.
                </p>

                {{-- Creator --}}
                <div class="flex items-center gap-2 pt-1">
                    <span class="text-xs {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">Made by</span>
                    <a href="https://gurpreetkait.in" target="_blank" rel="noopener noreferrer"
                        class="text-xs font-semibold {{ $light ? 'text-gray-700 hover:text-gray-900' : 'text-neutral-300 hover:text-white' }} transition-colors">
                        Gurpreet Kait
                    </a>
                </div>

                {{-- Social icons --}}
                <div class="flex items-center gap-3">
                    {{-- X (Twitter) --}}
                    <a href="https://x.com/gurpreetkait" target="_blank" rel="noopener noreferrer"
                        class="{{ $light ? 'text-gray-400 hover:text-gray-900' : 'text-neutral-500 hover:text-white' }} transition-colors"
                        title="X / Twitter">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622 5.911-5.622Zm-1.161 17.52h1.833L7.084 4.126H5.117Z"/></svg>
                    </a>
                    {{-- Website --}}
                    <a href="https://gurpreetkait.in" target="_blank" rel="noopener noreferrer"
                        class="{{ $light ? 'text-gray-400 hover:text-gray-900' : 'text-neutral-500 hover:text-white' }} transition-colors"
                        title="Personal website">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </a>
                    {{-- Email --}}
                    <a href="mailto:contact@gurpreetkait.in"
                        class="{{ $light ? 'text-gray-400 hover:text-gray-900' : 'text-neutral-500 hover:text-white' }} transition-colors"
                        title="Email">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </a>
                    {{-- Discord --}}
                    <a href="https://discord.gg/Y2mq4V5DBz" target="_blank" rel="noopener noreferrer"
                        class="{{ $light ? 'text-gray-400 hover:text-gray-900' : 'text-neutral-500 hover:text-white' }} transition-colors"
                        title="Discord">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.03.056a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03ZM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418Zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418Z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Right: link columns --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest mb-4 {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">Product</p>
                    <ul class="flex flex-col gap-2.5">
                        <li><a href="#how-it-works" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">How it works</a></li>
                        <li><a href="#pricing" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Pricing</a></li>
                        <li><a href="/changelog" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Changelog</a></li>
                        <li><a href="/blog" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest mb-4 {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">Compare</p>
                    <ul class="flex flex-col gap-2.5">
                        <li><a href="/alternative/loom" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">vs Loom</a></li>
                        <li><a href="/alternative/cap" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">vs Cap</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest mb-4 {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">Company</p>
                    <ul class="flex flex-col gap-2.5">
                        <li><a href="/about" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">About</a></li>
                        <li><a href="/contact" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Contact</a></li>
                        <li><a href="/privacy-policy" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms" class="text-xs {{ $light ? 'text-gray-500 hover:text-gray-900' : 'text-neutral-400 hover:text-white' }} transition-colors">Terms</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t {{ $light ? 'border-gray-100' : 'border-white/5' }} pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">&copy; {{ date('Y') }} OpenKap. All rights reserved.</span>
            <span class="text-xs {{ $light ? 'text-gray-400' : 'text-neutral-600' }}">Built with ☕ by <a href="https://gurpreetkait.in" target="_blank" rel="noopener noreferrer" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors font-medium">Gurpreet Kait</a></span>
        </div>

    </div>
</footer>
