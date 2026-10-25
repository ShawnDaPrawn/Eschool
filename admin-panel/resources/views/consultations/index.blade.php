@extends('layouts.master')
@section('title', __('Polls, surveys and elections'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/consultations.css') }}">@endsection
@section('content')
<div class="content-wrapper consultations" data-consultations-root>
<h1>{{ __('Polls, surveys and elections') }}</h1>
@include('consultations.header')
@if($managed && $canWrite)<a class="m3-button m3-button--filled" href="{{ route('consultations-panel.new') }}">{{ __('Create a consultation') }}</a>@endif
<p>{{ __('Only consultations within your current school and audience appear here.') }}</p>
@forelse($listing['items'] as $item)
<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h2><a href="{{ route($managed?'consultations-panel.edit':'consultations-panel.ballot',['consultation'=>$item['id']]) }}">{{ $item['title'] }}</a></h2>
<p>{{ __(ucfirst($item['type'])) }} · {{ __(ucfirst($item['status'])) }}</p><p>{{ __('Opens') }}: {{ \Carbon\CarbonImmutable::parse($item['opens_at'])->setTimezone($access['timezone'])->format('Y-m-d H:i') }} · {{ __('Closes') }}: {{ \Carbon\CarbonImmutable::parse($item['closes_at'])->setTimezone($access['timezone'])->format('Y-m-d H:i') }} ({{ $access['timezone'] }})</p>
<p>{{ $item['anonymous'] ? __('Anonymous responses') : __('Named responses') }}</p></div></article>
@empty<p>{{ __('No consultations are available in this year and audience.') }}</p>@endforelse
<nav aria-label="{{ __('Consultation pages') }}">
@if($listing['page']>1)<a href="{{ route('consultations-panel.index',['page'=>$listing['page']-1,'session_year_id'=>$year]) }}">{{ __('Previous page') }}</a>@endif
@if($listing['page']<$listing['last_page'])<a href="{{ route('consultations-panel.index',['page'=>$listing['page']+1,'session_year_id'=>$year]) }}">{{ __('Next page') }}</a>@endif
</nav></div>
@endsection
