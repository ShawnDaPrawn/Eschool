@extends('layouts.master')
@section('title', __('What would it take'))
@section('content')
<div class="content-wrapper m3 page-content" style="overflow-wrap:anywhere">
<x-page.header :title="__('What would it take')" :subtitle="__('Start a conversation about a career: what its sources ask for, next to the learner\'s current subjects and learning evidence.')" />
<p>{{ __('This is guidance, not a prediction or a recommendation. Every learner can explore every career, and there is usually more than one route.') }}</p>
<form method="GET" action="{{ route('pathways-panel.guidance') }}" role="search">
<x-page.field id="guidance-search" name="q" type="search" :label="__('Find a learner by name')" :value="$search" maxlength="80" :restore-old-input="false" />
<button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button></form>
@if($search !== '')
@forelse($learners as $row)<p><a href="{{ route('pathways-panel.guidance',array_filter(['learner_id'=>$row['id'],'profile_id'=>$profileId])) }}">{{ $row['name'] }}</a></p>
@empty<p>{{ __('No learners you can open match that name.') }}</p>@endforelse
@endif
@if($learner)
<section aria-labelledby="guidance-learner" class="m3-card m3-card--outlined" style="margin-block:16px"><div class="m3-card__content">
<h2 id="guidance-learner">{{ $learner['name'] }}</h2>
@if($profiles === [])<p>{{ __('No career profiles have been published at this school yet.') }}</p>@else
<form method="GET" action="{{ route('pathways-panel.guidance') }}"><input type="hidden" name="learner_id" value="{{ $learner['id'] }}">
<x-page.field id="guidance-profile" name="profile_id" type="select" :label="__('Career profile')" :value="$profileId" :options="$profiles" :placeholder="__('Choose a career profile')" :required="true" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Show what it would take') }}</button></form>@endif
</div></section>
@endif
@if($view)
@php($profile = $view['profile'])
<article aria-labelledby="guidance-profile-title">
<h2 id="guidance-profile-title">{{ $profile['title'] }}</h2>
@if($profile['stale'])<div role="status" class="m3-alert"><p>{{ __('This career profile is past its review date. Check the source before sharing it.') }}</p></div>@endif
<p style="white-space:pre-wrap">{{ $profile['summary'] }}</p>
<p><a href="{{ $profile['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $profile['source_title'] }} ({{ $profile['source_year'] }})</a> · {{ __('Reviewed') }} {{ $profile['reviewed_on'] }}</p>
<section aria-labelledby="guidance-subjects"><h3 id="guidance-subjects">{{ __('Subjects') }}</h3>
<p>{{ __('Current subjects') }}: @forelse($view['current_subjects'] as $subject){{ $subject['name'] }}@if(!$loop->last), @endif @empty{{ __('No current subject groups are recorded.') }}@endforelse</p>
<ul>@foreach($view['subjects'] as $row)<li><strong>{{ implode(' / ', $row['names']) }}</strong> · {{ $row['requirement'] === 'required' ? __('Required by the source') : __('Recommended by the source') }}
@if($row['minimum_bp'] !== null) · {{ __('Source minimum') }} {{ \App\Services\Academics\AcademicNumbers::decimal($row['minimum_bp']) }}%@endif
<br>{{ $row['retired'] ? __('No longer offered at this school.') : ($row['taking'] ? __('Already in current subjects.') : __('Not in current subjects yet. Worth talking through the options and timing with the learner and family.')) }}</li>@endforeach</ul>
</section>
@if($view['topics'] !== [])
<section aria-labelledby="guidance-topics"><h3 id="guidance-topics">{{ __('Linked curriculum topics') }}</h3>
<p>{{ __('Missing evidence is unknown, not zero.') }}</p>
@foreach($view['topics'] as $section)<h4>{{ $section['subject_name'] }}</h4>
@if($section['evidence'] === 'mastery_off')<p>{{ __('Mastery is switched off at this school, so these topics show as unknown.') }}</p>
@elseif($section['evidence'] === 'not_taking')<p>{{ __('No current class evidence for this subject, so these topics show as unknown.') }}</p>
@elseif($section['evidence'] === 'not_shared')<p>{{ __('This learner\'s evidence in this subject is shown to their teachers and academic leaders only, so these topics show as unknown here.') }}</p>@endif
<ul>@foreach($section['topics'] as $topic)<li>{{ $topic['name'] }} ({{ $topic['grade_name'] }}) · {{ $topic['level'] === null ? __('Not yet assessed') : __('Level').' '.$topic['level'].' / 4' }}</li>@endforeach</ul>
@if($section['starting_points'] !== [])<p><strong>{{ __('Good places to start') }}</strong></p><ul>@foreach($section['starting_points'] as $point)<li>{{ $point['name'] }}: {{ match($point['reason']) {'not_yet_assessed'=>__('Not yet assessed. A first check would give a starting point.'),'prerequisite_gap'=>__('An earlier topic that supports this goal.'),default=>__('More practice on this topic may help.')} }}</li>@endforeach</ul>@endif
@endforeach
</section>
@endif
<section aria-labelledby="guidance-routes"><h3 id="guidance-routes">{{ __('Routes into this career') }}</h3>
@foreach($view['routes'] as $route)<div class="m3-card m3-card--outlined" style="margin-block:12px"><div class="m3-card__content">
<p><strong>{{ match($route['type']) {'tvet'=>__('TVET college'),'learnership'=>__('Learnership'),default=>__('University')} }}</strong> · {{ $route['title'] }} · {{ $route['provider'] }}</p>
<p style="white-space:pre-wrap">{{ $route['entry_note'] }}</p>
<p><a href="{{ $route['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $route['source_title'] }}</a>@if($route['programme']) · {{ __('Programme source') }}: {{ $route['programme']['title'] }}@endif</p>
</div></div>@endforeach
</section>
</article>
@endif
</div>
@endsection
