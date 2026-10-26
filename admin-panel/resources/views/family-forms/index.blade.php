@extends('layouts.master')
@section('title', __('Family forms'))
@section('content')<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Family forms')" :subtitle="__('Publish exact requests and check current participant permissions.')" />
@if($access['groups'])<p><a href="{{ route('family-forms-panel.new') }}">{{ __('Publish activity or trip consent') }}</a></p>
<form method="GET" action="{{ route('family-forms-panel.index') }}"><x-page.field id="family-group" name="activity_group_id" type="select" :label="__('Managed activity')" :value="$group" :options="collect($access['groups'])->pluck('name','id')->all()" :restore-old-input="false" /><button type="submit" class="m3-button m3-button--outlined">{{ __('Show activity requests') }}</button></form>@endif
<section class="page-section" aria-labelledby="family-requests"><h2 id="family-requests">{{ __('Consent requests') }}</h2>
@forelse($listing['items'] as $row)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3><a href="{{ route('family-forms-panel.managed',['consent'=>$row['id']]) }}">{{ $row['title'] }}</a></h3><p>{{ __(ucfirst($row['status'])) }}</p></div></article>@empty<p>{{ __('No consent requests are available in this scope.') }}</p>@endforelse
@if($listing['next_page'])<a href="{{ route('family-forms-panel.index',array_filter(['activity_group_id'=>$group,'learner_id'=>$learner,'page'=>$listing['next_page']])) }}">{{ __('Older requests') }}</a>@endif
</section></div>@endsection
