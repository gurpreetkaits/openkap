@props([
    'twitterUrl' => null,
    'githubUrl' => 'https://github.com/gurpreetkaits/screensense',
    'websiteUrl' => null,
    'unsubscribeUrl' => null,
    'preferencesUrl' => null,
])
<!-- Footer -->
<div class="bg-zinc-50 px-8 py-8 border-t border-zinc-100">
    <div class="flex justify-center gap-5 mb-6">
        @if($twitterUrl)
        <a href="{{ $twitterUrl }}" class="text-zinc-400 hover:text-zinc-900 transition-colors">
            <iconify-icon icon="mdi:twitter" class="text-lg"></iconify-icon>
        </a>
        @endif
        @if($githubUrl)
        <a href="{{ $githubUrl }}" class="text-zinc-400 hover:text-zinc-900 transition-colors">
            <iconify-icon icon="mdi:github" class="text-lg"></iconify-icon>
        </a>
        @endif
        @if($websiteUrl)
        <a href="{{ $websiteUrl }}" class="text-zinc-400 hover:text-zinc-900 transition-colors">
            <iconify-icon icon="solar:globe-linear" class="text-lg"></iconify-icon>
        </a>
        @endif
    </div>

    @if($unsubscribeUrl || $preferencesUrl)
    <div class="text-center">
        <div class="flex justify-center gap-3 text-[11px] text-zinc-400">
            @if($unsubscribeUrl)
            <a href="{{ $unsubscribeUrl }}" class="hover:text-zinc-600 underline decoration-zinc-300 underline-offset-2">Unsubscribe</a>
            @endif
            @if($unsubscribeUrl && $preferencesUrl)
            <span>&bull;</span>
            @endif
            @if($preferencesUrl)
            <a href="{{ $preferencesUrl }}" class="hover:text-zinc-600 underline decoration-zinc-300 underline-offset-2">Preferences</a>
            @endif
        </div>
    </div>
    @endif
</div>
