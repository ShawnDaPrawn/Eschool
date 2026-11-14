@extends('layouts.master')
@section('title', $item['title'])
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$item['title']" :subtitle="$item['topic']['framework'].' · '.$item['topic']['subject_code'].' · '.$item['topic']['grade'].' · '.$item['topic']['name']" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p><a class="m3-button m3-button--text" href="{{ route('shared-library.index') }}">{{ __('Shared library') }}</a></p>

    <nav class="panel-section-nav" aria-label="{{ __('Question sections') }}"><a href="#shared-question">{{ __('Question') }}</a><a href="#ratings-heading">{{ __('Ratings') }}</a>@if($item['copyable'])<a href="#copy-heading">{{ __('Copy into your question bank') }}</a>@endif</nav>
    <div class="workspace-layout"><div class="workspace-main">
    <article class="m3-card m3-card--outlined page-section" aria-labelledby="shared-question"><div class="m3-card__content">
        <h2 id="shared-question" dir="auto">{{ $item['question']['prompt'] }}</h2>
        @if ($item['question']['options'] !== [])
            <ul>@foreach ($item['question']['options'] as $option)<li dir="auto">{{ $option['id'] ?? '' }}: {{ $option['text'] ?? '' }}</li>@endforeach</ul>
        @endif
        @if ($item['question']['steps'] !== [])
            <ol>@foreach ($item['question']['steps'] as $step)<li dir="auto">{{ $step['prompt'] ?? '' }} → {{ implode(', ', (array) ($step['answers'] ?? [])) }}</li>@endforeach</ol>
        @endif
        @if ($item['question']['answers'] !== [])<p>{{ __('Accepted answers') }}: {{ implode(', ', $item['question']['answers']) }}</p>@endif
        <p dir="auto">{{ __('Worked explanation') }}: {{ $item['question']['explanation'] }}</p>
        <p>{{ __('Expected time: :seconds seconds', ['seconds' => $item['estimated_seconds']]) }}</p>
        <p>{{ $item['attribution'] }} · <a href="{{ $item['licence']['url'] }}" rel="noopener noreferrer license">{{ $item['licence']['code'] }}</a></p>
    </div></article>

    <section class="page-section" aria-labelledby="ratings-heading">
        <h2 id="ratings-heading">{{ __('Ratings') }}</h2>
        <p>{{ $item['rating'] === null ? __('Not enough ratings to show yet.') : __(':average out of 5 from :count ratings', ['average' => number_format($item['rating']['average'], 1), 'count' => $item['rating']['count']]) }}</p>
        @foreach ($item['comments'] as $comment)<p dir="auto">{{ str_repeat('★', $comment['stars']) }} {{ $comment['comment'] }}</p>@endforeach
        @if ($item['can_rate'])
            <form method="POST" action="{{ route('shared-library.items.rate', $item['id']) }}" aria-label="{{ __('Your rating') }}">
                @csrf
                <x-page.field name="stars" id="rate-stars" type="select" :label="__('Your rating (1 to 5)')" :options="[1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']" :value="$item['my_rating']['stars'] ?? null" required />
                <x-page.field name="comment" id="rate-comment" type="textarea" :label="__('A short comment (optional)')" maxlength="500" :value="$item['my_rating']['comment'] ?? null"
                    :help="__('Other teachers see your comment without your name or your school. Do not name a learner.')" />
                <button class="m3-button m3-button--outlined" type="submit">{{ $item['my_rating'] === null ? __('Save my rating') : __('Change my rating') }}</button>
            </form>
            @if ($item['own_school'])<p>{{ __('Your school shared this question, so your school\'s ratings are not counted in its average.') }}</p>@endif
        @else
            <p>{{ __('You can rate it once your school has used it: copy it into a question bank, or let your learners practise it.') }}</p>
        @endif
    </section>

    </div><aside class="workspace-context">
    @if ($item['copyable'])
    <section class="page-section" aria-labelledby="copy-heading">
        <h2 id="copy-heading">{{ __('Copy into your question bank') }}</h2>
        <p>{{ __('The copy becomes your school\'s own question. You approve it for one of your groups, with a topic and lesson material of that group, as you do for any question.') }}</p>
        @if ($groups === [])
            <x-page.empty-state :title="__('No groups to copy it into')" :message="__('You can copy questions into the learning groups you teach this year.')" />
        @else
            <form method="GET" action="{{ route('shared-library.items.show', $item['id']) }}" aria-label="{{ __('Choose a group') }}">
                <x-page.field name="group" id="copy-group" type="select" :label="__('Group')" :restore-old-input="false"
                    :options="collect($groups)->mapWithKeys(fn ($g) => [$g['id'] => $g['name'].' · '.$g['subject_name']])->all()" :value="$group['id'] ?? null" />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Choose group') }}</button>
            </form>
        @endif
        @if ($choices !== null)
            @if ($choices['topics'] === [] || $choices['sources'] === [])
                <x-page.empty-state :title="__('Nothing to link the question to yet')" :message="__('A question needs a topic from the group\'s published curriculum plan and lesson material published to the group.')" />
            @else
                <form method="POST" action="{{ route('shared-library.items.copy', $item['id']) }}" aria-label="{{ __('Copy and approve') }}">
                    @csrf
                    <input type="hidden" name="operation_id" value="{{ old('operation_id', $operationId) }}">
                    <input type="hidden" name="group_id" value="{{ $group['id'] }}">
                    <x-page.field name="topic_id" id="copy-topic" type="select" :label="__('Topic')" :options="collect($choices['topics'])->pluck('name', 'id')->all()" required />
                    <x-page.field name="content_version_id" id="copy-source" type="select" :label="__('Lesson material it practises')" :options="collect($choices['sources'])->pluck('name', 'id')->all()" required />
                    <button class="m3-button m3-button--filled" type="submit">{{ __('Copy and approve for :group', ['group' => $group['name']]) }}</button>
                </form>
            @endif
        @endif
    </section>
    @endif
    </aside></div>
</div>
@endsection
