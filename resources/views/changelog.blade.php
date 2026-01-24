@extends('layouts.app')

@section('title', 'Changelog | ScreenSense')
@section('meta_description', 'See what\'s new in ScreenSense. Track our latest updates, features, and improvements.')

@push('styles')
<style>
    .page-hero {
        padding: 4rem 0 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .page-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .page-hero p {
        font-size: 1.25rem;
        color: rgba(255, 255, 255, 0.6);
        max-width: 600px;
        margin: 0 auto;
    }

    .changelog-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .changelog-entry {
        margin-bottom: 4rem;
        padding-bottom: 3rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .changelog-entry:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .changelog-header {
        margin-bottom: 1.5rem;
    }

    .changelog-date {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #ea580c;
        margin-bottom: 0.5rem;
    }

    .changelog-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        line-height: 1.3;
    }

    .change-section {
        margin-bottom: 1.5rem;
    }

    .section-label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        margin-bottom: 0.75rem;
    }

    .section-label.features {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }

    .section-label.improvements {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .section-label.fixes {
        background: rgba(234, 88, 12, 0.15);
        color: #ea580c;
    }

    .change-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .change-list li {
        position: relative;
        padding-left: 1.25rem;
        margin-bottom: 0.625rem;
        color: rgba(255, 255, 255, 0.75);
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .change-list li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.6rem;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.4);
        border-radius: 50%;
    }

    .change-list.features li::before {
        background: #22c55e;
    }

    .change-list.improvements li::before {
        background: #3b82f6;
    }

    .change-list.fixes li::before {
        background: #ea580c;
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .changelog-title {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <h1>Changelog</h1>
            <p>See what we've been building lately.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="changelog-container">

        <!-- January 25, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 25, 2025</div>
                <h2 class="changelog-title">Email Notifications & Changelog Page</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Added changelog page to track product updates</li>
                    <li>Email notifications with Resend integration</li>
                    <li>Product update email templates with inline CSS</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label fixes">Bug Fixes</div>
                <ul class="change-list fixes">
                    <li>Fixed email template styling for better email client compatibility</li>
                </ul>
            </div>
        </div>

        <!-- January 23, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 23, 2025</div>
                <h2 class="changelog-title">Landing Page Refresh & Extension Detection</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Browser extension detection with install prompt for new recordings</li>
                    <li>Video grid selection mode with click-to-select behavior</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label improvements">Improvements</div>
                <ul class="change-list improvements">
                    <li>Redesigned landing page hero for better conversion</li>
                    <li>Updated application logo and branding assets</li>
                    <li>New favicon for browser tabs</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label fixes">Bug Fixes</div>
                <ul class="change-list fixes">
                    <li>Fixed duplicate checkbox display on video selection</li>
                    <li>Hide play button when video is selected</li>
                </ul>
            </div>
        </div>

        <!-- January 22, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 22, 2025</div>
                <h2 class="changelog-title">Bulk Video Actions</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Bulk delete videos - select multiple videos and delete at once</li>
                    <li>Bulk actions toolbar for managing multiple videos</li>
                </ul>
            </div>
        </div>

        <!-- January 18-19, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 18-19, 2025</div>
                <h2 class="changelog-title">Bunny CDN Integration & Video Streaming</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Bunny CDN integration for faster video delivery</li>
                    <li>HLS streaming support for adaptive bitrate playback</li>
                    <li>API logging for better debugging and monitoring</li>
                    <li>Zoom controls for video playback (disabled by default)</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label improvements">Improvements</div>
                <ul class="change-list improvements">
                    <li>Refactored API integrations to use Saloon HTTP client</li>
                    <li>Fallback to WebM if HLS conversion not complete</li>
                    <li>Updated license as suggested by community</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label fixes">Bug Fixes</div>
                <ul class="change-list fixes">
                    <li>Fixed Bunny HLS streaming issues</li>
                    <li>Fixed video playback when transcoding is in progress</li>
                </ul>
            </div>
        </div>

        <!-- January 4-7, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 4-7, 2025</div>
                <h2 class="changelog-title">AI Transcription & Feedback System</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Video transcription powered by OpenAI Whisper</li>
                    <li>AI-generated video summaries</li>
                    <li>Feedback system for user suggestions and bug reports</li>
                    <li>Comment threading on shared videos</li>
                    <li>Video embeds for external websites</li>
                    <li>PostHog analytics integration</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label improvements">Improvements</div>
                <ul class="change-list improvements">
                    <li>Blur background when recording modal is visible</li>
                    <li>Show toast instead of browser alert when video limit reached</li>
                    <li>Refactored codebase to manager/repository pattern</li>
                </ul>
            </div>
        </div>

        <!-- January 1-3, 2025 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">January 1-3, 2025</div>
                <h2 class="changelog-title">Dashboard Improvements & Blog</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Blog section with markdown support</li>
                    <li>Sitemap for better SEO</li>
                    <li>Admin settings panel</li>
                    <li>Global recording setup panel</li>
                    <li>Discord community link</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label improvements">Improvements</div>
                <ul class="change-list improvements">
                    <li>Improved dashboard UI and navigation</li>
                    <li>Better landing page design and responsiveness</li>
                    <li>Load 20 videos per page for faster loading</li>
                    <li>Added SEO meta properties</li>
                    <li>Restored chunk uploading for large recordings</li>
                </ul>
            </div>

            <div class="change-section">
                <div class="section-label fixes">Bug Fixes</div>
                <ul class="change-list fixes">
                    <li>Fixed subscription redirection flow</li>
                    <li>Fixed HLS video loading and streaming</li>
                    <li>Fixed video upload duration calculation</li>
                    <li>Improved recording reliability</li>
                </ul>
            </div>
        </div>

        <!-- December 31, 2024 -->
        <div class="changelog-entry">
            <div class="changelog-header">
                <div class="changelog-date">December 31, 2024</div>
                <h2 class="changelog-title">Video Sharing & Embeds</h2>
            </div>

            <div class="change-section">
                <div class="section-label features">New Features</div>
                <ul class="change-list features">
                    <li>Public video sharing with unique links</li>
                    <li>Embeddable video player for external sites</li>
                    <li>View count tracking on shared videos</li>
                </ul>
            </div>
        </div>

    </div>
@endsection
