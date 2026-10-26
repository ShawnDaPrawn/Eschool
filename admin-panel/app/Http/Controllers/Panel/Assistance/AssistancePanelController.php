<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Assistance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assistance\AssistancePanelRequest;
use App\Models\Assistance\KnowledgeDocument;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Assistance\AssistanceInference;
use App\Services\Assistance\AssistanceQueries;
use App\Services\Assistance\KnowledgeDocuments;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Panel\SessionYearContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AssistancePanelController extends Controller
{
    private function model(AssistancePanelRequest $r, array $messages = []): array
    {
        $q = app(AssistanceQueries::class);
        $actor = $r->user();
        $configuration = $q->configuration($actor);
        abort_unless($configuration['can_office'], 403);
        $thread = $r->route('thread') !== null && $r->route('operation') !== 'claim'
            ? app(SchoolSupportThreads::class)->read($actor, (string) $r->route('thread'), true, (int) $r->input('message_page', 1)) : null;
        $docId = $r->route('document') ?? ($r->isMethodSafe() ? $r->input('document') : null);
        if ($docId === null && ! $r->isMethodSafe() && $r->route('operation') === 'publish'
            && is_string($r->input('document_key')) && Str::isUuid($r->input('document_key'))) {
            $docId = KnowledgeDocument::where('document_key', $r->input('document_key'))->latest('revision')->value('id');
        }
        $document = $docId === null ? null : $q->document($actor, $docId);
        $turnId = $r->route('turn') ?? ($r->isMethodSafe() ? $r->input('turn') : null);
        $turn = $turnId === null ? null : app(AssistanceInference::class)->view($actor, $turnId);
        $canWrite = $configuration['writable'] && ! app(SessionYearContext::class)->isReadOnly();
        $draft = [];
        if (! $r->isMethodSafe()) {
            foreach (['text' => 4000, 'reason' => 1000, 'title' => 191, 'markdown' => 16000, 'source_reference' => 2048, 'expires_on' => 10] as $field => $limit) {
                if (is_string($r->input($field))) {
                    $draft[$field] = mb_substr($r->input($field), 0, $limit);
                }
            }
        }

        return compact('configuration', 'thread', 'document', 'turn', 'canWrite', 'draft', 'messages') + [
            'documents' => $q->officeDocuments($actor, (int) $r->input('document_page', 1)),
            'queue' => app(SchoolSupportThreads::class)->officeQueue($actor, (int) $r->input('page', 1)),
            'staff' => $thread === null ? [] : $q->officeStaff($actor)['staff'],
            'draftOperation' => $r->isMethodSafe() ? null : $r->route('operation'),
            'operationId' => is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid(),
        ];
    }

    public function index(AssistancePanelRequest $r): Response
    {
        return response()->view('assistance.office', $this->model($r));
    }

    public function failure(AssistancePanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('assistance.office', $this->model($r, $messages), $status);
    }

    public function write(AssistancePanelRequest $r): Response|RedirectResponse
    {
        $op = $r->route('operation');
        $d = $r->validated();
        unset($d['expected_account_id'], $d['expected_school_id']);
        $s = app(SchoolSupportThreads::class);
        $actor = $r->user();
        $thread = (string) $r->route('thread');
        try {
            $row = match ($op) {
                'claim' => $s->claim($actor, $thread, $d),
                'reply' => $s->reply($actor, $thread, $d, true),
                'close' => $s->close($actor, $thread, $d, true),
                'reassign' => $s->reassign($actor, $thread, $d),
                'publish' => app(KnowledgeDocuments::class)->publish($actor, $d),
                'withdraw' => app(KnowledgeDocuments::class)->withdraw($actor, (string) $r->route('document'), $d),
                'translate' => app(AssistanceInference::class)->prepare($actor, (int) $s->read($actor, $thread, true)['learner_id'], 'translation', $d, true),
                'run' => app(AssistanceInference::class)->run($actor, (string) $r->route('turn'), true),
            };
        } catch (ValidationException $error) {
            return $this->failure($r, $error->validator->errors()->all(), 422);
        } catch (AiUnavailable) {
            return $this->failure($r, [__('Translation is currently unavailable. The original message and human conversation remain available.')], 503);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 429], true)) {
                throw $error;
            }

            return $this->failure($r, [__('The change was refused. Compare current information before submitting again.')], $error->getStatusCode());
        }
        $args = [];
        if (in_array($op, ['claim', 'reply', 'close'], true)) {
            $args['thread'] = $row['id'];
        } elseif ($op === 'publish') {
            $args['document'] = $row['id'];
        } elseif (in_array($op, ['translate', 'run'], true)) {
            $args['turn'] = $row['id'];
        }

        return redirect()->route(isset($args['thread']) ? 'assistance-panel.thread' : 'assistance-panel.index', $args);
    }
}
