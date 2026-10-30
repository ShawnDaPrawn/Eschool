@extends('saas.layout')

@section('title', __('Onboarding').' · '.$school['name'])

@section('content')
@php
    $labels = ['done' => __('Done'), 'started' => __('In progress'), 'todo' => __('Not started'), 'later' => __('Not yet available')];
@endphp
<h1>{{ $school['name'] }}</h1>
<p class="saas-lead">
    {{ __('Onboarding: :done of :total steps done.', ['done' => $checklist['done'], 'total' => $checklist['total']]) }}
    {{ __('Counts only: no learner, parent or staff record is shown here.') }}
</p>
@if (session('success'))<p role="status" class="saas-card">{{ session('success') }}</p>@endif
@if ($errors->any())<p role="alert" class="saas-card">{{ $errors->first() }}</p>@endif

<ol class="saas-onboarding">
    @foreach ($checklist['steps'] as $step)
        <li class="saas-card saas-onboarding__step saas-onboarding__step--{{ $step['status'] }}" data-onboarding-step="{{ $step['key'] }}">
            <h2>{{ $step['title'] }}</h2>
            <p><span class="saas-badge {{ $step['status'] === 'done' ? '' : 'saas-badge--warn' }}">{{ $labels[$step['status']] }}</span> {{ $step['detail'] }}</p>
            @if ($step['next'])<p><strong>{{ __('Next:') }}</strong> {{ $step['next'] }}</p>@endif
        </li>
    @endforeach
</ol>

@if ($canRecord)
<h2 id="training">{{ __('Record a training session') }}</h2>
<form method="POST" action="{{ route('saas.schools.training', $school['id']) }}" class="saas-card" aria-label="{{ __('Record a training session') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field"><label for="training-date">{{ __('Date') }}</label><input id="training-date" type="date" name="held_on" required max="{{ now()->toDateString() }}" value="{{ old('held_on') }}"></div>
        <div class="saas-field"><label for="training-topic">{{ __('Topic') }}</label><input id="training-topic" name="topic" required maxlength="200" value="{{ old('topic') }}"></div>
        <div class="saas-field"><label for="training-people">{{ __('People who attended') }}</label><input id="training-people" type="number" name="attendees" min="1" max="1000" value="{{ old('attendees') }}"></div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the session') }}</button></div>
</form>
@endif

<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.show', $school['id']) }}">{{ __('Back to the school') }}</a></p>
@endsection
