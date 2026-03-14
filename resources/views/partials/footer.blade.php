@php $light = $lightMode ?? false; @endphp

<!-- Footer -->
<footer class="border-t {{ $light ? 'border-gray-200/60 bg-gray-50' : 'border-white/10 bg-neutral-950' }} py-12 mt-auto">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-3">
            <img src="/logo.png" alt="OpenKap" class="size-5 rounded">
            <span class="text-xs font-medium {{ $light ? 'text-gray-400' : 'text-neutral-500' }}">&copy; {{ date('Y') }} OpenKap</span>
        </div>
        <div class="flex gap-6 text-xs {{ $light ? 'text-gray-400' : 'text-neutral-500' }} font-medium">
            <a href="/about" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors">About</a>
            <a href="/contact" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors">Contact</a>
            <a href="/privacy-policy" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors">Privacy</a>
            <a href="/terms" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors">Terms</a>
            <a href="https://discord.gg/Y2mq4V5DBz" target="_blank" rel="noopener noreferrer" class="{{ $light ? 'hover:text-gray-900' : 'hover:text-white' }} transition-colors">Discord</a>
        </div>
    </div>
</footer>
