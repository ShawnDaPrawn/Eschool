@extends('layouts.master')
@section('title', __('Publish activity or trip consent'))
@section('content')<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Publish activity or trip consent')" :subtitle="__('Published wording and participants are fixed. A changed request needs new guardian consent.')" />
@if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
@if(!$access['writable'])<p role="status">{{ __('Family forms are read-only.') }}</p>@endif
<p>{{ __('School timezone') }}: {{ $access['timezone'] }}</p>
@if($access['groups'])<form method="POST" action="{{ route('family-forms-panel.prepare') }}" aria-label="{{ __('Consent configuration') }}">@include('family-forms.hidden')
<x-page.field id="consent-group" name="activity_group_id" type="select" :label="__('Managed activity')" :value="$draft['activity_group_id']" :options="collect($access['groups'])->pluck('name','id')->all()" required :restore-old-input="false" />
<x-page.field id="consent-title" name="title" :label="__('Request title')" :value="$draft['title']" maxlength="191" :restore-old-input="false" />
<x-page.field id="consent-wording" name="wording" type="textarea" :label="__('Exact consent wording')" :value="$draft['wording']" maxlength="20000" :restore-old-input="false" />
@foreach(['event_starts_at_local'=>'Activity starts','event_ends_at_local'=>'Activity ends','respond_by_local'=>'Positive response deadline'] as $field=>$label)<x-page.field :id="'consent-'.$field" :name="$field" type="datetime-local" :label="__($label)" :value="$draft[$field]" required :restore-old-input="false" />@endforeach
<button type="submit" class="m3-button m3-button--outlined" @disabled(!$access['writable'])>{{ __('Find eligible participants') }}</button>
@if($eligible)<fieldset><legend>{{ __('Choose the participants for this exact request') }}</legend>@foreach($eligible as $child)<label class="m3-checkbox"><input id="consent-learner-{{ $child['id'] }}" type="checkbox" name="learner_ids[]" value="{{ $child['id'] }}" @checked(in_array($child['id'],$selected,true))><span>{{ $child['name'] }}</span></label>@endforeach</fieldset>
<button type="submit" formaction="{{ route('family-forms-panel.create') }}" class="m3-button m3-button--filled" @disabled(!$access['writable'])>{{ __('Publish exact consent request') }}</button>@endif
</form>@else<p>{{ __('No activity is assigned for publishing consent.') }}</p>@endif
</div>@endsection
