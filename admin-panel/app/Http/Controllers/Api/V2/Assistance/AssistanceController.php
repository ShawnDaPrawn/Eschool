<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Assistance;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assistance\AssistanceRequest;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Assistance\AssistanceAccess;
use App\Services\Assistance\AssistanceInference;
use App\Services\Assistance\AssistanceQueries;
use App\Services\Assistance\KnowledgeDocuments;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\JsonResponse;

class AssistanceController extends Controller
{
    public function read(AssistanceRequest $r): JsonResponse
    {
        $learner = (int) $r->route('learner');
        $actor = $r->user();
        $page = (int) $r->validated('page', 1);
        $queries = app(AssistanceQueries::class);

        return $this->result($r, fn () => match ($r->action()) {
            'configuration' => $queries->configuration($actor),
            'documents' => app(KnowledgeDocuments::class)->familyList($actor, $learner, $page),
            'document' => $queries->document($actor, (string) $r->route('document'), $learner),
            'threads' => app(SchoolSupportThreads::class)->familyList($actor, $learner, $page),
            'thread.read' => app(SchoolSupportThreads::class)->read($actor, (string) $r->route('thread'), $r->validated('persona') === 'office', $page),
            'turn.read' => app(AssistanceInference::class)->view($actor, (string) $r->route('turn')),
            'office.queue' => app(SchoolSupportThreads::class)->officeQueue($actor, $page),
            'office.documents' => $queries->officeDocuments($actor, $page),
            'office.document' => $queries->document($actor, (string) $r->route('document')),
            'office.staff' => $queries->officeStaff($actor),
        });
    }

    public function write(AssistanceRequest $r): JsonResponse
    {
        $learner = (int) $r->route('learner');
        $actor = $r->user();
        $input = $r->validated();
        $threads = app(SchoolSupportThreads::class);
        $id = (string) $r->route('thread');

        return $this->result($r, fn () => match ($r->action()) {
            'thread.open' => $threads->open($actor, $learner, $input),
            'thread.reply' => $threads->reply($actor, $id, $input, $r->validated('persona') === 'office'),
            'thread.close' => $threads->close($actor, $id, $input, $r->validated('persona') === 'office'),
            'office.claim' => $threads->claim($actor, $id, $input),
            'office.reassign' => $threads->reassign($actor, $id, $input),
            'office.publish' => app(KnowledgeDocuments::class)->publish($actor, $input),
            'office.withdraw' => app(KnowledgeDocuments::class)->withdraw($actor, (string) $r->route('document'), $input),
            'answer.prepare' => app(AssistanceInference::class)->prepare($actor, $learner, 'answer', $input),
            'translation.prepare', 'office.translation' => app(AssistanceInference::class)->prepare($actor, $learner, 'translation', $input, $r->action() === 'office.translation'),
            'turn.run' => app(AssistanceInference::class)->run($actor, (string) $r->route('turn'), $r->boolean('identifiers_reviewed')),
        });
    }

    private function result(AssistanceRequest $r, callable $action): JsonResponse
    {
        try {
            $data = $action();
            app(AssistanceAccess::class)->actor($r->user());

            return response()->json(['error' => false, 'message' => 'OK', 'data' => $data + ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $r->user()->id], 'code' => 200]);
        } catch (AiUnavailable $error) {
            $status = in_array($error->reason, ['school_budget_exceeded', 'actor_budget_exceeded', 'learner_budget_exceeded'], true) ? 429 : 503;

            return ApiV2ExceptionRenderer::envelope($status, data: ['reason' => $error->reason]);
        }
    }
}
