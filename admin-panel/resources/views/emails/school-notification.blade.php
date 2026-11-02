@php
    $brandName = $brand['name'] ?? $schoolName;
    $colour = \App\Services\Platform\Notifications\EmailContent::colour($brand['colour'] ?? null);
    $crest = $brand['crest_url'] ?? null;
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>{{ $noticeTitle }}</title></head>
<body style="margin:0;padding:0;background:#F4F5F7;font-family:Arial,Helvetica,sans-serif;color:#1B1F24;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F5F7;"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border-radius:8px;overflow:hidden;">
    <tr><td style="background:{{ $colour }};height:6px;line-height:6px;font-size:0;">&nbsp;</td></tr>
    <tr><td style="padding:20px 24px 8px 24px;">
        <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            @if ($crest)<td style="padding-right:12px;vertical-align:middle;"><img src="{{ $crest }}" alt="" width="40" height="40" style="display:block;width:40px;height:40px;object-fit:contain;"></td>@endif
            <td style="vertical-align:middle;font-size:15px;font-weight:bold;color:#1B1F24;">{{ $brandName }}</td>
        </tr></table>
    </td></tr>
    <tr><td style="padding:8px 24px 0 24px;"><h1 style="margin:0;font-size:22px;line-height:1.3;color:#1B1F24;">{{ $noticeTitle }}</h1></td></tr>
    <tr><td style="padding:12px 24px 0 24px;font-size:16px;line-height:1.5;">
        @if ($contentHtml !== null)
            {!! $contentHtml !!}
        @else
            <p style="margin:0;">{{ $noticeBody }}</p>
        @endif
    </td></tr>
    <tr><td style="padding:20px 24px 24px 24px;">
        <a href="{{ $noticeUrl }}" style="display:inline-block;background:{{ $colour }};color:#FFFFFF;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:6px;">{{ __('Open NavigateEd') }}</a>
    </td></tr>
    <tr><td style="padding:16px 24px;border-top:1px solid #E3E6EA;font-size:12px;line-height:1.5;color:#5A6370;">
        {{ __('Sent by :school through NavigateEd.', ['school' => $brandName]) }}
        @if ($unsubscribeUrl)
            <br><a href="{{ $unsubscribeUrl }}" style="color:#5A6370;">{{ __('Stop these emails') }}</a> · {{ __('You can change all your notices in the NavigateEd app.') }}
        @endif
    </td></tr>
</table>
</td></tr></table>
</body></html>
