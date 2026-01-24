@props([
    'badgeIcon' => 'solar:stars-minimalistic-linear',
    'badgeText' => 'Product Update',
    'headline',
    'subheadline' => null,
    'description',
    'features' => [],
    'ctaText' => 'Learn More',
    'ctaUrl' => '#',
    'ctaIcon' => 'solar:arrow-right-linear',
    'showVisualCard' => true,
    'visualCardItems' => [],
    'previewText' => null,
])

<x-emails.layout :title="$headline" :preview-text="$previewText ?? $description">

    <!-- Badge -->
    <x-emails.badge :icon="$badgeIcon" :text="$badgeText" />

    <!-- Headline -->
    <h1 class="text-[32px] font-semibold tracking-tighter text-brand-600 leading-[1.15] mb-4">
        {!! nl2br(e($headline)) !!}
        @if($subheadline)
        <br>{!! nl2br(e($subheadline)) !!}
        @endif
    </h1>

    <!-- Main Copy -->
    <p class="text-[15px] text-zinc-500 leading-relaxed mb-8 font-normal max-w-md">
        {{ $description }}
    </p>

    <!-- Visual Asset -->
    @if($showVisualCard)
    <x-emails.visual-card :items="$visualCardItems" />
    @endif

    <!-- Feature List -->
    @if(count($features) > 0)
    <div class="space-y-5 mb-10">
        @foreach($features as $feature)
        <x-emails.feature-item
            :icon="$feature['icon'] ?? 'solar:bolt-linear'"
            :title="$feature['title']"
            :description="$feature['description']"
        />
        @endforeach
    </div>
    @endif

    <!-- CTA -->
    <x-emails.button :url="$ctaUrl" :icon="$ctaIcon">
        {{ $ctaText }}
    </x-emails.button>

</x-emails.layout>
