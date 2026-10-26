@extends('layouts.master')
@section('title', __('School support office'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/school-assistance.css') }}">@endsection
@section('content')
@php($value = fn(string $operation, string $field, $default = '') => $draftOperation === $operation ? ($draft[$field] ?? $default) : $default)
<div class="content-wrapper m3 page-content assistance-workbench" data-assistance-private data-assistance-expires="{{ $turn['expires_at'] ?? $thread['expires_at'] ?? '' }}">
    <x-page.header :title="__('School support office')" :subtitle="__('Approved school information and explicitly assigned Parent conversations.')" />
    @include('academics.groups-status')
    @if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    @if(!$canWrite)<p role="status">{{ __('Writing is unavailable for this view. Existing documents and conversations remain available.') }}</p>@endif
    <p>{{ __('Claim a request before opening its private conversation. Academic source records are never copied into a handoff.') }}</p>
    <section class="page-section" aria-labelledby="assistance-queue"><h2 id="assistance-queue">{{ __('School support queue') }}</h2>
        @forelse($queue['items'] as $item)
        <article class="m3-card m3-card--outlined" data-assistance-thread="{{ $item['id'] }}"><div class="m3-card__content">
            <h3>{{ $item['assigned_to_reader'] ? __('Your assigned request') : __('School support request') }}</h3>
            <p>{{ __($item['state']) }} · {{ $item['created_at'] }}</p>
            @if($item['assigned_to_reader'])
                <a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.thread', ['thread' => $item['id']]) }}">{{ __('Open thread') }}</a>
            @elseif($canWrite && $item['state'] === 'open')
                <form method="POST" action="{{ route('assistance-panel.thread-write', ['thread' => $item['id'], 'operation' => 'claim']) }}" aria-label="{{ __('Claim request') }} {{ $loop->iteration }}">
                    @include('assistance.hidden', ['operation' => 'claim', 'revision' => $item['revision']])
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Claim request') }}</button>
                </form>
            @endif
        </div></article>
        @empty<p>{{ __('No current school-support requests.') }}</p>@endforelse
        <nav aria-label="{{ __('Support queue pages') }}">@foreach(['Previous requests' => -1, 'More requests' => 1] as $label => $delta)
            @if($queue['page']+$delta >= 1 && $queue['page']+$delta <= $queue['last_page'])<a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.index', ['page' => $queue['page']+$delta]) }}">{{ __($label) }}</a>@endif
        @endforeach</nav>
    </section>
    @if($thread)
    <section class="page-section" aria-labelledby="assistance-thread"><h2 id="assistance-thread">{{ __('Private school-support conversation') }}</h2>
        @foreach($thread['messages'] as $message)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content">
            <h3>{{ $message['author_kind'] === 'office' ? __('School office') : __('Parent') }}</h3>
            <p class="assistance-private-text">{{ $message['text'] }}</p>
            @if($canWrite)
            <form method="POST" action="{{ route('assistance-panel.thread-write', ['thread' => $thread['id'], 'operation' => 'translate']) }}" class="assistance-fields" aria-label="{{ __('Review message translation') }} {{ $loop->iteration }}">
                @include('assistance.hidden', ['operation' => 'translate'])
                <input type="hidden" name="message_id" value="{{ $message['id'] }}">
                <x-page.field :id="'assistance-language-'.$message['id']" name="language" type="select" :label="__('Message or answer language')" :options="array_combine($configuration['languages'], $configuration['languages'])" :value="'en'" :restore-old-input="false" required />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Review message translation') }}</button>
            </form>@endif
        </div></article>
        @endforeach
        <p>{{ __('The original message remains available. A translation is never sent as a new message.') }}</p>
        <nav aria-label="{{ __('Conversation pages') }}">@foreach(['Newer replies' => -1, 'Older replies' => 1] as $label => $delta)
            @if($thread['page']+$delta >= 1 && $thread['page']+$delta <= $thread['last_page'])<a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.thread', ['thread' => $thread['id'], 'message_page' => $thread['page']+$delta]) }}">{{ __($label) }}</a>@endif
        @endforeach</nav>
        @if($canWrite && $thread['state'] === 'open')
        <form method="POST" action="{{ route('assistance-panel.thread-write', ['thread' => $thread['id'], 'operation' => 'reply']) }}" class="assistance-fields" aria-label="{{ __('Send reply') }}">
            @include('assistance.hidden', ['operation' => 'reply'])
            <x-page.field id="assistance-reply" name="text" type="textarea" :label="__('Your reply')" :value="$value('reply','text')" :restore-old-input="false" maxlength="4000" required />
            <x-page.field id="assistance-reply-language" name="language" type="select" :label="__('Message or answer language')" :options="array_combine($configuration['languages'], $configuration['languages'])" :value="'en'" :restore-old-input="false" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Send reply') }}</button>
        </form>
        <form method="POST" action="{{ route('assistance-panel.thread-write', ['thread' => $thread['id'], 'operation' => 'reassign']) }}" class="assistance-fields" aria-label="{{ __('Confirm office handoff') }}">
            @include('assistance.hidden', ['operation' => 'reassign', 'revision' => $thread['revision']])
            <x-page.field id="assistance-assignee" name="assigned_user_id" type="select" :label="__('New office reader')" :options="array_column($staff,'name','id')" :restore-old-input="false" required />
            <x-page.field id="assistance-reason" name="reason" type="textarea" :label="__('Reason for office handoff')" :value="$value('reassign','reason')" :restore-old-input="false" maxlength="1000" required />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Confirm office handoff') }}</button>
        </form>
        <form method="POST" action="{{ route('assistance-panel.thread-write', ['thread' => $thread['id'], 'operation' => 'close']) }}" aria-label="{{ __('Close resolved thread') }}">
            @include('assistance.hidden', ['operation' => 'close', 'revision' => $thread['revision']])
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Close resolved thread') }}</button>
        </form>
        @endif
        <a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.index') }}">{{ __('Close private conversation') }}</a>
    </section>
    @endif
    @if($turn)
    <section class="page-section" aria-labelledby="assistance-review"><h2 id="assistance-review">{{ __('Review before sharing') }}</h2>
        <p>{{ __('Known names were minimised. Check the exact text for other visible names or identifiers before sharing.') }}</p>
        <p class="assistance-private-text">{{ json_decode($turn['review_text'], true, flags: JSON_THROW_ON_ERROR)['text'] }}</p>
        @if($turn['response'])<p class="assistance-private-text">{{ $turn['response']['text'] }}</p>
        @elseif($canWrite)
        <form method="POST" action="{{ route('assistance-panel.run', ['turn' => $turn['id']]) }}" class="assistance-fields" aria-label="{{ __('Request reviewed translation') }}">
            @include('assistance.hidden', ['operation' => 'run'])
            <label for="assistance-reviewed"><input id="assistance-reviewed" name="identifiers_reviewed" type="checkbox" value="1" required> {{ __('I reviewed this exact text and it contains no identifying details requiring removal') }}</label>
            <button class="m3-button m3-button--filled" type="submit" @disabled(!$configuration['translation_available'])>{{ __('Request reviewed translation') }}</button>
            @if(!$configuration['translation_available'])<p>{{ __('Translation is currently unavailable. The original message and human conversation remain available.') }}</p>@endif
        </form>
        @endif
        <a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.index') }}">{{ __('Close private review') }}</a>
    </section>
    @endif
    <section class="page-section" aria-labelledby="assistance-documents"><h2 id="assistance-documents">{{ __('Approved school documents') }}</h2>
        @foreach($documents['items'] as $item)<p><a href="{{ route('assistance-panel.index', ['document' => $item['id']]) }}">{{ $item['title'] }}</a> · {{ __($item['state']) }} · {{ __('Revision') }} {{ $item['revision'] }}</p>@endforeach
        <nav aria-label="{{ __('School document pages') }}">@foreach(['Previous documents' => -1, 'More documents' => 1] as $label => $delta)
            @if($documents['page']+$delta >= 1 && $documents['page']+$delta <= $documents['last_page'])<a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.index', ['document_page' => $documents['page']+$delta]) }}">{{ __($label) }}</a>@endif
        @endforeach</nav>
        @if($document)<h3>{{ $document['title'] }}</h3><p class="assistance-private-text">{{ $document['markdown'] }}</p><p>{{ $document['source_reference'] }}</p>@endif
    </section>
    @if($canWrite)
    <section class="page-section" aria-labelledby="assistance-publish"><h2 id="assistance-publish">{{ __('Publish approved school information') }}</h2>
        <form method="POST" action="{{ route('assistance-panel.publish') }}" class="assistance-fields" aria-label="{{ __('Publish approved school information') }}">
            @include('assistance.hidden', ['operation' => 'publish', 'revision' => $document['revision'] ?? 0])
            @if($document)<input type="hidden" name="document_key" value="{{ $document['document_key'] }}">@endif
            <x-page.field id="assistance-doc-title" name="title" :label="__('Document title')" :value="$value('publish','title',$document['title'] ?? '')" :restore-old-input="false" maxlength="191" required />
            <x-page.field id="assistance-doc-text" name="markdown" type="textarea" :label="__('Approved school text')" :value="$value('publish','markdown',$document['markdown'] ?? '')" :restore-old-input="false" maxlength="16000" required />
            <x-page.field id="assistance-doc-source" name="source_reference" :label="__('Document source or maintenance reference')" :value="$value('publish','source_reference',$document['source_reference'] ?? '')" :restore-old-input="false" maxlength="2048" required />
            <x-page.field id="assistance-doc-expiry" name="expires_on" type="date" :label="__('Optional expiry date')" :value="$value('publish','expires_on',$document['expires_on'] ?? '')" :restore-old-input="false" />
            <label for="assistance-doc-reviewed"><input id="assistance-doc-reviewed" name="identifiers_reviewed" type="checkbox" value="1" required> {{ __('I approve this exact school document and reviewed identifying details') }}</label>
            <button class="m3-button m3-button--filled" type="submit">{{ $document ? __('Publish new document version') : __('Publish approved document') }}</button>
        </form>
        @if($document)
        <form method="POST" action="{{ route('assistance-panel.withdraw', ['document' => $document['id']]) }}" aria-label="{{ __('Withdraw this document version') }}">
            @include('assistance.hidden', ['operation' => 'withdraw'])
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Withdraw this document version') }}</button>
        </form>
        <a class="m3-button m3-button--outlined" href="{{ route('assistance-panel.index') }}">{{ __('Start a separate document') }}</a>
        @endif
    </section>
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/school-assistance.js') }}" defer></script>@endsection
