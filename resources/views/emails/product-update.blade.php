@props([
    'badgeText' => 'Product Update',
    'headline',
    'subheadline' => null,
    'description',
    'features' => [],
    'ctaText' => 'Learn More',
    'ctaUrl' => '#',
    'showVisualCard' => true,
    'visualCardItems' => [],
    'previewText' => null,
])

<x-emails.layout :title="$headline" :preview-text="$previewText ?? $description">

    <!-- Badge -->
    <x-emails.badge :text="$badgeText" />

    <!-- Headline -->
    <h1 style="margin: 0 0 12px 0; font-size: 28px; font-weight: 600; color: #ea580c; line-height: 1.2; letter-spacing: -0.025em;">
        {!! nl2br(e($headline)) !!}
        @if($subheadline)
        <br><span style="color: #18181b;">{!! nl2br(e($subheadline)) !!}</span>
        @endif
    </h1>

    <!-- Main Copy -->
    <p style="margin: 0 0 24px 0; font-size: 15px; color: #71717a; line-height: 1.6;">
        {{ $description }}
    </p>

    <!-- Visual Asset -->
    @if($showVisualCard)
    <x-emails.visual-card :items="$visualCardItems" />
    @endif

    <!-- Feature List -->
    @if(count($features) > 0)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        @foreach($features as $feature)
        <tr>
            <td style="padding-bottom: 16px;">
                <x-emails.feature-item
                    :title="$feature['title']"
                    :description="$feature['description']"
                />
            </td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- CTA -->
    <x-emails.button :url="$ctaUrl">
        {{ $ctaText }}
    </x-emails.button>

</x-emails.layout>
