@extends('layouts.master')
@section('title', __('Facilitator leave request'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Facilitator leave request')" :subtitle="($names->get($record->user_id)?->full_name ?? __('Former facilitator')).' · '.$record->from_date->toDateString().' – '.$record->to_date->toDateString()"><x-slot:actions><a class="m3-button m3-button--text" href="{{ route('facilitator-leave.index') }}">{{ __('Back to requests') }}</a></x-slot:actions></x-page.header>
<p role="status">{{ __('Decision') }}: <strong>{{ __(ucfirst($record->status)) }}</strong> · {{ __('Revision') }} {{ $record->revision }}</p>
@if(!$canWrite)<p>{{ __('This school year is read-only. Select the current school year to make changes.') }}</p>@endif
@if(!$session)
<section class="page-section"><h2>{{ __('Original request') }}</h2><p>{{ $record->reason }}</p><dl>@foreach($record->portions as $date=>$portion)<dt>{{ $date }}</dt><dd>{{ __(str_replace('_',' ',ucfirst($portion))) }}</dd>@endforeach</dl>
@foreach($record->attachments ?? [] as $file=>$attachment)<p><a href="{{ route('facilitator-leave.download',[$record->id,$file]) }}">{{ $attachment['name'] }}</a></p>@endforeach
</section>
@if($canManage && $canWrite && $record->status==='pending')
<x-page.form-card id="facilitator-decision" :action="route('facilitator-leave.decide',$record->id)" :title="__('Review facilitator leave')" :submit-label="__('Save decision')" :draft="false">
<input type="hidden" name="operation_id" value="{{ $operationId }}"><input type="hidden" name="revision" value="{{ $record->revision }}">
<x-page.field name="decision" type="select" :label="__('Decision')" :options="['approved'=>__('Approve'),'rejected'=>__('Reject')]" :placeholder="__('Choose a decision')" required />
<p>{{ __('The decision becomes final. Approval does not replace the activity session facilitator automatically.') }}</p>
</x-page.form-card>
@endif
<section class="page-section"><h2>{{ __('Affected activity sessions') }}</h2>
@forelse($sessions as $affected)
@php($cover=$covers->get($affected->id))
<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $groups->get($affected->activity_group_id)?->name }} · {{ $affected->name }}</h3><p>{{ $affected->on_date->toDateString() }} · {{ $affected->starts_at ? substr($affected->starts_at,0,5) : __('Time not configured') }} · {{ $affected->venue }}</p>
@if($cover)<p>{{ __('Temporary cover') }}: {{ $names->get($cover->replacement_user_id)?->full_name }}@if($cover->needsReview($affected->getAttributes())) · <strong>{{ __('Session changed. Review cover again.') }}</strong>@endif</p>@else<p>{{ $record->status==='approved' ? __('Cover is not assigned') : __('Cover can be assigned after approval') }}</p>@endif
@if($canManage && $canWrite && $record->status==='approved' && $affected->status==='scheduled')<a class="m3-button m3-button--outlined" href="{{ route('facilitator-leave.show',[$record->id,'session'=>$affected->id]) }}">{{ $cover ? __('Review session cover') : __('Assign session cover') }}</a>@endif
@if($canManage)<a class="m3-button m3-button--text" href="{{ route('activity-groups.index',['group_id'=>$affected->activity_group_id,'view'=>'sessions']) }}">{{ __('Open activity sessions') }}</a>@endif
</div></article>
@empty<p>{{ __('No currently allocated activity sessions match these day portions.') }}</p>@endforelse
</section>
@else
@php($cover=$covers->get($session->id))
<fieldset @disabled(!$canManage || !$canWrite || $record->status!=='approved' || $candidates->isEmpty())>
<x-page.form-card id="facilitator-cover" :action="route('facilitator-leave.assign',[$record->id,$session->id])" :title="__('Assign facilitator session cover')" :submit-label="__('Confirm session cover')" :draft="false" :cancel-href="route('facilitator-leave.show',$record->id)">
<input type="hidden" name="operation_id" value="{{ $operationId }}"><input type="hidden" name="session_revision" value="{{ $session->revision }}"><input type="hidden" name="cover_revision" value="{{ $cover?->revision ?? 0 }}">
<p>{{ $groups->get($session->activity_group_id)?->name }} · {{ $session->name }} · {{ $session->on_date->toDateString() }} · {{ $session->starts_at ? substr($session->starts_at,0,5) : __('Time not configured') }}</p>
<x-page.field name="replacement_user_id" type="select" :label="__('Eligible replacement facilitator')" :value="$cover?->replacement_user_id" :options="$candidates->mapWithKeys(fn($person)=>[$person->id=>$person->full_name])->all()" :placeholder="__('Choose a facilitator')" required />
<x-page.field name="reason" type="textarea" :label="__('Reason for this cover assignment')" :value="$cover?->reason" maxlength="2000" required />
@if($candidates->isEmpty())<p role="status">{{ __('No eligible facilitator is available. Review dated group appointments, leave and conflicting sessions before trying again.') }}</p>@endif
<p>{{ __('This assignment covers only the selected activity session. It preserves the original facilitator appointment and checks availability again when saved.') }}</p>
</x-page.form-card>
</fieldset>
@endif
</div>
@endsection
