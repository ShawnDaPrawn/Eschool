@extends('saas.layout')

@section('title', __('Safeguarding phrase lists'))

@section('content')
<h1>{{ __('Safeguarding phrase lists') }}</h1>
<p class="saas-lead">{{ __('Every learner message to the tutor is checked on the server against every list below, reviewed or still draft, before any AI call. A message that matches never reaches the AI provider: it goes to the safeguarding queue and the learner is shown where to find help. A home language is offered in the tutor only after a safeguarding professional and a fluent speaker have reviewed its list and the review is recorded here. Any change to a list makes it draft again.') }}</p>
<p class="saas-card">{{ __('The phrases are read-only here and are changed only through a reviewed code change. They are never sent to an AI provider or written to a log. Do not copy them into tickets, chats or documents.') }}</p>

@foreach ($languages as $language)
<section class="saas-card" aria-labelledby="language-{{ $language['language'] }}">
    <h2 id="language-{{ $language['language'] }}">{{ $language['name'] }}
        @if ($language['state'] === 'reviewed')
            <span class="saas-badge">{{ __('Reviewed') }}</span>
        @else
            <span class="saas-badge saas-badge--warn">{{ __('Draft') }}</span>
        @endif
    </h2>
    <p>
        @if (! $language['gates_tutor'])
            {{ __('English is the tutor\'s own language, so its review does not switch the tutor on or off. Its list is checked on every message.') }}
        @elseif ($language['offered_in_tutor'])
            {{ __('Offered in the tutor as a home language.') }}
        @elseif ($language['state'] === 'reviewed')
            {{ __('Reviewed. Not offered in the tutor until this deployment lists it in AI_TUTOR_HOME_LANGUAGES.') }}
        @else
            {{ __('Not offered in the tutor until its list is reviewed. Its list is still checked on every message.') }}
        @endif
    </p>
    @if ($language['latest'] !== null)
        <p>{{ __('Latest review: :outcome by :reviewer on :date.', ['outcome' => $language['latest']['outcome'] === 'reviewed' ? __('reviewed') : __('changes needed'), 'reviewer' => $language['latest']['reviewer'], 'date' => $language['latest']['reviewed_on']]) }}
            @unless ($language['latest']['current']) {{ __('The list has changed since then, so it is draft again.') }} @endunless
        </p>
    @else
        <p>{{ __('No review recorded yet.') }}</p>
    @endif
    <p><small>{{ __('List fingerprint: :hash', ['hash' => substr($language['sha256'], 0, 12)]) }}</small></p>

    <details>
        <summary>{{ trans_choice('{1} Read the 1 phrase|[2,*] Read the :count phrases', $language['phrase_count'], ['count' => $language['phrase_count']]) }}</summary>
        @foreach ($language['categories'] as $category => $phrases)
            <h3>{{ ['self_harm' => __('Self-harm and suicide'), 'abuse' => __('Abuse'), 'sexual_violence' => __('Sexual violence')][$category] ?? $category }}</h3>
            <ul lang="{{ $language['language'] }}">@foreach ($phrases as $phrase)<li>{{ $phrase }}</li>@endforeach</ul>
        @endforeach
    </details>

    @if ($language['history'] !== [])
    <details>
        <summary>{{ __('Review history') }}</summary>
        <div class="saas-table-wrap" role="region" aria-label="{{ __('Review history for :language', ['language' => $language['name']]) }}" tabindex="0">
            <table class="saas-table">
                <thead><tr><th scope="col">{{ __('Reviewed on') }}</th><th scope="col">{{ __('Outcome') }}</th><th scope="col">{{ __('Reviewer') }}</th><th scope="col">{{ __('Note') }}</th><th scope="col">{{ __('Recorded by') }}</th><th scope="col">{{ __('For this list') }}</th></tr></thead>
                <tbody>
                @foreach ($language['history'] as $review)
                    <tr><td>{{ $review['reviewed_on'] }}</td><td>{{ $review['outcome'] === 'reviewed' ? __('Reviewed') : __('Changes needed') }}</td><td>{{ $review['reviewer'] }}</td>
                        <td>{{ $review['note'] }}</td><td>{{ $review['recorded_by'] }} · {{ $review['recorded_at'] }}</td><td>{{ $review['current'] ? __('Yes') : __('No, an earlier list') }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </details>
    @endif

    <form method="POST" action="{{ route('saas.safeguarding-phrases.review', $language['language']) }}" aria-label="{{ __('Record a review of the :language list', ['language' => $language['name']]) }}">
        @csrf
        {{-- The list this form shows: a list changed since is refused, never approved unseen. --}}
        <input type="hidden" name="list_sha256" value="{{ $language['sha256'] }}">
        <h3>{{ __('Record a review of this list') }}</h3>
        <div class="saas-grid">
            <div class="saas-field">
                <label for="outcome-{{ $language['language'] }}">{{ __('Outcome') }}</label>
                <select id="outcome-{{ $language['language'] }}" name="outcome" required>
                    <option value="reviewed">{{ __('Reviewed: fit to use') }}</option>
                    <option value="changes_needed">{{ __('Changes needed: keep as draft') }}</option>
                </select>
            </div>
            <div class="saas-field">
                <label for="reviewer-{{ $language['language'] }}">{{ __('Reviewer (name, and role or organisation)') }}</label>
                <input id="reviewer-{{ $language['language'] }}" name="reviewer" required minlength="3" maxlength="160">
            </div>
            <div class="saas-field">
                <label for="reviewed-on-{{ $language['language'] }}">{{ __('Reviewed on') }}</label>
                <input id="reviewed-on-{{ $language['language'] }}" name="reviewed_on" type="date" required max="{{ $today }}">
            </div>
            <div class="saas-field">
                <label for="note-{{ $language['language'] }}">{{ __('Note (what was checked, by whom, and anything to change)') }}</label>
                <textarea id="note-{{ $language['language'] }}" name="note" required minlength="10" maxlength="1000" rows="3"></textarea>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the review') }}</button></div>
    </form>
</section>
@endforeach
@endsection
