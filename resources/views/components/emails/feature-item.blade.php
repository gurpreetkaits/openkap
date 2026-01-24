@props([
    'icon' => 'solar:bolt-linear',
    'title',
    'description',
])
<!-- Feature Item -->
<div class="flex gap-4">
    <div class="mt-0.5 w-8 h-8 rounded-lg bg-zinc-50 border border-zinc-100 flex items-center justify-center shrink-0 text-zinc-900">
        <iconify-icon icon="{{ $icon }}" class="text-base"></iconify-icon>
    </div>
    <div>
        <h3 class="text-sm font-semibold text-zinc-900 tracking-tight">{{ $title }}</h3>
        <p class="text-sm text-zinc-500 mt-1 leading-relaxed">{{ $description }}</p>
    </div>
</div>
