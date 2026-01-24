@props([
    'logoUrl' => null,
    'appName' => 'ScreenSense',
    'viewInBrowserUrl' => null,
])
<!-- Header -->
<div class="px-8 pt-8 pb-6 flex justify-between items-center">
    <!-- Logo -->
    <a href="{{ $logoUrl ?? config('app.url') }}" class="flex items-center gap-2.5 group cursor-pointer">
        <img src="{{ url('/logo.png') }}" alt="{{ $appName }}" class="w-8 h-8 rounded-lg">
        <span class="text-sm font-semibold tracking-tight text-zinc-900">{{ $appName }}</span>
    </a>

    @if($viewInBrowserUrl)
    <a href="{{ $viewInBrowserUrl }}" class="text-[10px] font-medium text-zinc-400 hover:text-zinc-600 uppercase tracking-wide transition-colors">
        View in browser
    </a>
    @endif
</div>
