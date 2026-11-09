<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\RecordSafeguardingReviewRequest;
use App\Services\Learning\SafeguardingPhraseReviews;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ: each language's safeguarding phrase list, read-only, and its professional review (Action Plan 3.2). */
class PlatformSafeguardingPhrasesController extends Controller
{
    public function __construct(private readonly SafeguardingPhraseReviews $reviews) {}

    public function index(): View
    {
        return view('saas.safeguarding-phrases', ['languages' => $this->reviews->overview(), 'today' => now()->toDateString()]);
    }

    public function review(RecordSafeguardingReviewRequest $request, string $language): RedirectResponse
    {
        $this->reviews->record($request->user(), $language, $request->validated());

        return redirect()->route('saas.safeguarding-phrases')->with('success', __('Review recorded.'));
    }
}
