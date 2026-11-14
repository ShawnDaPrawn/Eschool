@extends('layouts.master')
@section('title', $row['title'])
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$row['title']" :subtitle="__('Exactly what this contribution shares with Navigate Ed and, once reviewed, other schools: the version the teacher submitted.')" />
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p><a class="m3-button m3-button--text" href="{{ route('shared-library.index') }}">{{ __('Shared library') }}</a></p>

    <p>{{ __('Shared by') }}: {{ $row['mine'] ? __('you') : $row['teacher'] }} · {{ __('Credit') }}: {{ $row['credit_label'] }} · {{ $row['licence'] }}</p>
    <p>@if ($row['topic']){{ $row['topic']['framework'] }} · {{ $row['topic']['subject_code'] }} · {{ $row['topic']['grade'] }} · {{ $row['topic']['name'] }} · @endif{{ $row['status_label'] }}@if ($row['decline_reason']): {{ $row['decline_reason'] }}@endif</p>

    <article class="m3-card m3-card--outlined page-section" aria-labelledby="contribution-question"><div class="m3-card__content">
        <h2 id="contribution-question" dir="auto">{{ $row['question']['prompt'] }}</h2>
        @if ($row['question']['options'] !== [])
            <ul>@foreach ($row['question']['options'] as $option)<li dir="auto">{{ $option['id'] ?? '' }}: {{ $option['text'] ?? '' }}</li>@endforeach</ul>
        @endif
        @if ($row['question']['steps'] !== [])
            <ol>@foreach ($row['question']['steps'] as $step)<li dir="auto">{{ $step['prompt'] ?? '' }} → {{ implode(', ', (array) ($step['answers'] ?? [])) }}</li>@endforeach</ol>
        @endif
        @if ($row['question']['answers'] !== [])<p>{{ __('Accepted answers') }}: {{ implode(', ', $row['question']['answers']) }}</p>@endif
        <p dir="auto">{{ __('Worked explanation') }}: {{ $row['question']['explanation'] }}</p>
        <p>{{ __('Expected time: :seconds seconds', ['seconds' => $row['estimated_seconds']]) }}</p>
    </div></article>

    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content">
    <h2>{{ __('Review and actions') }}</h2>
    @if ($row['can_decide'])
        <form method="POST" action="{{ route('shared-library.contributions.approve', $row['id']) }}" class="page-inline-form">@csrf
            <button class="m3-button m3-button--filled" type="submit">{{ __('Approve') }}</button></form>
        <form method="POST" action="{{ route('shared-library.contributions.decline', $row['id']) }}" class="page-inline-form">@csrf
            <x-page.field name="reason" id="decline-reason" type="text" :label="__('Reason for not approving')" minlength="10" maxlength="500" :restore-old-input="false" required />
            <button class="m3-button m3-button--text" type="submit">{{ __('Do not approve') }}</button></form>
    @endif
    @if ($row['can_withdraw'])
        <form method="POST" action="{{ route('shared-library.contributions.withdraw', $row['id']) }}" class="page-inline-form">@csrf
            <button class="m3-button m3-button--text" type="submit" data-confirm="{{ __('Withdraw this shared question?') }}">{{ __('Withdraw') }}</button></form>
    @endif
    </div></section>
</div>
@endsection
