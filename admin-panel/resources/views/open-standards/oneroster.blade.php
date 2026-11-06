@extends('layouts.master')

@section('title', __('OneRoster export'))

@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('OneRoster export')" :subtitle="__('Share your school\'s classes, learners and teachers with another system in the OneRoster 1.2 format.')" />
    @if ($errors->any())
        <div role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <section class="page-section" aria-labelledby="oneroster-what">
        <h2 id="oneroster-what">{{ __('What the export holds') }}</h2>
        <p>{{ __('This year\'s school year and terms, homerooms and teaching groups, and the learners and teachers in them today. Learners are identified by their LURITS number, or by their NavigateEd number when they have no LURITS number yet.') }}</p>
        <p>{{ __('It never holds guardians, contact details of learners, marks, health or wellbeing records, or anything from another school.') }}</p>
        <p>{{ __('School year') }}: {{ $year ?? '—' }}</p>
    </section>

    <section class="page-section" aria-labelledby="oneroster-new">
        <h2 id="oneroster-new">{{ __('Prepare an export') }}</h2>
        <form method="POST" action="{{ route('open-standards.store') }}" aria-label="{{ __('Prepare a OneRoster export') }}">
            @csrf
            <x-page.field name="reason" type="textarea" :label="__('Why are you sharing the roster, and with whom?')" :help="__('This is recorded with the export.')" maxlength="500" required />
            @if ($may_include_demographics)
                <x-m3.checkbox name="include_demographics" value="1" :label="__('Also include learners\' birth dates and sex (demographics.csv)')" />
            @else
                <p>{{ __('Birth dates and sex are not included. Including them needs the sensitive profile export ability.') }}</p>
            @endif
            <p><button class="m3-button m3-button--filled" type="submit">{{ __('Prepare the export') }}</button></p>
        </form>
    </section>

    <section class="page-section" aria-labelledby="oneroster-yours">
        <h2 id="oneroster-yours">{{ __('Your recent exports') }}</h2>
        @if ($exports === [])
            <p>{{ __('You have not prepared an export yet.') }}</p>
        @else
            <table class="page-table">
                <thead><tr><th scope="col">{{ __('Asked for') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Records') }}</th><th scope="col">{{ __('Download') }}</th></tr></thead>
                <tbody>
                    @foreach ($exports as $export)
                        <tr>
                            <td>{{ $export['requested_at'] }}{{ $export['demographics'] ? ' · '.__('with birth dates and sex') : '' }}</td>
                            <td>{{ match ($export['state']) { 'ready' => __('Ready'), 'failed' => __('Could not be prepared'), 'purged' => __('No longer available'), default => __('Being prepared') } }}</td>
                            <td>{{ array_sum($export['counts']) }}</td>
                            <td>
                                @if ($export['download_url'])
                                    <a class="m3-button m3-button--text" href="{{ $export['download_url'] }}">{{ __('Download the ZIP') }}</a>
                                    <br><small>{{ __('Available until :date. The link works for you only, for a few minutes; reload this page for a new one.', ['date' => $export['expires_at']]) }}</small>
                                @elseif ($export['state'] === 'ready')
                                    {{ __('No longer available') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
