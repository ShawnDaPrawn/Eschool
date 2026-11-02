<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\LibraryRequest;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryPages;
use App\Services\Learning\Library\LibraryReviewers;
use App\Services\Learning\Library\LibrarySchools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the platform content library and its review (Action Plan 3.3, SA.16). */
class PlatformLibraryController extends Controller
{
    public function index(Request $request, LibraryPages $pages): View
    {
        return view('saas.library.index', $pages->index($request->user(), (int) $request->query('page', 1)));
    }

    public function storeFramework(LibraryRequest $request, LibraryCatalogue $catalogue): RedirectResponse
    {
        $framework = $catalogue->createFramework($request->user(), $request->validated());

        return redirect()->route('saas.library.frameworks.show', $framework->id)->with('success', __('Framework :code added.', ['code' => $framework->code]));
    }

    public function framework(Request $request, LibraryPages $pages, int $framework): View
    {
        return view('saas.library.framework', $pages->framework($request->user(), $framework));
    }

    public function storeSource(LibraryRequest $request, LibraryCatalogue $catalogue): RedirectResponse
    {
        $catalogue->createSource($request->user(), $request->validated());

        return redirect()->route('saas.library.index')->with('success', __('Source added.'));
    }

    public function storeTopic(LibraryRequest $request, LibraryCatalogue $catalogue, int $framework): RedirectResponse
    {
        $catalogue->createTopic($request->user(), $framework, $request->validated());

        return redirect()->route('saas.library.frameworks.show', $framework)->with('success', __('Topic added.'));
    }

    public function prerequisites(LibraryRequest $request, LibraryCatalogue $catalogue, int $topic): RedirectResponse
    {
        $row = $catalogue->setPrerequisites($request->user(), $topic, $request->validated());

        return redirect()->route('saas.library.frameworks.show', $row->library_framework_id)->with('success', __('Prerequisites saved.'));
    }

    public function storeItem(LibraryRequest $request, LibraryItems $items): RedirectResponse
    {
        $item = $items->create($request->user(), $request->validated());

        return redirect()->route('saas.library.items.show', $item->id)->with('success', __('Draft saved.'));
    }

    public function item(Request $request, LibraryPages $pages, int $item): View
    {
        return view('saas.library.item', $pages->item($request->user(), $item));
    }

    public function draft(LibraryRequest $request, LibraryItems $items, int $item): RedirectResponse
    {
        $items->saveDraft($request->user(), $item, $request->validated());

        return redirect()->route('saas.library.items.show', $item)->with('success', __('Draft saved.'));
    }

    public function submit(LibraryRequest $request, LibraryItems $items, int $item): RedirectResponse
    {
        $items->submit($request->user(), $item, $request->validated());

        return redirect()->route('saas.library.items.show', $item)->with('success', __('Sent for review.'));
    }

    public function publish(LibraryRequest $request, LibraryItems $items, int $item): RedirectResponse
    {
        $version = $items->publish($request->user(), $item, $request->validated());

        return redirect()->route('saas.library.items.show', $item)->with('success', __('Version :number published.', ['number' => $version->number]));
    }

    public function reviews(Request $request, LibraryPages $pages): View
    {
        return view('saas.library.reviews', $pages->reviews($request->user()));
    }

    public function review(LibraryRequest $request, LibraryItems $items, int $version): RedirectResponse
    {
        $review = $items->review($request->user(), $version, $request->validated());

        return redirect()->route('saas.library.reviews')->with('success', $review->decision === 'approved' ? __('Approved.') : __('Sent back with your reason.'));
    }

    public function storeReviewer(LibraryRequest $request, LibraryReviewers $reviewers): RedirectResponse
    {
        $reviewers->assign($request->user(), $request->validated());

        return redirect()->route('saas.library.index')->with('success', __('Reviewer assigned.'));
    }

    public function removeReviewer(LibraryRequest $request, LibraryReviewers $reviewers, int $assignment): RedirectResponse
    {
        $reviewers->remove($request->user(), $assignment);

        return redirect()->route('saas.library.index')->with('success', __('Reviewer removed from that subject.'));
    }

    public function switch(LibraryRequest $request, LibrarySchools $schools, int $school): RedirectResponse
    {
        $schools->switch($request->user(), $school, $request->validated());

        return redirect()->route('saas.library.index')->with('success', __('Library switch saved.'));
    }
}
