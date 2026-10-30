<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\GradePromotionRequest;
use App\Models\SessionYear;
use App\Services\Academics\NewYearService;
use App\Services\Panel\SessionYearContext;
use App\Services\People\GradePromotion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * /promotions/by-grade: promote the whole school into next year in one go,
 * with exceptions per learner (Action Plan 1.18).
 * engineering/specs/academics/grade-promotion.md.
 */
class GradePromotionController extends Controller
{
    private const TRUNCATED = 'Too many choices arrived at once and some were lost. Paste the changes as a sheet instead.';

    public function __construct(
        private readonly GradePromotion $promotion,
        private readonly NewYearService $newYear,
        private readonly SessionYearContext $context,
    ) {}

    public function index(GradePromotionRequest $request): View|RedirectResponse
    {
        $years = $this->years($request);
        if ($years === null) {
            return redirect()->route('new-year.index')->withErrors(['message' => __('Create next school year first, then promote learners into it.')]);
        }

        return $this->page($request, $years[0], $years[1], []);
    }

    /** "Check changes": the promotion with the choices on the page and any pasted sheet. */
    public function preview(GradePromotionRequest $request): View|Response
    {
        [$from, $to] = $this->years($request) ?? abort(404);
        if (! $request->boolean('complete')) {
            return response($this->page($request, $from, $to, [], ['complete' => [self::TRUNCATED]]), 422);
        }
        $exceptions = $this->exceptions($request);
        $errors = [];
        if (trim((string) $request->validated('sheet')) !== '') {
            $sheet = $this->promotion->parseSheet($from, (string) $request->validated('sheet'));
            $exceptions = $sheet['exceptions'] + $exceptions;
            $errors = $sheet['errors'] !== [] ? ['sheet' => $sheet['errors']] : [];
        }

        return $this->page($request, $from, $to, $exceptions, $errors);
    }

    public function store(GradePromotionRequest $request): Response|RedirectResponse
    {
        [$from, $to] = $this->years($request) ?? abort(404);
        if (! $request->boolean('complete')) {
            return response($this->page($request, $from, $to, [], ['complete' => [self::TRUNCATED]]), 422);
        }
        $exceptions = $this->exceptions($request);
        try {
            $done = $this->promotion->commit($request->user(), $from, $to, $exceptions, (string) $request->validated('fingerprint'));
        } catch (ValidationException $e) {
            return response($this->page($request, $from, $to, $exceptions, $e->errors()), 422);
        }

        return redirect()->route('promotions.by-grade', ['from' => $from->id, 'to' => $to->id])->with('success', __(':promoted promoted, :repeated repeating, :placed placed and :left leaving, for :year.', [
            'promoted' => $done[GradePromotion::PROMOTE], 'repeated' => $done[GradePromotion::REPEAT],
            'placed' => $done[GradePromotion::PLACE], 'left' => $done[GradePromotion::LEAVE], 'year' => $to->name,
        ]));
    }

    /**
     * @param  array<int, array{outcome?: string, homeroom_id?: int|null}>  $exceptions
     * @param  array<string, list<string>>  $errors
     */
    private function page(GradePromotionRequest $request, SessionYear $from, SessionYear $to, array $exceptions, array $errors = []): View
    {
        try {
            $plan = $this->promotion->plan($from, $to, $exceptions);
        } catch (ValidationException $e) {
            $plan = null;
            $errors += $e->errors();
        }

        return view('people.grade-promotion', [
            'from' => $from,
            'to' => $to,
            'years' => SessionYear::query()->orderByDesc('start_date')->pluck('name', 'id')->all(),
            'plan' => $plan,
            'rooms' => $this->promotion->homeroomChoices(),
            'checked' => $request->isMethod('post'),
            'canCommit' => $request->user()->can('promote-student-edit'),
            'problems' => collect($errors)->flatten()->map(fn ($problem) => __((string) $problem))->values()->all(),
        ]);
    }

    /** @return array{0: SessionYear, 1: SessionYear}|null */
    private function years(GradePromotionRequest $request): ?array
    {
        $from = $request->validated('from') !== null ? SessionYear::query()->find((int) $request->validated('from')) : $this->context->current();
        if ($from === null) {
            return null;
        }
        $to = $request->validated('to') !== null ? SessionYear::query()->find((int) $request->validated('to')) : $this->newYear->upcomingYear($from);

        return $to === null ? null : [$from, $to];
    }

    /**
     * The choices on the page: learner id => outcome, with the homeroom (as
     * named on the page) for "place".
     *
     * @return array<int, array{outcome: string, homeroom_id: int|null}>
     */
    private function exceptions(GradePromotionRequest $request): array
    {
        $byName = collect($this->promotion->homeroomChoices())->mapWithKeys(fn (string $name, int $id) => [mb_strtolower($name) => $id]);
        $choices = [];
        foreach ((array) $request->validated('learners', []) as $learner => $choice) {
            if (($choice['outcome'] ?? null) === null) {
                continue;
            }
            $room = trim((string) ($choice['homeroom'] ?? ''));
            $choices[(int) $learner] = [
                'outcome' => (string) $choice['outcome'],
                'homeroom_id' => $room !== '' ? ($byName[mb_strtolower(preg_replace('/\s+/', ' ', $room) ?? $room)] ?? null) : null,
            ];
        }

        return $choices;
    }
}
