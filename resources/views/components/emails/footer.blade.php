@props([
    'twitterUrl' => null,
    'githubUrl' => 'https://github.com/gurpreetkaits/screensense',
    'websiteUrl' => null,
    'unsubscribeUrl' => null,
    'preferencesUrl' => null,
])
<!-- Footer -->
<tr>
    <td style="background-color: #fafafa; padding: 24px 32px; border-top: 1px solid #f4f4f5;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <!-- Social Links -->
            <tr>
                <td align="center" style="padding-bottom: 16px;">
                    @if($twitterUrl)
                    <a href="{{ $twitterUrl }}" style="display: inline-block; margin: 0 8px; color: #a1a1aa; text-decoration: none; font-size: 14px;">Twitter</a>
                    @endif
                    @if($githubUrl)
                    <a href="{{ $githubUrl }}" style="display: inline-block; margin: 0 8px; color: #a1a1aa; text-decoration: none; font-size: 14px;">GitHub</a>
                    @endif
                    @if($websiteUrl)
                    <a href="{{ $websiteUrl }}" style="display: inline-block; margin: 0 8px; color: #a1a1aa; text-decoration: none; font-size: 14px;">Website</a>
                    @endif
                </td>
            </tr>

            @if($unsubscribeUrl || $preferencesUrl)
            <!-- Unsubscribe / Preferences -->
            <tr>
                <td align="center">
                    <p style="margin: 0; font-size: 11px; color: #a1a1aa;">
                        @if($unsubscribeUrl)
                        <a href="{{ $unsubscribeUrl }}" style="color: #a1a1aa; text-decoration: underline;">Unsubscribe</a>
                        @endif
                        @if($unsubscribeUrl && $preferencesUrl)
                        <span style="margin: 0 8px;">&bull;</span>
                        @endif
                        @if($preferencesUrl)
                        <a href="{{ $preferencesUrl }}" style="color: #a1a1aa; text-decoration: underline;">Preferences</a>
                        @endif
                    </p>
                </td>
            </tr>
            @endif

            <!-- Copyright -->
            <tr>
                <td align="center" style="padding-top: 16px;">
                    <p style="margin: 0; font-size: 11px; color: #a1a1aa;">
                        &copy; {{ date('Y') }} ScreenSense. All rights reserved.
                    </p>
                </td>
            </tr>
        </table>
    </td>
</tr>
