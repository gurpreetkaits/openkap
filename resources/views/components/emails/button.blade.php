@props([
    'url' => '#',
])
<!-- CTA Button -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
    <tr>
        <td align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:48px;v-text-anchor:middle;width:100%;" arcsize="17%" strokecolor="#ea580c" fillcolor="#ea580c">
            <w:anchorlock/>
            <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;">{{ $slot }} &rarr;</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ $url }}" style="display: inline-block; background-color: #ea580c; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; text-align: center; box-shadow: 0 2px 8px rgba(234,88,12,0.25);">
                {{ $slot }} &rarr;
            </a>
            <!--<![endif]-->
        </td>
    </tr>
</table>
