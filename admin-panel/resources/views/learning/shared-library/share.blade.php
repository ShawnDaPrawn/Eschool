@extends('layouts.master')
@section('title', __('Share with other schools'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Share with other schools')" :subtitle="__('Offer this question to teachers at other schools. Only the question, its options, accepted answers, worked explanation and expected time are shared: never your learners\' answers, marks or names. Your question stays exactly as it is.')" />
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p><a class="m3-button m3-button--text" href="{{ route('question-banks.index') }}">{{ __('Question banks') }}</a> <a class="m3-button m3-button--text" href="{{ route('shared-library.index') }}">{{ __('Shared library') }}</a></p>

    <article class="m3-card m3-card--outlined page-section"><div class="m3-card__content"><p dir="auto">{{ $options['question']['prompt'] }}</p></div></article>

    @if (! $options['sharing_enabled'])
        <x-page.empty-state :title="__('Sharing is off at your school')" :message="__('A school leader can switch sharing on in the shared library.')" />
    @elseif (! $options['shareable'])
        <x-page.empty-state :title="__('This question cannot be shared yet')" :message="__('Questions with a picture cannot be shared yet.')" />
    @elseif ($options['current_contribution_id'] !== null)
        <x-page.empty-state :title="__('Already shared')" :message="__('This question is already shared or waiting for approval. Withdraw it in the shared library to share it again.')" />
    @elseif ($options['topics'] === [])
        <x-page.empty-state :title="__('No library topic for this subject and grade yet')" :message="__('Navigate Ed has no library topics for this question\'s curriculum, subject and grade yet.')" />
    @else
        <form method="POST" action="{{ route('shared-library.contributions.store') }}" class="m3-card m3-card--outlined page-section" aria-labelledby="share-heading"><div class="m3-card__content">
            <h2 id="share-heading">{{ __('Share this question') }}</h2>
            @csrf
            <input type="hidden" name="practice_item_id" value="{{ $options['practice_item_id'] }}">
            <x-page.field name="title" id="share-title" :label="__('A short title for other teachers')" :value="$options['suggested_title']" minlength="3" maxlength="191" required />
            <x-page.field name="library_topic_id" id="share-topic" type="select" :label="__('Library topic')"
                :options="collect($options['topics'])->mapWithKeys(fn ($t) => [$t['id'] => $t['framework'].' · '.$t['subject_code'].' · '.$t['grade'].' · '.$t['name']])->all()" required />
            <x-page.field name="credit" id="share-credit" type="select" :label="__('How other schools see who shared it')" :options="collect($credits)->map(fn ($label) => __($label))->all()" value="school" required />
            <p><label for="share-rights"><input id="share-rights" type="checkbox" name="rights_confirmed" value="1" required> {{ __('I wrote this question, or I have the right to share it.') }}</label></p>
            <p><label for="share-licence"><input id="share-licence" type="checkbox" name="licence_accepted" value="1" required> {{ __('I share it under :licence, so other teachers may use and adapt it with credit.', ['licence' => $options['licence']['name'].' ('.$options['licence']['code'].')']) }}</label></p>
            <p>{{ $options['leader_approval'] ? __('A school leader approves it first. Then Navigate Ed reviews it before any other school sees it.') : __('Navigate Ed reviews it before any other school sees it.') }}</p>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Share') }}</button>
        </div></form>
    @endif
</div>
@endsection
