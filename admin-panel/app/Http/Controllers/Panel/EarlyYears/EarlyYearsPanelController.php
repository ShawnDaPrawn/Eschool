<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\EarlyYears;

use App\Http\Controllers\Controller;
use App\Http\Requests\EarlyYears\DevelopmentAreaRequest;
use App\Http\Requests\EarlyYears\DiaryRequest;
use App\Http\Requests\EarlyYears\ObservationActionRequest;
use App\Http\Requests\EarlyYears\ObservationRequest;
use App\Models\EarlyYears\DiaryEntry;
use App\Models\EarlyYears\Observation;
use App\Services\EarlyYears\DailyDiary;
use App\Services\EarlyYears\DevelopmentAreas;
use App\Services\EarlyYears\EarlyYearsAuthority;
use App\Services\EarlyYears\Observations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** /early-years: daily diary, observations and portfolios (Action Plan 4.3). */
class EarlyYearsPanelController extends Controller
{
    public function __construct(private readonly EarlyYearsAuthority $authority) {}

    public function index(Request $request): View
    {
        abort_unless($this->authority->available($request->user()), 403);

        return view('early-years.index', ['classes' => $this->authority->classes($request->user()), 'manage' => $this->authority->canManage($request->user()),
            'areas' => app(DevelopmentAreas::class)->all()]);
    }

    public function saveArea(DevelopmentAreaRequest $request): RedirectResponse
    {
        $area = $request->route('area');

        return $this->attempt(fn () => app(DevelopmentAreas::class)->save($request->user(), $area === null ? null : (int) $area, $request->validated()),
            redirect()->route('early-years.index'), __('Development area saved.'));
    }

    public function classroom(Request $request, int $section): View
    {
        $class = $this->authority->classRead($request->user(), $section);
        $learners = $this->authority->roster($section);
        $counts = Observation::whereIn('learner_id', $learners->pluck('id'))->whereNull('withdrawn_at')->selectRaw('learner_id, count(*) as total')->groupBy('learner_id')->pluck('total', 'learner_id');
        $today = $this->authority->today();
        $diary = DiaryEntry::whereIn('learner_id', $learners->pluck('id'))->whereDate('entry_date', $today)->count();
        $this->authority->classRead($request->user(), $section);

        return view('early-years.class', ['section' => $class, 'learners' => $learners, 'counts' => $counts, 'today' => $today, 'diaryCount' => $diary]);
    }

    public function diary(Request $request, int $section): View
    {
        $date = is_string($request->query('date')) ? $request->query('date') : $this->authority->today();
        $sheet = app(DailyDiary::class)->sheet($request->user(), $section, $date);
        $write = true;
        try {
            $this->authority->classWrite($request->user(), $section);
        } catch (HttpExceptionInterface) {
            $write = false;
        }

        return view('early-years.diary', $sheet + ['choices' => DailyDiary::CHOICES, 'canWrite' => $write]);
    }

    public function saveDiary(DiaryRequest $request, int $section): RedirectResponse
    {
        $data = $request->validated();

        return $this->attempt(function () use ($request, $section, $data): string {
            $result = app(DailyDiary::class)->save($request->user(), $section, $data);

            return trans_choice(':count diary entry saved.|:count diary entries saved.', $result['saved'], ['count' => $result['saved']]);
        }, redirect()->route('early-years.diary', ['section' => $section, 'date' => $data['date']]));
    }

    public function portfolio(Request $request, int $learner): View
    {
        return view('early-years.portfolio', app(Observations::class)->portfolio($request->user(), $learner) + ['operationId' => (string) Str::uuid(), 'today' => $this->authority->today()]);
    }

    public function record(ObservationRequest $request, int $learner): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        return $this->attempt(fn () => app(Observations::class)->record($request->user(), $learner, $data, $request->file('photo')),
            redirect()->route('early-years.portfolio', ['learner' => $learner]), __('Observation saved.'));
    }

    public function photo(Request $request, int $observation): Response
    {
        $bytes = app(Observations::class)->photo($request->user(), $observation, $request->boolean('thumbnail'));

        return response($bytes, 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) strlen($bytes), 'Content-Disposition' => 'inline; filename="observation.jpg"']);
    }

    public function withdraw(ObservationActionRequest $request, int $observation): RedirectResponse
    {
        $learner = (int) Observation::whereKey($observation)->value('learner_id');

        return $this->attempt(fn () => app(Observations::class)->withdraw($request->user(), $observation, $request->validated()),
            redirect()->route('early-years.portfolio', ['learner' => $learner]), __('Observation withdrawn.'));
    }

    public function concern(ObservationActionRequest $request, int $observation): RedirectResponse
    {
        $learner = (int) Observation::whereKey($observation)->value('learner_id');

        return $this->attempt(fn () => app(Observations::class)->raiseConcern($request->user(), $observation, $request->validated()),
            redirect()->route('early-years.portfolio', ['learner' => $learner]), __('Early concern referred to the grade head.'));
    }

    /** Refusals the person can act on go back as errors, without their input. */
    private function attempt(callable $write, RedirectResponse $done, ?string $message = null): RedirectResponse
    {
        try {
            $result = $write();

            return $done->with('success', is_string($result) ? $result : $message);
        } catch (ValidationException $error) {
            return redirect()->to(url()->previous())->withErrors($error->errors());
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422, 423], true)) {
                throw $error;
            }

            return redirect()->to(url()->previous())->withErrors([$error->getMessage()]);
        }
    }
}
