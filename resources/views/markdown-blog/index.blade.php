@extends('layouts.app')

@section('title', isset($category) ? "{$category} | Blog | OpenKap" : 'Blog | OpenKap')
@section('meta_description', 'Tips, guides, and insights about screen recording and async communication.')

@push('styles')
<style>
    .blog-container {
        max-width: 680px;
        margin: 0 auto;
        padding: 2rem 1.5rem 4rem;
    }

    .blog-header {
        margin-bottom: 3rem;
    }

    .blog-header h1 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #e5e7eb;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }

    .blog-header p {
        font-size: 0.9375rem;
        color: #6b7280;
    }

    .blog-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .blog-item {
        display: block;
        padding: 1.25rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        transition: opacity 0.2s;
    }

    .blog-item:first-child {
        padding-top: 0;
    }

    .blog-item:hover {
        opacity: 0.8;
    }

    .blog-item-meta {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-bottom: 0.375rem;
    }

    .blog-item-title {
        font-size: 1.0625rem;
        font-weight: 500;
        color: #d1d5db;
        margin-bottom: 0.375rem;
        line-height: 1.4;
    }

    .blog-item-excerpt {
        font-size: 0.9375rem;
        color: #9ca3af;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 0;
        color: #6b7280;
        font-size: 0.9375rem;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.25rem;
        margin-top: 2.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .pagination a, .pagination span {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .pagination a {
        color: #9ca3af;
    }

    .pagination a:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #d1d5db;
    }

    .pagination .active {
        background: rgba(255, 255, 255, 0.1);
        color: #e5e7eb;
    }

    .pagination .disabled {
        color: #4b5563;
    }

    @media (max-width: 768px) {
        .blog-container {
            padding: 1.5rem 1rem 3rem;
        }

        .blog-header h1 {
            font-size: 1.25rem;
        }

        .blog-item-title {
            font-size: 1rem;
        }
    }
</style>
@endpush

@section('content')
    <div class="blog-container">
        <header class="blog-header">
            <h1>{{ isset($category) ? $category : 'Blog' }}</h1>
            <p>Tips and guides for screen recording</p>
        </header>

        <div class="blog-list">
            @forelse($blogs as $blog)
                <a href="/blog/{{ $blog->slug }}" class="blog-item">
                    <div class="blog-item-meta">
                        {{ $blog->publishedAt->format('M d, Y') }} · {{ $blog->readTime }} min
                    </div>
                    <h2 class="blog-item-title">{{ $blog->title }}</h2>
                    <p class="blog-item-excerpt">{{ $blog->excerpt }}</p>
                </a>
            @empty
                <div class="empty-state">
                    <p>No posts yet</p>
                </div>
            @endforelse
        </div>

        @if($blogs->hasPages())
            <div class="pagination">
                @if($blogs->onFirstPage())
                    <span class="disabled">&larr;</span>
                @else
                    <a href="{{ $blogs->previousPageUrl() }}">&larr;</a>
                @endif

                @foreach($blogs->getUrlRange(1, $blogs->lastPage()) as $page => $url)
                    @if($page == $blogs->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($blogs->hasMorePages())
                    <a href="{{ $blogs->nextPageUrl() }}">&rarr;</a>
                @else
                    <span class="disabled">&rarr;</span>
                @endif
            </div>
        @endif
    </div>
@endsection
