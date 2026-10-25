<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Discipline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discipline\DisciplineRequest;
use App\Services\Discipline\DisciplineDetentions;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineOperations;
use App\Services\Discipline\DisciplineQueries;
use App\Services\Discipline\DisciplineRecords;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class DisciplineController extends Controller
{
    public function configuration(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineQueries::class)->configuration($r->user()));
    }

    public function framework(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineFrameworks::class)->read($r->user(), $r->year()));
    }

    public function configure(DisciplineRequest $r): JsonResponse
    {
        return $this->write($r, 'framework:'.$r->year(), fn () => app(DisciplineFrameworks::class)->save($r->user(), $r->year(), $r->validated()));
    }

    public function learners(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineQueries::class)->learners($r->user(), $r->year(), (int) $r->validated('page', 1), $r->validated('search')));
    }

    public function records(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineQueries::class)->records($r->user(), $r->year(), (int) $r->validated('page', 1), $r->validated('status')));
    }

    public function detail(DisciplineRequest $r, int $record): JsonResponse
    {
        return $this->response(app(DisciplineRecords::class)->read($r->user(), $record));
    }

    public function history(DisciplineRequest $r, int $record): JsonResponse
    {
        return $this->response(['record_id' => $record, 'versions' => app(DisciplineRecords::class)->versions($r->user(), $record)]);
    }

    public function create(DisciplineRequest $r, int $learner): JsonResponse
    {
        return $this->write($r, 'record:'.$learner.':'.$r->year(),
            fn () => app(DisciplineRecords::class)->create($r->user(), $learner, $r->year(), $r->validated()),
            fn (array $receipt) => app(DisciplineRecords::class)->authorize($r->user(), (int) $receipt['id']));
    }

    public function review(DisciplineRequest $r, int $record): JsonResponse
    {
        return $this->write($r, 'review:'.$record, fn () => app(DisciplineRecords::class)->review($r->user(), $record, $r->validated()));
    }

    public function hearing(DisciplineRequest $r, int $record): JsonResponse
    {
        return $this->write($r, 'hearing:'.$record, fn () => app(DisciplineRecords::class)->hearing($r->user(), $record, $r->validated()));
    }

    public function family(DisciplineRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(DisciplineRecords::class)->family($r->user(), $learner, $r->year(), (int) $r->validated('page', 1)));
    }

    public function acknowledge(DisciplineRequest $r, int $record): JsonResponse
    {
        return $this->write($r, 'acknowledge:'.$record, fn () => app(DisciplineRecords::class)->acknowledge($r->user(), $record, (int) $r->validated('expected_revision')));
    }

    public function detentions(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineQueries::class)->detentions($r->user(), $r->year(), (int) $r->validated('page', 1)));
    }

    public function supervisors(DisciplineRequest $r): JsonResponse
    {
        return $this->response(app(DisciplineQueries::class)->supervisors($r->user(), $r->year(), (int) $r->validated('grade_level_id'), $r->validated('on_date'), (int) $r->validated('page', 1)));
    }

    public function detentionCreate(DisciplineRequest $r): JsonResponse
    {
        return $this->write($r, 'detention:'.$r->year().':'.$r->validated('grade_level_id'),
            fn () => app(DisciplineDetentions::class)->create($r->user(), $r->year(), (int) $r->validated('grade_level_id'), $r->validated()),
            fn (array $receipt) => app(DisciplineDetentions::class)->authorize($r->user(), (int) $receipt['id']));
    }

    public function detentionDetail(DisciplineRequest $r, int $detention): JsonResponse
    {
        return $this->response(app(DisciplineDetentions::class)->read($r->user(), $detention));
    }

    public function enroll(DisciplineRequest $r, int $detention): JsonResponse
    {
        return $this->write($r, 'enroll:'.$detention, fn () => app(DisciplineDetentions::class)->enroll($r->user(), $detention, (int) $r->validated('record_id'), (int) $r->validated('expected_revision')));
    }

    public function attendance(DisciplineRequest $r, int $detention, int $entry): JsonResponse
    {
        return $this->write($r, 'attendance:'.$detention.':'.$entry,
            fn () => app(DisciplineDetentions::class)->attendance($r->user(), $detention, $entry, (int) $r->validated('expected_revision'), (int) $r->validated('expected_session_revision'), $r->validated('attendance'), $r->validated('reason')));
    }

    public function cancel(DisciplineRequest $r, int $detention): JsonResponse
    {
        return $this->write($r, 'cancel:'.$detention,
            fn () => app(DisciplineDetentions::class)->cancel($r->user(), $detention, (int) $r->validated('expected_revision'), $r->validated('reason')));
    }

    private function write(DisciplineRequest $r, string $kind, callable $write, ?callable $replay = null): JsonResponse
    {
        return $this->response(app(DisciplineOperations::class)->once($r->user(), $kind, $r->validated(), fn () => $r->checkAuthority(), $write, $replay));
    }

    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse(data: $data)->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }
}
