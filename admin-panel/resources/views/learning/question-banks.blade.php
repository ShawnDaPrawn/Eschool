@extends('layouts.master')
@section('title', __('Question banks'))
@section('content')
@php
    $visibilityLabels = ['private' => __('Only me'), 'groups' => __('Teachers of the groups using it'), 'school' => __('Every teacher of this subject and grade')];
    $waitingLabels = ['source' => __('Waiting for material: publish its source material to this group, then approve the question again with it.'),
        'topic' => __('Waiting for the topic: it is not in this group\'s published curriculum plan yet.'), 'archived' => __('Archived: no learner gets it.')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Question banks')" :subtitle="__('Practice questions belong to their subject and grade, not to one class, so they stay when the year changes. Use them in any of your groups, and choose who else may find and reuse the ones you wrote.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @if ($scopes === [])
        <x-page.empty-state :title="__('No question banks yet')" :message="__('Banks appear for the subjects and grades you teach this year. Approve practice questions for a group in the teacher app to start one.')" />
    @else
        <form method="GET" action="{{ route('question-banks.index') }}" class="page-section" aria-label="{{ __('Choose a bank') }}">
            <x-page.field name="scope" id="bank-scope" type="select" :label="__('Subject and grade')" :restore-old-input="false"
                :options="collect($scopes)->mapWithKeys(fn ($s) => [$s['subject_id'].'-'.$s['grade_level_id'] => $s['label']])->all()"
                :value="$chosen ? $chosen['subject_id'].'-'.$chosen['grade_level_id'] : null" />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Open bank') }}</button>
        </form>
    @endif

    @if ($bank !== null)
    <section class="page-section" aria-labelledby="bank-questions">
        <h2 id="bank-questions">{{ trans_choice(':bank: :count question|:bank: :count questions', $bank['total'], ['bank' => $chosen['label'], 'count' => $bank['total']]) }}</h2>
        @forelse ($bank['items'] as $item)
            <article class="m3-card m3-card--outlined page-section" aria-labelledby="question-{{ $item['id'] }}"><div class="m3-card__content">
                <h3 id="question-{{ $item['id'] }}" dir="auto">{{ $item['prompt'] }}</h3>
                <p>{{ __('Topic') }}: {{ $item['topic'] }} · {{ __('Written by') }}: {{ $item['mine'] ? __('you') : ($item['author'] ?? __('unknown')) }} · {{ __('Who can reuse it') }}: {{ $visibilityLabels[$item['visibility']] ?? $item['visibility'] }}@unless ($item['active']) · {{ __('Archived') }}@endunless</p>

                <h4>{{ __('Used this year in') }}</h4>
                @if ($item['uses'] === [])
                    <p>{{ __('No group this year.') }}</p>
                @else
                    <ul>
                        @foreach ($item['uses'] as $use)
                            <li>
                                {{ $use['name'] }}: {{ $use['waiting'] === null ? __('learners get it in daily practice') : $waitingLabels[$use['waiting']] }}
                                @if ($use['mine'])
                                    <form method="POST" action="{{ route('question-banks.stop', ['item' => $item['id']]) }}" class="page-inline-form">
                                        @csrf<input type="hidden" name="group_id" value="{{ $use['group_id'] }}">
                                        <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Stop using it in :group', ['group' => $use['name']]) }}">{{ __('Stop using') }}</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($item['earlier_uses'] > 0)
                    <p>{{ trans_choice('Also used in :count group in earlier years.|Also used in :count groups in earlier years.', $item['earlier_uses'], ['count' => $item['earlier_uses']]) }}</p>
                @endif

                @if ($item['targets'] !== [])
                    <form method="POST" action="{{ route('question-banks.use', ['item' => $item['id']]) }}" aria-label="{{ __('Use this question in one of your groups') }}">
                        @csrf
                        <x-page.field name="group_id" id="use-{{ $item['id'] }}" type="select" :label="__('Use it in')" :options="collect($item['targets'])->pluck('name', 'group_id')->all()" :restore-old-input="false" required />
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Use in this group') }}</button>
                    </form>
                @endif

                @if ($item['can_edit'])
                    <form method="POST" action="{{ route('question-banks.update', ['item' => $item['id']]) }}" aria-label="{{ __('Sharing and archiving') }}">
                        @csrf
                        <x-page.field name="visibility" id="visibility-{{ $item['id'] }}" type="select" :label="__('Who can reuse it')" :options="$visibilityLabels" :value="$item['visibility']" :restore-old-input="false" required />
                        <input type="hidden" name="active" value="{{ $item['active'] ? 1 : 0 }}">
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Save sharing') }}</button>
                    </form>
                    <form method="POST" action="{{ route('question-banks.update', ['item' => $item['id']]) }}">
                        @csrf
                        <input type="hidden" name="visibility" value="{{ $item['visibility'] }}">
                        <input type="hidden" name="active" value="{{ $item['active'] ? 0 : 1 }}">
                        <button class="m3-button m3-button--text" type="submit">{{ $item['active'] ? __('Archive') : __('Restore') }}</button>
                    </form>
                @endif
            </div></article>
        @empty
            <x-page.empty-state :title="__('No questions you can see in this bank')" :message="__('Questions appear here when you or a colleague approve them for a group of this subject and grade, and colleagues share theirs.')" />
        @endforelse

        @if ($bank['last_page'] > 1)
            <nav aria-label="{{ __('Pages') }}"><ul class="m3-pagination">
                @for ($page = 1; $page <= $bank['last_page']; $page++)
                    <li><a href="{{ route('question-banks.index', ['scope' => $chosen['subject_id'].'-'.$chosen['grade_level_id'], 'page' => $page]) }}" @if ($page === $bank['page']) aria-current="page" @endif>{{ $page }}</a></li>
                @endfor
            </ul></nav>
        @endif
    </section>
    @endif
</div>
@endsection
