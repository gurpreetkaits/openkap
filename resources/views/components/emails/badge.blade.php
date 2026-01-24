@props([
    'icon' => 'solar:stars-minimalistic-linear',
    'text' => 'Update',
])
<!-- Badge -->
<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-zinc-50 border border-zinc-200 mb-6">
    <iconify-icon icon="{{ $icon }}" class="text-zinc-900 text-xs"></iconify-icon>
    <span class="text-[10px] font-semibold text-zinc-600 tracking-wide uppercase">{{ $text }}</span>
</div>
