@extends('layouts.master')
@section('title', __('Every child teaches something'))
@section('content')
<div class="content-wrapper m3 page-content peer-page">
<x-page.header :title="__('Every child teaches something')" :subtitle="__('How many learners in each grade have tutored at least once this term.')" />
<p>{{ __('Counts only. No learner is named or ranked. A session counts once its supervisor has confirmed it; cancelled and corrected sessions do not count.') }}</p>
<p><a href="{{ route('peer-tutoring-panel.index') }}">{{ __('Back to peer tutoring') }}</a></p>
@if($rows)
<div class="peer-table page-table__scroll" role="region" aria-label="{{ __('Learners who have tutored this term, per grade') }}" tabindex="0">
<table class="page-table__table">
<caption>{{ __('Learners who have tutored this term, per grade') }}</caption>
<thead><tr><th scope="col">{{ __('Grade') }}</th><th scope="col">{{ __('This term') }}</th><th scope="col">{{ __('Current learners') }}</th><th scope="col">{{ __('Have tutored') }}</th><th scope="col">{{ __('Share') }}</th></tr></thead>
<tbody>
@foreach($rows as $row)
<tr><th scope="row">{{ $row['grade'] }}</th><td>{{ $row['term'] }} ({{ $row['from'] }} – {{ $row['to'] }})</td><td>{{ $row['learners'] }}</td><td>{{ $row['tutored'] }}</td><td>{{ $row['share'] }}%</td></tr>
@endforeach
</tbody>
</table>
</div>
@else
<p>{{ __('No current learners in the grades you lead.') }}</p>
@endif
</div>
@endsection
@section('css')
<style>.peer-page{overflow-wrap:anywhere}.peer-table{overflow-x:auto;max-width:100%}</style>
@endsection
