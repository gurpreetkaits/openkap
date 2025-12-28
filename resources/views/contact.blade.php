@extends('layouts.blog')

@section('title', 'Contact Us | ScreenSense')
@section('meta_description', 'Get in touch with ScreenSense. We\'re here to help with questions, feedback, bug reports, and support.')

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
        margin-bottom: 1.5rem;
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

    .contact-grid {
        display: grid;
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .contact-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-left: 4px solid #ea580c;
        border-radius: 12px;
        padding: 2rem;
        transition: all 0.3s;
    }

    .contact-card:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(234, 88, 12, 0.3);
        border-left-color: #ea580c;
        transform: translateX(4px);
    }

    .contact-card h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .contact-card .icon {
        font-size: 1.5rem;
    }

    .contact-card p {
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .contact-card a {
        color: #ea580c;
        text-decoration: none;
        font-weight: 500;
        transition: opacity 0.2s;
    }

    .contact-card a:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    .contact-card .note {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.875rem;
        margin-top: 0.5rem;
        margin-bottom: 0;
    }

    .faq-section {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(234, 88, 12, 0.3);
        border-radius: 16px;
        padding: 2rem;
        margin: 2rem 0;
    }

    .faq-section h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 1.5rem;
    }

    .faq-item {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .faq-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .faq-question {
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .faq-answer {
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0;
        line-height: 1.7;
    }

    .faq-answer a {
        color: #ea580c;
        text-decoration: none;
    }

    .faq-answer a:hover {
        text-decoration: underline;
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

        .contact-card {
            padding: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you! Get in touch with any questions, feedback, or issues.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="content-section">
        <h2>Get in Touch</h2>
        <p>
            Have a question about ScreenSense? Need help with your account? Found a bug? We're here to help!
        </p>

        <div class="contact-grid">
            <div class="contact-card">
                <h3><span class="icon">📧</span>Email Support</h3>
                <p>For general inquiries, support requests, or feedback:</p>
                <p><a href="mailto:gurpreetkait.codes@gmail.com">gurpreetkait.codes@gmail.com</a></p>
                <p class="note">We typically respond within 24-48 hours</p>
            </div>

            <div class="contact-card">
                <h3><span class="icon">🐛</span>Bug Reports & Feature Requests</h3>
                <p>Found a bug or have an idea for a new feature?</p>
                <p><a href="https://github.com/gurpreetkaits/screensense/issues" target="_blank" rel="noopener noreferrer">Open an issue on GitHub</a></p>
                <p class="note">This helps us track and prioritize improvements</p>
            </div>

            <div class="contact-card">
                <h3><span class="icon">💬</span>Community & Discussions</h3>
                <p>Join the conversation and connect with other users:</p>
                <p><a href="https://github.com/gurpreetkaits/screensense/discussions" target="_blank" rel="noopener noreferrer">GitHub Discussions</a></p>
                <p class="note">Ask questions, share tips, and help others</p>
            </div>

            <div class="contact-card">
                <h3><span class="icon">⭐</span>Follow Development</h3>
                <p>Stay updated on new features and releases:</p>
                <p><a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer">Star us on GitHub</a></p>
                <p class="note">Get notified about updates and new releases</p>
            </div>
        </div>

        <div class="faq-section">
            <h3>Quick Answers to Common Questions</h3>

            <div class="faq-item">
                <div class="faq-question">How do I upgrade to Pro?</div>
                <div class="faq-answer">
                    Log into your account, go to Settings → Subscription, and click "Upgrade to Pro". You'll be directed to our secure payment processor.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">I forgot my password. How do I reset it?</div>
                <div class="faq-answer">
                    We use Google OAuth for authentication. Simply sign in with the same Google account you used to create your ScreenSense account.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">How do I delete my account?</div>
                <div class="faq-answer">
                    You can delete your account from Settings → Account → Delete Account. This will permanently remove all your videos and data.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">My video won't upload. What should I do?</div>
                <div class="faq-answer">
                    Check your internet connection and try again. If the issue persists, please email us at
                    <a href="mailto:gurpreetkait.codes@gmail.com">gurpreetkait.codes@gmail.com</a> with details about the file size and format.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Can I use ScreenSense for commercial purposes?</div>
                <div class="faq-answer">
                    Yes! Both our free and Pro plans can be used for personal or commercial purposes. Check our <a href="/terms">Terms and Conditions</a> for details.
                </div>
            </div>
        </div>

        <h2>Business Inquiries</h2>
        <p>
            For partnerships, enterprise licensing, or custom solutions, please email us at
            <a href="mailto:gurpreetkait.codes@gmail.com" style="color: #ea580c; text-decoration: none; font-weight: 500;">gurpreetkait.codes@gmail.com</a>
            with "Business Inquiry" in the subject line.
        </p>

        <h2>Response Times</h2>
        <p>
            We aim to respond to all inquiries within 24-48 hours during business days. For urgent issues related to billing or account access,
            we'll prioritize your request and respond as quickly as possible.
        </p>
    </div>
@endsection
