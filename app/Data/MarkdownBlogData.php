<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class MarkdownBlogData extends Data
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $content,
        public string $rawContent,
        public ?string $excerpt = null,
        public string $author = 'ScreenSense Team',
        public ?string $category = null,
        /** @var string[] */
        public array $tags = [],
        public ?string $featuredImage = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public int $readTime = 1,
        public bool $isPublished = true,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $publishedAt = null,
        public ?string $filePath = null,
    ) {}

    public function getUrl(): string
    {
        return "/articles/{$this->slug}";
    }
}
