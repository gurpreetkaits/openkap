@props([
    'logoUrl' => null,
    'appName' => 'OpenKap',
])
<!-- Header -->
<tr>
    <td style="padding: 24px 32px 16px 32px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td>
                    <a href="{{ $logoUrl ?? config('app.url') }}" style="text-decoration: none; display: inline-flex; align-items: center;">
                        <img src="{{ url('/logo.png') }}" alt="{{ $appName }}" width="32" height="32" style="width: 32px; height: 32px; border-radius: 8px; margin-right: 10px;">
                        <span style="font-size: 14px; font-weight: 600; color: #18181b; letter-spacing: -0.025em;">{{ $appName }}</span>
                    </a>
                </td>
            </tr>
        </table>
    </td>
</tr>
