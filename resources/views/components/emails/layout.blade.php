@props([
    'title' => 'ScreenSense Update',
    'previewText' => '',
])
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>
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
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; padding: 16px !important; }
            .content-padding { padding: 24px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    @if($previewText)
    <!-- Preview text -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {{ $previewText }}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    @endif

    <!-- Email wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f5;">
        <tr>
            <td style="padding: 40px 16px;">
                <!-- Email container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                    <x-emails.header />

                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding: 32px 32px 24px 32px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <div style="height: 1px; background: linear-gradient(to right, transparent, #e4e4e7, transparent);"></div>
                        </td>
                    </tr>

                    <!-- Signature -->
                    <tr>
                        <td style="padding: 24px 32px 32px 32px;">
                            <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">
                                Best,<br>
                                <span style="color: #18181b; font-weight: 500;">The ScreenSense Team</span>
                            </p>
                        </td>
                    </tr>

                    <x-emails.footer />

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
