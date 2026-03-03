@extends('layouts.app')

@section('title', $blog->metaTitle ?? $blog->title . ' | ScreenSense')
@section('meta_description', $blog->metaDescription ?? $blog->excerpt)
@section('og_title', $blog->title)
@section('og_description', $blog->excerpt)
@section('og_type', 'article')
@section('twitter_title', $blog->title)
@section('twitter_description', $blog->excerpt)

@push('styles')
<style>
    .article-container {
        max-width: 680px;
        margin: 0 auto;
        padding: 2rem 1.5rem 4rem;
    }

    .breadcrumb {
        margin-bottom: 2.5rem;
    }

    .breadcrumb a {
        color: #9ca3af;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.2s;
    }

    .breadcrumb a:hover {
        color: #d1d5db;
    }

    .breadcrumb svg {
        width: 14px;
        height: 14px;
    }

    .article-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: #6b7280;
        margin-bottom: 0.75rem;
    }

    .article-meta span:not(:last-child)::after {
        content: "·";
        margin-left: 0.5rem;
    }

    .article-title {
        font-size: 2rem;
        font-weight: 600;
        color: #e5e7eb;
        line-height: 1.3;
        margin-bottom: 2.5rem;
        letter-spacing: -0.02em;
    }

    /* Article Content */
    .prose {
        font-size: 1.0625rem;
        line-height: 1.75;
        color: #9ca3af;
    }

    .prose h1 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #d1d5db;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        letter-spacing: -0.01em;
    }

    .prose h2 {
        font-size: 1.375rem;
        font-weight: 600;
        color: #d1d5db;
        margin-top: 2.5rem;
        margin-bottom: 0.75rem;
        letter-spacing: -0.01em;
    }

    .prose h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #d1d5db;
        margin-top: 2rem;
        margin-bottom: 0.5rem;
    }

    .prose p {
        margin-bottom: 1.25rem;
    }

    .prose ul, .prose ol {
        margin: 1.25rem 0;
        padding-left: 1.25rem;
    }

    .prose ul {
        list-style-type: disc;
    }

    .prose ol {
        list-style-type: decimal;
    }

    .prose li {
        margin-bottom: 0.375rem;
        padding-left: 0.25rem;
    }

    .prose strong {
        font-weight: 500;
        color: #d1d5db;
    }

    .prose blockquote {
        border-left: 2px solid #4b5563;
        padding-left: 1.25rem;
        margin: 1.5rem 0;
        color: #6b7280;
        font-style: italic;
    }

    .prose blockquote p {
        margin-bottom: 0;
    }

    .prose a {
        color: #9ca3af;
        text-decoration: underline;
        text-underline-offset: 2px;
        transition: color 0.2s;
    }

    .prose a:hover {
        color: #d1d5db;
    }

    .prose code {
        background: rgba(255, 255, 255, 0.06);
        padding: 0.125rem 0.375rem;
        border-radius: 3px;
        font-size: 0.9em;
        color: #d1d5db;
    }

    .prose pre {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 6px;
        padding: 1rem;
        overflow-x: auto;
        margin: 1.5rem 0;
    }

    .prose pre code {
        background: none;
        padding: 0;
    }

    .prose img {
        max-width: 100%;
        height: auto;
        border-radius: 6px;
        margin: 1.5rem 0;
    }

    .prose hr {
        border: none;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        margin: 2rem 0;
    }

    /* Tags */
    .article-tags {
        margin-top: 3rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .tag {
        color: #6b7280;
        font-size: 0.8125rem;
    }

    .tag:not(:last-child)::after {
        content: ",";
    }

    /* Footer */
    .article-footer {
        margin-top: 3rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .author-info {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .author-avatar {
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .author-name {
        font-size: 0.875rem;
        color: #9ca3af;
    }

    .share-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .share-button {
        width: 32px;
        height: 32px;
        background: transparent;
        border: none;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        cursor: pointer;
    }

    .share-button:hover {
        background: rgba(255, 255, 255, 0.06);
    }

    .share-button svg {
        width: 16px;
        height: 16px;
        color: #6b7280;
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #1f2937;
        color: #d1d5db;
        padding: 0.625rem 1rem;
        border-radius: 6px;
        font-size: 0.8125rem;
        opacity: 0;
        transition: transform 0.3s, opacity 0.3s;
        z-index: 1000;
    }

    .toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }

    @media (max-width: 768px) {
        .article-container {
            padding: 1.5rem 1rem 3rem;
        }

        .article-title {
            font-size: 1.5rem;
        }

        .prose {
            font-size: 1rem;
        }

        .article-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
</style>
@endpush

@section('content')
    <article class="article-container">
        <nav class="breadcrumb">
            <a href="/blog">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Blog
            </a>
        </nav>

        <div class="article-meta">
            <span>{{ $blog->publishedAt->format('M d, Y') }}</span>
            <span>{{ $blog->readTime }} min read</span>
        </div>

        <h1 class="article-title">{{ $blog->title }}</h1>

        <div class="prose">
            {{-- Blog content is from admin-controlled markdown files, sanitized during parsing --}}
            {!! $blog->content !!}
        </div>

        @if(!empty($blog->tags))
            <div class="article-tags">
                <div class="tags-list">
                    @foreach($blog->tags as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <footer class="article-footer">
            <div class="author-info">
                <div class="author-avatar">{{ substr($blog->author, 0, 1) }}</div>
                <span class="author-name">{{ $blog->author }}</span>
            </div>

            <div class="share-buttons">
                <button class="share-button" onclick="shareTwitter()" title="Share on X">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                </button>
                <button class="share-button" onclick="shareLinkedIn()" title="Share on LinkedIn">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                    </svg>
                </button>
                <button class="share-button" onclick="copyLink()" title="Copy link">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </button>
            </div>
        </footer>
    </article>

    <div id="toast" class="toast"></div>
@endsection

@push('scripts')
<script>
    function shareTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('{{ $blog->title }}');
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
    }

    function shareLinkedIn() {
        const url = encodeURIComponent(window.location.href);
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('Link copied');
        }).catch(() => {
            showToast('Failed to copy');
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2000);
    }
</script>
@endpush
