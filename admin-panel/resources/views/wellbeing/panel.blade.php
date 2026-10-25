@extends('layouts.master')
@section('title', __('Incidents and counselling'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/wellbeing.css') }}">@endsection
@section('content')
@php
$value = fn(string $key, string $field, $default = '') => $draftKey === $key ? ($draft[$field] ?? $default) : $default;
$local = fn($stamp) => \Carbon\CarbonImmutable::parse($stamp)->setTimezone($timezone)->format('Y-m-d\TH:i');
$nowLocal = now($timezone)->format('Y-m-d\TH:i');
$professionalOptions = collect($professionals['items'] ?? [])->pluck('name','id')->all();
@endphp
<div class="content-wrapper m3 page-content wellbeing-workbench">
<x-page.header :title="__('Incidents and counselling')" :subtitle="__('Private incident triage and independently assigned counselling cases.')" />
@include('academics.groups-status')
<p>{{ __('Clinical records are limited to assigned counsellors and safeguarding staff. Configuration does not grant access to report or case contents.') }}</p>
@if($messages)<section role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</section>@endif
@if(!$canWrite)<p role="status">{{ __('Changes are unavailable. Check the current school year and module access.') }}</p>@endif
<nav aria-label="{{ __('Wellbeing workbenches') }}">
@if($access['professional'])<a class="m3-button m3-button--outlined" href="{{ route('wellbeing.index',['mode'=>'clinical']) }}" @if(!$configurationMode) aria-current="page" @endif>{{ __('My incidents and cases') }}</a>@endif
@if($access['can_configure'])<a class="m3-button m3-button--outlined" href="{{ route('wellbeing.index',['mode'=>'configuration']) }}" @if($configurationMode) aria-current="page" @endif>{{ __('Categories and assignments') }}</a>@endif
</nav>
@if($configurationMode)
<form method="get" action="{{ route('wellbeing.index') }}" class="wb-form wb-fields"><input type="hidden" name="mode" value="configuration">
@if($category)<input type="hidden" name="category" value="{{ $category['id'] }}">@endif @if($managedCase)<input type="hidden" name="managed_case" value="{{ $managedCase['id'] }}">@endif
<x-page.field id="wb-search" name="search" :label="__('Find learner or staff member')" :value="$search" :restore-old-input="false" maxlength="100" /><button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button></form>
<div class="wb-grid">
<section><h2>{{ __('Incident categories') }}</h2><ul>@foreach($configuration['categories'] as $row)<li><a href="{{ route('wellbeing.index',['mode'=>'configuration','category'=>$row->id]) }}">{{ $row->name }}</a> · {{ $row->active ? __('Active') : __('Inactive') }}</li>@endforeach</ul>
@if($canWrite)
@php($operation=$category ? 'category-save':'category-create') @php($target=$category['id'] ?? 'new') @php($key=$operation.':'.$target)
<form method="post" class="wb-form" action="{{ route('wellbeing.'.$operation,$category ? ['category'=>$category['id']] : []) }}">
<h3>{{ $category ? __('Edit category') : __('New category') }}</h3>@include('wellbeing.form-identity',compact('operation','target'))<input type="hidden" name="revision" value="{{ $category['revision'] ?? 0 }}">
<x-page.field id="wb-category-name" name="name" :label="__('Category name')" :value="$value($key,'name',$category['name'] ?? '')" :restore-old-input="false" maxlength="191" required />
<x-page.field id="wb-category-guidance" name="emergency_guidance" type="textarea" :label="__('Emergency guidance')" :value="$value($key,'emergency_guidance',$category['emergency_guidance'] ?? '')" :restore-old-input="false" maxlength="2000" required />
<input type="hidden" name="active" value="0"><label class="wb-check" for="wb-category-active"><input id="wb-category-active" type="checkbox" name="active" value="1" @checked($value($key,'active',$category['active'] ?? true))> {{ __('Active category') }}</label>
<button class="m3-button m3-button--filled" type="submit">{{ __('Save category') }}</button></form>
@endif
@if($category)
<h3>{{ __('Category safeguarding team') }}</h3>
@if($canWrite)
@php($operation='team-create') @php($target=$category['id']) @php($key=$operation.':'.$target)
<form method="post" class="wb-form" action="{{ route('wellbeing.team-create',['category'=>$category['id']]) }}">@include('wellbeing.form-identity',compact('operation','target'))<input type="hidden" name="revision" value="0"><input type="hidden" name="active" value="1">
<x-page.field id="wb-team-user" name="user_id" type="select" :label="__('Safeguarding professional')" :options="collect($configuration['professional_choices'])->pluck('name','id')->all()" :value="$value($key,'user_id')" :restore-old-input="false" :placeholder="__('Choose a professional')" required />
<div class="wb-fields"><x-page.field id="wb-team-start" name="starts_on" type="date" :label="__('Starts on')" :value="$value($key,'starts_on',$access['today'])" :restore-old-input="false" required /><x-page.field id="wb-team-end" name="ends_on" type="date" :label="__('Ends on')" :value="$value($key,'ends_on',$access['today'])" :restore-old-input="false" required /></div>
<x-page.field id="wb-team-reason" name="reason" :label="__('Reason for this change')" :value="$value($key,'reason')" :restore-old-input="false" maxlength="2000" required /><button class="m3-button m3-button--filled" type="submit">{{ __('Assign category professional') }}</button></form>
@endif
@foreach($team['items'] as $row)<article><h4>{{ $row['name'] }}</h4><p>{{ $row['starts_on'] }}–{{ $row['ends_on'] }} · {{ $row['active'] ? __('Active') : __('Inactive') }}</p>
@if($canWrite)@include('wellbeing.assignment-form',['row'=>$row,'kind'=>'team','owner'=>$category['id']])@endif</article>@endforeach
@endif
</section>
<section><h2>{{ __('Case assignment metadata') }}</h2><p>{{ __('Opening or assigning a case does not reveal its clinical contents.') }}</p>
@if($canWrite)
@php($operation='case-create') @php($target='new') @php($key=$operation.':'.$target)
<form method="post" class="wb-form" action="{{ route('wellbeing.case-create') }}">@include('wellbeing.form-identity',compact('operation','target'))
<x-page.field id="wb-case-learner" name="learner_id" type="select" :label="__('Learner')" :options="collect($configuration['learner_choices'])->pluck('name','id')->all()" :value="$value($key,'learner_id')" :restore-old-input="false" :placeholder="__('Choose a learner')" required /><button class="m3-button m3-button--filled" type="submit">{{ __('Open case metadata') }}</button></form>
@endif
<ul>@foreach($managedCases['items'] as $row)<li><a href="{{ route('wellbeing.index',['mode'=>'configuration','managed_case'=>$row['id']]) }}">{{ $row['learner_name'] }} · {{ __($row['status']) }} · {{ $row['opened_at'] }}</a></li>@endforeach</ul>
@if($managedCase)
<h3>{{ $managedCase['learner_name'] }} · {{ __('Assigned professionals') }}</h3>
@if($canWrite)
@php($operation='case-assignment-create') @php($target=$managedCase['id']) @php($key=$operation.':'.$target)
<form method="post" class="wb-form" action="{{ route('wellbeing.case-assignment-create',['case'=>$managedCase['id']]) }}">@include('wellbeing.form-identity',compact('operation','target'))<input type="hidden" name="revision" value="0"><input type="hidden" name="active" value="1">
<x-page.field id="wb-case-professional" name="user_id" type="select" :label="__('Case professional')" :options="collect($configuration['professional_choices'])->pluck('name','id')->all()" :value="$value($key,'user_id')" :restore-old-input="false" :placeholder="__('Choose a professional')" required />
<div class="wb-fields"><x-page.field id="wb-case-start" name="starts_on" type="date" :label="__('Starts on')" :value="$value($key,'starts_on',$access['today'])" :restore-old-input="false" required /><x-page.field id="wb-case-end" name="ends_on" type="date" :label="__('Ends on')" :value="$value($key,'ends_on',$access['today'])" :restore-old-input="false" required /></div>
<x-page.field id="wb-case-reason" name="reason" :label="__('Reason for this change')" :value="$value($key,'reason')" :restore-old-input="false" maxlength="2000" required /><button class="m3-button m3-button--filled" type="submit">{{ __('Assign case professional') }}</button></form>
@endif
@foreach($assignments['items'] as $row)<article><h4>{{ $row['name'] }}</h4><p>{{ $row['starts_on'] }}–{{ $row['ends_on'] }} · {{ $row['active'] ? __('Active') : __('Inactive') }}</p>@if($canWrite)@include('wellbeing.assignment-form',['row'=>$row,'kind'=>'case','owner'=>$managedCase['id']])@endif</article>@endforeach
@endif
</section></div>
@php($lastPage=max($configuration['professionals_last_page'],$configuration['learners_last_page'],$configuration['categories_last_page'],$managedCases['last_page'],$team['last_page'] ?? 1,$assignments['last_page'] ?? 1))
@include('wellbeing.pagination',['lastPage'=>$lastPage,'query'=>['mode'=>'configuration','search'=>$search,'category'=>$category['id'] ?? null,'managed_case'=>$managedCase['id'] ?? null]])
@else
<div class="wb-grid"><section><h2>{{ __('My incident queue') }}</h2><ul>@forelse($reportList['items'] as $row)<li><a href="{{ route('wellbeing.index',['mode'=>'clinical','report'=>$row['id']]) }}">{{ $row['category_name'] }} · {{ __($row['urgency']) }} · {{ __($row['status']) }} · {{ $row['created_at'] }}</a></li>@empty<li>{{ __('No incidents are currently assigned to your team.') }}</li>@endforelse</ul></section>
<section><h2>{{ __('My counselling cases') }}</h2><ul>@forelse($caseList['items'] as $row)<li><a href="{{ route('wellbeing.index',['mode'=>'clinical','case'=>$row['id']]) }}">{{ $row['learner_name'] }} · {{ __($row['status']) }} · {{ $row['opened_at'] }}</a></li>@empty<li>{{ __('No counselling cases are currently assigned to you.') }}</li>@endforelse</ul></section></div>
@include('wellbeing.pagination',['lastPage'=>max($reportList['last_page'],$caseList['last_page']),'query'=>['mode'=>'clinical']])
@if($report)
<section><h2>{{ $report['category_name'] }}</h2><p>{{ $report['anonymous'] ? __('Anonymous reporter') : ($report['reporter_name'] ?? __('Identified reporter')) }} · {{ $report['learner_name'] ?? '' }}</p><p class="wb-private-text">{{ $report['body'] }}</p><p>{{ __('Status') }}: {{ __($report['status']) }}</p>
<ul class="wb-timeline">@foreach($report['threads'] as $entry)<li><h3>{{ $entry['author_name'] }} · {{ $entry['internal'] ? __('Internal triage note') : __('Reporter-visible follow-up') }}</h3><p class="wb-private-text">{{ $entry['body'] }}</p><small>{{ $entry['created_at'] }}</small></li>@endforeach</ul>
@if($report['has_older'])<a class="m3-button m3-button--outlined" href="{{ route('wellbeing.index',['mode'=>'clinical','report'=>$report['id'],'before'=>$report['next_before']]) }}">{{ __('Read older incident replies') }}</a>@endif
@if($canWrite)
@php($operation='report-respond') @php($target=$report['id']) @php($key=$operation.':'.$target)
<form method="post" class="wb-form" action="{{ route('wellbeing.report-respond',['report'=>$report['id']]) }}">@include('wellbeing.form-identity',compact('operation','target'))<input type="hidden" name="revision" value="{{ $report['revision'] }}">
<x-page.field id="wb-triage-body" name="body" type="textarea" :label="__('Triage note or reply')" :value="$value($key,'body')" :restore-old-input="false" maxlength="20000" required />
<input type="hidden" name="internal" value="0"><label class="wb-check" for="wb-internal"><input id="wb-internal" type="checkbox" name="internal" value="1" @checked($value($key,'internal',1))> {{ __('Keep this note internal to the safeguarding team') }}</label>
<x-page.field id="wb-report-status" name="status" type="select" :label="__('Report status')" :options="['open'=>__('open'),'triaged'=>__('triaged'),'resolved'=>__('resolved')]" :value="$value($key,'status',$report['status'])" :restore-old-input="false" required /><button class="m3-button m3-button--filled" type="submit">{{ __('Save triage response') }}</button></form>
@endif</section>
@endif
@if($case)
@include('wellbeing.case-panel')
@endif
@endif
</div>
@endsection
