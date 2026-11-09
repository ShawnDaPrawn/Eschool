@extends('saas.layout')

@section('title', __('Imports for :school', ['school' => $school['name']]))

@use('App\Services\Platform\Saas\PlatformSchoolImports')
@php
    $statusLabels = ['create' => __('Will be imported'), 'lurits' => __('Already here, LURITS number added'), 'exists' => __('Already here, left alone'), 'transfer' => __('Start a transfer'), 'error' => __('Fix this row')];
    $rank = ['error' => 0, 'transfer' => 1, 'create' => 2, 'lurits' => 3, 'exists' => 4];
    $writes = $check === null ? 0 : $check['counts']['create'] + $check['counts']['lurits'];
    $eligible = array_filter($approvers, fn (array $approver) => in_array($kind, $approver['kinds'], true));
@endphp

@section('content')
<h1>{{ __('Imports for :school', ['school' => $school['name']]) }}</h1>
<p class="saas-lead">{{ __('Run the school\'s own onboarding imports for it: homerooms, learners with their guardians, or staff. Check the file first (nothing is written), then send it to a leader at the school, who approves or declines it in their panel. Once they approve, apply it here. The school sees every step.') }}</p>
<p>{{ __('The file holds personal information. It is stored encrypted until the import is applied, declined, cancelled or expires (:days days after it is sent), and then deleted.', ['days' => PlatformSchoolImports::EXPIRY_DAYS]) }}</p>
@foreach ($problems as $problem)<p role="alert" class="saas-error">{{ $problem }}</p>@endforeach

<h2>{{ __('Imports for this school') }}</h2>
@if ($imports === [])
    <p class="saas-card">{{ __('No imports yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Imports for this school') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Sent') }}</th><th scope="col">{{ __('Import') }}</th><th scope="col">{{ __('Rows') }}</th><th scope="col">{{ __('Approver') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
        <tbody>
        @foreach ($imports as $import)
            <tr>
                <td><a href="{{ route('saas.imports.show', $import['id']) }}">{{ $import['sent_at'] }}</a></td>
                <th scope="row">{{ $import['kind_label'] }}</th>
                <td>{{ $import['rows'] }}</td>
                <td>{{ $import['approver'] ?? __('Unknown') }}</td>
                <td><span class="saas-badge">{{ $import['status_label'] }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="check">{{ __('1. Check a file') }}</h2>
<form method="POST" action="{{ route('saas.schools.imports.preview', $school['id']) }}" enctype="multipart/form-data" class="saas-card" aria-label="{{ __('Check a file') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field">
            <label for="import-kind">{{ __('What to import') }}</label>
            <select id="import-kind" name="kind" required>
                @foreach (PlatformSchoolImports::KINDS as $value => $label)<option value="{{ $value }}" @selected($kind === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="import-approver">{{ __('Leader at the school who approves it') }}</label>
            <select id="import-approver" name="approver_id" required>
                <option value="">{{ __('Choose a leader') }}</option>
                @foreach ($approvers as $id => $approver)
                    <option value="{{ $id }}" @selected($approverId === $id)>{{ $approver['name'] }} ({{ collect($approver['kinds'])->map(fn ($k) => mb_strtolower(__(PlatformSchoolImports::KINDS[$k])))->implode(', ') }})</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="import-upload">{{ __('CSV file') }}</label>
            <input type="file" id="import-upload" name="file" accept=".csv,.txt,text/csv">
        </div>
        <div class="saas-field">
            <label for="import-paste">{{ __('Or paste the rows, with the header row first') }}</label>
            <textarea id="import-paste" name="csv" rows="5">{{ $check === null ? $csv : '' }}</textarea>
        </div>
        <div class="saas-field">
            <label for="import-reason">{{ __('Why Navigate Ed is importing this for the school (the school sees this)') }}</label>
            <input id="import-reason" name="reason" required minlength="5" maxlength="500" value="{{ $check === null ? $reason : '' }}">
        </div>
    </div>
    <p>{{ __('The check runs in the school as the leader you choose, with their permissions there, so it shows exactly what their own import would do.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Check the file') }}</button></div>
</form>

@if ($check !== null)
<form method="POST" action="{{ route('saas.schools.imports.preview', $school['id']) }}" aria-label="{{ __('Check and send for approval') }}">
    @csrf
    <input type="hidden" name="kind" value="{{ $kind }}">
    <input type="hidden" name="approver_id" value="{{ $approverId }}">
    <input type="hidden" name="mapped" value="1">
    <input type="hidden" name="fingerprint" value="{{ $check['fingerprint'] }}">
    <textarea name="csv" hidden aria-hidden="true">{{ $csv }}</textarea>

    <section class="saas-card" aria-labelledby="import-columns">
        <h2 id="import-columns">{{ __('2. Match the columns') }}</h2>
        @foreach ($check['missing'] as $missing)<p role="alert">{{ __('Choose the column for :field.', ['field' => __($missing)]) }}</p>@endforeach
        <div class="saas-grid">
            @foreach ($fields as $field => $definition)
                <div class="saas-field">
                    <label for="import-map-{{ $field }}">{{ __($definition['label']) }}{{ $definition['required'] ? ' *' : '' }}</label>
                    <select id="import-map-{{ $field }}" name="mapping[{{ $field }}]">
                        <option value="">{{ __('Not in the file') }}</option>
                        @foreach ($check['headers'] as $header)<option value="{{ $header }}" @selected(($check['mapping'][$field] ?? '') === $header)>{{ $header }}</option>@endforeach
                    </select>
                </div>
            @endforeach
        </div>
    </section>

    <section class="saas-card" aria-labelledby="import-settings">
        <h2 id="import-settings">{{ __('3. Settings') }}</h2>
        @if ($kind === 'staff')
            <label><input type="checkbox" name="invite" value="1" @checked($check['options']['invite'])> {{ __('Email each new staff member an invitation to set their password once it is imported') }}</label>
        @elseif ($kind === 'homerooms')
            <div class="saas-field">
                <label for="import-medium">{{ __('Medium for rows that do not name one') }}</label>
                <select id="import-medium" name="medium_id"><option value="">{{ __('Choose a medium') }}</option>
                    @foreach ($choices['mediums'] as $id => $name)<option value="{{ $id }}" @selected(($check['options']['medium_id'] ?? null) === $id)>{{ $name }}</option>@endforeach
                </select>
            </div>
        @else
            <div class="saas-grid">
                <div class="saas-field">
                    <label for="import-year">{{ __('School year') }}</label>
                    <select id="import-year" name="session_year_id">
                        @foreach ($choices['years'] as $id => $name)<option value="{{ $id }}" @selected($check['options']['session_year_id'] === $id)>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="saas-field">
                    <label for="import-starts">{{ __('In their homerooms from') }}</label>
                    <input id="import-starts" name="starts_on" type="date" value="{{ $check['options']['starts_on'] }}">
                </div>
                <div class="saas-field">
                    <label for="import-category">{{ __('Learner category') }}</label>
                    <select id="import-category" name="category_id">
                        @foreach ($choices['categories'] as $id => $name)<option value="{{ $id }}" @selected($check['options']['category_id'] === $id)>{{ $name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label><input type="checkbox" name="invite" value="1" @checked($check['options']['invite'])> {{ __('Email the guardians their invitations once it is imported') }}</label>
            <label><input type="checkbox" name="from_sasams" value="1" @checked($check['options']['from_sasams'])> {{ __('This is a learner list exported from SA-SAMS') }}</label>
        @endif
    </section>

    <section class="saas-card" aria-labelledby="import-why">
        <h2 id="import-why">{{ __('Reason') }}</h2>
        <div class="saas-field">
            <label for="import-reason-again">{{ __('Why Navigate Ed is importing this for the school (the school sees this)') }}</label>
            <input id="import-reason-again" name="reason" required minlength="5" maxlength="500" value="{{ $reason }}">
        </div>
    </section>

    <section class="saas-card" aria-labelledby="import-rows">
        <h2 id="import-rows">{{ __('4. Check the rows') }}</h2>
        <p>{{ __(':create to import, :lurits LURITS numbers to add, :exists already here, :transfer to transfer, :error to fix.', $check['counts']) }}</p>
        @if ($check['rows'] !== [])
            <div class="saas-table-wrap" role="region" aria-label="{{ __('Rows in the file') }}" tabindex="0"><table class="saas-table">
                <thead><tr><th scope="col">{{ __('Line') }}</th><th scope="col">{{ __('Row') }}</th><th scope="col">{{ __('Result') }}</th><th scope="col">{{ __('Notes') }}</th></tr></thead>
                <tbody>
                @foreach (collect($check['rows'])->sortBy(fn ($row) => [$rank[$row['status']], $row['line']]) as $row)
                    <tr><td>{{ $row['line'] }}</td><th scope="row">{{ $row['label'] }}</th><td>{{ $statusLabels[$row['status']] }}</td><td>{{ implode(' ', $row['notes']) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <div class="saas-actions">
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Check again') }}</button>
        <button class="saas-btn" type="submit" formaction="{{ route('saas.schools.imports.store', $school['id']) }}"
            @disabled($check['missing'] !== [] || $check['counts']['error'] > 0 || $writes === 0)>{{ __('Send :count rows to :name for approval', ['count' => $writes, 'name' => $approvers[$approverId]['name'] ?? __('the leader')]) }}</button>
    </div>
    @if ($check['counts']['error'] > 0)<p>{{ __('Fix the rows with problems in the file and check it again, or remove them.') }}</p>@endif
</form>
@endif

<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.show', $school['id']) }}">{{ __('Back to the school') }}</a></p>
@endsection
