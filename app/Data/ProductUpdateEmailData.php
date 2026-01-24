<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ProductUpdateEmailData extends Data
{
    /**
     * @param  string  $headline  Main headline (use \n for line breaks)
     * @param  string  $description  Main description paragraph
     * @param  array<array{icon?: string, title: string, description: string}>  $features  List of features
     * @param  string  $ctaText  Call-to-action button text
     * @param  string  $ctaUrl  Call-to-action URL
     * @param  string|null  $subheadline  Optional second line of headline
     * @param  string  $badgeText  Badge text (e.g., "Product Update 2.0")
     * @param  string  $badgeIcon  Iconify icon for badge
     * @param  string  $ctaIcon  Iconify icon for CTA button
     * @param  bool  $showVisualCard  Whether to show the visual card mockup
     * @param  array<array{icon?: string, width?: string}>  $visualCardItems  Items for visual card
     * @param  string|null  $previewText  Email preview text
     */
    public function __construct(
        public string $headline,
        public string $description,
        public array $features = [],
        public string $ctaText = 'Learn More',
        public string $ctaUrl = '#',
        public ?string $subheadline = null,
        public string $badgeText = 'Product Update',
        public string $badgeIcon = 'solar:stars-minimalistic-linear',
        public string $ctaIcon = 'solar:arrow-right-linear',
        public bool $showVisualCard = true,
        public array $visualCardItems = [],
        public ?string $previewText = null,
    ) {}

    /**
     * Create a simple product update
     */
    public static function simple(
        string $headline,
        string $description,
        string $ctaText,
        string $ctaUrl
    ): self {
        return new self(
            headline: $headline,
            description: $description,
            ctaText: $ctaText,
            ctaUrl: $ctaUrl,
            showVisualCard: false,
        );
    }

    /**
     * Create a feature announcement
     */
    public static function featureAnnouncement(
        string $headline,
        string $description,
        array $features,
        string $ctaText,
        string $ctaUrl,
        ?string $subheadline = null
    ): self {
        return new self(
            headline: $headline,
            description: $description,
            features: $features,
            ctaText: $ctaText,
            ctaUrl: $ctaUrl,
            subheadline: $subheadline,
            badgeText: 'New Feature',
            badgeIcon: 'solar:star-bold',
        );
    }
}
