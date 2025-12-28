@extends('layouts.blog')

@section('title', 'Privacy Policy | ScreenSense')
@section('meta_description', 'Privacy policy for ScreenSense. Learn how we handle your data, protect your privacy, and what permissions our Chrome extension uses.')

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

    .page-hero .last-updated {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .content-section {
        max-width: 800px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .content-section h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
        margin-top: 3rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .content-section h2:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    .content-section h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 0.75rem;
        margin-top: 2rem;
    }

    .content-section p {
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 1.25rem;
        font-size: 1rem;
    }

    .content-section ul, .content-section ol {
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 1.25rem;
        margin-left: 1.5rem;
    }

    .content-section li {
        margin-bottom: 0.5rem;
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

    .important-box {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(234, 88, 12, 0.3);
        border-radius: 12px;
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .important-box p {
        margin-bottom: 0;
    }

    .contact-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
    }

    .contact-box h2 {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    .contact-box p:last-child {
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2rem;
        }

        .content-section h2 {
            font-size: 1.5rem;
        }

        .content-section h3 {
            font-size: 1.15rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <h1>Privacy Policy</h1>
            <p class="last-updated">Last updated: December 28, 2025</p>
        </div>
    </div>

    <!-- Content -->
    <div class="content-section">
        <div class="important-box">
            <p>
                <strong>ScreenSense is committed to protecting your privacy.</strong> This privacy policy explains how our
                Chrome extension and web application handle your data.
            </p>
        </div>

        <h2>Overview</h2>
        <p>
            ScreenSense is a screen recording and video sharing platform. This policy describes what information we collect,
            how we use it, and your rights regarding your data.
        </p>

        <h2>Data Collection</h2>
        <p>
            <strong>ScreenSense does NOT collect, store, or transmit any personal data to external servers without your knowledge.</strong>
        </p>

        <h2>Chrome Extension</h2>
        <p>Our Chrome extension operates with the following privacy principles:</p>
        <ul>
            <li><strong>Local Processing:</strong> All screen recordings are processed locally in your browser</li>
            <li><strong>No External Servers:</strong> No recording data is uploaded to external servers without your action</li>
            <li><strong>User Control:</strong> Recordings are only saved when you choose to download or upload them</li>
            <li><strong>Storage Permission:</strong> We only use browser storage to save your recording preferences (camera/microphone settings)</li>
            <li><strong>No Tracking:</strong> We do not track your browsing activity or collect analytics</li>
        </ul>

        <h2>Web Application</h2>
        <p>When using the ScreenSense web application:</p>
        <ul>
            <li><strong>Account Information:</strong> If you create an account, we store your name, email, and profile information</li>
            <li><strong>Video Storage:</strong> Videos you upload are stored on our servers and are private by default</li>
            <li><strong>Sharing:</strong> When you share a video, a unique cryptographically secure shareable link is generated</li>
            <li><strong>Control:</strong> You have full control to delete your videos and account at any time</li>
        </ul>

        <h2>Permissions Explained</h2>
        <p>Our Chrome extension requires certain permissions to function:</p>
        <ul>
            <li><strong>activeTab & tabs:</strong> To capture the current browser tab for recording</li>
            <li><strong>desktopCapture:</strong> To record your screen and windows</li>
            <li><strong>tabCapture:</strong> To record browser tab audio and video</li>
            <li><strong>storage:</strong> To save your recording preferences locally</li>
            <li><strong>scripting:</strong> To inject the camera overlay interface</li>
            <li><strong>host permissions:</strong> To allow recording on any website you visit</li>
        </ul>
        <p>
            <strong>Important:</strong> These permissions are used solely for recording functionality.
            We do not monitor, track, or collect data about your browsing activity.
        </p>

        <h2>Third-Party Services</h2>
        <p>
            ScreenSense integrates with the following third-party services:
        </p>
        <ul>
            <li><strong>Google OAuth:</strong> For secure authentication when signing in</li>
            <li><strong>Polar.sh:</strong> For payment processing (subscription plans only)</li>
        </ul>
        <p>
            The Chrome extension does not use any third-party analytics, tracking, or advertising services.
            It operates entirely locally in your browser.
        </p>

        <h2>Data Security</h2>
        <p>
            We take data security seriously:
        </p>
        <ul>
            <li>Video share tokens are 64-character cryptographically secure and non-guessable</li>
            <li>All data transmission uses HTTPS encryption</li>
            <li>Videos are private by default unless you explicitly share them</li>
            <li>Passwords are never stored in plain text</li>
        </ul>

        <h2>Data Retention</h2>
        <p>
            We retain your data as long as your account is active. When you delete content or your account:
        </p>
        <ul>
            <li>Videos are permanently deleted from our servers</li>
            <li>Account information is removed within 30 days</li>
            <li>Backups are purged according to our retention policy</li>
        </ul>

        <h2>Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access your data stored in our web application</li>
            <li>Download your videos at any time</li>
            <li>Delete your videos and account at any time</li>
            <li>Revoke extension permissions through your browser settings</li>
            <li>Uninstall the extension to stop all local data processing</li>
        </ul>

        <h2>Children's Privacy</h2>
        <p>
            ScreenSense is not intended for use by children under the age of 13. We do not knowingly collect
            personal information from children under 13. If we discover we have collected information from a child
            under 13, we will delete it promptly.
        </p>

        <h2>Changes to This Policy</h2>
        <p>
            We may update this privacy policy from time to time. We will notify users of any significant changes
            by updating the "Last updated" date at the top of this page. Continued use of ScreenSense after
            changes constitutes acceptance of the updated policy.
        </p>

        <div class="contact-box">
            <h2>Contact Us</h2>
            <p>
                If you have any questions about this privacy policy or our data practices, please contact us:
            </p>
            <p>
                <strong>Email:</strong> <a href="mailto:gurpreetkait.codes@gmail.com">gurpreetkait.codes@gmail.com</a><br>
                <strong>GitHub:</strong> <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer">github.com/gurpreetkaits/screensense</a>
            </p>
        </div>
    </div>
@endsection
