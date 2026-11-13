@extends('layouts.master')
@section('title', __('Re-enrolment'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Re-enrolment')" :subtitle="__('Publish the contract for the next school year and follow each family’s response.')" :primary-label="$canWrite ? __('Publish a contract') : null" :primary-href="$canWrite ? route('reenrolment-panel.create') : null" />
    <div class="m3-banner" role="note">{{ __('Re-enrolment records family consent. Learner promotion is managed in People; changing the active school year is a global action.') }}</div>
    @if($listing['items'] === [])
        <x-page.empty-state :title="__('No re-enrolment contracts yet')" :message="__('Create a future school year in Settings, then publish its contract here.')" />
    @else
        <section class="panel-record-list" aria-label="{{ __('Contracts') }}">
        @foreach($listing['items'] as $item)
            <article class="m3-card m3-card--outlined panel-record-card">
                <h2><a href="{{ route('reenrolment-panel.show', $item['id']) }}">{{ $item['title'] }}</a></h2>
                <p>{{ $item['session_year'] }} · {{ __('Version :version', ['version'=>$item['version']]) }} · {{ __(ucfirst($item['state'])) }}</p>
                <dl class="panel-summary-grid"><div><dt>{{ __('Reply by') }}</dt><dd>{{ $item['respond_by'] }}</dd></div><div><dt>{{ __('Confirmed learners') }}</dt><dd>{{ $item['confirmed'] }}</dd></div></dl>
                <div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('reenrolment-panel.show', ['contract'=>$item['id'], 'view'=>'outstanding']) }}">{{ __('View responses') }}</a><a class="m3-button m3-button--text" href="{{ route('reenrolment-panel.show', ['contract'=>$item['id'], 'view'=>'wording']) }}">{{ __('Read contract') }}</a></div>
            </article>
        @endforeach
        </section>
        <p>{{ __('Showing the latest 100 contract versions.') }}</p>
    @endif
</div>
@endsection
