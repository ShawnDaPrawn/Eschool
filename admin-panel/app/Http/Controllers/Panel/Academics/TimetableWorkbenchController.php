<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TimetableWorkbenchRequest;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\Room;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TimetableLesson;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableImport;
use App\Services\Academics\TimetableQueries;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TimetableWorkbenchController extends Controller
{
    public function index(TimetableWorkbenchRequest $request): View
    {
        return view('academics.timetable-workbench', $this->model($request));
    }

    private function model(TimetableWorkbenchRequest $request): array
    {
        app(AcademicAuthority::class)->manage($request->user());
        $years = SessionYear::orderByDesc('start_date')->get();
        $year = $request->validated('session_year_id') === null ? $years->firstWhere('default', 1) ?? $years->first() : SessionYear::findOrFail((int) $request->validated('session_year_id'));
        abort_if($year === null, 422, __('Set up a school year first.'));
        $today = app(AcademicAuthority::class)->today();
        $weekOn = $request->validated('week_on') ?? CarbonImmutable::parse(max($year->start_date, min($today, $year->end_date)))->startOfWeek()->toDateString();
        $queries = app(TimetableQueries::class);
        $configuration = $queries->configuration($request->user(), (int) $year->id);
        $groups = TeachingGroup::with('subject', 'gradeLevel')->where('session_year_id', $year->id)->orderBy('name')->get();
        $choices = [
            'teaching_group_id' => $groups->mapWithKeys(fn ($group) => [(int) $group->id => $group->name.' · '.$group->subject->name.' · '.$group->gradeLevel->name.($group->active ? '' : ' ('.__('Inactive').')')])->all(),
            'class_section_id' => array_column($configuration['homerooms'], 'name', 'id'),
            'teacher_id' => array_column($configuration['teachers'], 'name', 'id'),
            'room_id' => array_column($configuration['rooms'], 'name', 'id'),
            'bell_period_id' => [], 'weekday' => [1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')],
        ];
        foreach ($configuration['bell_schedules'] as $schedule) {
            foreach ($schedule['periods'] as $period) {
                if (! $period['is_break']) {
                    $choices['bell_period_id'][$period['id']] = $schedule['section'].' · '.$period['name'].' · '.$period['starts_at'].'–'.$period['ends_at'];
                }
            }
        }
        $record = $request->validated('edit') === null ? null : TimetableLesson::where('session_year_id', $year->id)->findOrFail((int) $request->validated('edit'));
        $draft = $record === null ? ['session_year_id' => (int) $year->id, 'starts_on' => max($weekOn, $year->start_date), 'ends_on' => min(CarbonImmutable::parse($weekOn)->addDays(6)->toDateString(), $year->end_date), 'weekday' => 1, 'active' => true, 'week_pattern' => 'all'] : $queries->data($record);
        $room = $request->validated('room') === null ? null : Room::findOrFail((int) $request->validated('room'));
        $schedule = $request->validated('schedule') === null ? null : BellSchedule::with('periods')->findOrFail((int) $request->validated('schedule'));

        return compact('years', 'year', 'weekOn', 'configuration', 'choices', 'draft', 'room', 'schedule') + ['week' => $queries->week($request->user(), (int) $year->id, $weekOn), 'preview' => null, 'conflict' => null, 'import' => null];
    }

    public function room(TimetableWorkbenchRequest $request): RedirectResponse
    {
        app(TimetableConfiguration::class)->room($request->user(), $request->validated(), $request->validated('room_id') === null ? null : (int) $request->validated('room_id'));

        return redirect()->route('academic-timetable.index', ['session_year_id' => $request->validated('session_year_id'), 'week_on' => $request->validated('week_on')])->with('success', __('Room saved'));
    }

    public function bells(TimetableWorkbenchRequest $request): RedirectResponse
    {
        app(TimetableConfiguration::class)->bells($request->user(), $request->validated(), $request->validated('schedule_id') === null ? null : (int) $request->validated('schedule_id'));

        return redirect()->route('academic-timetable.index', ['session_year_id' => $request->validated('session_year_id'), 'week_on' => $request->validated('week_on')])->with('success', __('Bell schedule saved'));
    }

    public function preview(TimetableWorkbenchRequest $request): View
    {
        $row = $request->validated('lesson');
        $row['session_year_id'] = (int) $request->validated('session_year_id');
        if (! filter_var($row['active'] ?? true, FILTER_VALIDATE_BOOLEAN) && ! empty($row['id'])) {
            $saved = TimetableLesson::where('session_year_id', $row['session_year_id'])->findOrFail((int) $row['id']);
            $row = $saved->getAttributes() + ['revision' => null];
            $row['revision'] = $request->validated('lesson')['revision'] ?? null;
            $row['active'] = false;
        }

        $preview = app(TimetableEngine::class)->preview($request->user(), [$row]);

        return view('academics.timetable-workbench', array_replace($this->model($request), ['preview' => $preview, 'draft' => $preview['rows'][0]]));
    }

    public function commit(TimetableWorkbenchRequest $request): RedirectResponse|Response
    {
        try {
            app(TimetableEngine::class)->save($request->user(), $request->validated('rows'), $request->validated('fingerprint'));
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return response()->view('academics.timetable-workbench', array_replace($this->model($request), ['conflict' => $error->getMessage(), 'draft' => $request->validated('rows')[0]]), 409);
        }

        return redirect()->route('academic-timetable.index', ['session_year_id' => $request->validated('session_year_id'), 'week_on' => $request->validated('week_on')])->with('success', __('Timetable saved'));
    }

    public function mapImport(TimetableWorkbenchRequest $request): View
    {
        $sheet = app(TimetableImport::class)->inspect($request->user(), $request->validated('csv'));
        $columns = $request->validated('columns', []);
        foreach ($columns as $column) {
            abort_unless(in_array($column, $sheet['headers'], true), 422, __('Choose a column from this sheet.'));
        }
        $import = ['csv' => $request->validated('csv'), 'sheet' => $sheet, 'columns' => $columns, 'stage' => $request->validated('stage') === 'values' ? 'values' : 'columns'];

        return view('academics.timetable-workbench', array_replace($this->model($request), ['import' => $import]));
    }

    public function previewImport(TimetableWorkbenchRequest $request): View
    {
        $preview = app(TimetableImport::class)->preview($request->user(), $request->validated() + ['defaults' => []]);

        return view('academics.timetable-workbench', array_replace($this->model($request), ['preview' => $preview]));
    }
}
