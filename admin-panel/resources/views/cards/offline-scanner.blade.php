@php
$readerMessages = [];
foreach (['A saved scan could not be verified. Use online verification.', 'Camera access is unavailable. Enter a current signed app code.', 'Camera scanning is unavailable here. Enter a current signed app code.', 'Current cached cards', 'Current card status changed. Use online verification.', 'Current reader or card access could not be verified.', 'Identity matched the current cached status. Early collection and sign-in are not authorized.', 'Invalid identity-only history receipt.', 'Offline cache expired. Reconnect to refresh current status.', 'Offline identity cache ready for up to sixty seconds.', 'Offline identity cache refreshed.', 'Offline verification requires a current signed app code. Secure taps and printed locators need online verification.', 'Portrait display timed out.', 'Protected device keys are unavailable. Use the online reader.', 'Protected device storage is unavailable. Use the online reader.', 'Reader access changed.', 'Reader access changed. Reconnect with a current registered reader.', 'Reader cleared. Reconnect before scanning again.', 'Reader key', 'Register this public key as a gate reader in School card readers. The private key remains on this device.', 'Saved scan outcome is unconfirmed. Retry synchronization when connected.', 'Saved scans awaiting synchronization', 'Select the device key registered for this reader.', 'The card is not in a current offline cache or the proof was already used. Reconnect to verify online.', 'The next cache page could not be prepared. Reconnect to verify uncached cards online.', 'Use the private credential for this registered gate reader.'] as $word) { $readerMessages[$word] = __($word); }
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer">
<title>{{ __('Offline school identity reader') }}</title>
<link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-components.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/card-reader.css') }}">
<script type="module" src="{{ asset('assets/js/card-offline-reader.mjs') }}"></script></head>
<body><main class="m3 school-identity-reader" data-offline-reader data-api-base="/api/v2/card-device" data-messages="{{ json_encode($readerMessages, JSON_UNESCAPED_UNICODE) }}">
<h1>{{ __('Offline school identity reader') }}</h1>
<p>{{ __('Cached identity and photos expire within one minute. Early collection and sign-in always need a separate online check.') }}</p>
<p><a href="{{ route('card-reader.index') }}">{{ __('Use the online reader') }}</a></p>
<noscript><p>{{ __('JavaScript is required for protected offline verification. No private credential is sent by these forms without it.') }}</p></noscript>
<fieldset data-offline-setup disabled><legend>{{ __('Prepare this device') }}</legend>
<button type="button" class="m3-button m3-button--outlined" data-offline-create-key>{{ __('Create protected reader key') }}</button>
<label for="offline-reader-key">{{ __('Reader key on this device') }}</label><select id="offline-reader-key" data-offline-keys></select>
<label for="offline-reader-public">{{ __('Public key for school reader registration') }}</label><textarea id="offline-reader-public" data-offline-public-key readonly rows="3"></textarea>
<p>{{ __('Register the public key as a gate reader in School card readers, then enter its private credential here. The private device key stays in this browser.') }}</p>
<form data-offline-pair method="post" action="{{ route('card-reader.offline') }}">
<label for="offline-reader-credential">{{ __('Private reader credential') }}</label><input id="offline-reader-credential" data-offline-credential type="password" autocomplete="off" spellcheck="false" required maxlength="160">
<button class="m3-button m3-button--filled" type="submit">{{ __('Prepare offline cache') }}</button></form></fieldset>
<section data-offline-ready hidden aria-labelledby="offline-scan-heading"><h2 id="offline-scan-heading">{{ __('Scan a current app code') }}</h2>
<div class="school-identity-reader-actions"><button data-offline-refresh class="m3-button m3-button--outlined" type="button">{{ __('Refresh cache online') }}</button><button data-offline-next hidden class="m3-button m3-button--outlined" type="button">{{ __('Cache next page') }}</button><button data-offline-sync class="m3-button m3-button--outlined" type="button">{{ __('Synchronize saved scans') }}</button><button data-offline-camera class="m3-button m3-button--outlined" type="button">{{ __('Use camera') }}</button><button data-offline-stop class="m3-button m3-button--outlined" type="button">{{ __('Clear reader') }}</button></div>
<video data-offline-video hidden playsinline muted aria-label="{{ __('Local card camera') }}"></video>
<form data-offline-manual method="post" action="{{ route('card-reader.offline') }}"><label for="offline-reader-proof">{{ __('Current signed app code') }}</label><input id="offline-reader-proof" data-offline-proof type="password" autocomplete="off" spellcheck="false" required maxlength="1024"><button class="m3-button m3-button--filled" type="submit">{{ __('Verify cached identity') }}</button></form>
<p data-offline-pending></p></section>
<p data-offline-cache-status role="status"></p><p data-offline-status role="status" aria-live="polite"></p>
<section data-offline-result hidden aria-labelledby="offline-result-heading"><h2 id="offline-result-heading">{{ __('Identity matched cached status') }}</h2><p>{{ __('Identity only: collection and sign-in are not authorized.') }}</p><img data-offline-portrait alt="{{ __('Private card portrait from the current cache') }}" width="128" height="128"></section>
</main></body></html>
