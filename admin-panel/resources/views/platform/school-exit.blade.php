@extends('layouts.master')

@section('title', __('Your school\'s data export'))

@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Your school\'s data export')" />
    @if ($errors->any())
        <div role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif
    @if ($offboarding === null)
        <p>{{ __('Your school is not leaving NavigateEd, so there is no export to collect.') }}</p>
    @else
        <div class="m3 m3-card m3-card--outlined">
            <p>{{ __('Your school is leaving NavigateEd. Before your school is archived, you receive a full export of your school\'s data. Download it, check it, and confirm below that you received it.') }}</p>
            <p>{{ __('Deadline to download and confirm: :date. After that date the export can no longer be downloaded.', ['date' => $offboarding['receipt_deadline']]) }}</p>
            <p>{{ __('NavigateEd deletes your school\'s data on :date, as your contract says.', ['date' => $offboarding['delete_on']]) }}</p>
        </div>
        <div class="m3 m3-card m3-card--outlined">
            @if ($offboarding['state'] === 'preparing')
                <p>{{ __('The export is being prepared. You will get a notice when it is ready.') }}</p>
            @else
                <p>{{ __('The export holds :tables tables and :rows rows, one file per table. Keep it safe: it contains personal information.', ['tables' => $offboarding['tables'], 'rows' => $offboarding['rows']]) }}</p>
                <p>{{ __('Fingerprint (SHA-256)') }}: <code>{{ $offboarding['sha256'] }}</code></p>
                @if ($offboarding['download_url'])
                    <p><a class="m3-button m3-button--filled" href="{{ $offboarding['download_url'] }}">{{ __('Download the export') }}</a></p>
                    <p>{{ __('The download link works for you only, for a few minutes. Reload this page for a new one.') }}</p>
                @else
                    <p>{{ __('This export is no longer available to download.') }}</p>
                @endif
                @if ($offboarding['state'] === 'ready' && $offboarding['available'])
                    <form method="POST" action="{{ route('school-exit.confirm', $offboarding['id']) }}">
                        @csrf
                        <label><input type="checkbox" name="confirm" value="1" required> {{ __('I downloaded the export and our school has received its data.') }}</label>
                        <p><button class="m3-button m3-button--outlined" type="submit">{{ __('Confirm receipt') }}</button></p>
                    </form>
                @elseif ($offboarding['received_at'])
                    <p>{{ $offboarding['receipt_basis'] === 'confirmed' ? __('Receipt confirmed :at.', ['at' => $offboarding['received_at']]) : __('The deadline passed :at; the export counts as received.', ['at' => $offboarding['received_at']]) }}</p>
                @endif
            @endif
        </div>
    @endif
</div>
@endsection
