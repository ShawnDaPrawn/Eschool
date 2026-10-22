<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SearchRequest;
use App\Services\Panel\GlobalSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Global search: JSON for the header's search box (a few results per group),
 * a results page otherwise (more per group).
 */
class SearchController extends Controller
{
    private const BOX_LIMIT = 5;

    private const PAGE_LIMIT = 20;

    public function __invoke(SearchRequest $request, GlobalSearch $search): JsonResponse|View
    {
        $term = $request->term();

        if ($request->expectsJson()) {
            $groups = $search->search($request->user(), $term, self::BOX_LIMIT);

            // Hex-escape markup characters, so a name never arrives as live HTML.
            return response()->json([
                'error' => false,
                'query' => $term,
                'groups' => $groups,
                'search_url' => route('search', ['q' => $term]),
            ], 200, [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        return view('search.index', [
            'term' => $term,
            'groups' => $search->search($request->user(), $term, self::PAGE_LIMIT),
            'tooShort' => $term !== '' && mb_strlen($term) < GlobalSearch::MIN_LENGTH,
        ]);
    }
}
