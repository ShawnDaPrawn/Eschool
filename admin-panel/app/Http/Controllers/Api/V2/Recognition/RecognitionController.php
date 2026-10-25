<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Recognition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recognition\RecognitionRequest;
use App\Services\Recognition\AwardNominations;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\HousePoints;
use App\Services\Recognition\RecognitionCatalog;
use App\Services\Recognition\RecognitionQueries;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class RecognitionController extends Controller
{
    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $data);
    }

    private function reference(Model $row): JsonResponse
    {
        return $this->response(['id' => (int) $row->id, 'revision' => (int) ($row->revision ?? $row->id)]);
    }

    private function module(RecognitionRequest $r, string $module): void
    {
        abort_unless($r->route('module') === $module, 404);
    }

    private function family(RecognitionRequest $r): bool
    {
        return $r->validated('audience') === 'family' || app(RecognitionQueries::class)->family($r->user());
    }

    private function year(RecognitionRequest $r): int
    {
        return (int) $r->validated('session_year_id', schoolSetting('session_year'));
    }

    public function access(RecognitionRequest $r): JsonResponse
    {
        return $this->response(app(RecognitionQueries::class)->access($r->user(), (string) $r->route('module')));
    }

    public function configuration(RecognitionRequest $r): JsonResponse
    {
        return $this->response(app(RecognitionQueries::class)->configuration($r->user(), (string) $r->route('module')));
    }

    public function learners(RecognitionRequest $r): JsonResponse
    {
        return $this->response(app(RecognitionQueries::class)->learners($r->user(), (string) $r->route('module'), (string) $r->validated('search', ''), (int) $r->validated('after', 0)));
    }

    public function counters(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'house_points');

        return $this->response(app(HousePoints::class)->counters($r->user(), $this->year($r)));
    }

    public function history(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'house_points');

        return $this->response(app(HousePoints::class)->history($r->user(), (int) $r->route('learner'), $this->year($r), $this->family($r), (int) $r->validated('before', 0)));
    }

    public function profile(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->response(app(AwardPresentations::class)->profile($r->user(), (int) $r->route('learner'), $this->family($r), (int) $r->validated('before', 0)));
    }

    public function catalogue(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');
        abort_unless($r->filled('grade_level_id'), 422);

        return $this->response(app(AwardNominations::class)->catalogue($r->user(), $this->year($r), (int) $r->validated('grade_level_id')));
    }

    public function nominations(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->response(app(RecognitionQueries::class)->nominations($r->user(), $this->year($r), $r->filled('grade_level_id') ? (int) $r->validated('grade_level_id') : null, (int) $r->validated('before', 0)));
    }

    public function configure(RecognitionRequest $r): JsonResponse
    {
        return $this->reference(app(RecognitionCatalog::class)->configure($r->user(), (string) $r->route('module'), $r->validated()));
    }

    public function catalogueWrite(RecognitionRequest $r): JsonResponse
    {
        $kind = (string) $r->route('kind');
        $module = in_array($kind, ['houses', 'point-categories', 'house-memberships'], true) ? 'house_points' : 'awards';
        $this->module($r, $module);
        $method = RecognitionQueries::CATALOGUES[$kind] ?? abort(404);
        $id = $r->route('record') === null ? null : (int) $r->route('record');
        abort_if($method === 'window' && $id !== null, 404);

        return $this->reference(app(RecognitionCatalog::class)->$method($r->user(), $r->validated(), $id));
    }

    public function issue(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'house_points');

        return $this->reference(app(HousePoints::class)->issue($r->user(), $r->validated()));
    }

    public function reverse(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'house_points');

        return $this->reference(app(HousePoints::class)->reverse($r->user(), (int) $r->route('record'), $r->validated()));
    }

    public function nominate(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->reference(app(AwardNominations::class)->nominate($r->user(), $r->validated()));
    }

    public function review(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->reference(app(AwardNominations::class)->review($r->user(), (int) $r->route('record'), $r->validated()));
    }

    public function withdraw(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->reference(app(AwardNominations::class)->withdraw($r->user(), (int) $r->route('record'), $r->validated()));
    }

    public function present(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->reference(app(AwardPresentations::class)->present($r->user(), $r->validated()));
    }

    public function outside(RecognitionRequest $r): JsonResponse
    {
        $this->module($r, 'awards');

        return $this->reference(app(AwardPresentations::class)->outside($r->user(), $r->validated()));
    }

    public function void(RecognitionRequest $r): JsonResponse
    {
        $this->module($r,'awards');

        return $this->reference(app(AwardPresentations::class)->void($r->user(),(int) $r->route('record'),$r->validated()));
    }
}
