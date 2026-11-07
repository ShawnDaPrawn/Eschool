@extends('saas.layout')

@section('title', $shelf['name'])

@section('content')
<p><a href="{{ route('saas.reading.index') }}">{{ __('Reading library') }}</a></p>
<h1>{{ $shelf['name'] }}</h1>
<p class="saas-lead">{{ $shelf['grades'] }} · {{ $shelf['active'] ? __('Shown') : __('Put away') }}@if ($shelf['description']) · {{ $shelf['description'] }}@endif</p>

<h2>{{ __('Books on this shelf') }}</h2>
@if ($books === [])
    <p class="saas-card">{{ __('No books on this shelf yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Books on this shelf') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('No.') }}</th><th scope="col">{{ __('Title') }}</th><th scope="col">{{ __('Author') }}</th><th scope="col">{{ __('Rights') }}</th></tr></thead>
        <tbody>
        @foreach ($books as $book)
            <tr>
                <td>{{ $book['id'] }}</td>
                <th scope="row"><a href="{{ route('saas.reading.books.show', $book['id']) }}">{{ $book['title'] }}</a></th>
                <td>{{ $book['author'] }}</td>
                <td><span class="saas-badge {{ $book['cleared'] ? '' : 'saas-badge--warn' }}">{{ __($book['rights']) }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<p>{{ __('Only books cleared as public domain in South Africa reach learners, even when they are on a shelf.') }}</p>
<form method="POST" action="{{ route('saas.reading.shelves.books', $shelf['id']) }}" class="saas-card" aria-label="{{ __('Choose the books') }}">
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $shelf['revision'] }}">
    <div class="saas-field">
        <label for="shelf-books">{{ __('Book numbers (No.) in order, separated by commas') }}</label>
        <input id="shelf-books" name="book_list" maxlength="2000" value="{{ old('book_list', $bookList) }}">
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the books') }}</button></div>
</form>

<h2>{{ __('Shelf details') }}</h2>
<form method="POST" action="{{ route('saas.reading.shelves.update', $shelf['id']) }}" class="saas-card" aria-label="{{ __('Shelf details') }}">
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $shelf['revision'] }}">
    @include('saas.reading.shelf-fields', ['shelf' => $shelf])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the shelf') }}</button></div>
</form>
@endsection
