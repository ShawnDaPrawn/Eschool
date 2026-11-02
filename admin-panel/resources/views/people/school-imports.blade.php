@extends('layouts.master')
@section('title', __('Import school data'))
@section('content')
@php
    $kindLabels = ['homerooms' => __('Homerooms'), 'learners' => __('Learners and guardians'), 'staff' => __('Staff')];
    $statusLabels = ['create' => __('Will be imported'), 'lurits' => __('Already here, LURITS number added'), 'exists' => __('Already here, left alone'), 'transfer' => __('Start a transfer'), 'error' => __('Fix this row')];
    $rank = ['error' => 0, 'transfer' => 1, 'create' => 2, 'lurits' => 3, 'exists' => 4];
    $writes = $check === null ? 0 : $check['counts']['create'] + $check['counts']['lurits'];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Import school data')" :subtitle="__('Bring in homerooms, then learners with their guardians, from a spreadsheet saved as CSV (for example the learner list from SA-SAMS). Every row is checked before anything is saved.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($problems as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @if (count($kinds) > 1)
        <nav class="page-section" aria-label="{{ __('What to import') }}">
            @foreach ($kinds as $option)
                <a class="m3-button {{ $option === $kind ? 'm3-button--filled' : 'm3-button--outlined' }}" href="{{ route('school-imports.index', ['kind' => $option]) }}" @if ($option === $kind) aria-current="page" @endif>{{ $kindLabels[$option] }}</a>
            @endforeach
        </nav>
    @endif

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="import-file"><div class="m3-card__content">
        <h2 id="import-file">{{ __('1. Choose the file') }}</h2>
        <p>
            @if ($kind === 'homerooms')
                {{ __('One row per homeroom: its grade (R or 1 to 12) and letter, and optionally the class name and medium. Homerooms that already exist are left alone.') }}
            @elseif ($kind === 'staff')
                {{ __('One row per staff member: names, email address and role (Teacher or one of the school\'s own roles), and optionally mobile number, gender and, for teachers, qualification. Nobody gets a password: each sets their own from an emailed invitation. People already on the staff are left alone.') }}
            @else
                {{ __('One row per learner: admission number, names, and the homeroom (for example Grade 8 B, or the grade and letter). Add the LURITS number, date of birth, gender and up to two guardians with their email addresses when you have them. Learners already at the school are left alone, so you can import the same file again.') }}
            @endif
        </p>
        <p><a class="m3-button m3-button--text" href="{{ route('school-imports.template', $kind) }}">{{ __('Download a template') }}</a></p>
        <form method="POST" action="{{ route('school-imports.check') }}" enctype="multipart/form-data" aria-label="{{ __('Choose the file') }}">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <p>
                <label for="import-upload">{{ __('CSV file') }}</label>
                <input type="file" id="import-upload" name="file" accept=".csv,.txt,text/csv">
            </p>
            <x-page.field name="csv" id="import-paste" type="textarea" :label="__('Or paste the rows, with the header row first')" :value="$check === null ? $csv : ''" :restore-old-input="false" />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Check the file') }}</button>
        </form>
    </div></section>

    @if ($check !== null)
        <form method="POST" action="{{ route('school-imports.check') }}" aria-label="{{ __('Check and import') }}">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="hidden" name="mapped" value="1">
            <input type="hidden" name="fingerprint" value="{{ $check['fingerprint'] }}">
            <textarea name="csv" hidden aria-hidden="true">{{ $csv }}</textarea>

            <section class="page-section" aria-labelledby="import-columns">
                <h2 id="import-columns">{{ __('2. Match the columns') }}</h2>
                <p>{{ __('We matched the columns we recognised. Change any that are wrong, then check again.') }}</p>
                @foreach ($check['missing'] as $missing)
                    <p role="alert">{{ __('Choose the column for :field.', ['field' => __($missing)]) }}</p>
                @endforeach
                @foreach ($fields as $field => $definition)
                    <x-page.field :name="'mapping['.$field.']'" :id="'import-map-'.$field" type="select" :label="__($definition['label']).($definition['required'] ? ' *' : '')"
                        :value="$check['mapping'][$field] ?? ''" :options="array_combine($check['headers'], $check['headers'])" :placeholder="__('Not in the file')" :restore-old-input="false" />
                @endforeach
            </section>

            <section class="page-section" aria-labelledby="import-settings">
                <h2 id="import-settings">{{ __('3. Settings') }}</h2>
                @if ($kind === 'staff')
                    <label><input type="checkbox" name="invite" value="1" @checked($check['options']['invite'])> {{ __('Email each new staff member an invitation to set their password now') }}</label>
                @elseif ($kind === 'homerooms')
                    <x-page.field name="medium_id" id="import-medium" type="select" :label="__('Medium for rows that do not name one')" :value="$check['options']['medium_id'] ?? ''" :options="$mediums" :placeholder="__('Choose a medium')" :restore-old-input="false" />
                @else
                    <x-page.field name="session_year_id" id="import-year" type="select" :label="__('School year')" :value="$check['options']['session_year_id']" :options="$years" :restore-old-input="false" />
                    <x-page.field name="starts_on" id="import-starts" type="date" :label="__('In their homerooms from')" :value="$check['options']['starts_on']" :restore-old-input="false" />
                    <x-page.field name="category_id" id="import-category" type="select" :label="__('Learner category')" :value="$check['options']['category_id']" :options="$categories" :restore-old-input="false" />
                    <label><input type="checkbox" name="invite" value="1" @checked($check['options']['invite'])> {{ __('Email the guardians their invitations to sign in and approve their children now') }}</label>
                    <label><input type="checkbox" name="from_sasams" value="1" @checked($check['options']['from_sasams'])> {{ __('This is a learner list exported from SA-SAMS (the LURITS numbers are recorded as coming from SA-SAMS)') }}</label>
                @endif
            </section>

            <section class="page-section" aria-labelledby="import-rows">
                <h2 id="import-rows">{{ __('4. Check the rows') }}</h2>
                <p>{{ __(':create to import, :exists already here, :transfer to transfer, :error to fix.', $check['counts']) }}</p>
                @if ($check['counts']['lurits'] > 0)
                    <p>{{ trans_choice(':count learner already here gets the LURITS number from the file.|:count learners already here get the LURITS number from the file.', $check['counts']['lurits']) }}</p>
                @endif
                @if ($check['rows'] !== [])
                    <div class="page-table__scroll" role="region" aria-label="{{ __('Rows in the file') }}" tabindex="0"><table class="page-table__table">
                        <caption class="m3-visually-hidden">{{ __('Rows in the file') }}</caption>
                        <thead><tr>
                            <th scope="col">{{ __('Line') }}</th>
                            <th scope="col">{{ ['homerooms' => __('Homeroom'), 'staff' => __('Staff member')][$kind] ?? __('Learner') }}</th>
                            @if ($kind === 'learners')<th scope="col">{{ __('Homeroom') }}</th><th scope="col">{{ __('Guardians') }}</th>@endif
                            <th scope="col">{{ __('Result') }}</th>
                            <th scope="col">{{ __('Notes') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach (collect($check['rows'])->sortBy(fn ($row) => [$rank[$row['status']], $row['line']]) as $row)
                            <tr>
                                <td>{{ $row['line'] }}</td>
                                <th scope="row">{{ $row['label'] }}</th>
                                @if ($kind === 'learners')<td>{{ $row['homeroom'] }}</td><td>{{ $row['guardians'] }}</td>@endif
                                <td>{{ $statusLabels[$row['status']] }}</td>
                                <td>{{ implode(' ', $row['notes']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </section>

            <button class="m3-button m3-button--outlined" type="submit">{{ __('Check again') }}</button>
            <button class="m3-button m3-button--filled" type="submit" formaction="{{ route('school-imports.store') }}"
                @disabled($check['missing'] !== [] || $check['counts']['error'] > 0 || $writes === 0)>{{ trans_choice('Import :count row|Import :count rows', $writes, ['count' => $writes]) }}</button>
            @if ($check['counts']['error'] > 0)
                <p>{{ __('Fix the rows with problems in the file and check it again, or remove them.') }}</p>
            @endif
        </form>
    @endif
</div>
@endsection
