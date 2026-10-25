<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Pastoral;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\SupportPanelRequest;
use App\Models\Pastoral\SupportDisclosure;
use App\Services\Panel\SessionYearContext;
use App\Services\Pastoral\SupportPlans;
use App\Services\Pastoral\SupportQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SupportPanelController extends Controller
{
    private function model(SupportPanelRequest $r, array $messages = [], bool $conflict = false): array
    {
        $q = app(SupportQueries::class);
        $access = $q->access($r->user());
        $op = $r->operation();
        $audience = $r->isMethodSafe() ? $r->input('audience', $access['audiences'][0]) : (in_array($op, ['assign', 'revoke-assignment'], true) ? 'principal' : 'professional');
        abort_unless(in_array($audience, $access['audiences'], true), 403);
        $page = max(1, (int) $r->input('page', 1));
        $planId = $r->route('plan') ?? $r->input('plan');
        if ($r->route('disclosure') !== null) {
            $planId = SupportDisclosure::findOrFail((int) $r->route('disclosure'))->support_plan_id;
        }
        $record = $audience === 'professional' && $planId !== null ? $q->plan($r->user(), (int) $planId) : null;
        $historyKind = in_array($r->input('history'), ['reviews', 'versions', 'disclosures'], true) ? $r->input('history') : 'reviews';
        $history = $record === null ? null : $q->{$historyKind}($r->user(), $record['id'], max(1, (int) $r->input('history_page', 1)));
        $listing = match ($audience) {
            'principal' => $q->assignments($r->user(), $page), 'professional' => $q->cases($r->user(), $page), 'teacher' => $q->teacher($r->user(), $page)
        };
        $configuration = $audience === 'teacher' ? null : $q->configuration($r->user(), $audience, (string) $r->input('search', ''), $page);
        $draft = [];
        if (! $r->isMethodSafe()) {
            foreach (['needs' => 20000, 'accommodations' => 20000, 'outcome' => 10000, 'reason' => 2000, 'sias_stage' => 100, 'learner_id' => 20, 'user_id' => 20, 'started_on' => 10, 'review_due_on' => 10, 'reviewed_on' => 10, 'next_review_on' => 10, 'expires_on' => 10, 'starts_on' => 10, 'ends_on' => 10] as $field => $limit) {
                if (is_scalar($r->input($field))) {
                    $draft[$field] = mb_substr((string) $r->input($field), 0, $limit);
                }
            }
        }
        $target = $r->route('assignment') ?? $r->route('disclosure') ?? $r->route('plan') ?? 'new';
        $draftKey = $r->isMethodSafe() ? '' : $op.':'.$target;
        $operationId = ! $conflict && is_string($r->input('operation_id')) && Str::isUuid($r->input('operation_id')) ? $r->input('operation_id') : (string) Str::uuid();

        return compact('access', 'audience', 'page', 'record', 'listing', 'configuration', 'history', 'historyKind', 'messages', 'conflict', 'draft', 'draftKey', 'operationId') + ['canWrite' => $access['can_write'] && ! app(SessionYearContext::class)->isReadOnly(), 'today' => $access['today']];
    }

    public function index(SupportPanelRequest $r): Response
    {
        return response()->view('pastoral.support', $this->model($r))->header('Cache-Control', 'no-store, private');
    }

    public function failure(SupportPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('pastoral.support', $this->model($r, $messages, $status === 409), $status)->header('Cache-Control', 'no-store, private');
    }

    public function write(SupportPanelRequest $r): Response|RedirectResponse
    {
        $s = app(SupportPlans::class);
        $op = $r->operation();
        $plan = $r->route('plan') === null ? null : (int) $r->route('plan');
        $d = $r->validated();
        try {
            $row = match ($op) {
                'assign' => $s->assign($r->user(), $d), 'create','save' => $s->save($r->user(), $d, $plan),
                'review' => $s->review($r->user(), $plan, $d), 'close' => $s->close($r->user(), $plan, $d),
                'disclose' => $s->disclose($r->user(), $plan, $d),
                'revoke-assignment' => $s->revoke($r->user(), 'assignment', (int) $r->route('assignment'), $d),
                'revoke-disclosure' => $s->revoke($r->user(), 'disclosure', (int) $r->route('disclosure'), $d),
            };
        } catch (ValidationException $e) {
            return $this->failure($r, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage(), __('Your changes were not applied. Compare the current record before trying again.')], $e->getStatusCode());
        }
        $audience = in_array($op, ['assign', 'revoke-assignment'], true) ? 'principal' : 'professional';
        if ($op === 'create') {
            $plan = (int) $row->id;
        }
        if ($op === 'revoke-disclosure') {
            $plan = (int) $row->support_plan_id;
        }

        return redirect()->route('support-plans.index', ['audience' => $audience, 'plan' => $plan])->with('success', __('Support plan change saved'))->header('Cache-Control', 'no-store, private');
    }
}
