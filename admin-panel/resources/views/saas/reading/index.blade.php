@extends('saas.layout')

@section('title', __('Reading library'))

@section('content')
<h1>{{ __('Reading library') }}</h1>
<p class="saas-lead">{{ __('Public-domain books for learners to read, kept by Navigate Ed. A book reaches learners only once a rights decision says it is public domain in South Africa, and only on a shelf for their grade. The server fetches each text once from the configured mirror, removes the Project Gutenberg licence and name, and serves it from its own private copy. Schools can hide shelves and books, never change them.') }}</p>
@unless ($mirror)
    <p class="saas-card" role="status">{{ __('No mirror is configured (READING_MIRROR_URL), so no text can be fetched yet.') }}</p>
@endunless

<h2>{{ __('Books') }}</h2>
@if ($books === [])
    <p class="saas-card">{{ __('No books yet. Add one below.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Books') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('No.') }}</th><th scope="col">{{ __('Title') }}</th><th scope="col">{{ __('Author') }}</th><th scope="col">{{ __('Gutenberg number') }}</th><th scope="col">{{ __('Level') }}</th><th scope="col">{{ __('Rights') }}</th><th scope="col">{{ __('Text') }}</th></tr></thead>
        <tbody>
        @foreach ($books as $book)
            <tr>
                <td>{{ $book['id'] }}</td>
                <th scope="row"><a href="{{ route('saas.reading.books.show', $book['id']) }}">{{ $book['title'] }}</a></th>
                <td>{{ $book['author'] }}</td>
                <td>{{ $book['gutenberg_id'] }}</td>
                <td>{{ __($book['level']) }}</td>
                <td><span class="saas-badge {{ $book['cleared'] ? '' : 'saas-badge--warn' }}">{{ __($book['rights']) }}</span></td>
                <td>{{ __($book['text']) }}@if ($book['pages']) · {{ trans_choice(':count page|:count pages', $book['pages'], ['count' => $book['pages']]) }}@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.reading.books.store') }}" class="saas-card" aria-label="{{ __('Add a book') }}">
    @csrf
    <h3>{{ __('Add a book') }}</h3>
    <p>{{ __('Use the details of the Project Gutenberg record (as Gutendex lists them). The author\'s death year decides South African copyright.') }}</p>
    @include('saas.reading.book-fields', ['book' => null])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the book') }}</button></div>
</form>

<h2>{{ __('Shelves') }}</h2>
@if ($shelves !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Shelves') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Shelf') }}</th><th scope="col">{{ __('Grades') }}</th><th scope="col">{{ __('Books') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
        <tbody>
        @foreach ($shelves as $shelf)
            <tr>
                <th scope="row"><a href="{{ route('saas.reading.shelves.show', $shelf['id']) }}">{{ $shelf['name'] }}</a></th>
                <td>{{ $shelf['grades'] }}</td>
                <td>{{ $shelf['books'] }}</td>
                <td>{{ $shelf['active'] ? __('Shown') : __('Put away') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.reading.shelves.store') }}" class="saas-card" aria-label="{{ __('Add a shelf') }}">
    @csrf
    <h3>{{ __('Add a shelf') }}</h3>
    @include('saas.reading.shelf-fields', ['shelf' => null])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the shelf') }}</button></div>
</form>
@endsection
