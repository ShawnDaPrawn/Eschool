@extends('saas.layout')

@section('title', __('Needs attention'))

@section('content')
<p><a href="{{ route('saas.library.index') }}">{{ __('Content library') }}</a></p>
<h1>{{ __('Needs attention') }}</h1>
<p class="saas-lead">{{ __('Published library questions whose learners\' answers fall outside the expected range: fewer than :low% or more than :high% right. Only questions answered by at least :learners learners across at least :schools schools are counted, so no school or learner can be picked out; this page never shows a school, a learner or an answer.', ['low' => $low, 'high' => $high, 'learners' => $minimumLearners, 'schools' => $minimumSchools]) }}</p>

@if ($rows === [])
    <p class="saas-card">{{ __('No published question needs attention.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Needs attention') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Item') }}</th><th scope="col">{{ __('Topic') }}</th><th scope="col">{{ __('Right') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Signal') }}</th><th scope="col">{{ __('Next step') }}</th></tr></thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <th scope="row"><a href="{{ route('saas.library.items.show', $row['id']) }}">#{{ $row['id'] }} {{ $row['title'] }}</a>@if ($row['origin'] === 'ai_draft') <span class="saas-badge saas-badge--warn">{{ __('AI draft') }}</span>@endif</th>
                <td>{{ $row['framework'] }} · {{ $row['subject_code'] }} · {{ $row['grade'] }} · {{ $row['topic'] }}</td>
                <td>{{ $row['facility'] }}%</td>
                <td>{{ $row['learners'] }}</td>
                <td>{{ $row['signal_label'] }}</td>
                <td>
                    @if ($row['open'])
                        <a href="{{ route('saas.library.items.show', $row['id']) }}">{{ $row['open'] }}</a>
                    @else
                        <form class="saas-inline" method="POST" action="{{ route('saas.library.items.redraft', $row['id']) }}">
                            @csrf
                            <input type="hidden" name="revision" value="{{ $row['revision'] }}">
                            <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Open a new draft') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@if ($lastPage > 1)
    <nav aria-label="{{ __('Pages') }}">
        @if ($page > 1)<a href="{{ route('saas.library.attention', ['page' => $page - 1]) }}">{{ __('Previous') }}</a>@endif
        {{ __('Page :page of :last', ['page' => $page, 'last' => $lastPage]) }}
        @if ($page < $lastPage)<a href="{{ route('saas.library.attention', ['page' => $page + 1]) }}">{{ __('Next') }}</a>@endif
    </nav>
@endif
@endsection
