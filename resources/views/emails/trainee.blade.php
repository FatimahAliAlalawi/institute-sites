@php
    $primary = setting('color_primary', '#32255D');
    $siteName = setting('site_name', config('app.name'));
    $logo = media_url(setting('logo', 'assets/img/logo.png'));
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f3f8;font-family:Tahoma,Arial,sans-serif;color:#1c1830">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f8;padding:24px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden" dir="rtl">
            <tr><td style="background:{{ $primary }};padding:18px 22px;text-align:right">
                <img src="{{ $logo }}" alt="{{ $siteName }}" height="44" style="background:#fff;border-radius:8px;padding:4px 8px;vertical-align:middle">
            </td></tr>
            <tr><td style="padding:24px 22px;font-size:16px;line-height:1.9;text-align:right">{!! nl2br(e($body)) !!}</td></tr>
            <tr><td style="padding:14px 22px;background:#faf9fd;color:#6b6880;font-size:12px;text-align:right">
                {{ $siteName }}@if (setting('phone')) · <span dir="ltr">{{ setting('phone') }}</span>@endif
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
