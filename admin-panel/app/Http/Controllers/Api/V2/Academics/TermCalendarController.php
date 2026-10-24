<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TermRequest;
use App\Http\Requests\Academics\TermTemplateRequest;
use App\Http\Resources\Academics\CalendarResource;
use App\Models\TermTemplate;
use App\Services\Academics\TermCalendar;
use App\Services\Academics\TermTemplates;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class TermCalendarController extends Controller
{
    public function index(TermRequest $request, TermCalendar $calendar): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new CalendarResource(
            $calendar->list($request->user(), (int) $request->input('session_year_id'), $request->has('grade') ? (int) $request->input('grade') : null)
        ))->resolve($request));
    }

    public function store(TermRequest $request, TermCalendar $calendar): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new CalendarResource(
            TermCalendar::data($calendar->save($request->user(), $request->validated()))
        ))->resolve($request));
    }

    public function update(TermRequest $request, TermCalendar $calendar, int $term): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', (new CalendarResource(
            TermCalendar::data($calendar->save($request->user(), $request->validated(), $term))
        ))->resolve($request));
    }

    public function destroy(TermRequest $request, TermCalendar $calendar, int $term): JsonResponse
    {
        $calendar->remove($request->user(), $term);

        return ResponseService::successResponse('data_delete_successfully');
    }

    public function templates(TermTemplateRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new CalendarResource([
            'templates' => TermTemplate::query()->orderBy('name')->get()->map(fn ($t) => self::templateData($t))->all(),
        ]))->resolve($request));
    }

    public static function templateData(TermTemplate $template): array
    {
        return ['id' => (int) $template->id, 'name' => $template->name, 'base_year' => $template->base_year, 'term_count' => count($template->terms)];
    }

    public function saveTemplate(TermTemplateRequest $request, TermTemplates $templates): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new CalendarResource(self::templateData(
            $templates->save($request->user(), (int) $request->input('session_year_id'), $request->string('name')->toString())
        )))->resolve($request));
    }

    public function applyTemplate(TermTemplateRequest $request, TermTemplates $templates, int $template): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', (new CalendarResource(
            $templates->apply($request->user(), $template, (int) $request->input('session_year_id'), $request->boolean('apply'))
        ))->resolve($request));
    }
}
