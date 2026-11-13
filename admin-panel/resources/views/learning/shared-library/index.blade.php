@extends('layouts.master')
@section('title', __('Shared library'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Shared library')" :subtitle="__('Practice questions teachers share between schools. Navigate Ed reviews every shared question before any other school sees it. Only the teaching content is shared: never a learner\'s answer, mark or name.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @php
        $libraryViews = [];
        if ($teacher) $libraryViews += ['browse' => __('Browse library'), 'mine' => __('My contributions')];
        if ($leader || $school !== []) $libraryViews['school'] = __('School contributions');
        if ($leader) $libraryViews['configuration'] = __('Configuration');
        $libraryView = request('view', ($school !== [] || !$teacher) ? 'school' : 'browse');
        if (!array_key_exists($libraryView, $libraryViews)) $libraryView = array_key_first($libraryViews);
    @endphp
    <nav class="workspace-tabs" aria-label="{{ __('Shared library workspace') }}">
        @foreach($libraryViews as $view => $label)<a class="m3-button m3-button--text" href="{{ route('shared-library.index', ['view' => $view]) }}" @if($libraryView === $view) aria-current="page" @endif>{{ $label }}</a>@endforeach
    </nav>
    @if ($leader && $libraryView === 'configuration')
        <form method="POST" action="{{ route('shared-library.settings') }}" class="m3-card m3-card--outlined page-section" aria-labelledby="sharing-heading"><div class="m3-card__content">
            @csrf @method('PUT')
            <h2 id="sharing-heading">{{ __('How your school shares') }}</h2>
            <p>{{ __('Teachers can share their own approved practice questions with other schools only while sharing is on. Each one is shared under :licence.', ['licence' => $licence['name'].' ('.$licence['code'].')']) }}</p>
            <p><label for="sharing-enabled"><input id="sharing-enabled" type="checkbox" name="sharing_enabled" value="1" @checked($settings['sharing_enabled'])> {{ __('Teachers may share questions with the library') }}</label></p>
            <p><label for="leader-approval"><input id="leader-approval" type="checkbox" name="leader_approval" value="1" @checked($settings['leader_approval'])> {{ __('A school leader approves each question before it leaves the school') }}</label></p>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save sharing settings') }}</button>
        </div></form>
    @endif

    @if ($libraryView === 'school')
        <section class="page-section" aria-labelledby="school-heading">
            <h2 id="school-heading">{{ __('Questions your school shared') }}</h2>
            @if($school === [])<x-page.empty-state :title="__('No school contributions yet')" :message="__('Questions teachers share will appear here for review.')" />@else
            @include('learning.shared-library.contributions', ['rows' => $school, 'decide' => true])
            @endif
        </section>
    @endif

    @if ($teacher)
        @if($libraryView === 'mine')
        <section class="page-section" aria-labelledby="mine-heading">
            <h2 id="mine-heading">{{ __('Questions you shared') }}</h2>
            <p>{{ $settings['sharing_enabled'] ? __('To share one of your questions, open it in your question bank and choose "Share with other schools".') : __('Your school has not switched sharing on. A school leader can switch it on.') }}
                <a class="m3-button m3-button--text" href="{{ route('question-banks.index') }}">{{ __('Question banks') }}</a></p>
            @if ($mine === [])
                <p>{{ __('You have not shared a question yet.') }}</p>
            @else
                @include('learning.shared-library.contributions', ['rows' => $mine, 'decide' => false])
            @endif
        </section>

        @endif
        @if($libraryView === 'browse')
        <section class="page-section" aria-labelledby="browse-heading">
            <h2 id="browse-heading">{{ __('Questions other teachers shared') }}</h2>
            @if ($catalogue === null)
                <x-page.empty-state :title="__('The shared library is not on for your school yet')" :message="__('Navigate Ed switches the library on for a school. Until then you can still share your own questions when your school allows it.')" />
            @else
                <form method="GET" action="{{ route('shared-library.index') }}" aria-label="{{ __('Find questions') }}" class="panel-form-grid">
                    <input type="hidden" name="view" value="browse">
                    <x-page.field name="framework" id="find-framework" type="select" :label="__('Curriculum')" :placeholder="__('Any')" :restore-old-input="false"
                        :options="collect($catalogue['filters']['frameworks'])->mapWithKeys(fn ($f) => [$f => $f])->all()" :value="$chosen['framework'] ?? null" />
                    <x-page.field name="subject_code" id="find-subject" type="select" :label="__('Subject')" :placeholder="__('Any')" :restore-old-input="false"
                        :options="collect($catalogue['filters']['subject_codes'])->mapWithKeys(fn ($s) => [$s => $s])->all()" :value="$chosen['subject_code'] ?? null" />
                    <x-page.field name="grade_level_id" id="find-grade" type="select" :label="__('Grade')" :placeholder="__('Any')" :restore-old-input="false"
                        :options="collect($catalogue['filters']['grades'])->pluck('name', 'id')->all()" :value="$chosen['grade_level_id'] ?? null" />
                    <x-page.field name="library_topic_id" id="find-topic" type="select" :label="__('Topic')" :placeholder="__('Any')" :restore-old-input="false"
                        :options="collect($catalogue['filters']['topics'])->mapWithKeys(fn ($t) => [$t['id'] => $t['subject_code'].' · '.$t['grade'].' · '.$t['name']])->all()" :value="$chosen['library_topic_id'] ?? null" />
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Find') }}</button>
                </form>
                @forelse ($catalogue['items'] as $row)
                    <article class="m3-card m3-card--outlined page-section" aria-labelledby="shared-{{ $row['id'] }}"><div class="m3-card__content">
                        <h3 id="shared-{{ $row['id'] }}" dir="auto"><a href="{{ route('shared-library.items.show', $row['id']) }}">{{ $row['title'] }}</a></h3>
                        <p>{{ $row['topic']['framework'] }} · {{ $row['topic']['subject_code'] }} · {{ $row['topic']['grade'] }} · {{ $row['topic']['name'] }}</p>
                        <p>{{ $row['attribution'] }} · {{ $row['licence']['code'] }}@if ($row['copied']) · {{ __('In your school\'s banks') }}@endif</p>
                        <p>{{ $row['rating'] === null ? __('Not enough ratings to show yet.') : __(':average out of 5 from :count ratings', ['average' => number_format($row['rating']['average'], 1), 'count' => $row['rating']['count']]) }}</p>
                    </div></article>
                @empty
                    <x-page.empty-state :title="__('No shared questions found')" :message="__('Try another subject, grade or topic.')" />
                @endforelse
                @if ($catalogue['last_page'] > 1)
                    <nav aria-label="{{ __('Pages') }}">
                        @if ($catalogue['page'] > 1)<a href="{{ route('shared-library.index', ['view' => 'browse', 'page' => $catalogue['page'] - 1] + array_filter($chosen)) }}">{{ __('Previous') }}</a>@endif
                        {{ __('Page :page of :last', ['view' => 'browse', 'page' => $catalogue['page'], 'last' => $catalogue['last_page']]) }}
                        @if ($catalogue['page'] < $catalogue['last_page'])<a href="{{ route('shared-library.index', ['view' => 'browse', 'page' => $catalogue['page'] + 1] + array_filter($chosen)) }}">{{ __('Next') }}</a>@endif
                    </nav>
                @endif
            @endif
        </section>
        @endif
    @endif
</div>
@endsection
