@props([
    'url' => '#',
    'icon' => 'solar:arrow-right-linear',
])
<!-- CTA Button -->
<a href="{{ $url }}" class="group block w-full bg-brand-600 hover:bg-brand-700 text-white text-center py-3.5 rounded-lg text-sm font-medium transition-all shadow-[0_2px_10px_rgba(234,88,12,0.3)] hover:shadow-[0_4px_16px_rgba(234,88,12,0.4)] mb-8">
    <span class="flex items-center justify-center gap-2">
        {{ $slot }}
        @if($icon)
        <iconify-icon icon="{{ $icon }}" class="group-hover:translate-x-0.5 transition-transform"></iconify-icon>
        @endif
    </span>
</a>
