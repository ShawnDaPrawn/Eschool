<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ReportPanelRequest;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportComment;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Academics\Reports\ReportQueries;
use App\Services\Platform\IdempotencyOutcome;
use App\Services\Platform\IdempotencyService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ReportWorkbenchController extends Controller
{
    private function model(ReportPanelRequest $request): array
    {
        $queries = app(ReportQueries::class);
        $batch = $request->validated('batch_id') === null ? null : $queries->show($request->user(), (int) $request->validated('batch_id'));
        $year = $batch['selection']['session_year_id'] ?? $request->validated('session_year_id');
        $configuration = $queries->configuration($request->user(), $year === null ? null : (int) $year);
        $year = $configuration['session_year_id'];
        $room = $request->validated('class_section_id') === null ? ($configuration['homerooms'][0]['id'] ?? null) : (int) $request->validated('class_section_id');
        $term = $request->validated('term_id') === null ? null : (int) $request->validated('term_id');
        $roster = $batch !== null || $room === null ? null : $queries->roster($request->user(), $year, $room, $term);
        if ($batch !== null) {
            foreach ($batch['cards'] as &$card) {
                $card['panel_pdf'] = $card['pdf_ready'] ? URL::temporarySignedRoute('academic-reports.pdf', now()->addMinutes(10), ['card' => $card['id'], 'school' => app(CurrentSchool::class)->id(), 'reader' => $request->user()->id]) : null;
            }
        }
        $template = collect($configuration['templates'])->firstWhere('id', (int) $request->validated('template_id'));
        $bank = collect($configuration['comment_banks'])->firstWhere('id', (int) $request->validated('bank_id'));

        return ['configuration' => $configuration, 'roster' => $roster, 'room' => $room, 'term' => $term, 'batch' => $batch, 'template' => $template, 'bank' => $bank,
            'comments' => $queries->comments($request->user(), $year, (int) $request->validated('comment_page', 1)),
            'batches' => $queries->batches($request->user(), $year, (int) $request->validated('batch_page', 1)),
            'preview' => null, 'sourcePreview' => null, 'releasePreview' => null, 'conflict' => null, 'submittedComment' => null, 'reviewedComment' => null];
    }

    public function index(ReportPanelRequest $request): View
    {
        return view('academics.report-workbench', $this->model($request));
    }

    /** Atomic body + receipt. Private response encryption is shared with API retries. */
    private function once(ReportPanelRequest $request, callable $write): Response
    {
        return DB::transaction(function () use ($request, $write) {
            $keys = app(IdempotencyService::class);
            $key = 'report-panel:'.app(CurrentSchool::class)->id().':'.$request->validated('operation_id');
            [$outcome, $record] = $keys->begin((int) $request->user()->id, $key, $request);
            if ($outcome === IdempotencyOutcome::Replay) {
                return $keys->replay($record);
            }
            abort_if($outcome === IdempotencyOutcome::Mismatch, 422, __('This operation has changed. Review the form before retrying.'));
            abort_if($outcome === IdempotencyOutcome::InProgress, 409, __('This operation is still processing. Retry shortly.'));
            $response = $write();
            $keys->complete($record, $response);

            return $response;
        });
    }

    public function templates(ReportPanelRequest $request): Response
    {
        return $this->once($request, function () use ($request) {
            $saved = app(ReportDesigns::class)->save($request->user(), $request->validated());

            return redirect()->route('academic-reports.index', ['session_year_id' => $request->validated('session_year_id'), 'template_id' => $saved['id']])->with('success', __('Report design saved'));
        });
    }

    public function banks(ReportPanelRequest $request): Response
    {
        return $this->once($request, function () use ($request) {
            $saved = app(ReportDesigns::class)->bank($request->user(), $request->validated());

            return redirect()->route('academic-reports.index', ['session_year_id' => $request->validated('session_year_id'), 'bank_id' => $saved['id']])->with('success', __('Comment bank saved'));
        });
    }

    public function preview(ReportPanelRequest $request): View
    {
        $preview = app(ReportBatches::class)->preview($request->user(), $request->validated());

        return view('academics.report-workbench', array_replace($this->model($request), ['preview' => $preview]));
    }

    public function prepare(ReportPanelRequest $request): Response
    {
        return $this->once($request, function () use ($request) {
            $draft = app(ReportBatches::class)->prepare($request->user(), $request->validated());

            return redirect()->route('academic-reports.index', ['batch_id' => $draft['id']])->with('success', __('Report batch prepared'));
        });
    }

    public function action(ReportPanelRequest $request, int $batch, string $action): Response
    {
        $service = app(ReportBatches::class);
        if ($action === 'source-preview' || $action === 'release-preview') {
            $preview = $action === 'source-preview' ? $service->sourcePreview($request->user(), $batch) : $service->releasePreview($request->user(), $batch);

            return response()->view('academics.report-workbench', array_replace($this->model($request), [$action === 'source-preview' ? 'sourcePreview' : 'releasePreview' => $preview]));
        }
        try {
            return $this->once($request, function () use ($request, $batch, $action, $service) {
                match ($action) {
                    'refresh' => $service->refresh($request->user(), $batch, $request->validated()),
                    'grade-sign' => $service->approve($request->user(), $batch, 'grade_head', $request->validated()),
                    'principal-sign' => $service->approve($request->user(), $batch, 'principal', $request->validated()),
                    'release' => $service->release($request->user(), $batch, $request->validated()),
                    'retry' => $service->retry($request->user(), $batch, $request->validated()),
                };

                return redirect()->route('academic-reports.index', ['batch_id' => $batch])->with('success', __('Report batch updated'));
            });
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return response()->view('academics.report-workbench', array_replace($this->model($request), ['conflict' => $error->getMessage()]), 409);
        }
    }

    public function comment(ReportPanelRequest $request, int $card): Response
    {
        try {
            return $this->once($request, function () use ($request, $card) {
                app(ReportBatches::class)->comment($request->user(), $card, $request->validated());

                return redirect()->route('academic-reports.index', ['session_year_id' => $request->validated('session_year_id'), 'batch_id' => $request->validated('batch_id'), 'comment_page' => $request->validated('comment_page')])->with('success', __('Report comment saved'));
            });
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }
            $submitted = $request->validated() + ['card_id' => $card];
            $submitted['text'] = app(ReportBatches::class)->commentText($submitted);
            unset($submitted['bank_id']);

            return response()->view('academics.report-workbench', array_replace($this->model($request), ['conflict' => $error->getMessage(), 'submittedComment' => $submitted]), 409);
        }
    }

    /** Review keeps the submitted text, but adopting current revisions is explicit. */
    public function reviewComment(ReportPanelRequest $request, int $card): Response
    {
        $row = ReportCard::with('batch')->findOrFail($card);
        app(ReportPolicy::class)->comment($request->user(), $row, $request->validated('subject_id') === null ? null : (int) $request->validated('subject_id'));
        $scope = $request->validated('subject_id') === null ? 'homeroom' : 'subject:'.$request->validated('subject_id');
        $saved = ReportComment::where('report_card_id', $card)->where('scope_key', $scope)->first();
        $draft = array_replace($request->validated(), ['card_id' => $card, 'revision' => $saved?->revision ?? 0, 'batch_revision' => $row->batch->revision]);
        $draft['text'] = app(ReportBatches::class)->commentText($draft);
        unset($draft['bank_id']);

        return response()->view('academics.report-workbench', array_replace($this->model($request), ['reviewedComment' => $draft]));
    }

    public function pdf(ReportPanelRequest $request, int $card): Response
    {
        $row = ReportCard::with('batch')->findOrFail($card);
        app(ReportPolicy::class)->batch($request->user(), (int) $row->report_batch_id);

        return response(app(ReportArtifacts::class)->read($row), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="report-'.$card.'.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
