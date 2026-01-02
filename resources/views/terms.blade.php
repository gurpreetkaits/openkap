@extends('layouts.app')

@section('title', 'Terms and Conditions | ScreenSense')
@section('meta_description', 'Terms and conditions for using ScreenSense. Read our usage policy, subscription terms, and legal agreements.')

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
            <h1>Terms and Conditions</h1>
            <p class="last-updated">Last updated: December 28, 2025</p>
        </div>
    </div>

    <!-- Content -->
    <div class="content-section">
        <div class="important-box">
            <p>
                <strong>Please read these terms carefully before using ScreenSense.</strong> By accessing or using our service,
                you agree to be bound by these terms. If you do not agree with any part of these terms, you may not use our service.
            </p>
        </div>

        <h2>1. Acceptance of Terms</h2>
        <p>
            By creating an account, accessing our website, or using our Chrome extension, you agree to comply with and be bound by
            these Terms and Conditions, our Privacy Policy, and all applicable laws and regulations.
        </p>

        <h2>2. Description of Service</h2>
        <p>
            ScreenSense is a screen recording and video sharing platform that provides:
        </p>
        <ul>
            <li>Web-based screen recording capabilities</li>
            <li>Chrome extension for browser-based recording</li>
            <li>Video storage, management, and streaming</li>
            <li>Secure video sharing with unique shareable links</li>
            <li>Video trimming and editing features</li>
            <li>Comments and reactions on shared videos</li>
            <li>User profile and account management</li>
            <li>Subscription-based premium features</li>
        </ul>

        <h2>3. User Accounts</h2>

        <h3>3.1 Account Creation</h3>
        <p>
            To use certain features of ScreenSense, you must create an account using Google OAuth authentication. You are responsible
            for maintaining the confidentiality of your account and for all activities that occur under your account.
        </p>

        <h3>3.2 Account Eligibility</h3>
        <p>
            You must be at least 13 years old to use ScreenSense. By creating an account, you represent that you meet this age requirement
            and that all information you provide is accurate and complete.
        </p>

        <h3>3.3 Account Termination</h3>
        <p>
            We reserve the right to suspend or terminate your account if you violate these terms or engage in prohibited activities.
            You may delete your account at any time from your account settings.
        </p>

        <h2>4. Acceptable Use Policy</h2>

        <h3>4.1 Permitted Uses</h3>
        <p>You may use ScreenSense for lawful purposes including:</p>
        <ul>
            <li>Creating educational tutorials and demonstrations</li>
            <li>Recording bug reports and technical issues</li>
            <li>Sharing project updates and presentations</li>
            <li>Personal or commercial video recording and sharing</li>
        </ul>

        <h3>4.2 Prohibited Uses</h3>
        <p>You agree NOT to use ScreenSense to:</p>
        <ul>
            <li>Upload, share, or record content that infringes on intellectual property rights</li>
            <li>Share illegal, harmful, threatening, abusive, or offensive content</li>
            <li>Record or share content containing malware, viruses, or malicious code</li>
            <li>Harass, stalk, or harm other users</li>
            <li>Upload pornographic, sexually explicit, or adult content</li>
            <li>Violate privacy rights by recording others without consent</li>
            <li>Spam, phish, or engage in fraudulent activities</li>
            <li>Attempt to access, probe, or test the vulnerability of our systems</li>
            <li>Reverse engineer or attempt to extract source code</li>
            <li>Resell or redistribute our service without authorization</li>
        </ul>

        <h2>5. Subscription Plans and Billing</h2>

        <h3>5.1 Free Plan</h3>
        <p>
            Our free plan includes 1 video recording with basic features. Free accounts are subject to storage limits and may have
            restricted access to premium features.
        </p>

        <h3>5.2 Pro Plans</h3>
        <p>
            Pro subscriptions (Monthly and Yearly) provide unlimited recordings, priority support, and access to premium features.
            Billing is handled securely through Polar.sh, our payment processor.
        </p>

        <h3>5.3 Billing and Payments</h3>
        <ul>
            <li>Pro subscriptions are billed on a recurring basis (monthly or annually)</li>
            <li>You authorize us to charge your payment method for the subscription fee</li>
            <li>All fees are non-refundable except as required by law</li>
            <li>We reserve the right to change pricing with 30 days' notice to existing subscribers</li>
            <li>Failure to pay may result in downgrade to free plan or account suspension</li>
        </ul>

        <h3>5.4 Cancellation</h3>
        <p>
            You may cancel your Pro subscription at any time from your account settings. Cancellations take effect at the end of
            your current billing period. You will retain Pro access until the end of your paid period.
        </p>

        <h2>6. Content Ownership and Rights</h2>

        <h3>6.1 Your Content</h3>
        <p>
            You retain all ownership rights to videos and content you create and upload to ScreenSense. By uploading content,
            you grant us a limited license to store, process, and display your content as necessary to provide our service.
        </p>

        <h3>6.2 Our Rights</h3>
        <p>
            We may use your content for:
        </p>
        <ul>
            <li>Providing and improving our service</li>
            <li>Processing and encoding videos</li>
            <li>Displaying videos to users you've shared links with</li>
            <li>Generating thumbnails and previews</li>
        </ul>

        <h3>6.3 Content Responsibility</h3>
        <p>
            You are solely responsible for the content you upload and share. We do not pre-screen content but reserve the right
            to remove content that violates these terms.
        </p>

        <h2>7. Video Sharing and Privacy</h2>

        <h3>7.1 Shareable Links</h3>
        <p>
            When you share a video, ScreenSense generates a unique 64-character cryptographic token. Anyone with this link can
            access the video if it's not expired or set to private.
        </p>

        <h3>7.2 Expiration Settings</h3>
        <p>
            You can set expiration dates for shared videos. After expiration, the shareable link will no longer work. It is your
            responsibility to manage expiration settings appropriately.
        </p>

        <h3>7.3 Privacy Controls</h3>
        <p>
            Videos are private by default. You control who can access your videos through share links and privacy settings.
        </p>

        <h2>8. Data and Storage</h2>

        <h3>8.1 Storage Limits</h3>
        <p>
            Free accounts have storage limitations. Pro accounts have significantly higher limits. We reserve the right to
            enforce storage limits and may delete content if limits are exceeded after notice.
        </p>

        <h3>8.2 Data Retention</h3>
        <p>
            We retain your content as long as your account is active. If you delete content or close your account, we will
            delete your data within a reasonable timeframe (typically 30 days), subject to backup retention policies.
        </p>

        <h3>8.3 Backups</h3>
        <p>
            While we maintain backups, we are not responsible for data loss. You should maintain your own backups of important content.
        </p>

        <h2>9. Intellectual Property</h2>
        <p>
            The ScreenSense platform, including our logo, design, code, and features, is protected by copyright and other
            intellectual property laws. ScreenSense is open source under the terms of its license agreement.
        </p>

        <h2>10. Third-Party Services</h2>
        <p>
            ScreenSense integrates with third-party services including:
        </p>
        <ul>
            <li><strong>Google OAuth:</strong> For authentication</li>
            <li><strong>Polar.sh:</strong> For payment processing</li>
            <li><strong>FFmpeg:</strong> For video processing</li>
        </ul>
        <p>
            Your use of these services is subject to their respective terms and privacy policies.
        </p>

        <h2>11. Disclaimers and Limitations of Liability</h2>

        <h3>11.1 Service Availability</h3>
        <p>
            ScreenSense is provided "as is" and "as available." We do not guarantee uninterrupted, error-free, or secure service.
            We may suspend service for maintenance or updates without notice.
        </p>

        <h3>11.2 No Warranties</h3>
        <p>
            We make no warranties regarding the accuracy, reliability, or quality of our service. We do not warrant that our
            service will meet your requirements or be compatible with all systems.
        </p>

        <h3>11.3 Limitation of Liability</h3>
        <p>
            To the maximum extent permitted by law, ScreenSense and its operators shall not be liable for any indirect, incidental,
            special, consequential, or punitive damages, including loss of profits, data, or other intangibles, even if we have
            been advised of the possibility of such damages.
        </p>

        <h2>12. Indemnification</h2>
        <p>
            You agree to indemnify and hold harmless ScreenSense and its operators from any claims, damages, losses, or expenses
            (including legal fees) arising from your use of the service, your content, or your violation of these terms.
        </p>

        <h2>13. Copyright and DMCA</h2>
        <p>
            We respect intellectual property rights. If you believe content on ScreenSense infringes your copyright, please
            contact us at <a href="mailto:gurpreetkait.codes@gmail.com">gurpreetkait.codes@gmail.com</a> with:
        </p>
        <ul>
            <li>Identification of the copyrighted work</li>
            <li>URL of the infringing content</li>
            <li>Your contact information</li>
            <li>A statement of good faith belief that use is not authorized</li>
            <li>A statement that the information is accurate and you are authorized to act</li>
        </ul>

        <h2>14. Modifications to Terms</h2>
        <p>
            We reserve the right to modify these terms at any time. We will notify users of material changes by updating the
            "Last updated" date at the top of this page. Continued use of ScreenSense after changes constitutes acceptance
            of the modified terms.
        </p>

        <h2>15. Governing Law</h2>
        <p>
            These terms shall be governed by and construed in accordance with applicable laws. Any disputes shall be resolved
            in the appropriate courts.
        </p>

        <h2>16. Severability</h2>
        <p>
            If any provision of these terms is found to be unenforceable or invalid, the remaining provisions will continue
            in full force and effect.
        </p>

        <h2>17. Contact Information</h2>
        <p>
            For questions about these terms, please contact us:
        </p>
        <p>
            <strong>Email:</strong> <a href="mailto:gurpreetkait.codes@gmail.com">gurpreetkait.codes@gmail.com</a><br>
            <strong>GitHub:</strong> <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer">github.com/gurpreetkaits/screensense</a>
        </p>
    </div>
@endsection
