@php
    $badgeText = $badgeText ?? 'Product Update';
    $headline = $headline ?? '';
    $subheadline = $subheadline ?? null;
    $description = $description ?? '';
    $features = $features ?? [];
    $ctaText = $ctaText ?? 'Learn More';
    $ctaUrl = $ctaUrl ?? '#';
    $showVisualCard = $showVisualCard ?? true;
    $visualCardItems = $visualCardItems ?? [];
    $previewText = $previewText ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $headline }}</title>
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
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    @if($previewText)
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {{ $previewText }}
    </div>
    @endif

    <!-- Email wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f5;">
        <tr>
            <td style="padding: 40px 16px;">
                <!-- Email container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 24px 32px 16px 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td>
                                        <a href="{{ config('app.url') }}" style="text-decoration: none;">
                                            <img src="{{ url('/logo.png') }}" alt="OpenKap" width="32" height="32" style="width: 32px; height: 32px; border-radius: 8px; vertical-align: middle; margin-right: 10px;">
                                            <span style="font-size: 14px; font-weight: 600; color: #18181b; vertical-align: middle;">OpenKap</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 16px 32px 24px 32px;">

                            <!-- Badge -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="background-color: #fafafa; border: 1px solid #e4e4e7; border-radius: 9999px; padding: 6px 14px;">
                                        <span style="font-size: 11px; font-weight: 600; color: #52525b; text-transform: uppercase; letter-spacing: 0.05em;">✨ {{ $badgeText }}</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Headline -->
                            <h1 style="margin: 0 0 12px 0; font-size: 28px; font-weight: 600; color: #18181b; line-height: 1.2;">
                                {!! nl2br(e($headline)) !!}
                                @if($subheadline)
                                <br><span style="color: #18181b;">{!! nl2br(e($subheadline)) !!}</span>
                                @endif
                            </h1>

                            <!-- Description -->
                            <p style="margin: 0 0 24px 0; font-size: 15px; color: #71717a; line-height: 1.6;">
                                {{ $description }}
                            </p>

                            <!-- Visual Card -->
                            @if($showVisualCard)
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fafafa; border: 1px solid #e4e4e7; border-radius: 12px; padding: 24px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 16px; border-bottom: 1px solid #e4e4e7;">
                                                    <span style="display: inline-block; width: 8px; height: 8px; background-color: #d4d4d8; border-radius: 50%; margin-right: 4px;"></span>
                                                    <span style="display: inline-block; width: 8px; height: 8px; background-color: #e4e4e7; border-radius: 50%;"></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 16px;">
                                                    <div style="background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 12px; margin-bottom: 8px;">
                                                        <div style="width: 60%; height: 8px; background-color: #e4e4e7; border-radius: 4px;"></div>
                                                    </div>
                                                    <div style="background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 12px;">
                                                        <div style="width: 80%; height: 8px; background-color: #e4e4e7; border-radius: 4px;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Features -->
                            @if(count($features) > 0)
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                @foreach($features as $feature)
                                <tr>
                                    <td style="padding-bottom: 16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td width="44" valign="top" style="padding-right: 12px;">
                                                    <div style="width: 32px; height: 32px; background-color: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; text-align: center; line-height: 30px; font-size: 14px;">
                                                        ⚡
                                                    </div>
                                                </td>
                                                <td valign="top">
                                                    <h3 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #18181b;">{{ $feature['title'] }}</h3>
                                                    <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">{{ $feature['description'] }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ctaUrl }}" style="display: inline-block; background-color: #ea580c; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; text-align: center;">
                                            {{ $ctaText }} →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <div style="height: 1px; background-color: #e4e4e7;"></div>
                        </td>
                    </tr>

                    <!-- Signature -->
                    <tr>
                        <td style="padding: 24px 32px;">
                            <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">
                                Best,<br>
                                <span style="color: #18181b; font-weight: 500;">The OpenKap Team</span>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
