<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\MessageRequest;
use App\Http\Resources\Communication\MessageResource;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\CommunicationOperations;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\Communication\MessageReport;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    private function response(MessageRequest $r, array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new MessageResource($data))->resolve($r));
    }

    public function configuration(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->configuration($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id'), $r->supportsActivityGroups()));
    }

    public function contacts(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->contacts($r->user(), (int) $r->validated('session_year_id'), (int) $r->validated('page', 1)));
    }

    public function audiences(MessageRequest $r): JsonResponse
    {
        return $this->response($r, ['items' => app(CommunicationAudiences::class)->listing($r->user(), (int) $r->validated('session_year_id'))]);
    }

    public function audienceCreate(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(CommunicationAudiences::class)->save($r->user(), null, $r->validated()));
    }

    public function audienceSave(MessageRequest $r, int $audience): JsonResponse
    {
        return $this->response($r, app(CommunicationAudiences::class)->save($r->user(), $audience, $r->validated()));
    }

    public function rules(MessageRequest $r): JsonResponse
    {
        return $this->response($r, ['items' => app(CommunicationRules::class)->listing($r->user())]);
    }

    public function ruleSave(MessageRequest $r, int $role): JsonResponse
    {
        return $this->response($r, app(CommunicationOperations::class)->once($r->user(), 'rule', ['role' => $role] + $r->validated(),
            fn () => abort_unless(app(CommunicationRules::class)->manages($r->user()), 403), fn () => app(CommunicationRules::class)->save($r->user(), $role, $r->validated())));
    }

    public function drafts(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->drafts($r->user(), (int) $r->validated('page', 1), $r->supportsActivityGroups()));
    }

    public function editor(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->editor($r->user(), $message, $r->supportsActivityGroups()));
    }

    public function create(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->save($r->user(), null, $r->validated()));
    }

    public function save(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->save($r->user(), $message, $r->validated()));
    }

    public function preview(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->preview($r->user(), $message));
    }

    public function submit(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->submit($r->user(), $message, $r->validated()));
    }

    public function decision(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->decide($r->user(), $message, $r->validated()));
    }

    public function withdraw(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageFlow::class)->withdraw($r->user(), $message, $r->validated()));
    }

    public function report(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageReport::class)->for($r->user(), $message, (int) $r->validated('page', 1)));
    }

    public function inbox(MessageRequest $r): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->inbox($r->user(), (int) $r->validated('page', 1)));
    }

    public function reader(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->reader($r->user(), $message));
    }

    public function read(MessageRequest $r, int $message): JsonResponse
    {
        return $this->response($r, app(MessageQueries::class)->read($r->user(), $message));
    }
}
