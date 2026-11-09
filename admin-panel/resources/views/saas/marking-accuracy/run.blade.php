@extends('saas.layout')

@section('title', __('Evaluation run :run', ['run' => $run['id']]))

@php
    $percent = fn ($value) => $value === null ? '—' : number_format((float) $value, 1).'%';
    $marks = fn ($value) => $value === null ? '—' : number_format((float) $value, 2);
    $types = ['method' => __('Method'), 'answer' => __('Answer'), 'accuracy' => __('Accuracy'), 'consistent_accuracy' => __('Consistent accuracy'), 'explanation' => __('Explanation')];
@endphp

@section('content')
<p><a href="{{ route('saas.marking-accuracy.index') }}">{{ __('Marking accuracy') }}</a></p>
<h1>{{ __('Evaluation run :run', ['run' => $run['id']]) }}: {{ $run['label'] }}</h1>
<p class="saas-lead">{{ __('Engine :engine, profile :profile, :scripts scripts and :points mark points, run :date.', ['engine' => $run['engine_version'], 'profile' => $run['profile_version'], 'scripts' => $run['scripts'], 'points' => $run['points'], 'date' => $run['created_at']->format('Y-m-d H:i')]) }}</p>

<div class="saas-card" role="status">
    <p><span class="saas-badge {{ $run['passed'] ? '' : 'saas-badge--warn' }}">{{ $run['passed'] ? __('Passed') : __('Did not pass') }}</span>
        @unless ($current) {{ __('This run measured an earlier engine or profile version, so it no longer counts for the gate.') }} @endunless
        @unless ($run['confirmed']) {{ __('Measured against placeholder figures the owner had not confirmed.') }} @endunless
    </p>
    @if ($failures !== [])
        <ul>@foreach ($failures as $failure)<li>{{ $failure }}</li>@endforeach</ul>
    @endif
    <p>{{ __('A mark point the engine sends to a teacher counts as a disagreement and as not awarded in the script total.') }}</p>
</div>

<h2>{{ __('Agreement') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Agreement') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col"></th><th scope="col">{{ __('Count') }}</th><th scope="col">{{ __('Exact agreement') }}</th><th scope="col">{{ __('Within tolerance') }}</th><th scope="col">{{ __('Mean absolute difference (marks)') }}</th><th scope="col">{{ __('Bias (marks)') }}</th><th scope="col">{{ __('Sent to a teacher') }}</th></tr></thead>
        <tbody>
            <tr><th scope="row">{{ __('Mark points') }}</th><td>{{ $metrics['points']['count'] }}</td><td>{{ $percent($metrics['points']['exact_percent']) }}</td><td>{{ $percent($metrics['points']['within_percent']) }}</td><td>{{ $marks($metrics['points']['mean_abs_marks']) }}</td><td>{{ $marks($metrics['points']['bias_marks']) }}</td><td>{{ $percent($metrics['points']['needs_teacher_percent']) }}</td></tr>
            <tr><th scope="row">{{ __('Script totals') }}</th><td>{{ $metrics['scripts']['count'] }}</td><td>{{ $percent($metrics['scripts']['exact_percent']) }}</td><td>{{ $percent($metrics['scripts']['within_percent']) }}</td><td>{{ $marks($metrics['scripts']['mean_abs_marks']) }}</td><td>{{ $marks($metrics['scripts']['bias_marks']) }} ({{ $percent($metrics['scripts']['bias_percent']) }})</td><td>—</td></tr>
        </tbody>
    </table>
</div>
<p>{{ __('Bias is the mean of engine minus teacher: above zero, the engine marks more generously than teachers.') }}</p>

<h2>{{ __('By mark point type') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('By mark point type') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Count') }}</th><th scope="col">{{ __('Exact agreement') }}</th><th scope="col">{{ __('Within tolerance') }}</th><th scope="col">{{ __('Bias (marks)') }}</th><th scope="col">{{ __('Sent to a teacher') }}</th></tr></thead>
        <tbody>
        @foreach ($metrics['by_type'] as $type => $row)
            <tr><th scope="row">{{ $types[$type] ?? $type }}</th><td>{{ $row['count'] }}</td><td>{{ $percent($row['exact_percent']) }}</td><td>{{ $percent($row['within_percent']) }}</td><td>{{ $marks($row['bias_marks']) }}</td><td>{{ $percent($row['needs_teacher_percent']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('By memo rule') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('By memo rule') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Rule') }}</th><th scope="col">{{ __('Count') }}</th><th scope="col">{{ __('Exact agreement') }}</th><th scope="col">{{ __('Within tolerance') }}</th><th scope="col">{{ __('Bias (marks)') }}</th><th scope="col">{{ __('Sent to a teacher') }}</th></tr></thead>
        <tbody>
        @foreach ($metrics['by_rule'] as $rule => $row)
            <tr><th scope="row">{{ $rule }}</th><td>{{ $row['count'] }}</td><td>{{ $percent($row['exact_percent']) }}</td><td>{{ $percent($row['within_percent']) }}</td><td>{{ $marks($row['bias_marks']) }}</td><td>{{ $percent($row['needs_teacher_percent']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('Mark points that disagree most') }}</h2>
<p>{{ __('Where the engine and teachers differ most often: start here to improve a profile, a rule or the memo.') }}</p>
@if ($worst === [])
    <p class="saas-card">{{ __('Every mark point agreed within tolerance.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Mark points that disagree most') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Paper') }}</th><th scope="col">{{ __('Question') }}</th><th scope="col">{{ __('Mark point') }}</th><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Rule') }}</th><th scope="col">{{ __('Scripts') }}</th><th scope="col">{{ __('Agree') }}</th><th scope="col">{{ __('Engine higher') }}</th><th scope="col">{{ __('Engine lower') }}</th><th scope="col">{{ __('Sent to a teacher') }}</th><th scope="col">{{ __('Most common reason') }}</th></tr></thead>
        <tbody>
        @foreach ($worst as $point)
            <tr>
                <td>{{ $point['paper'] }}</td>
                <td>{{ $point['question'] }}</td>
                <th scope="row">{{ $point['index'] }}. {{ $point['earns'] }}</th>
                <td>{{ $types[$point['type']] ?? $point['type'] }}</td>
                <td>{{ $point['rule'] }}</td>
                <td>{{ $point['count'] }}</td>
                <td>{{ $percent($point['agreement_percent']) }}</td>
                <td>{{ $point['over'] }}</td>
                <td>{{ $point['under'] }}</td>
                <td>{{ $point['pending'] }}</td>
                <td>{{ $point['reason'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
