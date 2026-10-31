<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Services\Platform\Modules\AccessReview;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Who can see what: one person's or one role's access, read-only (Action Plan 1.15). */
class AccessReviewController extends Controller
{
    public function __invoke(Request $request, AccessReview $review): Response
    {
        $data = $request->validate(['person' => ['nullable', 'integer', 'min:1'], 'role' => ['nullable', 'integer', 'min:1']]);
        $choices = $review->choices($request->user());
        $result = match (true) {
            isset($data['person']) => $review->person($request->user(), (int) $data['person']),
            isset($data['role']) => $review->role($request->user(), (int) $data['role']),
            default => null,
        };

        return response()->view('people.access-review', ['choices' => $choices, 'result' => $result, 'selected' => $data])
            ->header('Cache-Control', 'private, no-store');
    }
}
