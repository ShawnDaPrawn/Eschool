@extends('layouts.master')
@section('title', __('Sponsored place assignments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench"><x-page.header :title="$record['sponsor_name']" :subtitle="$record['reference'].' · '.__($record['effective_status'])" />
<x-m3.button variant="text" :href="route('sponsored-places.index')">{{ __('Back to sponsorships') }}</x-m3.button>
<p>{{ __('Assigned places') }}: {{ count($record['learners']) }} / {{ $record['places'] }}</p>
@if($section==='assign')
<x-m3.button variant="text" :href="route('sponsored-places.show',['subscription'=>$record['id']])">{{ __('Back to assigned learners') }}</x-m3.button>
<form method="get" class="workspace-filters"><input type="hidden" name="section" value="assign"><x-page.field name="search" type="search" :label="__('Find a learner')" :value="request('search')" maxlength="120" /><x-m3.button type="submit" variant="outlined">{{ __('Search learners') }}</x-m3.button></form>
<p>{{ __('Showing up to 100 matching learners. Refine the search if the learner is not listed.') }}</p>
@if(count($record['learners'])<$record['places'] && in_array($record['status'],['requested','active'],true))
<x-page.form-card id="sponsored-assign" :action="route('sponsored-places.add',['subscription'=>$record['id']])" :title="__('Assign a sponsored place')" :submit-label="__('Assign learner')" :draft="false" :cancel-href="route('sponsored-places.show',['subscription'=>$record['id']])">
@include('money.business.hidden')
<x-page.field name="learner_id" type="select" :label="__('Learner')" :options="$learners->mapWithKeys(fn($learner)=>[$learner->id=>$learner->user?->full_name??__('Unavailable learner')])->all()" :placeholder="__('Choose the learner')" required />
<p>{{ __('The available capacity and current sponsorship state are checked again when saving.') }}</p>
</x-page.form-card>
@else<p role="status">{{ __('No place can be assigned in the current state. Release an existing place first if the sponsorship is full.') }}</p>@endif
@else
@if(count($record['learners'])<$record['places'] && in_array($record['status'],['requested','active'],true))<x-m3.button :href="route('sponsored-places.show',['subscription'=>$record['id'],'section'=>'assign'])">{{ __('Assign a sponsored place') }}</x-m3.button>@endif
<div class="page-table__scroll" role="region" aria-label="{{ __('Assigned learners') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Assigned learners') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Assigned on') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@forelse($record['learners'] as $row)<tr><td>{{ $row['name'] }}</td><td>{{ $row['added_on'] }}</td><td><x-m3.button variant="outlined" :href="route('sponsored-places.release',['subscription'=>$record['id'],'learner'=>$row['learner_id']])">{{ __('Review release') }}</x-m3.button></td></tr>@empty<tr><td colspan="3">{{ __('No learners assigned yet.') }}</td></tr>@endforelse
</tbody></table></div>@endif</div>
@endsection
