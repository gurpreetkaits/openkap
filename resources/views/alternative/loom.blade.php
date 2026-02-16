@extends('layouts.app')

@section('title', 'ScreenSense vs Loom - Best Loom Alternative | ScreenSense')
@section('meta_description', 'Compare ScreenSense to Loom. See why teams are switching to ScreenSense for simpler pricing, no watermarks, and instant sharing.')

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

    .brand-highlight {
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

    .pain-points {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .pain-point {
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
    }

    .pain-point h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .pain-point p {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
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
            <h1>ScreenSense vs Loom</h1>
            <p>The screen recorder built for simplicity. No bloat, no surprise paywalls, just record and share.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="compare-container">

        <!-- Pain Points -->
        <h2 class="section-title">Why teams are switching from Loom</h2>
        <p class="section-description">Loom started simple but has grown complex. Here's what we hear from users who switched:</p>

        <div class="pain-points">
            <div class="pain-point">
                <h4>Pricing creep</h4>
                <p>Loom's pricing has increased significantly. Features that were free now require paid plans. ScreenSense keeps pricing simple and transparent.</p>
            </div>
            <div class="pain-point">
                <h4>Feature bloat</h4>
                <p>AI summaries, engagement analytics, viewer insights... sometimes you just want to record and share. ScreenSense focuses on what matters.</p>
            </div>
            <div class="pain-point">
                <h4>Watermarks on free</h4>
                <p>Loom's free tier adds watermarks and branding. ScreenSense gives you clean, professional recordings even on the free plan.</p>
            </div>
        </div>

        <!-- Comparison Table -->
        <h2 class="section-title">Feature Comparison</h2>
        <p class="section-description">See how ScreenSense stacks up against Loom for core screen recording needs.</p>

        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>ScreenSense</th>
                    <th>Loom</th>
                </tr>
            </thead>
            <tbody>
                <tr class="highlight-row">
                    <td>Free plan videos</td>
                    <td>5 videos</td>
                    <td>25 videos (5 min limit)</td>
                </tr>
                <tr>
                    <td>Recording watermarks (free)</td>
                    <td><span class="check-icon">No watermarks</span></td>
                    <td><span class="cross-icon">Loom branding</span></td>
                </tr>
                <tr>
                    <td>Screen + audio recording</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Included</span></td>
                </tr>
                <tr>
                    <td>Instant shareable links</td>
                    <td><span class="check-icon">Instant</span></td>
                    <td><span class="check-icon">Instant</span></td>
                </tr>
                <tr>
                    <td>No account to view</td>
                    <td><span class="check-icon">Anyone can view</span></td>
                    <td><span class="check-icon">Anyone can view</span></td>
                </tr>
                <tr>
                    <td>Browser extension</td>
                    <td><span class="check-icon">Chrome</span></td>
                    <td><span class="check-icon">Chrome, Safari, etc.</span></td>
                </tr>
                <tr>
                    <td>HLS adaptive streaming</td>
                    <td><span class="check-icon">Pro plan</span></td>
                    <td><span class="check-icon">All plans</span></td>
                </tr>
                <tr>
                    <td>AI transcription</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Paid plans</span></td>
                </tr>
                <tr>
                    <td>Video comments</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Included</span></td>
                </tr>
                <tr>
                    <td>Embeddable player</td>
                    <td><span class="check-icon">Included</span></td>
                    <td><span class="check-icon">Included</span></td>
                </tr>
                <tr class="highlight-row">
                    <td>Pro pricing</td>
                    <td>$8/month</td>
                    <td>$15/month</td>
                </tr>
                <tr>
                    <td>Desktop app</td>
                    <td><span class="partial-icon">Coming soon</span></td>
                    <td><span class="check-icon">Mac, Windows</span></td>
                </tr>
                <tr>
                    <td>Mobile app</td>
                    <td><span class="cross-icon">Not available</span></td>
                    <td><span class="check-icon">iOS, Android</span></td>
                </tr>
            </tbody>
        </table>

        <!-- CTA -->
        <div class="cta-section">
            <h2>Ready to try ScreenSense?</h2>
            <p>Start with 10 free recordings. No credit card required.</p>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">
                Get Started Free
                <i data-lucide="arrow-right" class="size-4"></i>
            </a>
        </div>

    </div>
@endsection
