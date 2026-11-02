@extends('saas.layout')

@section('title', __('Feature flags'))

@php
    $rollouts = ['off' => __('Off everywhere'), 'beta' => __('Beta: only the schools switched on below'), 'everyone' => __('On for everyone, except schools switched off below')];
@endphp

@section('content')
<h1>{{ __('Feature flags') }}</h1>
<p class="saas-lead">{{ __('Try a new feature with chosen schools first, then turn it on for everyone. A school switched on or off by name keeps that choice whatever the rollout, and the kill switch turns the feature off everywhere at once. A flag with the same key as a feature already in the apps can only switch that feature off, never on where the app does not offer it.') }}</p>

@forelse ($flags as $flag)
<section class="saas-card" aria-labelledby="flag-{{ $flag['id'] }}">
    <h2 id="flag-{{ $flag['id'] }}"><code>{{ $flag['key'] }}</code>
        @if ($flag['killed'])<span class="saas-badge">{{ __('Killed') }}</span>@else<span class="saas-badge">{{ $rollouts[$flag['rollout']] }}</span>@endif
    </h2>
    <p>{{ __('On at :on of :all schools.', ['on' => $flag['on_count'], 'all' => $flag['school_count']]) }}@if ($flag['killed']) {{ __('Killed because: :reason', ['reason' => $flag['kill_reason']]) }}@endif</p>

    <form method="POST" action="{{ route('saas.flags.update', $flag['id']) }}" aria-label="{{ __('Roll out :key', ['key' => $flag['key']]) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision" value="{{ $flag['revision'] }}">
        <div class="saas-grid">
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-description">{{ __('What it switches') }}</label>
                <input id="flag-{{ $flag['id'] }}-description" name="description" required minlength="5" maxlength="500" value="{{ $flag['description'] }}">
            </div>
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-rollout">{{ __('Rollout') }}</label>
                <select id="flag-{{ $flag['id'] }}-rollout" name="rollout" required>
                    @foreach ($rollouts as $value => $label)<option value="{{ $value }}" @selected($flag['rollout'] === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-killed">{{ __('Kill switch') }}</label>
                <select id="flag-{{ $flag['id'] }}-killed" name="killed" required>
                    <option value="0" @selected(! $flag['killed'])>{{ __('Not killed') }}</option>
                    <option value="1" @selected($flag['killed'])>{{ __('Killed: off everywhere') }}</option>
                </select>
            </div>
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-reason">{{ __('Why it is killed (needed to kill it)') }}</label>
                <input id="flag-{{ $flag['id'] }}-reason" name="kill_reason" maxlength="500" value="{{ $flag['kill_reason'] }}">
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save :key', ['key' => $flag['key']]) }}</button></div>
    </form>

    <h3>{{ __('Schools switched by name') }}</h3>
    @if ($flag['switches'] === [])
        <p>{{ __('None: every school follows the rollout.') }}</p>
    @else
        <ul>
            @foreach ($flag['switches'] as $switch)
                <li>{{ $switch['name'] }}: {{ $switch['enabled'] ? __('on') : __('off') }}</li>
            @endforeach
        </ul>
    @endif
    <form method="POST" action="{{ route('saas.flags.schools', $flag['id']) }}" aria-label="{{ __('Switch a school for :key', ['key' => $flag['key']]) }}">
        @csrf
        <div class="saas-grid">
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-school">{{ __('School') }}</label>
                <select id="flag-{{ $flag['id'] }}-school" name="school_id" required>
                    @foreach ($schoolChoices as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div class="saas-field">
                <label for="flag-{{ $flag['id'] }}-switch">{{ __('Switch') }}</label>
                <select id="flag-{{ $flag['id'] }}-switch" name="switch" required>
                    <option value="on">{{ __('On for this school') }}</option>
                    <option value="off">{{ __('Off for this school') }}</option>
                    <option value="clear">{{ __('Follow the rollout') }}</option>
                </select>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn saas-btn--quiet" type="submit">{{ __('Save the school switch') }}</button></div>
    </form>
</section>
@empty
    <p class="saas-card">{{ __('No feature flags yet.') }}</p>
@endforelse

<h2>{{ __('New flag') }}</h2>
<form method="POST" action="{{ route('saas.flags.store') }}" class="saas-card" aria-label="{{ __('New flag') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field">
            <label for="new-flag-key">{{ __('Key (lower case, as the apps read it)') }}</label>
            <input id="new-flag-key" name="key" required pattern="[a-z][a-z0-9_]{2,63}" maxlength="64" value="{{ old('key') }}">
        </div>
        <div class="saas-field">
            <label for="new-flag-description">{{ __('What it switches') }}</label>
            <input id="new-flag-description" name="description" required minlength="5" maxlength="500" value="{{ old('description') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Create the flag (off)') }}</button></div>
</form>
@endsection
