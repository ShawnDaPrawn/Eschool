<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiReviewPanelRequest;
use App\Services\Ai\Quarantine\AiFlagReviews;
use App\Services\Ai\Quarantine\AiReviewPanelScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiReviewPanelController extends Controller
{
    public function media(AiReviewPanelRequest $request, int $flag, string $reference): Response
    {
        $media = app(AiFlagReviews::class)->media($request->user(), $flag, $reference);

        return response($media['bytes'], 200, ['Content-Type' => $media['mime'], 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }

    public function index(AiReviewPanelRequest $request): Response
    {
        return $this->page($request);
    }

    public function show(AiReviewPanelRequest $request, int $flag): Response
    {
        return $this->page($request, $flag);
    }

    private function page(AiReviewPanelRequest $request, ?int $flag = null, array $messages = [], int $status = 200): Response
    {
        $listing = app(AiFlagReviews::class)->listing($request->user(), max(1, min(10000, $request->integer('page', 1))));
        $detail = $flag === null ? null : app(AiFlagReviews::class)->show($request->user(), $flag);
        $schoolId = app(CurrentSchool::class)->id();
        $accountId = (int) $request->user()->id;
        $operationId = Str::isUuid($request->input('operation_id', '')) ? $request->input('operation_id') : (string) Str::uuid();
        $reason = $request->isMethodSafe() || ! is_string($request->input('reason')) ? '' : mb_substr($request->input('reason'), 0, 2000);

        return response()->view('ai.flag-review', compact('listing', 'detail', 'schoolId', 'accountId', 'operationId', 'reason', 'messages'), $status);
    }

    public function review(AiReviewPanelRequest $request, int $flag): RedirectResponse|Response
    {
        try {
            app(AiFlagReviews::class)->review($request->user(), $flag, $request->validated());

            return redirect()->route('ai-review.show', ['flag' => $flag]);
        } catch (ValidationException $error) {
            return $this->failure($request, $error->errors(), 422);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return $this->failure($request, ['Refresh the current review before continuing.'], $error->getStatusCode());
        }
    }

    public function failure(AiReviewPanelRequest $request, array $messages, int $status): Response
    {
        app(AiReviewPanelScope::class)->authorize($request);

        return $this->page($request, (int) $request->route('flag'), $messages, $status);
    }
}
