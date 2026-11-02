@extends('saas.layout')

@section('title', __('Library reviews'))

@section('content')
<h1>{{ __('Library reviews') }}</h1>
<p class="saas-lead">{{ __('Library versions waiting for a decision. Approve what is correct, fits the topic and may be used under its licence; send anything else back with a reason the author can act on.') }}</p>
<p>{{ $subjects === [] ? __('You are not assigned to any subject yet, so you cannot decide any of these.') : __('Your subjects: :subjects', ['subjects' => implode(', ', $subjects)]) }}</p>

@forelse ($waiting as $version)
    <section class="saas-card" aria-label="{{ $version['title'] }}">
        <h2>{{ $version['title'] }} · {{ __('version :number', ['number' => $version['number']]) }}</h2>
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
    <p class="saas-card">{{ __('Nothing is waiting for review.') }}</p>
@endforelse
@endsection
