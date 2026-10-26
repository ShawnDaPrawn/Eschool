<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>{{ $registration ? __('Tablet access code') : __('Teacher classroom authorization') }}</title><link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-components.css') }}"><script src="{{ asset('assets/js/foundation-management.js') }}" defer></script></head>
<body><main class="m3 page-content" style="max-width:40rem;margin:2rem auto;padding:1rem" data-foundation-management data-return-url="{{ route('foundation-panel.index') }}" data-expires-at="{{ $result['expires_at'] }}">
<h1>{{ $registration ? __('Tablet access code') : __('Teacher classroom authorization') }}</h1>
<p>{{ $registration ? __('Enter this private access code only on the school tablet that generated the setup code.') : __('Enter this short authorization only on the registered tablet for your allocated classroom lesson.') }}</p>
<p>{{ __('Keep this code private. Leaving this page or its expiry removes the displayed code. It cannot sign in to an ordinary school account.') }}</p>
<p><code data-foundation-secret style="overflow-wrap:anywhere">{{ $registration ? $result['tablet_access_code'] : $result['classroom_authorization'] }}</code></p>
<p>{{ __('Expires') }} {{ $result['expires_at'] }}</p>
<p><a href="{{ route('foundation-classroom.device') }}" target="_blank" rel="noopener noreferrer">{{ __('Open classroom tablet page') }}</a></p>
<p><a href="{{ route('foundation-panel.index') }}">{{ __('Return to Foundation classrooms') }}</a></p>
</main></body></html>
