@extends('saas.layout')

@section('title', $framework['code'])

@section('content')
<p><a href="{{ route('saas.library.index') }}">{{ __('Content library') }}</a></p>
<h1>{{ $framework['code'] }} · {{ $framework['name'] }}</h1>
<p class="saas-lead">{{ __('Topics by subject code and grade, the prerequisites between them, and each topic\'s items. A prerequisite that would make a topic its own prerequisite is refused.') }}</p>

<h2>{{ __('Topics') }}</h2>
@if ($topics === [])
    <p class="saas-card">{{ __('No topics yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Topics') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Topic') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Grade') }}</th><th scope="col">{{ __('Prerequisites') }}</th><th scope="col">{{ __('Items') }}</th></tr></thead>
        <tbody>
        @foreach ($topics as $topic)
            <tr>
                <th scope="row">{{ $topic['name'] }}</th>
                <td>{{ $topic['subject_code'] }}</td>
                <td>{{ $topic['grade'] }}</td>
                <td>
                    {{ $topic['prerequisites'] === [] ? __('None') : implode(', ', $topic['prerequisites']) }}
                    @if ($topic['siblings'] !== [])
                    <details>
                        <summary>{{ __('Change') }}</summary>
                        <form method="POST" action="{{ route('saas.library.topics.prerequisites', $topic['id']) }}" aria-label="{{ __('Prerequisites of :topic', ['topic' => $topic['name']]) }}">
                            @csrf @method('PUT')
                            <label for="prerequisites-{{ $topic['id'] }}">{{ __('Learners need these first') }}</label>
                            <select id="prerequisites-{{ $topic['id'] }}" name="prerequisite_ids[]" multiple size="5">
                                @foreach ($topic['siblings'] as $id => $name)<option value="{{ $id }}" @selected(in_array($id, $topic['prerequisite_ids'], true))>{{ $name }}</option>@endforeach
                            </select>
                            <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Save prerequisites') }}</button>
                        </form>
                    </details>
                    @endif
                </td>
                <td>
                    @if ($topic['items'] === [])
                        {{ __('None') }}
                    @else
                        <ul>
                            @foreach ($topic['items'] as $item)
                                <li><a href="{{ route('saas.library.items.show', $item['id']) }}">#{{ $item['id'] }} {{ $item['title'] }}</a> · {{ $item['kind'] }} · {{ $item['state'] }}@if ($item['published']) · <span class="saas-badge">{{ __('Live') }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="topic">{{ __('Add a topic') }}</h2>
@if ($subjectChoices === [])
    <p class="saas-card">{{ __('The subject standards catalogue has no published :code subjects yet, so no topic can be added.', ['code' => $framework['code']]) }}</p>
@else
<form method="POST" action="{{ route('saas.library.topics.store', $framework['id']) }}" class="saas-card" aria-label="{{ __('Add a topic') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field">
            <label for="topic-subject">{{ __('Subject') }}</label>
            <select id="topic-subject" name="subject_code" required>
                @foreach ($subjectChoices as $code => $label)<option value="{{ $code }}" @selected(old('subject_code') === $code)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="topic-grade">{{ __('Grade') }}</label>
            <select id="topic-grade" name="grade_level_id" required>
                @foreach ($gradeChoices as $id => $name)<option value="{{ $id }}" @selected((string) old('grade_level_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="topic-name">{{ __('Topic name') }}</label>
            <input id="topic-name" name="name" required minlength="2" maxlength="191" value="{{ old('name') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the topic') }}</button></div>
</form>
@endif

<h2 id="item">{{ __('Add an item') }}</h2>
@if ($topicChoices === [] || $sourceChoices === [])
    <p class="saas-card">{{ __('Add a topic here and a source on the library page first.') }}</p>
@else
<form method="POST" action="{{ route('saas.library.items.store') }}" class="saas-card" aria-label="{{ __('Add an item') }}">
    @csrf
    <p>{{ __('An item starts as a draft. Send it for review from its page; a reviewer for its subject approves it, and then it can be published.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="item-topic">{{ __('Topic') }}</label>
            <select id="item-topic" name="library_topic_id" required>
                @foreach ($topicChoices as $id => $label)<option value="{{ $id }}" @selected((string) old('library_topic_id') === (string) $id)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="item-kind">{{ __('Kind') }}</label>
            <select id="item-kind" name="kind" required>
                @foreach ($kinds as $value => $label)<option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="item-title">{{ __('Title') }}</label>
            <input id="item-title" name="title" required minlength="3" maxlength="191" value="{{ old('title') }}">
        </div>
        <div class="saas-field">
            <label for="item-source">{{ __('Source and licence') }}</label>
            <select id="item-source" name="library_source_id" required>
                @foreach ($sourceChoices as $id => $label)<option value="{{ $id }}" @selected((string) old('library_source_id') === (string) $id)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
    @include('saas.library.content-fields', ['kind' => null, 'fields' => [], 'prefix' => 'new'])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the draft') }}</button></div>
</form>
@endif
@endsection
