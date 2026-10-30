<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('Email notices') }} · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<main class="saas-main" id="main">
    <h1>{{ __('Email notices') }}</h1>
    @if ($result === 'unsubscribed' || ($result === 'already'))
        <p class="saas-ok" role="status">{{ __('You will no longer get emails about ":label" from :school.', ['label' => $preview['label'], 'school' => $preview['school']]) }}</p>
        <p>{{ __('You still see these notices in the NavigateEd app, and you can turn the emails back on in your notification settings there.') }}</p>
    @elseif ($result === 'required' || $preview['state'] === 'required')
        <p class="saas-card" role="status">{{ __(':school needs to send you ":label" by email, so it cannot be switched off here.', ['label' => $preview['label'], 'school' => $preview['school']]) }}</p>
    @elseif ($preview['state'] === 'off')
        <p class="saas-card" role="status">{{ __('Emails about ":label" from :school are already off.', ['label' => $preview['label'], 'school' => $preview['school']]) }}</p>
    @else
        <p class="saas-lead">{{ __('Stop emails about ":label" from :school?', ['label' => $preview['label'], 'school' => $preview['school']]) }}</p>
        <form method="POST" action="{{ request()->fullUrl() }}" class="saas-card" aria-label="{{ __('Stop these emails') }}">
            <p>{{ __('You will still see these notices in the NavigateEd app. Notices the school must send you, such as account and safety notices, keep coming by email.') }}</p>
            <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Stop these emails') }}</button></div>
        </form>
    @endif
</main>
</body>
</html>
