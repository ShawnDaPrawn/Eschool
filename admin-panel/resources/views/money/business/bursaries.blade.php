@extends('layouts.master')
@section('title', __('Bursary applications'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench">
<x-page.header :title="__('Bursary applications')" :subtitle="__('Review applications, retain committee decisions and pass awarded support to Finance for independent approval.')" />
<form method="get" class="workspace-filters">
<x-page.field name="state" type="select" :label="__('Application state')" :value="request('state','submitted')" :options="['submitted'=>__('Awaiting decision'),'awarded'=>__('Awarded'),'declined'=>__('Declined'),'withdrawn'=>__('Withdrawn'),'expired'=>__('Expired')]" />
<x-m3.button type="submit" variant="outlined">{{ __('Apply filter') }}</x-m3.button>
</form>
@if(!$queue['items'])<x-page.empty-state :title="__('No applications in this state')" :message="__('Choose another state to review retained decisions. Families submit their own private applications.')" />
@else
<div class="page-table__scroll" role="region" aria-label="{{ __('Bursary applications') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Bursary applications') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('School year') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Submitted') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@foreach($queue['items'] as $row)<tr><td>{{ $row['learner_name'] }}</td><td>{{ $row['session_year'] }}</td><td>{{ __($row['state']) }}</td><td>{{ $row['submitted_at'] ?? '—' }}</td><td><x-m3.button variant="text" :href="route('bursary-panel.show',['application'=>$row['id']])">{{ __('Review application') }}</x-m3.button></td></tr>@endforeach
</tbody></table></div>
<nav class="m3-row m3-gap-3" aria-label="{{ __('Application pages') }}">
@if($queue['page']>1)<x-m3.button variant="outlined" :href="route('bursary-panel.index',['state'=>request('state','submitted'),'page'=>$queue['page']-1])">{{ __('Previous') }}</x-m3.button>@endif
<p>{{ __('Page') }} {{ $queue['page'] }} · {{ $queue['total'] }} {{ __('applications') }}</p>
@if($queue['page']*50<$queue['total'])<x-m3.button variant="outlined" :href="route('bursary-panel.index',['state'=>request('state','submitted'),'page'=>$queue['page']+1])">{{ __('Next') }}</x-m3.button>@endif
</nav>
@endif
</div>
@endsection
