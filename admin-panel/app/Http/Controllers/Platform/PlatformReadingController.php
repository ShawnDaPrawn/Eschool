<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\ReadingRequest;
use App\Services\Reading\ReadingCatalogue;
use App\Services\Reading\ReadingPages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the reading library catalogue (Action Plan 4.4). */
class PlatformReadingController extends Controller
{
    public function index(Request $request, ReadingPages $pages): View
    {
        return view('saas.reading.index', $pages->index($request->user()));
    }

    public function storeBook(ReadingRequest $request, ReadingCatalogue $catalogue): RedirectResponse
    {
        $book = $catalogue->createBook($request->user(), $request->validated());

        return redirect()->route('saas.reading.books.show', $book->id)->with('success', __('Book added. Record a rights decision before learners can read it.'));
    }

    public function book(Request $request, ReadingPages $pages, int $book): View
    {
        return view('saas.reading.book', $pages->book($request->user(), $book));
    }

    public function updateBook(ReadingRequest $request, ReadingCatalogue $catalogue, int $book): RedirectResponse
    {
        $catalogue->updateBook($request->user(), $book, $request->validated());

        return redirect()->route('saas.reading.books.show', $book)->with('success', __('Book details saved.'));
    }

    public function rights(ReadingRequest $request, ReadingCatalogue $catalogue, int $book): RedirectResponse
    {
        $catalogue->decideRights($request->user(), $book, $request->validated());

        return redirect()->route('saas.reading.books.show', $book)->with('success', __('Rights decision recorded.'));
    }

    public function fetch(ReadingRequest $request, ReadingCatalogue $catalogue, int $book): RedirectResponse
    {
        $text = $catalogue->fetchNow($request->user(), $book);

        return redirect()->route('saas.reading.books.show', $book)->with('success', __('Text fetched: :pages pages.', ['pages' => $text->page_count]));
    }

    public function storeQuestion(ReadingRequest $request, ReadingCatalogue $catalogue, int $book): RedirectResponse
    {
        $catalogue->addQuestion($request->user(), $book, $request->validated());

        return redirect()->route('saas.reading.books.show', $book)->with('success', __('Question added.'));
    }

    public function retireQuestion(ReadingRequest $request, ReadingCatalogue $catalogue, int $question): RedirectResponse
    {
        $row = $catalogue->retireQuestion($request->user(), $question);

        return redirect()->route('saas.reading.books.show', $row->reading_book_id)->with('success', __('Question retired.'));
    }

    public function storeShelf(ReadingRequest $request, ReadingCatalogue $catalogue): RedirectResponse
    {
        $shelf = $catalogue->createShelf($request->user(), $request->validated());

        return redirect()->route('saas.reading.shelves.show', $shelf->id)->with('success', __('Shelf added.'));
    }

    public function shelf(Request $request, ReadingPages $pages, int $shelf): View
    {
        return view('saas.reading.shelf', $pages->shelf($request->user(), $shelf));
    }

    public function updateShelf(ReadingRequest $request, ReadingCatalogue $catalogue, int $shelf): RedirectResponse
    {
        $catalogue->updateShelf($request->user(), $shelf, $request->validated());

        return redirect()->route('saas.reading.shelves.show', $shelf)->with('success', __('Shelf saved.'));
    }

    public function shelfBooks(ReadingRequest $request, ReadingCatalogue $catalogue, int $shelf): RedirectResponse
    {
        $catalogue->setShelfBooks($request->user(), $shelf, $request->validated());

        return redirect()->route('saas.reading.shelves.show', $shelf)->with('success', __('Shelf books saved.'));
    }
}
