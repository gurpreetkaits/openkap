@php
    $firstName = isset($user) ? explode(' ', trim($user->name))[0] : ($firstName ?? 'there');
    $frontendUrl = config('services.frontend.url', 'http://localhost:3333');
    $appUrl = config('app.url', 'http://localhost:8888');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Introducing Jira Integration</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .fluid-padding { padding-left: 24px !important; padding-right: 24px !important; }
            .hero-padding { padding: 40px 24px !important; }
            .stack-column { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #e4e4e7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        OpenKap now integrates with Jira &mdash; detect bugs from recordings and create issues in one click.
    </div>

    <!-- Email wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #e4e4e7;">
        <tr>
            <td style="padding: 32px 16px;">
                <!-- Email container -->
                <table class="email-container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10), 0 1px 3px rgba(0,0,0,0.06);">

                    <!-- ===== HERO BANNER ===== -->
                    <tr>
                        <td class="hero-padding" style="background: linear-gradient(135deg, #18181b 0%, #27272a 50%, #1c1917 100%); padding: 48px 40px 40px 40px; text-align: center; position: relative;">
                            <!-- Logo + Brand -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="text-align: center;">
                                        <img src="{{ url('/logo.png') }}" alt="OpenKap" width="44" height="44" style="width: 44px; height: 44px; border-radius: 12px; margin-bottom: 8px;">
                                        <br>
                                        <span style="font-size: 15px; font-weight: 600; color: #ffffff; letter-spacing: 0.03em;">OpenKap</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Product Preview Mockup -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 32px;">
                                <tr>
                                    <td>
                                        <div style="background-color: #27272a; border: 1px solid #3f3f46; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
                                            <!-- Window chrome -->
                                            <div style="padding: 10px 14px; border-bottom: 1px solid #3f3f46; display: flex;">
                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #ef4444; border-radius: 50%; margin-right: 5px;"></span>
                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #eab308; border-radius: 50%; margin-right: 5px;"></span>
                                                <span style="display: inline-block; width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%;"></span>
                                            </div>
                                            <!-- Mockup content -->
                                            <div style="padding: 20px;">
                                                <!-- Bug card mockup -->
                                                <div style="background-color: #1c1917; border: 1px solid #3f3f46; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="10" valign="top" style="padding-right: 10px; padding-top: 2px;">
                                                                <div style="width: 8px; height: 8px; background-color: #ef4444; border-radius: 50%;"></div>
                                                            </td>
                                                            <td>
                                                                <div style="font-size: 12px; font-weight: 600; color: #fafafa; margin-bottom: 4px;">Login page not loading on Safari</div>
                                                                <div style="font-size: 10px; color: #a1a1aa;">HIGH &bull; Steps: 3 &bull; 0:42</div>
                                                            </td>
                                                            <td width="90" style="text-align: right;">
                                                                <div style="display: inline-block; background-color: #ea580c; color: #ffffff; font-size: 9px; font-weight: 600; padding: 4px 10px; border-radius: 4px;">Create in Jira</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <!-- Second bug card -->
                                                <div style="background-color: #1c1917; border: 1px solid #3f3f46; border-radius: 8px; padding: 14px 16px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="10" valign="top" style="padding-right: 10px; padding-top: 2px;">
                                                                <div style="width: 8px; height: 8px; background-color: #f59e0b; border-radius: 50%;"></div>
                                                            </td>
                                                            <td>
                                                                <div style="font-size: 12px; font-weight: 600; color: #fafafa; margin-bottom: 4px;">Cart total doesn't update on quantity change</div>
                                                                <div style="font-size: 10px; color: #a1a1aa;">MEDIUM &bull; Steps: 2 &bull; 1:15</div>
                                                            </td>
                                                            <td width="90" style="text-align: right;">
                                                                <div style="display: inline-block; background-color: #ea580c; color: #ffffff; font-size: 9px; font-weight: 600; padding: 4px 10px; border-radius: 4px;">Create in Jira</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ===== BODY CONTENT ===== -->
                    <tr>
                        <td class="fluid-padding" style="padding: 40px 44px 16px 44px;">

                            <!-- Greeting -->
                            <p style="margin: 0 0 24px 0; font-size: 15px; color: #52525b; line-height: 1.5;">
                                Hi {{ $firstName }},
                            </p>

                            <!-- Badge -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 16px;">
                                <tr>
                                    <td style="background-color: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 9999px; padding: 5px 14px;">
                                        <span style="font-size: 11px; font-weight: 700; color: #18181b; text-transform: uppercase; letter-spacing: 0.06em;">New Integration</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Headline -->
                            <h1 style="margin: 0 0 14px 0; font-size: 28px; font-weight: 700; color: #18181b; line-height: 1.2; letter-spacing: -0.02em;">
                                Introducing <span style="color: #18181b;">Jira Integration</span>
                            </h1>

                            <!-- Description -->
                            <p style="margin: 0 0 36px 0; font-size: 16px; color: #71717a; line-height: 1.7;">
                                OpenKap now analyzes your recordings, detects bugs from the transcript, and lets you push them straight to Jira &mdash; no copy-pasting, no context switching.
                            </p>
                        </td>
                    </tr>

                    <!-- ===== HOW IT WORKS ===== -->
                    <tr>
                        <td class="fluid-padding" style="padding: 0 44px 40px 44px;">
                            <!-- Section label -->
                            <p style="margin: 0 0 20px 0; font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.1em;">
                                How it works
                            </p>

                            <!-- Step 1 -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td width="48" valign="top" style="padding-right: 14px;">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fed7aa; border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px;">
                                            &#127909;
                                        </div>
                                    </td>
                                    <td valign="center">
                                        <h3 style="margin: 0 0 2px 0; font-size: 15px; font-weight: 600; color: #18181b;">Record</h3>
                                        <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">Capture your screen in one click</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Step 2 -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td width="48" valign="top" style="padding-right: 14px;">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fed7aa; border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px;">
                                            &#129302;
                                        </div>
                                    </td>
                                    <td valign="center">
                                        <h3 style="margin: 0 0 2px 0; font-size: 15px; font-weight: 600; color: #18181b;">Analyze</h3>
                                        <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">AI detects bugs from your video transcript automatically</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Step 3 -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 0;">
                                <tr>
                                    <td width="48" valign="top" style="padding-right: 14px;">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fed7aa; border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px;">
                                            &#128027;
                                        </div>
                                    </td>
                                    <td valign="center">
                                        <h3 style="margin: 0 0 2px 0; font-size: 15px; font-weight: 600; color: #18181b;">Create</h3>
                                        <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">Push bugs to Jira in one click with full context</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ===== CTA BUTTON ===== -->
                    <tr>
                        <td class="fluid-padding" style="padding: 0 44px 40px 44px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $frontendUrl }}/integrations" style="display: inline-block; background-color: #ea580c; color: #ffffff; padding: 14px 40px; border-radius: 50px; font-size: 14px; font-weight: 600; text-decoration: none; text-align: center; box-shadow: 0 2px 8px rgba(234,88,12,0.3);">
                                            Connect Jira Now &rarr;
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 14px;">
                                        <a href="{{ $frontendUrl }}/integrations" style="font-size: 13px; color: #71717a; text-decoration: none;">
                                            Learn more about integrations &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ===== FOOTER ===== -->
                    <tr>
                        <td style="background-color: #fafafa; border-top: 1px solid #e4e4e7;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td class="fluid-padding" style="padding: 28px 44px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td>
                                                    <img src="{{ url('/logo.png') }}" alt="OpenKap" width="24" height="24" style="width: 24px; height: 24px; border-radius: 6px; vertical-align: middle; margin-right: 8px;">
                                                    <span style="font-size: 13px; font-weight: 600; color: #3f3f46; vertical-align: middle;">OpenKap</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 12px;">
                                                    <p style="margin: 0; font-size: 12px; color: #a1a1aa; line-height: 1.6;">
                                                        You're receiving this because you have an account on OpenKap.
                                                        <br>
                                                        <a href="{{ $frontendUrl }}/settings" style="color: #71717a; text-decoration: underline;">Manage preferences</a>
                                                        &nbsp;&middot;&nbsp;
                                                        <a href="{{ $frontendUrl }}/settings" style="color: #71717a; text-decoration: underline;">Unsubscribe</a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
