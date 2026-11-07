@extends('layouts.master')
@section('title', __('Reading library'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Reading library')" :subtitle="__('Books your learners can read in the app: classic stories that are free to read in South Africa, chosen by NavigateEd and placed on shelves by grade. You can hide a shelf or a book from your learners. Learners see only their own reading.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="reading-shelves">
        <h2 id="reading-shelves">{{ __('Shelves and books') }}</h2>
        @if ($shelves === [])
            <x-page.empty-state :title="__('No shelves yet')" :message="__('NavigateEd has not put any shelves in the reading library yet.')" />
        @endif
        @foreach ($shelves as $shelf)
            <div class="m3-card m3-card--outlined page-table">
                <div class="page-table__scroll" role="region" aria-label="{{ $shelf['name'] }}" tabindex="0">
                <table class="page-table__table">
                    <caption>{{ $shelf['name'] }} · {{ $shelf['grades'] }}@if ($shelf['hidden']) · {{ __('Hidden from your learners') }}@endif</caption>
                    <thead><tr><th scope="col">{{ __('Book') }}</th><th scope="col">{{ __('Author') }}</th><th scope="col">{{ __('Reading level') }}</th><th scope="col">{{ __('Your learners') }}</th></tr></thead>
                    <tbody>
                    @forelse ($shelf['books'] as $book)
                        <tr>
                            <th scope="row">{{ $book['title'] }}</th>
                            <td>{{ $book['author'] }}</td>
                            <td>{{ __($book['level']) }}</td>
                            <td>
                                <form method="POST" action="{{ route('reading-library.hidden') }}">
                                    @csrf
                                    <input type="hidden" name="kind" value="book">
                                    <input type="hidden" name="item_id" value="{{ $book['id'] }}">
                                    <input type="hidden" name="hidden" value="{{ $book['hidden'] ? 0 : 1 }}">
                                    {{ $book['hidden'] ? __('Hidden') : __('Shown') }}
                                    <button type="submit" class="m3-button m3-button--text" aria-label="{{ ($book['hidden'] ? __('Show') : __('Hide')).' '.$book['title'] }}">{{ $book['hidden'] ? __('Show') : __('Hide') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('No books on this shelf can be read yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <form method="POST" action="{{ route('reading-library.hidden') }}">
                @csrf
                <input type="hidden" name="kind" value="shelf">
                <input type="hidden" name="item_id" value="{{ $shelf['id'] }}">
                <input type="hidden" name="hidden" value="{{ $shelf['hidden'] ? 0 : 1 }}">
                <button type="submit" class="m3-button m3-button--outlined">{{ $shelf['hidden'] ? __('Show this shelf to your learners') : __('Hide this shelf from your learners') }}</button>
            </form>
        @endforeach
    </section>

    <section class="page-section" aria-labelledby="reading-badges">
        <h2 id="reading-badges">{{ __('Reading badges') }}</h2>
        <p>{{ __('Badges are given through Awards, on the learner\'s own profile. They celebrate a learner\'s own reading and never compare learners.') }}</p>
        @unless ($awardsOn)<p role="status">{{ __('Switch on the Awards module for badges to be given.') }}</p>@endunless
        <form method="POST" action="{{ route('reading-library.settings') }}" class="m3-card m3-card--outlined" aria-label="{{ __('Reading badges') }}">
            <div class="m3-card__content">
                @csrf
                @method('PUT')
                <input type="hidden" name="revision" value="{{ $settings['revision'] }}">
                <input type="hidden" name="finished_badge" value="0">
                <label for="reading-finished"><input id="reading-finished" type="checkbox" name="finished_badge" value="1" @checked($settings['finished_badge'])> {{ __('A badge for finishing a book') }}</label>
                <input type="hidden" name="days_badge" value="0">
                <label for="reading-days"><input id="reading-days" type="checkbox" name="days_badge" value="1" @checked($settings['days_badge'])> {{ __('A badge for reading on several days in one week') }}</label>
                <x-page.field id="reading-days-required" name="days_required" type="number" :label="__('Different reading days needed in a week')" :value="$settings['days_required']" :restore-old-input="false" min="2" max="7" required />
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save reading badges') }}</button>
            </div>
        </form>
    </section>
</div>
@endsection
