@extends('layouts.app')

@section('title', 'ScreenSense vs Cap.so - Screen Recording Comparison | ScreenSense')
@section('meta_description', 'Compare ScreenSense to Cap.so. Both are modern Loom alternatives. See which screen recorder fits your workflow better.')

@push('styles')
<style>
    .page-hero {
        padding: 4rem 0 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .page-hero h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .page-hero p {
        font-size: 1.125rem;
        color: rgba(255, 255, 255, 0.6);
        max-width: 600px;
        margin: 0 auto;
    }

    .compare-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .comparison-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 3rem;
    }

    .comparison-table th,
    .comparison-table td {
        padding: 1rem 1.25rem;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .comparison-table th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.5);
        background: rgba(255, 255, 255, 0.02);
    }

    .comparison-table th:first-child {
        border-radius: 8px 0 0 0;
    }

    .comparison-table th:last-child {
        border-radius: 0 8px 0 0;
    }

    .comparison-table td {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.95rem;
    }

    .comparison-table td:first-child {
        font-weight: 500;
        color: #ffffff;
    }

    .comparison-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    .check-icon {
        color: #22c55e;
    }

    .cross-icon {
        color: #ef4444;
    }

    .partial-icon {
        color: #f59e0b;
    }

    .highlight-row td {
        background: rgba(234, 88, 12, 0.05);
    }

    .highlight-row td:nth-child(2) {
        color: #ea580c;
        font-weight: 600;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .section-description {
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .cta-section {
        text-align: center;
        padding: 3rem;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .cta-section h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.75rem;
    }

    .cta-section p {
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 1.5rem;
    }

    .cta-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #ea580c;
        color: #ffffff;
        padding: 0.875rem 2rem;
        border-radius: 9999px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .cta-button:hover {
        background: #f97316;
        box-shadow: 0 0 20px -5px rgba(234, 88, 12, 0.4);
    }

    .intro-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .intro-card {
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
    }

    .intro-card h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .intro-card p {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.6;
    }

    .summary-box {
        padding: 1.5rem;
        background: rgba(234, 88, 12, 0.05);
        border: 1px solid rgba(234, 88, 12, 0.2);
        border-radius: 12px;
        margin-bottom: 3rem;
    }

    .summary-box h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #ea580c;
        margin-bottom: 0.75rem;
    }

    .summary-box p {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 1.75rem;
        }

        .comparison-table {
            font-size: 0.875rem;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-brand-500/20 bg-brand-500/5 text-brand-400 text-xs font-medium mb-6">
                Comparison
            </div>
            <h1>ScreenSense vs Cap.so</h1>
            <p>Two modern alternatives to Loom. See which screen recorder fits your needs.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="compare-container">

        <!-- Intro -->
        <h2 class="section-title">Both are great Loom alternatives</h2>
        <p class="section-description">Cap.so and ScreenSense are both built as simpler alternatives to Loom. Here's how they differ:</p>

        <div class="intro-section">
            <div class="intro-card">
                <h4>Cap.so</h4>
                <p>Open-source screen recorder with a focus on privacy and local-first recording. Uses Tauri for a lightweight desktop app. Great for developers who want full control.</p>
            </div>
            <div class="intro-card">
                <h4>ScreenSense</h4>
                <p>Browser-first screen recorder with instant cloud sharing. Focus on simplicity and speed. Record from Chrome, get a link immediately. No desktop app needed.</p>
            </div>
        </div>

        <div class="summary-box">
            <h3>Quick Summary</h3>
            <p>Choose <strong>Cap.so</strong> if you want open-source, local-first recording with a desktop app. Choose <strong>ScreenSense</strong> if you want instant browser-based recording with cloud sharing and no installation.</p>
        </div>

        <!-- Comparison Table -->
        <h2 class="section-title">Feature Comparison</h2>
        <p class="section-description">A detailed look at how ScreenSense and Cap.so compare.</p>

        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>ScreenSense</th>
                    <th>Cap.so</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Open source</td>
                    <td><span class="cross-icon">No</span></td>
                    <td><span class="check-icon">Yes (AGPL)</span></td>
                </tr>
                <tr class="highlight-row">
                    <td>Browser extension</td>
                    <td>Chrome extension</td>
                    <td>Not available</td>
                </tr>
                <tr>
                    <td>Desktop app</td>
                    <td><span class="partial-icon">Coming soon</span></td>
                    <td><span class="check-icon">Mac, Windows, Linux</span></td>
                </tr>
                <tr class="highlight-row">
                    <td>No installation needed</td>
                    <td>Record from browser</td>
                    <td>Requires app download</td>
                </tr>
                <tr>
                    <td>Screen + audio</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Included</span></td>
                </tr>
                <tr>
                    <td>Cloud hosting</td>
                    <td><span class="check-icon">Automatic</span></td>
                    <td><span class="check-icon">Optional (Cap Cloud)</span></td>
                </tr>
                <tr>
                    <td>Self-hosting</td>
                    <td><span class="cross-icon">Not available</span></td>
                    <td><span class="check-icon">Fully supported</span></td>
                </tr>
                <tr>
                    <td>Instant shareable links</td>
                    <td><span class="check-icon">Immediate</span></td>
                    <td><span class="check-icon">After upload</span></td>
                </tr>
                <tr>
                    <td>AI transcription</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Available</span></td>
                </tr>
                <tr>
                    <td>HLS adaptive streaming</td>
                    <td><span class="check-icon">Pro plan</span></td>
                    <td><span class="check-icon">Available</span></td>
                </tr>
                <tr>
                    <td>Video comments</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Available</span></td>
                </tr>
                <tr>
                    <td>Embeddable player</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Available</span></td>
                </tr>
                <tr>
                    <td>Free tier</td>
                    <td><span class="check-icon">10 videos</span></td>
                    <td><span class="check-icon">Unlimited (local)</span></td>
                </tr>
                <tr>
                    <td>Team features</td>
                    <td>Workspaces, shared library</td>
                    <td>Workspaces, shared library</td>
                </tr>
                <tr class="highlight-row">
                    <td>Pricing (Pro)</td>
                    <td>$8/month</td>
                    <td>$12/month</td>
                </tr>
            </tbody>
        </table>

        <!-- CTA -->
        <div class="cta-section">
            <h2>Try ScreenSense today</h2>
            <p>Record your first video in seconds. No download required.</p>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">
                Get Started Free
                <i data-lucide="arrow-right" class="size-4"></i>
            </a>
        </div>

    </div>
@endsection
