@props([
    'items' => [],
])
<!-- Visual Asset (CSS Generated UI Mockup) -->
<div class="w-full bg-zinc-50 rounded-xl border border-zinc-100 mb-8 overflow-hidden relative">
    <div class="absolute inset-0 bg-[radial-gradient(#e4e4e7_1px,transparent_1px)] [background-size:16px_16px] opacity-40"></div>

    <div class="relative p-6 flex justify-center items-center min-h-[200px]">
        <!-- Abstract Card -->
        <div class="bg-white rounded-lg border border-zinc-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] w-3/4 p-4 z-10">
            <div class="flex items-center justify-between mb-4 border-b border-zinc-50 pb-3">
                <div class="flex gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-zinc-200"></div>
                    <div class="w-2 h-2 rounded-full bg-zinc-100"></div>
                </div>
                <div class="w-12 h-1.5 rounded-full bg-zinc-100"></div>
            </div>
            <div class="space-y-2.5">
                @if(count($items) > 0)
                    @foreach($items as $item)
                    <div class="flex items-center gap-3 p-2 rounded bg-zinc-50/50 border border-zinc-100/50">
                        <iconify-icon icon="{{ $item['icon'] ?? 'solar:widget-linear' }}" class="text-zinc-400 text-xs"></iconify-icon>
                        <div class="h-2 rounded bg-zinc-200" style="width: {{ $item['width'] ?? '6rem' }}"></div>
                    </div>
                    @endforeach
                @else
                    <div class="flex items-center gap-3 p-2 rounded bg-zinc-50/50 border border-zinc-100/50">
                        <iconify-icon icon="solar:filter-linear" class="text-zinc-400 text-xs"></iconify-icon>
                        <div class="w-24 h-2 rounded bg-zinc-200"></div>
                    </div>
                    <div class="flex items-center gap-3 p-2 rounded bg-zinc-50/50 border border-zinc-100/50">
                        <iconify-icon icon="solar:sort-by-time-linear" class="text-zinc-400 text-xs"></iconify-icon>
                        <div class="w-32 h-2 rounded bg-zinc-200"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
