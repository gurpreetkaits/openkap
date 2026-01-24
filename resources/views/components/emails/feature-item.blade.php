@props([
    'title',
    'description',
])
<!-- Feature Item -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 16px;">
    <tr>
        <td width="40" valign="top" style="padding-right: 12px;">
            <div style="width: 32px; height: 32px; background-color: #fafafa; border: 1px solid #f4f4f5; border-radius: 8px; text-align: center; line-height: 32px; font-size: 14px;">
                &#9889;
            </div>
        </td>
        <td valign="top">
            <h3 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #18181b;">{{ $title }}</h3>
            <p style="margin: 0; font-size: 14px; color: #71717a; line-height: 1.5;">{{ $description }}</p>
        </td>
    </tr>
</table>
