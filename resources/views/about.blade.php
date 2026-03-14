@extends('layouts.app')

@section('title', 'About Us | OpenKap')
@section('meta_description', 'Learn about OpenKap - Simple, powerful screen recording for everyone. Open source, privacy-focused, and built for teams and individuals.')

@push('styles')
<style>
    .page-hero {
        padding: 4rem 0 3rem;
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

    .content-section {
        max-width: 800px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .content-section h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
        margin-top: 3rem;
    }

    .content-section h2:first-child {
        margin-top: 0;
    }

    .content-section p {
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 1.5rem;
        font-size: 1.05rem;
    }

    .content-section ul {
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 1.5rem;
        margin-left: 1.5rem;
    }

    .content-section li {
        margin-bottom: 0.75rem;
    }

    .content-section strong {
        color: #ffffff;
        font-weight: 600;
    }

    .content-section a {
        color: #ea580c;
        text-decoration: none;
        transition: opacity 0.2s;
    }

    .content-section a:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    .highlight-box {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(234, 88, 12, 0.3);
        border-radius: 16px;
        padding: 2rem;
        margin: 2rem 0;
    }

    .highlight-box p {
        margin-bottom: 0;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s;
    }

    .feature-card:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(234, 88, 12, 0.3);
        transform: translateY(-4px);
    }

    .feature-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .feature-card p {
        font-size: 0.95rem;
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.6);
    }

    .cta-section {
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        padding: 3rem 2rem;
        text-align: center;
        border-radius: 24px;
        margin: 3rem 0;
    }

    .cta-section h2 {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        margin-top: 0;
    }

    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.125rem;
        margin-bottom: 2rem;
    }

    .cta-button {
        display: inline-block;
        background: white;
        color: #ea580c;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .content-section h2 {
            font-size: 1.5rem;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <h1>About OpenKap</h1>
            <p>Simple, powerful screen recording for everyone.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="content-section">
        <div class="highlight-box">
            <p>
                <strong>OpenKap</strong> is an open-source screen recording and instant sharing platform designed to make
                capturing and sharing your screen as simple as possible. Whether you're creating tutorials, bug reports,
                or sharing quick updates with your team, OpenKap has you covered.
            </p>
        </div>

        <h2>Our Mission</h2>
        <p>
            We believe screen recording should be fast, simple, and accessible to everyone. Too many screen recording tools
            are bloated with features you don't need, require complex setups, or lock you into expensive subscriptions.
        </p>
        <p>
            OpenKap was built to solve this problem. We've created a streamlined platform that focuses on what matters:
            quick recording, instant sharing, and a great user experience.
        </p>

        <h2>What We Offer</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3>🎥 Screen Recording</h3>
                <p>Capture your entire screen, specific browser tabs, or application windows with audio support.</p>
            </div>
            <div class="feature-card">
                <h3>🔗 Instant Sharing</h3>
                <p>Every recording gets a unique, secure shareable link that you can send to anyone.</p>
            </div>
            <div class="feature-card">
                <h3>💬 Collaboration</h3>
                <p>Add comments and reactions to shared videos for better feedback and discussion.</p>
            </div>
            <div class="feature-card">
                <h3>🔒 Privacy First</h3>
                <p>Videos are private by default. You control who sees your recordings and for how long.</p>
            </div>
        </div>

        <h2>Built for Teams & Individuals</h2>
        <p>
            Whether you're a solo developer documenting bugs, a designer sharing UI feedback, a teacher creating tutorials,
            or a team collaborating on projects, OpenKap adapts to your workflow.
        </p>

        <h2>Key Features</h2>
        <ul>
            <li><strong>Browser Extension:</strong> Record directly from your browser with our Chrome extension</li>
            <li><strong>Web Application:</strong> Full-featured dashboard to manage and organize your recordings</li>
            <li><strong>Secure Sharing:</strong> Every video gets a cryptographically secure 64-character share token</li>
            <li><strong>Video Trimming:</strong> Edit your recordings to keep only the parts that matter</li>
            <li><strong>Flexible Expiration:</strong> Set custom expiration dates for shared links</li>
            <li><strong>Comment System:</strong> Collaborate with comments and reactions on shared videos</li>
            <li><strong>Profile Customization:</strong> Build your personal profile with avatar, bio, and website</li>
            <li><strong>Free Tier:</strong> Start recording with our free plan - no credit card required</li>
        </ul>

        <h2>Open Source</h2>
        <p>
            OpenKap is proudly open source. We believe in transparency and community-driven development. You can
            view our code, contribute features, report issues, and even self-host your own instance.
        </p>
        <p>
            Find us on GitHub: <a href="https://github.com/gurpreetkaits/openkap" target="_blank" rel="noopener noreferrer">github.com/gurpreetkaits/openkap</a>
        </p>

        <h2>The Technology</h2>
        <p>
            OpenKap is built with modern, reliable technologies:
        </p>
        <ul>
            <li><strong>Backend:</strong> Laravel (PHP) for robust server-side processing</li>
            <li><strong>Frontend:</strong> Vue 3 for a responsive, interactive user interface</li>
            <li><strong>Video Processing:</strong> FFmpeg for professional-grade video handling</li>
            <li><strong>Payments:</strong> Polar.sh integration for transparent subscription management</li>
            <li><strong>Storage:</strong> Efficient media library management with Spatie Media Library</li>
        </ul>

        <h2>Our Values</h2>
        <ul>
            <li><strong>Simplicity:</strong> We keep the interface clean and the workflow intuitive</li>
            <li><strong>Privacy:</strong> Your recordings are yours. We don't sell data or track your activity</li>
            <li><strong>Transparency:</strong> Open source code and honest pricing</li>
            <li><strong>Reliability:</strong> Built with proven technologies and best practices</li>
            <li><strong>Community:</strong> We listen to feedback and improve based on user needs</li>
        </ul>

        <div class="cta-section">
            <h2>Get Started Today</h2>
            <p>Ready to simplify your screen recording workflow? Sign up for a free account and start recording in minutes.</p>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">Get Started Free</a>
        </div>
    </div>
@endsection
