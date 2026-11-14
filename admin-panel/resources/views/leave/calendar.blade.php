@extends('layouts.master')
@section('title', __('Leave calendar'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Leave calendar')" :subtitle="__('Authorised staff, learner and facilitator leave for the selected school year. Private reasons and attachments are visible only inside the authorised request.')" />
<form method="GET" class="page-filters"><x-page.field name="week" type="date" :label="__('Week containing')" :value="$from->toDateString()" required /><x-page.field name="person_type" type="select" :label="__('Person type')" :value="$type" :options="['all'=>__('All authorised people'),'staff'=>__('Staff'),'learners'=>__('Learners'),'facilitators'=>__('Facilitators')]" /><x-page.field name="status" type="select" :label="__('Decision state')" :value="$status" :options="['all'=>__('All decisions'),'pending'=>__('Pending'),'approved'=>__('Approved'),'rejected'=>__('Rejected')]" /><button class="m3-button m3-button--outlined" type="submit">{{ __('View week') }}</button></form>
<nav class="panel-local-nav" aria-label="{{ __('Calendar weeks') }}"><a href="{{ route('leave-calendar.index',['week'=>$from->subWeek()->toDateString(),'person_type'=>$type,'status'=>$status]) }}">{{ __('Previous week') }}</a><span>{{ $from->format('d M') }} – {{ $to->format('d M Y') }} · {{ $year->name }}</span><a href="{{ route('leave-calendar.index',['week'=>$from->addWeek()->toDateString(),'person_type'=>$type,'status'=>$status]) }}">{{ __('Next week') }}</a></nav>
<div class="panel-workspace__grid">
@forelse($rows as $row)<article class="panel-workspace__card m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $row['name'] }}</h2><p>{{ __($row['kind']) }} · {{ __(ucfirst($row['status'])) }}</p><p>{{ $row['from'] }} – {{ $row['to'] }}</p><ul>@foreach($row['portions'] as $portion)<li>{{ $portion }}</li>@endforeach</ul><a href="{{ $row['url'] }}" class="m3-button m3-button--outlined">{{ __('Open request') }}</a></div></article>@empty<p>{{ __('No authorised leave requests match this week and these filters.') }}</p>@endforelse
</div>
</div>
@endsection
