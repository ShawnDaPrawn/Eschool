@extends('layouts.master')
@use('App\Services\Platform\Privacy\DataRequests')
@section('title', __('Data requests'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Data requests')" :subtitle="__('POPIA requests that reached Navigate Ed about personal information your school holds. Your school is the responsible party: decide each request, answer the person who asked, and record your answer here by the date shown so Navigate Ed can close it.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @if ($requests === [])
        <x-page.empty-state :title="__('No data requests')" :message="__('Navigate Ed has not passed any requests to your school.')" />
    @endif

    @foreach ($requests as $request)
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="request-{{ $request['id'] }}"><div class="m3-card__content">
        <h2 id="request-{{ $request['id'] }}">{{ $request['reference'] }} · {{ __(DataRequests::KINDS[$request['kind']]) }}</h2>
        <p>
            {{ __(DataRequests::STATUSES[$request['status']]) }} ·
            {{ __('Answer due :date', ['date' => $request['due_on']]) }}
            @if ($request['overdue'])<strong>{{ __('Overdue') }}</strong>@endif
        </p>
        <dl>
            <dt>{{ __('Who is asking') }}</dt><dd>{{ $request['requester_name'] }} ({{ __(DataRequests::REQUESTER_ROLES[$request['requester_role']]) }}) · {{ $request['requester_contact'] }}</dd>
            <dt>{{ __('What they asked for') }}</dt><dd style="white-space: pre-line">{{ $request['details'] }}</dd>
            <dt>{{ __('Received by Navigate Ed') }}</dt><dd>{{ $request['received_on'] }}</dd>
            @if ($request['answered_at'])
                <dt>{{ __('Your answer') }}</dt><dd>{{ __(DataRequests::OUTCOMES[$request['outcome']]) }} · {{ $request['answered_at'] }} · {{ $request['answered_by'] }}</dd>
                <dt>{{ __('Your note') }}</dt><dd style="white-space: pre-line">{{ $request['outcome_note'] }}</dd>
            @endif
        </dl>
        @if ($request['status'] === 'with_school')
        <form method="POST" action="{{ route('data-requests.answer', $request['id']) }}" aria-label="{{ __('Record your answer') }}">
            @csrf
            <x-page.field name="outcome" type="select" :id="'outcome-'.$request['id']" :label="__('What you did')" :options="collect(DataRequests::OUTCOMES)->map(fn ($label) => __($label))->all()" :placeholder="__('Choose')" required />
            <x-page.field name="note" type="textarea" :id="'note-'.$request['id']" :label="__('What you told the person, and why if you refused any part')" minlength="10" maxlength="2000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Record your answer') }}</button>
        </form>
        @endif
    </div></section>
    @endforeach
</div>
@endsection
