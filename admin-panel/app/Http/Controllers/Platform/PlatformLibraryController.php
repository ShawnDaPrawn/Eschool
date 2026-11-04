<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\LibraryRequest;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryDrafting;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryPages;
use App\Services\Learning\Library\LibraryQuality;
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
        return view('saas.library.reviews', $pages->reviews($request->user(), (int) $request->query('page', 1), (string) $request->query('filter', 'all')));
    }

    /** Ask for AI drafts of library items (Action Plan 3.4): they arrive as drafts for review, never published. */
    public function storeDrafts(LibraryRequest $request, LibraryDrafting $drafting): RedirectResponse
    {
        $draft = $drafting->request($request->user(), $request->validated());
        if ($draft->state !== 'completed') {
            return back()->withInput()->withErrors(['drafting' => LibraryDrafting::message($draft->state === 'pending' ? 'pending' : $draft->failure_code)]);
        }
        $ids = (array) $draft->item_ids;

        return redirect()->route('saas.library.items.show', $ids[0])->with('success', trans_choice('{1} 1 AI draft is ready. Check it, change it as needed and send it for review.|[2,*] :count AI drafts are ready. Check each one, change it as needed and send it for review.', count($ids), ['count' => count($ids)]));
    }

    /** Library items learners' answers have flagged, as counts and rates only. */
    public function attention(Request $request, LibraryQuality $quality): View
    {
        return view('saas.library.attention', $quality->attention($request->user(), (int) $request->query('page', 1)));
    }

    public function redraft(LibraryRequest $request, LibraryItems $items, int $item): RedirectResponse
    {
        $version = $items->redraft($request->user(), $item, $request->validated());

        return redirect()->route('saas.library.items.show', $item)->with('success', __('Version :number started as a new draft.', ['number' => $version->number]));
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
