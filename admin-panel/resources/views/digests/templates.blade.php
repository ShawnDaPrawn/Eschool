@extends('layouts.master')
@section('title', __('Weekly digest templates'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Weekly digest templates')" :subtitle="__('Review the wording parents receive in each language. Weekly summaries use only their own child’s school records.')" />
    @if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    <p>{{ __('Unapproved languages wait for review. Other approved languages continue normally.') }}</p>
    @unless($canWrite)<p role="status">{{ __('Switch to the current school year and check module access before making changes.') }}</p>@endunless
    <section class="page-section" aria-labelledby="digest-versions"><h2 id="digest-versions">{{ __('Current language versions') }}</h2>
        @forelse($listing['items'] as $template)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content">
            <h3>{{ $template['language'] }} · {{ __('Version') }} {{ $template['version'] }}</h3>
            <p role="status">{{ $template['approved'] ? __('Approved for weekly delivery') : __('Not approved for delivery') }}</p>
            <p dir="auto">{{ $template['title'] }}</p><p dir="auto">{{ $template['body'] }}</p>
            <p dir="auto">{{ $template['notice_prompt'] }}</p>
            @foreach($template['actions'] as $kind => $text)<p dir="auto">{{ $kind }}: {{ $text }}</p>@endforeach
            @if($canWrite)<form method="POST" action="{{ route('digest-panel.review', ['template' => $template['id']]) }}" aria-label="{{ __('Review language version').' '.$template['language'].' '.$template['version'] }}">
                @include('digests.hidden')<input type="hidden" name="revision" value="{{ $template['revision'] }}">
                <input type="hidden" name="decision" value="{{ $template['approved'] ? 'revoke' : 'approve' }}">
                <button type="submit" class="m3-button m3-button--outlined">{{ $template['approved'] ? __('Withdraw language approval') : __('Approve this language version') }}</button>
            </form>@endif
        </div></article>
        @empty<p>{{ __('No language versions have been created.') }}</p>@endforelse
    </section>
    @if($canWrite)
    <section class="page-section" aria-labelledby="digest-new-version"><h2 id="digest-new-version">{{ __('Create a language version') }}</h2>
        <p>{{ __('Starting text is English. Translate and review the title, summary, notification and all home instructions for the selected language before approving it.') }}</p>
        <p>{{ __('Keep the summary short enough to read in one minute. Use the supported placeholders exactly as shown.') }}</p>
        <p>{{ implode(', ', array_map(fn ($key) => '{'.$key.'}', config('weekly_digests.tokens'))) }}</p>
        <form method="POST" action="{{ route('digest-panel.create') }}" aria-label="{{ __('Create a language version') }}">
            @include('digests.hidden')
            <x-page.field name="language" type="select" :label="__('Language')" :options="array_combine(config('weekly_digests.languages'), config('weekly_digests.languages'))" :value="$draft['language']" :restore-old-input="false" required />
            <x-page.field name="title" :label="__('Digest title')" :value="$draft['title']" :restore-old-input="false" maxlength="120" dir="auto" required />
            <x-page.field name="body" type="textarea" :label="__('Digest summary template')" :value="$draft['body']" :restore-old-input="false" maxlength="1200" dir="auto" required />
            <x-page.field name="notice_prompt" :label="__('Private notification wording')" :value="$draft['notice_prompt']" :restore-old-input="false" maxlength="120" dir="auto" required />
            @foreach(['practice' => 'Practice instruction', 'marks' => 'Released results instruction', 'attendance' => 'Attendance instruction', 'calendar' => 'School calendar instruction'] as $key => $label)
            <x-page.field :name="'actions['.$key.']'" :label="__($label)" :value="$draft['actions'][$key]" :restore-old-input="false" maxlength="160" dir="auto" required />
            @endforeach
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save language draft') }}</button>
        </form>
    </section>
    @endif
</div>
@endsection
