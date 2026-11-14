@extends('saas.layout')

@section('title', __('Library reviews'))

@section('content')
<h1>{{ __('Library reviews') }}</h1>
<p class="saas-lead">{{ __('Library versions waiting for a decision. Approve what is correct, fits the topic and may be used under its licence; send anything else back with a reason the author can act on.') }}</p>
<p><a href="{{ route('saas.library.ratings') }}">{{ __('Teachers\' ratings of shared items') }}</a></p>
<p>{{ $subjects === [] ? __('You are not assigned to any subject yet, so nothing is waiting for you.') : __('Your subjects: :subjects', ['subjects' => implode(', ', $subjects)]) }}</p>
<nav aria-label="{{ __('Show') }}" class="saas-actions">
    @foreach ($filters as $value => $label)
        @if ($value === $filter)<strong aria-current="page">{{ __($label) }}</strong>@else<a href="{{ route('saas.library.reviews', $value === 'all' ? [] : ['filter' => $value]) }}">{{ __($label) }}</a>@endif
    @endforeach
</nav>
@if ($total > 0)<p>{{ trans_choice('{1} 1 version is waiting in your subjects.|[2,*] :count versions are waiting in your subjects.', $total, ['count' => $total]) }}</p>@endif

@forelse ($waiting as $version)
    <section class="saas-card" aria-label="{{ $version['title'] }}">
        <h2>{{ $version['title'] }} · {{ __('version :number', ['number' => $version['number']]) }}</h2>
        @if ($version['contribution'])
            <p><span class="saas-badge">{{ __('Shared by a school') }}</span> {{ __('Shared by :teacher at :school on :date. Shown to other schools as: :credit.', ['teacher' => $version['contribution']['teacher'], 'school' => $version['contribution']['school'], 'date' => $version['contribution']['date'], 'credit' => __($version['contribution']['credit'])]) }}</p>
        @endif
        @if ($version['ai'] || $version['attention'])
            <p>@if ($version['ai'])<span class="saas-badge saas-badge--warn">{{ __('AI draft: check every fact, answer and explanation') }}</span> @endif @if ($version['attention'])<span class="saas-badge saas-badge--warn">{{ __('Needs attention: learners\' answers flagged the published version') }}</span>@endif</p>
        @endif
        <p>{{ $version['kind'] }} · {{ $version['framework'] }} · {{ $version['subject_code'] }} · {{ $version['grade'] }} · {{ $version['topic'] }}</p>
        <p>{{ __('Source') }}: {{ $version['source'] }} · {{ __('sent by :name on :at', ['name' => $version['submitted_by'], 'at' => $version['submitted_at']]) }}</p>
        @include('saas.library.content', ['content' => $version['content']])
        @if ($version['refusal'] !== null)
            <p><span class="saas-badge saas-badge--warn">{{ $version['refusal'] }}</span></p>
        @else
            <form method="POST" action="{{ route('saas.library.versions.review', $version['id']) }}">
                @csrf
                <div class="saas-field">
                    <label for="reason-{{ $version['id'] }}">{{ __('Reason (needed to send it back)') }}</label>
                    <textarea id="reason-{{ $version['id'] }}" name="reason" maxlength="1000" rows="2"></textarea>
                </div>
                <div class="saas-actions">
                    <button class="saas-btn" type="submit" name="decision" value="approved">{{ __('Approve') }}</button>
                    <button class="saas-btn saas-btn--danger" type="submit" name="decision" value="rejected">{{ __('Send back') }}</button>
                </div>
            </form>
        @endif
    </section>
@empty
    <p class="saas-card">{{ __('Nothing is waiting for review in your subjects.') }}</p>
@endforelse
@if ($lastPage > 1)
    <nav aria-label="{{ __('Review pages') }}">
        @if ($page > 1)<a href="{{ route('saas.library.reviews', ['page' => $page - 1] + ($filter === 'all' ? [] : ['filter' => $filter])) }}">{{ __('Previous') }}</a>@endif
        {{ __('Page :page of :last', ['page' => $page, 'last' => $lastPage]) }}
        @if ($page < $lastPage)<a href="{{ route('saas.library.reviews', ['page' => $page + 1] + ($filter === 'all' ? [] : ['filter' => $filter])) }}">{{ __('Next') }}</a>@endif
    </nav>
@endif
@endsection
