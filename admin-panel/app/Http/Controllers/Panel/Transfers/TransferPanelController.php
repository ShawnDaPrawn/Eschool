<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Transfers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transfers\TransferPanelRequest;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferDownloads;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransferPanelController extends Controller
{
    private function model(TransferPanelRequest $r, array $messages = []): array
    {
        $q = app(TransferQueries::class);
        $access = $q->access($r->user());
        $op = $r->operation();
        $lane = $r->isMethodSafe() ? (string) $r->input('lane', 'outgoing') : ($op === 'accept' ? 'incoming' : 'outgoing');
        $id = $r->route('package') ?? $r->input('record');
        $record = $id === null ? null : $q->show($r->user(), $lane, (int) $id);
        $identifier = $record === null ? null : $q->primaryIdentifier($r->user(), $lane, (int) $id);
        $page = max(1, (int) $r->input('page', 1));
        $listing = $q->listing($r->user(), $lane, $page);
        $learner = $r->filled('learner_id') ? (int) $r->input('learner_id') : null;
        $guardian = $r->filled('guardian_id') ? (int) $r->input('guardian_id') : null;
        if ($r->isMethodSafe() && $learner !== null && $guardian !== null && $q->choices($r->user(), 'guardians', '', 1, $learner, $guardian, (string) $guardian)['items'] === []) {
            $guardian = null;
        }
        $choiceKind = (string) $r->input('choice_kind', $lane === 'incoming' ? 'rooms' : 'learners');
        $search = is_string($r->input('search')) ? mb_substr($r->input('search'), 0, 100) : '';
        $choicePage = max(1, (int) $r->input('choice_page', 1));
        $choices = [];
        $selected = ['learners' => $learner, 'guardians' => $guardian, 'destinations' => $r->input('to_school_id'), 'years' => $r->input('session_year_id'), 'rooms' => $r->input('class_section_id'), 'categories' => $r->input('category_id')];
        foreach ($lane === 'incoming' ? ['years', 'rooms', 'categories'] : ($lane === 'outgoing' ? ['learners', 'destinations', 'guardians', 'reports', 'documents'] : []) as $kind) {
            if (($learner === null && in_array($kind, ['guardians', 'reports', 'documents'], true)) || ($kind === 'documents' && $guardian === null)) {
                $choices[$kind] = ['items' => [], 'has_more' => false, 'page' => 1];

                continue;
            }
            $result = $q->choices($r->user(), $kind, $kind === $choiceKind ? $search : '', $kind === $choiceKind ? $choicePage : 1, $learner, $guardian);
            if (! empty($selected[$kind])) {
                $named = $q->choices($r->user(), $kind, '', 1, $learner, $guardian, (string) $selected[$kind]);
                if ($named['items'] !== [] && ! in_array($named['items'][0]['key'], array_column($result['items'], 'key'), true)) {
                    $result['items'][] = $named['items'][0];
                }
            }
            $choices[$kind] = $result;
        }
        $draft = [];
        foreach (['external_school' => 160, 'last_attended_on' => 10, 'starts_on' => 10, 'session_year_id' => 20, 'class_section_id' => 20, 'category_id' => 20, 'to_school_id' => 20] as $key => $limit) {
            if (is_scalar($r->input($key))) {
                $draft[$key] = mb_substr((string) $r->input($key), 0, $limit);
            }
        }
        $operationId = $messages === [] && Str::isUuid((string) $r->input('operation_id')) ? (string) $r->input('operation_id') : (string) Str::uuid();
        $canWrite = $access['can_write'] && ! app(SessionYearContext::class)->isReadOnly();
        $account = (int) $r->user()->id;
        $school = app(CurrentSchool::class)->id();

        return compact('access', 'lane', 'record', 'identifier', 'listing', 'page', 'learner', 'guardian', 'choiceKind', 'search', 'choicePage', 'choices', 'draft', 'operationId', 'canWrite', 'messages', 'account', 'school');
    }

    public function index(TransferPanelRequest $r): Response
    {
        return response()->view('transfers.workbench', $this->model($r));
    }

    public function failure(TransferPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('transfers.workbench', $this->model($r, $messages), $status)->header('Cache-Control', 'private, no-store');
    }

    public function write(TransferPanelRequest $r): Response|RedirectResponse
    {
        $op = $r->operation();
        $service = app(TransferPackages::class);
        try {
            $row = match ($op) {
                'prepare' => $service->prepare($r->user(), $r->validated()),
                'accept' => app(TransferAccept::class)->accept($r->user(), (int) $r->route('package'), $r->validated()),
                'release' => $service->release($r->user(), (int) $r->route('package'), $r->validated()),
                'cancel' => $service->cancel($r->user(), (int) $r->route('package'), $r->validated()),
            };
        } catch (ValidationException $e) {
            return $this->failure($r, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage(), __('Review the current transfer before trying again.')], $e->getStatusCode());
        }

        return redirect()->route('school-transfers.index', ['lane' => $op === 'accept' ? 'receipts' : 'outgoing', 'record' => $row->id])->with('success', __('School transfer change saved'));
    }

    public function archive(TransferPanelRequest $r, string $lane, int $record): Response
    {
        abort_unless(in_array($lane, ['outgoing', 'incoming', 'receipts'], true), 404);

        return response(app(TransferDownloads::class)->read($r->user(), $lane, $record), 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="school-transfer.zip"']);
    }
}
