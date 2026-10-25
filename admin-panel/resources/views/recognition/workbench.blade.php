@extends('layouts.master')
@section('title', __($module === 'house_points' ? 'Houses and points' : 'Awards and assembly'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/recognition.css') }}">@endsection
@section('content')
<div data-recognition-root class="content-wrapper recognition @if($assembly) recognition--assembly @endif">
<h1>{{ __($module === 'house_points' ? 'Houses and points' : 'Awards and assembly') }}</h1>
@if($messages)<div role="alert" class="recognition-warning"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($draft)<details open class="m3-card"><summary>{{ __('Your unsaved recognition text') }}</summary>@foreach($draft as $key=>$value)<p><strong>{{ __(ucfirst(str_replace('_',' ',$key))) }}</strong>: {{ $value }}</p>@endforeach<p>{{ __('Compare the current record and enter the corrected change below.') }}</p></details>@endif
@if(!$canWrite)<p role="status">{{ __('This view is read only. Return to the current school year to make changes, or ask a school administrator about module access.') }}</p>@endif
@if($module==='house_points')
<section aria-label="{{ __('House counters') }}" class="recognition-counters" data-counter-url="{{ url('/api/v2/recognition/house_points/counters') }}" data-year="{{ $year }}" data-account="{{ $account }}" data-school="{{ $school }}" data-revision="{{ $counter['revision'] }}">
@foreach($counter['items'] as $house)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $house['name'] }}</h2>@include('recognition.emblem')<span class="recognition-emblem">{{ __(ucfirst($house['emblem'])) }}</span><p class="recognition-score" data-house="{{ $house['id'] }}">{{ $house['points'] }}</p><span>{{ __('House points') }}</span></div></article>@endforeach
</section><p role="status" id="counter-status"></p>
@endif
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Choose a learner') }}</h2>
<form method="GET" action="{{ route('recognition.index',['module'=>$module]) }}" class="recognition-fields" aria-label="{{ __('Find a recognition learner') }}">
@include('recognition.field',['prefix'=>'find','field'=>'search','label'=>'Search by name','required'=>false,'max'=>100,'value'=>is_string(request('search'))?request('search'):''])
@include('recognition.select',['prefix'=>'find','field'=>'session_year_id','label'=>'School year','options'=>$config['years'],'value'=>$year,'blank'=>false])
<button class="m3-button m3-button--outlined" type="submit">{{ __('Find learners') }}</button></form>
<ul>@foreach($choices['items'] as $choice)<li><a href="{{ route('recognition.index',['module'=>$module,'learner_id'=>$choice['id'],'session_year_id'=>$year]) }}">{{ $choice['name'] }}</a></li>@endforeach</ul>
@if(!$choices['items'])<p>{{ __('No learners are available in your current allocations for this search.') }}</p>@endif
@if($choices['next_after']!==null)<a href="{{ route('recognition.index',['module'=>$module,'after'=>$choices['next_after'],'search'=>request('search'),'session_year_id'=>$year]) }}">{{ __('More learners') }}</a>@endif
</div></section>
@if($selected)
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $selected->user?->full_name }}</h2>
@if($module==='house_points')
@if($membership)<p>{{ __('Current house') }}: {{ collect($config['houses'])->firstWhere('id',(int)$membership->house_id)['name'] ?? __('Archived house') }}</p>@else<p>{{ __('Assign a dated house membership before awarding points.') }}</p>@endif
@if($canWrite && $membership)<form method="POST" action="{{ route('recognition.create',['module'=>$module,'operation'=>'issue']) }}" class="recognition-fields" aria-label="{{ __('Award house points') }}">
@include('recognition.hidden')<input type="hidden" name="session_year_id" value="{{ $year }}">
@include('recognition.select',['prefix'=>'issue','field'=>'point_category_id','label'=>'Point category','options'=>collect($config['point_categories'])->where('active',true)->all()])
@include('recognition.field',['prefix'=>'issue','field'=>'points','label'=>'Points','type'=>'number','min'=>-10000,'maximum'=>10000,'value'=>5])
<label for="issue-reason">{{ __('Reason for points') }}<textarea id="issue-reason" name="reason" maxlength="2000" required></textarea></label>
@include('recognition.select',['prefix'=>'issue','field'=>'public','label'=>'Public celebration','options'=>[['id'=>0,'name'=>'Private learner moment'],['id'=>1,'name'=>'Allow a school celebration']],'value'=>0,'blank'=>false])
<p>{{ __('Daily staff point limit') }}: {{ $config['settings']['daily_staff_cap'] }}. {{ __('Reversals retain the original and do not restore the daily allowance.') }}</p>
<button class="m3-button m3-button--filled" type="submit">{{ __('Award points') }}</button></form>@endif
<h3>{{ __('House point history for this year') }}</h3>
@forelse($history['items'] as $entry)<article class="recognition-entry"><h4>{{ $entry['points'] }} · {{ $entry['house_name'] }} · {{ $entry['category_name'] }}</h4><p>{{ $entry['on_date'] }} · {{ $entry['reason'] }}</p>
@if($entry['reverses_id']!==null)<p>{{ __('Reasoned reversal') }}</p>@endif
@if($canWrite && ($pointControls[$entry['id']] ?? false))<form method="POST" action="{{ route('recognition.change',['module'=>$module,'operation'=>'reverse','record'=>$entry['id']]) }}" aria-label="{{ __('Reverse house points') }} {{ $entry['id'] }}">@include('recognition.hidden')@include('recognition.field',['prefix'=>'reverse-'.$entry['id'],'field'=>'reason','label'=>'Reason for reversal','max'=>2000])<button class="m3-button m3-button--outlined" type="submit">{{ __('Append a reversal') }}</button></form>@endif</article>@empty<p>{{ __('No house points have been recorded in this year.') }}</p>@endforelse
@if($history['next_before']!==null)<a href="{{ route('recognition.index',['module'=>$module,'learner_id'=>$learner,'session_year_id'=>$year,'before'=>$history['next_before']]) }}">{{ __('Older point entries') }}</a>@endif
@if($canWrite && $config['access']['manager'])<details><summary>{{ __('Assign or move house membership') }}</summary>
@if($membership)<p>{{ __('End the old membership before the new house starts. Existing points retain their original house.') }}</p><form method="POST" action="{{ route('recognition.catalogue-save',['module'=>$module,'kind'=>'house-memberships','record'=>$membership->id]) }}" class="recognition-fields">@include('recognition.hidden',['revision'=>$membership->revision])<input type="hidden" name="house_id" value="{{ $membership->house_id }}"><input type="hidden" name="session_year_id" value="{{ $year }}"><input type="hidden" name="starts_on" value="{{ $membership->starts_on->toDateString() }}"><input type="hidden" name="active" value="1">@include('recognition.field',['prefix'=>'membership-end','field'=>'ends_on','label'=>'Last day in this house','type'=>'date'])@include('recognition.field',['prefix'=>'membership-end','field'=>'reason','label'=>'Reason for house change','max'=>2000])<button type="submit" class="m3-button m3-button--outlined">{{ __('End this dated membership') }}</button></form>@endif
<form method="POST" action="{{ route('recognition.catalogue-create',['module'=>$module,'kind'=>'house-memberships']) }}" class="recognition-fields">@include('recognition.hidden')<input type="hidden" name="session_year_id" value="{{ $year }}"><input type="hidden" name="active" value="1"><input type="hidden" name="ends_on" value="">@include('recognition.select',['prefix'=>'membership-new','field'=>'house_id','label'=>'New house','options'=>collect($config['houses'])->where('active',true)->all()])@include('recognition.field',['prefix'=>'membership-new','field'=>'starts_on','label'=>'First day in this house','type'=>'date','value'=>$config['access']['today']])@include('recognition.field',['prefix'=>'membership-new','field'=>'reason','label'=>'Reason for house allocation','max'=>2000])<button type="submit" class="m3-button m3-button--filled">{{ __('Assign dated house membership') }}</button></form></details>@endif
@else
@include('recognition.awards-learner')
@endif
</div></section>@endif
@if($module==='awards')@include('recognition.assembly')@endif
@if($canWrite)@include('recognition.configuration')@endif
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/recognition-session.js') }}" defer></script>
@if($module==='house_points')
<script src="{{ asset('assets/js/house-counters.js') }}" defer></script>
@endif
@endsection
