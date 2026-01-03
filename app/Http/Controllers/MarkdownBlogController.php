<?php

namespace App\Http\Controllers;

use App\Http\Resources\MarkdownBlogResource;
use App\Managers\MarkdownBlogManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MarkdownBlogController extends Controller
{
    public function __construct(
        protected MarkdownBlogManager $blogManager
    ) {}

    // ============================================
    // WEB ROUTES (Server-rendered Blade views)
    // ============================================

    /**
     * Display all markdown blog posts
     */
    public function index(Request $request): View
    {
        $posts = $this->blogManager->getAllPosts();

        // Manual pagination for collection
        $page = $request->get('page', 1);
        $perPage = 10;
        $paginatedPosts = new LengthAwarePaginator(
            $posts->forPage($page, $perPage),
            $posts->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return view('markdown-blog.index', [
            'blogs' => $paginatedPosts,
        ]);
    }

    /**
     * Display a single markdown blog post
     */
    public function show(string $slug): View
    {
        $post = $this->blogManager->getPostBySlug($slug);

        if (! $post || ! $post->isPublished) {
            abort(404);
        }

        return view('markdown-blog.show', [
            'blog' => $post,
        ]);
    }

    /**
     * Get posts by category (web view)
     */
    public function byCategory(string $category, Request $request): View
    {
        $posts = $this->blogManager->getPostsByCategory($category);

        // Manual pagination for collection
        $page = $request->get('page', 1);
        $perPage = 10;
        $paginatedPosts = new LengthAwarePaginator(
            $posts->forPage($page, $perPage),
            $posts->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return view('markdown-blog.index', [
            'blogs' => $paginatedPosts,
            'category' => $category,
        ]);
    }

    // ============================================
    // API ROUTES (JSON responses with Resources)
    // ============================================

    /**
     * API: Get all blog posts
     */
    public function apiIndex(): AnonymousResourceCollection
    {
        $posts = $this->blogManager->getAllPosts();

        return MarkdownBlogResource::collection($posts);
    }

    /**
     * API: Get a single blog post by slug
     */
    public function apiShow(string $slug): MarkdownBlogResource|JsonResponse
    {
        $post = $this->blogManager->getPostBySlug($slug);

        if (! $post || ! $post->isPublished) {
            return response()->json(['message' => 'Blog post not found'], 404);
        }

        return new MarkdownBlogResource($post);
    }

    /**
     * API: Get recent posts
     */
    public function recent(Request $request): AnonymousResourceCollection
    {
        $limit = (int) $request->get('limit', 3);
        $posts = $this->blogManager->getRecentPosts($limit);

        return MarkdownBlogResource::collection($posts);
    }

    /**
     * API: Get posts by category
     */
    public function apiByCategory(string $category): AnonymousResourceCollection
    {
        $posts = $this->blogManager->getPostsByCategory($category);

        return MarkdownBlogResource::collection($posts);
    }
}
