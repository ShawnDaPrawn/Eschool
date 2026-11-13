@extends('layouts.master')
@section('title', __('Sponsored learning places'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench"><x-page.header :title="__('Sponsored learning places')" :subtitle="__('Assign this school’s sponsored places to learners and review available capacity.')" />
@if($records->isEmpty())<x-page.empty-state :title="__('No sponsored places recorded')" :message="__('Sponsorship funding and capacity are recorded by the platform. Current school administrators assign the available places here.')" />
@else<div class="page-table__scroll" role="region" aria-label="{{ __('Sponsored learning places') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Sponsored learning places') }}</caption><thead><tr><th scope="col">{{ __('Sponsor') }}</th><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Places used') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Period ends') }}</th></tr></thead><tbody>
@foreach($records as $row)<tr><td><a href="{{ route('sponsored-places.show',['subscription'=>$row['id']]) }}">{{ $row['sponsor_name'] }}</a></td><td>{{ $row['reference'] }}</td><td>{{ count($row['learners']) }} / {{ $row['places'] }}</td><td>{{ __($row['effective_status']) }}</td><td>{{ $row['current_period_end']??'—' }}</td></tr>@endforeach
</tbody></table></div>{{ $records->withQueryString()->links() }}@endif</div>
@endsection
