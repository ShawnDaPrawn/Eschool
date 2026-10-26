@extends('layouts.master')
@section('title', __('Subject choice and pathways'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Subject choice and pathways')" :subtitle="__('Maintain reviewed sources for family subject choices and projected admission scores.')" />
<p>{{ __('A published minimum is not an admission offer. Verify each source and its scoring rules before publication.') }}</p>
@if($configuration['writable'])
<div class="page-actions"><a class="m3-button m3-button--filled" href="{{ route('pathways-panel.new',['kind'=>'programme']) }}">{{ __('Add a programme source') }}</a>
<a class="m3-button m3-button--outlined" href="{{ route('pathways-panel.new',['kind'=>'subject_choice_guide']) }}">{{ __('Add a Grade 9 choice guide') }}</a></div>
@endif
@forelse($listing['items'] as $row)
<article class="m3-card m3-card--outlined" style="margin-block:16px;overflow-wrap:anywhere"><div class="m3-card__content">
<h2><a href="{{ route('pathways-panel.edit',['reference'=>$row['id']]) }}">{{ $row['title'] }}</a></h2>
@if($row['institution'])<p>{{ $row['institution'] }}</p>@endif
<p>{{ __('Revision') }} {{ $row['revision'] }} · {{ __(ucfirst($row['status'])) }} · {{ __('Reviewed') }} {{ $row['reviewed_on'] }} · {{ __('Review expires') }} {{ $row['expires_on'] }}</p>
@if($row['expires_on'] < app(\App\Services\Academics\AcademicAuthority::class)->today())<p>{{ __('This source needs review before families can use it.') }}</p>@endif
<p><a href="{{ $row['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $row['source_title'] }} ({{ $row['source_year'] }})</a></p>
<p style="white-space:pre-wrap">{{ $row['guidance'] }}</p>
</div></article>
@empty<p>{{ __('No pathway sources have been published at this school.') }}</p>@endforelse
@if($listing['next_page'])<a href="{{ route('pathways-panel.index',['page'=>$listing['next_page']]) }}">{{ __('More pathway sources') }}</a>@endif
</div>
@endsection
