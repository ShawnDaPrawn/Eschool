@extends('layouts.master')
@section('title', __('Relief cover'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/relief-cover.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content cover-workbench">
    <x-page.header :title="__('Relief cover')" :subtitle="__('Request teacher absence cover and review dated substitute lessons.')" />
    @include('academics.groups-status')
    @if ($messages)<div role="alert">@foreach ($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    @if (!$canWrite)<p role="status">{{ __('This school year is read-only. Switch to the current year to change cover.') }}</p>@endif
    <form method="GET" action="{{ route('relief-cover.index') }}" aria-label="{{ __('Choose cover dates') }}" class="cover-fields">
        <x-page.field id="cover-year" name="session_year_id" type="select" :label="__('School year')" :value="$year->id" :options="$years->pluck('name','id')->all()" :restore-old-input="false" required />
        <x-page.field id="cover-from" name="from" type="date" :label="__('From date')" :value="$from" :restore-old-input="false" required />
        <x-page.field id="cover-to" name="to" type="date" :label="__('To date')" :value="$to" :restore-old-input="false" required />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('View cover') }}</button>
    </form>
    <section class="page-section" aria-labelledby="my-cover-title"><h2 id="my-cover-title">{{ __('My assigned lessons') }}</h2>
        @forelse ($mine as $entry)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $entry['source']['title'] }}</h3><p>{{ $entry['on_date'] }} · {{ $entry['source']['starts_at'] }}–{{ $entry['source']['ends_at'] }} · {{ $entry['source']['room_name'] }}</p><p>{{ __('Covering') }} {{ $entry['source']['teacher_name'] }}</p></div></article>
        @empty<p>{{ __('No assigned cover in these dates.') }}</p>@endforelse
    </section>
    @if ($record)
        <section class="page-section" aria-labelledby="cover-detail-title"><h2 id="cover-detail-title">{{ $record['teacher_name'] }} · {{ $record['starts_on'] }} – {{ $record['ends_on'] }}</h2>
            <p>{{ __('Status') }}: {{ __($record['state']) }} · {{ __('Revision') }} {{ $record['revision'] }}</p>
            @if ($record['reason'])<p>{{ __('Private reason') }}: {{ $record['reason'] }}</p>@endif
            @if ($record['cancel_reason'])<p>{{ __('Cancellation reason') }}: {{ $record['cancel_reason'] }}</p>@endif
            <p>{{ __('Private reasons are visible only to the requester and authorized managers.') }}</p>
            @foreach ($record['occurrences'] as $entry)
                <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $entry['source']['title'] }}</h3><p>{{ $entry['on_date'] }} · {{ $entry['source']['starts_at'] }}–{{ $entry['source']['ends_at'] }} · {{ $entry['source']['room_name'] }}</p><p>{{ __('Status') }}: {{ __($entry['state']) }} @if ($entry['substitute_name'])· {{ $entry['substitute_name'] }}@endif</p>
                    @if ($entry['state']==='needs_review')<p>{{ __('The timetable or availability changed. Review and refresh this request before assigning cover.') }}</p>@endif
                    @if ($canWrite && $configuration['can_manage'] && in_array($entry['state'],['open','assigned'],true))
                        <a href="{{ route('relief-cover.show',['absence'=>$record['id'],'session_year_id'=>$year->id,'from'=>$from,'to'=>$to,'choose'=>$entry['id']]) }}#cover-choices">{{ __('Choose substitute') }} · {{ $entry['on_date'] }} {{ $entry['source']['starts_at'] }}</a>
                    @endif
                </div></article>
            @endforeach
            @if ($canWrite && $choices)
                <form id="cover-choices" method="POST" action="{{ route('relief-cover.assign',['occurrence'=>$choices['occurrence_id']]) }}" aria-label="{{ __('Assign substitute') }}" class="cover-fields">
                    @include('pastoral.cover-hidden',['formOperation'=>isset($draft['substitute_teacher_id'])?$operationId:null])
                    <input type="hidden" name="revision" value="{{ $choices['revision'] }}">
                    <x-page.field id="cover-substitute" name="substitute_teacher_id" type="select" :label="__('Available substitute')" :options="array_column($choices['teachers'],'name','id')" :value="$draft['substitute_teacher_id'] ?? ''" :placeholder="__('Choose')" :restore-old-input="false" required />
                    <button class="m3-button m3-button--filled" type="submit" @disabled(!$choices['teachers'])>{{ __('Assign substitute') }}</button>
                    @if (!$choices['teachers'])<p>{{ __('No available teachers. Review their timetables and absence requests.') }}</p>@endif
                </form>
            @endif
            @if ($canWrite && $record['state']==='requested')
                @foreach (['refresh'=>__('Refresh timetable mapping'),'cancel'=>__('Cancel cover request')] as $action=>$label)
                    <form method="POST" action="{{ route('relief-cover.'.$action,['absence'=>$record['id']]) }}" aria-label="{{ $label }}" class="cover-fields">
                        @include('pastoral.cover-hidden')<input type="hidden" name="revision" value="{{ $record['revision'] }}">
                        <x-page.field :id="'cover-'.$action.'-reason'" name="reason" type="textarea" :label="__('Reason for this change')" :value="isset($draft['revision'])?($draft['reason'] ?? ''):''" :restore-old-input="false" maxlength="500" required />
                        <button class="m3-button m3-button--outlined" type="submit">{{ $label }}</button>
                    </form>
                @endforeach
            @endif
        </section>
    @endif
    @if ($canWrite)
        <section class="page-section" aria-labelledby="request-cover-title"><h2 id="request-cover-title">{{ __('Request cover') }}</h2>
            <form method="POST" action="{{ route('relief-cover.request') }}" aria-label="{{ __('Request cover') }}" class="cover-fields">
                @include('pastoral.cover-hidden',['formOperation'=>isset($draft['starts_on'])?$operationId:null])
                <x-page.field id="cover-request-teacher" name="teacher_id" type="select" :label="__('Absent teacher')" :options="array_column($configuration['teachers'],'name','id')" :value="$draft['teacher_id'] ?? ''" :restore-old-input="false" required />
                <x-page.field id="cover-request-start" name="starts_on" type="date" :label="__('First absence date')" :value="$draft['starts_on'] ?? $today" :restore-old-input="false" required />
                <x-page.field id="cover-request-end" name="ends_on" type="date" :label="__('Last absence date')" :value="$draft['ends_on'] ?? $today" :restore-old-input="false" required />
                <x-page.field id="cover-request-reason" name="reason" type="textarea" :label="__('Private reason (optional)')" :value="isset($draft['starts_on'])?($draft['reason'] ?? ''):''" :restore-old-input="false" maxlength="500" />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Request cover') }}</button>
            </form>
        </section>
    @endif
    <section class="page-section" aria-labelledby="cover-requests-title"><h2 id="cover-requests-title">{{ $configuration['can_manage']?__('School cover requests'):__('My cover requests') }}</h2>
        @forelse ($configuration['absences'] as $row)<p><a href="{{ route('relief-cover.show',['absence'=>$row['id'],'session_year_id'=>$year->id,'from'=>$from,'to'=>$to]) }}">{{ $row['teacher_name'] }} · {{ $row['starts_on'] }} – {{ $row['ends_on'] }}</a> · {{ __($row['state']) }}</p>@empty<p>{{ __('No cover requests in these dates.') }}</p>@endforelse
        @if ($configuration['current_page']>1)<a href="{{ route('relief-cover.index',['session_year_id'=>$year->id,'from'=>$from,'to'=>$to,'page'=>$configuration['current_page']-1]) }}">{{ __('Previous page') }}</a>@endif
        @if ($configuration['current_page']<$configuration['last_page'])<a href="{{ route('relief-cover.index',['session_year_id'=>$year->id,'from'=>$from,'to'=>$to,'page'=>$configuration['current_page']+1]) }}">{{ __('Next page') }}</a>@endif
    </section>
</div>
@endsection
