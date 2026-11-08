@extends('saas.layout')

@section('title', __('Import for :school', ['school' => $school['name']]))

@php
    $statusLabels = ['create' => __('Will be imported'), 'lurits' => __('Already here, LURITS number added'), 'exists' => __('Already here, left alone'), 'transfer' => __('Start a transfer'), 'error' => __('Fix this row')];
    $open = in_array($import['status'], ['awaiting_approval', 'approved'], true) && ! $import['expired'];
@endphp

@section('content')
<h1>{{ __(':kind for :school', ['kind' => $import['kind_label'], 'school' => $school['name']]) }}</h1>
<p class="saas-lead"><span class="saas-badge">{{ $import['status_label'] }}</span></p>

<dl class="saas-card">
    <dt>{{ __('Sent for approval') }}</dt><dd>{{ $import['sent_at'] }} {{ __('by :name', ['name' => $import['requested_by'] ?? __('Unknown')]) }}</dd>
    <dt>{{ __('Approver') }}</dt><dd>{{ $import['approver'] ?? __('Unknown') }}</dd>
    <dt>{{ __('Rows') }}</dt><dd>{{ __(':rows rows: :create to import, :lurits LURITS numbers to add, :exists already here.', ['rows' => $import['rows']] + $import['counts']) }}</dd>
    <dt>{{ __('File (SHA-256)') }}</dt><dd><code>{{ $import['file_sha256'] }}</code></dd>
    @if ($open)<dt>{{ __('Expires') }}</dt><dd>{{ $import['expires_at'] }}</dd>@endif
    @if ($import['decision_reason'])<dt>{{ __('The school\'s reason') }}</dt><dd>{{ $import['decision_reason'] }}</dd>@endif
    @if ($import['close_reason'])<dt>{{ __('Cancelled because') }}</dt><dd>{{ $import['close_reason'] }}</dd>@endif
    @if ($import['result'])<dt>{{ __('Written') }}</dt><dd>{{ collect($import['result'])->map(fn ($count, $key) => str_replace('_', ' ', $key).' '.$count)->implode(', ') }}</dd>@endif
</dl>

@if ($import['status'] === 'approved' && ! $import['expired'])
<form method="POST" action="{{ route('saas.imports.apply', $import['id']) }}" class="saas-card" aria-label="{{ __('Apply the import') }}">
    @csrf
    <p>{{ __('The school approved this file. Applying it writes the rows through the school\'s own import, in this school only, with the approving leader\'s permissions. It is refused if the file or the school\'s records changed since the preview.') }}</p>
    <div class="saas-field">
        <label for="apply-reason">{{ __('Why apply it now? (the school sees this)') }}</label>
        <input id="apply-reason" name="reason" required minlength="5" maxlength="500">
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Apply the import') }}</button></div>
</form>
@endif

@if ($open)
<form method="POST" action="{{ route('saas.imports.cancel', $import['id']) }}" class="saas-card" aria-label="{{ __('Cancel the import') }}">
    @csrf
    <div class="saas-field">
        <label for="cancel-reason">{{ __('Why cancel it? (the school sees this)') }}</label>
        <input id="cancel-reason" name="reason" required minlength="5" maxlength="500">
    </div>
    <div class="saas-actions"><button class="saas-btn saas-btn--quiet" type="submit">{{ __('Cancel and delete the file') }}</button></div>
</form>
@endif

@if ($import['preview'] !== [])
<h2>{{ __('Rows the school is approving') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Rows in the file') }}" tabindex="0"><table class="saas-table">
    <thead><tr><th scope="col">{{ __('Line') }}</th><th scope="col">{{ __('Row') }}</th><th scope="col">{{ __('Result') }}</th><th scope="col">{{ __('Notes') }}</th></tr></thead>
    <tbody>
    @foreach ($import['preview'] as $row)
        <tr><td>{{ $row['line'] }}</td><th scope="row">{{ $row['label'] }}</th><td>{{ $statusLabels[$row['status']] ?? $row['status'] }}</td><td>{{ implode(' ', $row['notes']) }}</td></tr>
    @endforeach
    </tbody>
</table></div>
@endif

<h2>{{ __('Steps') }}</h2>
<ol class="saas-card">
    @foreach ($import['steps'] as $step)
        <li>{{ $step['at'] }} · {{ $step['label'] }} · {{ $step['actor'] }}@if ($step['reason']) · “{{ $step['reason'] }}”@endif @if ($step['stopped']) · {{ $step['stopped'] }}@endif</li>
    @endforeach
</ol>

<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.imports', $school['id']) }}">{{ __('All imports for this school') }}</a></p>
@endsection
