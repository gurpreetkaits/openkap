<?php

namespace App\Managers;

use App\Data\MarkdownBlogData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownBlogManager
{
    protected MarkdownConverter $converter;

    protected string $blogPath;

    public function __construct()
    {
        $this->blogPath = public_path('blogs');
        $this->initializeConverter();
    }

    protected function initializeConverter(): void
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Get all published blog posts, sorted by published_at desc
     */
    public function getAllPosts(?int $limit = null): Collection
    {
        $posts = collect();

        if (! is_dir($this->blogPath)) {
            return $posts;
        }

        $files = glob($this->blogPath.'/*.md');

        foreach ($files as $file) {
            $post = $this->parseFile($file);
            if ($post && $post->isPublished && $this->isPostPublished($post)) {
                $posts->push($post);
            }
        }

        $posts = $posts->sortByDesc('publishedAt')->values();

        return $limit ? $posts->take($limit) : $posts;
    }

    /**
     * Get a single post by slug
     */
    public function getPostBySlug(string $slug): ?MarkdownBlogData
    {
        if (! is_dir($this->blogPath)) {
            return null;
        }

        $files = glob($this->blogPath.'/*.md');

        foreach ($files as $file) {
            $post = $this->parseFile($file);
            if ($post && $post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Get recent posts
     */
    public function getRecentPosts(int $limit = 3): Collection
    {
        return $this->getAllPosts($limit);
    }

    /**
     * Get posts by category
     */
    public function getPostsByCategory(string $category): Collection
    {
        return $this->getAllPosts()->filter(
            fn (MarkdownBlogData $post) => strtolower($post->category ?? '') === strtolower($category)
        )->values();
    }

    /**
     * Get posts by tag
     */
    public function getPostsByTag(string $tag): Collection
    {
        return $this->getAllPosts()->filter(
            fn (MarkdownBlogData $post) => in_array(strtolower($tag), array_map('strtolower', $post->tags))
        )->values();
    }

    /**
     * Parse a markdown file into MarkdownBlogData
     */
    protected function parseFile(string $filePath): ?MarkdownBlogData
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $result = $this->converter->convert($content);

        if (! $result instanceof RenderedContentWithFrontMatter) {
            return null;
        }

        $frontMatter = $result->getFrontMatter();
        $htmlContent = $result->getContent();

        // Extract raw markdown (content without frontmatter)
        $rawContent = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $content);

        // Calculate read time if not provided
        $readTime = $frontMatter['read_time'] ?? $this->calculateReadTime($rawContent);

        // Generate excerpt if not provided
        $excerpt = $frontMatter['excerpt'] ?? $this->generateExcerpt($rawContent);

        return new MarkdownBlogData(
            title: $frontMatter['title'] ?? basename($filePath, '.md'),
            slug: $frontMatter['slug'] ?? $this->generateSlug(basename($filePath, '.md')),
            content: $htmlContent,
            rawContent: $rawContent,
            excerpt: $excerpt,
            author: $frontMatter['author'] ?? 'OpenKap Team',
            category: $frontMatter['category'] ?? null,
            tags: $frontMatter['tags'] ?? [],
            featuredImage: $frontMatter['featured_image'] ?? null,
            metaTitle: $frontMatter['meta_title'] ?? $frontMatter['title'] ?? null,
            metaDescription: $frontMatter['meta_description'] ?? $excerpt,
            readTime: $readTime,
            isPublished: $frontMatter['is_published'] ?? true,
            publishedAt: isset($frontMatter['published_at'])
                ? Carbon::parse($frontMatter['published_at'])
                : Carbon::now(),
            filePath: $filePath,
        );
    }

    /**
     * Check if post is published (based on published_at date)
     */
    protected function isPostPublished(MarkdownBlogData $post): bool
    {
        if (! $post->publishedAt) {
            return true;
        }

        return $post->publishedAt->lte(now());
    }

    /**
     * Calculate estimated read time in minutes
     */
    protected function calculateReadTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $readTime = ceil($wordCount / 200);

        return max(1, (int) $readTime);
    }

    /**
     * Generate excerpt from content
     */
    protected function generateExcerpt(string $content, int $length = 160): string
    {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }

    /**
     * Generate slug from filename
     */
    protected function generateSlug(string $filename): string
    {
        return Str::slug($filename);
    }
}
