@extends('layouts.master')
@section('title', __('Peer tutoring'))
@section('content')
<div class="content-wrapper m3 page-content peer-page">
<x-page.header :title="__('Peer tutoring')" :subtitle="__('Match learners from current topic evidence and record supervised service.')" />
@if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<p>{{ __('Verified service does not change mastery or marks. House points follow existing school policy.') }}</p>
<p>{{ __('School time zone') }}: {{ $configuration['timezone'] }}</p>
@if($configuration['writable'])
<section aria-labelledby="peer-schedule"><h2 id="peer-schedule">{{ __('Schedule supervised peer tutoring') }}</h2>
<form method="GET" action="{{ route('peer-tutoring-panel.index') }}" class="peer-fields">
<label for="peer-group">{{ __('Teaching group') }}</label>
<select id="peer-group" name="group" required><option value="">{{ __('Choose a group') }}</option>@foreach($configuration['groups'] as $choice)<option value="{{ $choice['id'] }}" @selected($group === $choice['id'])>{{ $choice['name'] }}</option>@endforeach</select>
<button class="m3-button m3-button--outlined" type="submit">{{ __('Load current learners') }}</button>
</form>
@if($group && $roster)
<form method="GET" action="{{ route('peer-tutoring-panel.index') }}" class="peer-fields">
<input type="hidden" name="group" value="{{ $group }}">
<label for="peer-learner">{{ __('Learner needing support') }}</label>
<select id="peer-learner" name="learner" required><option value="">{{ __('Choose a learner') }}</option>@foreach($roster as $choice)<option value="{{ $choice['id'] }}" @selected($learner === $choice['id'])>{{ $choice['name'] }}</option>@endforeach</select>
<button class="m3-button m3-button--outlined" type="submit">{{ __('Find current tutor matches') }}</button>
</form>
@endif
@if($learner && ! $matches)<p>{{ __('No current evidenced tutor matches for this learner.') }}</p>@endif
@foreach($matches as $match)
<details class="m3-card m3-card--outlined peer-card"><summary>{{ $match['tutor_name'] }} · {{ $match['topic_name'] }}</summary>
<form method="POST" action="{{ route('peer-tutoring-panel.create') }}" class="peer-fields">
@include('peer-tutoring.scope')
<input type="hidden" name="teaching_group_id" value="{{ $group }}"><input type="hidden" name="tutee_learner_id" value="{{ $learner }}">
<input type="hidden" name="tutor_learner_id" value="{{ $match['tutor_learner_id'] }}"><input type="hidden" name="topic_id" value="{{ $match['topic_id'] }}">
<label for="peer-start-{{ $loop->index }}">{{ __('Starts at') }}</label><input id="peer-start-{{ $loop->index }}" type="datetime-local" name="starts_at" required>
<label for="peer-end-{{ $loop->index }}">{{ __('Ends at') }}</label><input id="peer-end-{{ $loop->index }}" type="datetime-local" name="ends_at" required>
<label for="peer-venue-{{ $loop->index }}">{{ __('Venue') }}</label><input id="peer-venue-{{ $loop->index }}" name="venue" maxlength="191" required>
<p>{{ __('You will be recorded as the supervisor. Choose up to three hours within current allocations.') }}</p>
<button class="m3-button m3-button--filled" type="submit">{{ __('Schedule peer session') }}</button>
</form></details>
@endforeach
</section>
@endif
<section aria-labelledby="peer-history"><h2 id="peer-history">{{ __('Peer sessions') }}</h2>
@forelse($listing['items'] as $row)
<article class="m3-card m3-card--outlined peer-card" data-peer-session="{{ $row['id'] }}">
<h3>{{ $row['topic_name'] }}</h3><p>{{ $row['tutor_name'] ?? __('Learner') }} → {{ $row['tutee_name'] ?? __('Learner') }}</p>
<p>{{ __('Supervisor') }}: {{ $row['supervisor_name'] }}</p><p>{{ $row['venue'] }}</p>
<p>{{ \Carbon\CarbonImmutable::parse($row['starts_at'])->setTimezone($configuration['timezone'])->format('Y-m-d H:i') }} — {{ \Carbon\CarbonImmutable::parse($row['ends_at'])->setTimezone($configuration['timezone'])->format('Y-m-d H:i') }}</p>
<p>{{ __(ucfirst($row['status'])) }} · {{ __('Revision') }} {{ $row['revision'] }}</p>
@if($row['service_minutes'] !== null)<p>{{ __('Verified service minutes') }}: {{ $row['service_minutes'] }}</p>@endif
@if($row['house_point_id'] !== null)<p>{{ __('House points recorded under school policy.') }}</p>@endif
@if($row['can_complete'])
<details><summary>{{ __('Confirm supervised service') }}</summary>
<form method="POST" action="{{ route('peer-tutoring-panel.change',['session'=>$row['id'],'action'=>'complete']) }}" class="peer-fields">
@include('peer-tutoring.scope')<input type="hidden" name="revision" value="{{ $row['revision'] }}">
<label for="peer-minutes-{{ $row['id'] }}">{{ __('Verified service minutes') }}</label><input id="peer-minutes-{{ $row['id'] }}" type="number" min="1" max="180" name="minutes" required>
@if($configuration['can_award_points'])
<label for="peer-category-{{ $row['id'] }}">{{ __('Optional house point category') }}</label>
<select id="peer-category-{{ $row['id'] }}" name="point_category_id"><option value="">{{ __('No house point award') }}</option>@foreach($configuration['point_categories'] as $category)<option value="{{ $category['id'] }}">{{ $category['name'] }} ({{ $category['minimum_points'] }}–{{ $category['maximum_points'] }})</option>@endforeach</select>
<label for="peer-points-{{ $row['id'] }}">{{ __('Optional integer house points') }}</label><input id="peer-points-{{ $row['id'] }}" type="number" min="1" max="10000" name="points">
@endif
<button class="m3-button m3-button--filled" type="submit">{{ __('Confirm session change') }}</button>
</form></details>
@endif
@foreach(['cancel'=>__('Cancel peer session'),'reverse'=>__('Correct completed service')] as $action=>$label)
@if($row['can_'.$action])<details><summary>{{ $label }}</summary><form method="POST" action="{{ route('peer-tutoring-panel.change',['session'=>$row['id'],'action'=>$action]) }}" class="peer-fields">
@include('peer-tutoring.scope')<input type="hidden" name="revision" value="{{ $row['revision'] }}">
<label for="peer-reason-{{ $action }}-{{ $row['id'] }}">{{ __('Reason') }}</label><textarea id="peer-reason-{{ $action }}-{{ $row['id'] }}" name="reason" maxlength="1000" required></textarea>
<button class="m3-button m3-button--outlined" type="submit">{{ $label }}</button></form></details>@endif
@endforeach
</article>
@empty<p>{{ __('No peer sessions are available.') }}</p>@endforelse
@if($listing['next_page'])<a href="{{ route('peer-tutoring-panel.index',['page'=>$listing['next_page']]) }}">{{ __('More peer sessions') }}</a>@endif
</section>
</div>
@endsection
@section('css')
<style>.peer-page{overflow-wrap:anywhere}.peer-card{padding:16px;margin-block:16px}.peer-fields{display:grid;gap:10px;margin-block:16px;max-width:42rem}.peer-fields input,.peer-fields select,.peer-fields textarea{box-sizing:border-box;max-width:100%;min-width:0;width:100%;padding:10px}.peer-page summary{cursor:pointer;padding:10px;min-height:44px}.peer-page button{white-space:normal;min-height:44px}</style>
@endsection
