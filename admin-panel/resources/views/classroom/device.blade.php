<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer">
<title>{{ __('Classroom practice') }}</title>
<link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/m3-components.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/foundation-device.css') }}">
<script src="{{ asset('assets/js/foundation-device.js') }}" defer></script></head>
<body>
@php
$foundationMessages = [
    'Ask your teacher to pair this tablet.',
    'By myself',
    'Camera access is unavailable. Enter the printed classroom code.',
    'Camera scanning is unavailable. Enter the printed classroom code.',
    'Camera scanning stopped. Enter the printed classroom code.',
    'Check your classroom entry or answer, then ask your teacher if needed.',
    'Choose an answer or enter a number using a dot.',
    'Choose your answer',
    'Classroom access changed. Ask your teacher to pair again.',
    'Easy for me',
    'Give only this setup code to your school administrator.',
    'Great practice today!',
    'How did it feel?',
    'Let us learn together.',
    'Next question',
    'Please wait.',
    'Ready for your classroom practice.',
    'Retry the same request before starting another question.',
    'Save my answer',
    'Saved practice could not be checked. Try reading it again.',
    'Scan your classroom card, or enter its printed classroom code.',
    'Tablet setup is unavailable in this browser. Ask your school administrator.',
    'This classroom session ended. Ask your teacher to pair again.',
    'This tablet is clear. Ask your teacher before the next learner.',
    'Try one question. Ask your teacher whenever you need help.',
    'Use a printed card from this classroom school.',
    'Use the tablet access code and your teacher classroom authorization.',
    'We could not confirm the result. Retry the same request.',
    'Well done!',
    'With help',
    'Your classroom practice is ready.',
    'Your number',
    'Your saved practice is ready for your next lesson.'
];
$foundationCopy = array_combine($foundationMessages, array_map(fn ($message) => __($message), $foundationMessages));
@endphp
<main class="m3 foundation-tablet" data-foundation-device data-messages="{{ json_encode($foundationCopy, JSON_UNESCAPED_UNICODE) }}">
<h1>{{ __('Classroom practice') }}</h1>
<noscript><p role="alert">{{ __('This classroom needs a supported browser. Ask your teacher for help.') }}</p></noscript>
<p>{{ __('A short classroom lesson with your teacher. This page does not sign in to an ordinary school account or record a gate scan.') }}</p>
<section data-foundation-pairing aria-labelledby="foundation-pair-heading">
<h2 id="foundation-pair-heading">{{ __('Teacher tablet setup') }}</h2>
<p>{{ __('A school administrator registers this tablet first. Your allocated Teacher then authorizes one approved classroom group.') }}</p>
<button type="button" class="m3-button m3-button--outlined" data-foundation-setup data-foundation-write disabled>{{ __('Generate tablet setup code') }}</button>
<div data-foundation-setup-result hidden><p>{{ __('Tablet setup code') }}</p><p><code data-foundation-setup-code></code></p></div>
<form data-foundation-pair-form method="POST" action="{{ route('api.v2.foundation.device.select') }}" autocomplete="off">
<label for="foundation-tablet-code">{{ __('Tablet access code') }}</label><input id="foundation-tablet-code" data-foundation-tablet-code type="password" maxlength="159" required autocomplete="off" spellcheck="false" data-foundation-write disabled>
<label for="foundation-teacher-code">{{ __('Teacher classroom authorization') }}</label><input id="foundation-teacher-code" data-foundation-authorization type="password" maxlength="159" required autocomplete="off" spellcheck="false" data-foundation-write disabled>
<button type="submit" class="m3-button m3-button--filled" data-foundation-write disabled>{{ __('Pair classroom tablet') }}</button>
</form></section>
<section data-foundation-ready hidden aria-labelledby="foundation-card-heading">
<h2 id="foundation-card-heading">{{ __('Choose your classroom card') }}</h2>
<div class="foundation-actions"><button type="button" class="m3-button m3-button--outlined" data-foundation-camera data-foundation-write disabled>{{ __('Use classroom card camera') }}</button><button type="button" class="m3-button m3-button--outlined" data-foundation-stop-camera>{{ __('Stop camera') }}</button></div>
<video data-foundation-video hidden muted playsinline aria-label="{{ __('Local classroom card camera') }}"></video>
<form data-foundation-select-form method="POST" action="{{ route('api.v2.foundation.device.select') }}" autocomplete="off"><label for="foundation-locator">{{ __('Printed classroom card code') }}</label><input id="foundation-locator" data-foundation-locator maxlength="90" required autocomplete="off" spellcheck="false" data-foundation-write disabled><button type="submit" class="m3-button m3-button--filled" data-foundation-write disabled>{{ __('Choose this classroom card') }}</button></form>
</section>
<section data-foundation-practice hidden aria-labelledby="foundation-learner-name"><h2 id="foundation-learner-name" data-foundation-name></h2>
<div data-foundation-questions></div><button type="button" data-foundation-start data-foundation-write class="m3-button m3-button--filled">{{ __('Start my practice') }}</button>
</section>
<p data-foundation-status role="status" aria-live="polite"></p>
<div class="foundation-actions"><button type="button" data-foundation-retry hidden class="m3-button m3-button--filled">{{ __('Retry same request') }}</button><button type="button" data-foundation-refresh hidden class="m3-button m3-button--outlined">{{ __('Read saved practice') }}</button><button type="button" data-foundation-exit class="m3-button m3-button--outlined">{{ __('Finish and clear tablet') }}</button></div>
<p>{{ __('Leaving this page clears the learner, answers, codes and camera. Ask your Teacher to pair it again. Saved practice remains at school.') }}</p>
</main></body></html>
