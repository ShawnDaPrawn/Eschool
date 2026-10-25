<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\TutorRequest;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Learning\TutorAccess;
use App\Services\Learning\TutorPhotos;
use App\Services\Learning\TutorQueries;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TutorController extends Controller
{
    private function result(TutorRequest $request, callable $action): JsonResponse
    {
        try {
            $data = $action();
            if ($request->route()->getActionMethod() !== 'summary') {
                app(TutorAccess::class)->learner($request->user());
            }

            return ResponseService::successResponse(data: array_merge($data, ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $request->user()->id]));
        } catch (AiUnavailable $error) {
            $status = $error->reason === 'tutor_turn_in_progress' ? 409 : (str_ends_with($error->reason, '_invalid') || str_ends_with($error->reason, '_required') ? 422 : 503);

            return ApiV2ExceptionRenderer::envelope($status, data: ['reason' => $error->reason]);
        }
    }

    public function configuration(TutorRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TutorQueries::class)->configuration($r->user()));
    }

    public function index(TutorRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TutorQueries::class)->sessions($r->user(), (int) $r->validated('page', 1)));
    }

    public function create(TutorRequest $r): JsonResponse
    {
        return $this->result($r, fn () => app(TutorSessions::class)->create($r->user(), (int) $r->validated('group_id'), $r->validated('source_version_ids'), $r->validated('operation_id')));
    }

    public function show(TutorRequest $r, string $session): JsonResponse
    {
        return $this->result($r, fn () => app(TutorSessions::class)->read($r->user(), $session, (int) $r->validated('page', 1)));
    }

    public function close(TutorRequest $r, string $session): JsonResponse
    {
        return $this->result($r, fn () => app(TutorSessions::class)->close($r->user(), $session));
    }

    public function send(TutorRequest $r, string $session): JsonResponse
    {
        return $this->result($r, fn () => app(TutorSessions::class)->send($r->user(), $session, $r->validated('operation_id'), $r->validated('question'),
            (string) $r->validated('working', ''), $r->validated('photo_ids', [])));
    }

    public function turn(TutorRequest $r, string $session, string $operation): JsonResponse
    {
        return $this->result($r, fn () => app(TutorSessions::class)->turnByOperation($r->user(), $session, $operation));
    }

    public function upload(TutorRequest $r, string $session): JsonResponse
    {
        return $this->result($r, fn () => app(TutorPhotos::class)->upload($r->user(), $session, $r->validated('operation_id'), $r->file('photo'),
            ['x' => (int) $r->validated('crop_x'), 'y' => (int) $r->validated('crop_y'), 'width' => (int) $r->validated('crop_width'), 'height' => (int) $r->validated('crop_height')], true));
    }

    public function preview(TutorRequest $r, string $session, string $photo): Response
    {
        $image = app(TutorPhotos::class)->preview($r->user(), $session, $photo);

        return response($image['bytes'], 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'inline; filename="working.png"', 'Cache-Control' => 'private, no-store']);
    }

    public function revoke(TutorRequest $r, string $session, string $photo): JsonResponse
    {
        return $this->result($r, function () use ($r, $session, $photo) {
            app(TutorPhotos::class)->revoke($r->user(), $session, $photo);

            return ['id' => $photo, 'revoked' => true];
        });
    }

    public function summary(TutorRequest $r, int $learner, int $subject): JsonResponse
    {
        return $this->result($r, fn () => app(TutorQueries::class)->parentSummary($r->user(), $learner, $subject));
    }
}
