@extends('layouts.master')
@section('title', __('Import by Navigate Ed'))
@section('content')
@php
    $statusLabels = ['create' => __('Will be imported'), 'lurits' => __('Already here, LURITS number added'), 'exists' => __('Already here, left alone'), 'transfer' => __('Start a transfer'), 'error' => __('Fix this row')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$import['kind_label']" :subtitle="$import['status_label']" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="import-summary"><div class="m3-card__content">
        <h2 id="import-summary">{{ __('About this import') }}</h2>
        <dl>
            <dt>{{ __('Prepared by') }}</dt><dd>{{ __('Navigate Ed (:name), :at', ['name' => $import['requested_by'] ?? __('Unknown'), 'at' => $import['sent_at']]) }}</dd>
            <dt>{{ __('To approve') }}</dt><dd>{{ $import['approver'] ?? __('Unknown') }}</dd>
            <dt>{{ __('Rows') }}</dt><dd>{{ __(':rows rows: :create to import, :lurits LURITS numbers to add, :exists already here.', ['rows' => $import['rows']] + $import['counts']) }}</dd>
            @if (! $import['expired'] && in_array($import['status'], ['awaiting_approval', 'approved'], true))
                <dt>{{ __('Expires') }}</dt><dd>{{ __(':at. The file is deleted then if it has not been imported.', ['at' => $import['expires_at']]) }}</dd>
            @endif
            @if ($import['decision_reason'])<dt>{{ __('Reason given') }}</dt><dd>{{ $import['decision_reason'] }}</dd>@endif
            @if ($import['close_reason'])<dt>{{ __('Navigate Ed\'s reason') }}</dt><dd>{{ $import['close_reason'] }}</dd>@endif
        </dl>
    </div></section>

    @if ($decides)
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="import-decision"><div class="m3-card__content">
            <h2 id="import-decision">{{ __('Your decision') }}</h2>
            <p>{{ __('Check the rows below. If you approve, Navigate Ed imports exactly these rows into the school, with your permissions. If the file or the school\'s records change first, the import is stopped.') }}</p>
            <form method="POST" action="{{ route('hq-imports.approve', $import['id']) }}" aria-label="{{ __('Approve the import') }}">
                @csrf
                <x-page.field name="reason" id="approve-reason" :label="__('Why approve it? For example, the list was checked against SA-SAMS.')" :restore-old-input="false" required minlength="5" maxlength="500" />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Approve the import') }}</button>
            </form>
            <form method="POST" action="{{ route('hq-imports.decline', $import['id']) }}" aria-label="{{ __('Decline the import') }}">
                @csrf
                <x-page.field name="reason" id="decline-reason" :label="__('Why decline it?')" :restore-old-input="false" required minlength="5" maxlength="500" />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Decline and delete the file') }}</button>
            </form>
        </div></section>
    @endif

    @if ($import['preview'] !== [])
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="import-settings"><div class="m3-card__content">
            <h2 id="import-settings">{{ __('Settings') }}</h2>
            <dl>
                @foreach ($import['settings'] as $setting)<dt>{{ $setting['label'] }}</dt><dd>{{ $setting['value'] }}</dd>@endforeach
            </dl>
        </div></section>

        <section class="page-section" aria-labelledby="import-rows">
            <h2 id="import-rows">{{ __('Rows in the file') }}</h2>
            <div class="page-table__scroll" role="region" aria-label="{{ __('Rows in the file') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Rows in the file') }}</caption>
                <thead><tr>
                    <th scope="col">{{ __('Line') }}</th>
                    <th scope="col">{{ __('Row') }}</th>
                    <th scope="col">{{ __('Result') }}</th>
                    <th scope="col">{{ __('What will be written') }}</th>
                    <th scope="col">{{ __('Notes') }}</th>
                </tr></thead>
                <tbody>
                @foreach ($import['preview'] as $row)
                    <tr>
                        <td>{{ $row['line'] }}</td>
                        <th scope="row">{{ $row['label'] }}</th>
                        <td>{{ $statusLabels[$row['status']] ?? $row['status'] }}</td>
                        <td>@if ($row['details'] !== [])<ul>@foreach ($row['details'] as $detail)<li>{{ $detail['label'] }}: {{ $detail['value'] }}</li>@endforeach</ul>@endif</td>
                        <td>{{ implode(' ', $row['notes']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    @endif

    <section class="page-section" aria-labelledby="import-steps">
        <h2 id="import-steps">{{ __('Every step') }}</h2>
        <ol>
            @foreach ($import['steps'] as $step)
                <li>{{ $step['at'] }} · {{ $step['label'] }} · {{ $step['actor'] }}@if ($step['reason']) · “{{ $step['reason'] }}”@endif @if ($step['stopped']) · {{ $step['stopped'] }}@endif</li>
            @endforeach
        </ol>
    </section>

    <p><a class="m3-button m3-button--text" href="{{ route('hq-imports.index') }}">{{ __('All imports by Navigate Ed') }}</a></p>
</div>
@endsection
