<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Leave;

use App\Http\Controllers\Controller;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Models\Leave\FacilitatorSessionCover;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Leave\FacilitatorLeave;
use App\Services\Panel\SessionYearContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilitatorLeaveController extends Controller
{
    public function index(Request $request, FacilitatorLeave $service)
    {
        $actor = $service->actor($request->user());
        $year = SessionYear::findOrFail(app(SessionYearContext::class)->selectedId());
        $rows = $service->listing($actor, (int) $year->id, (string) $request->query('status', ''));
        $names = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return response()->view('leave.facilitators.index', compact('rows', 'names', 'year') + ['canRequest' => $service->canRequest($actor), 'canManage' => $service->canManage($actor)])->header('Cache-Control', 'no-store, private');
    }

    public function cover(Request $request, FacilitatorLeave $service)
    {
        $actor = $service->owner($request->user());
        $year = SessionYear::findOrFail(app(SessionYearContext::class)->selectedId());
        $covers = FacilitatorSessionCover::where('replacement_user_id', $actor->id)
            ->whereIn('activity_group_id', ActivityGroup::where('session_year_id', $year->id)->select('id'))
            ->orderBy('on_date')->paginate(25)->withQueryString();
        $sessions = ActivitySession::whereIn('id', $covers->pluck('activity_session_id'))->get()->keyBy('id');
        $groups = ActivityGroup::withTrashed()->whereIn('id', $covers->pluck('activity_group_id'))->get()->keyBy('id');

        return response()->view('leave.facilitators.cover', compact('covers', 'sessions', 'groups', 'year'))->header('Cache-Control', 'no-store, private');
    }

    public function create(Request $request, FacilitatorLeave $service)
    {
        $actor = $service->owner($request->user());
        $year = SessionYear::findOrFail(app(SessionYearContext::class)->selectedId());
        $data = $request->validate(['from' => ['sometimes', 'date_format:Y-m-d'], 'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $from = $data['from'] ?? max(CarbonImmutable::parse($year->start_date)->toDateString(), min(now()->toDateString(), CarbonImmutable::parse($year->end_date)->toDateString()));
        $to = $data['to'] ?? $from;
        abort_if(CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 365, 422);
        $portions = [];
        for ($day = CarbonImmutable::parse($from); $day->toDateString() <= $to; $day = $day->addDay()) {
            $portions[$day->toDateString()] = 'full';
        }
        $sessions = $service->affected((int) $actor->id, (int) $year->id, $portions);
        $groups = ActivityGroup::whereIn('id', $sessions->pluck('activity_group_id'))->get()->keyBy('id');

        return response()->view('leave.facilitators.create', compact('actor', 'year', 'from', 'to', 'portions', 'sessions', 'groups') + ['operationId' => (string) Str::uuid(), 'canWrite' => (int) $year->id === app(SessionYearContext::class)->currentId()])->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, FacilitatorLeave $service, int $absence)
    {
        $record = $service->record($request->user(), $absence);
        $sessions = $service->affected((int) $record->user_id, (int) $record->session_year_id, $record->portions);
        $covers = FacilitatorSessionCover::where('facilitator_absence_id', $record->id)->get()->keyBy('activity_session_id');
        $groups = ActivityGroup::withTrashed()->whereIn('id', $sessions->pluck('activity_group_id'))->get()->keyBy('id');
        $names = User::whereIn('id', [$record->user_id, ...$covers->pluck('replacement_user_id')->all()])->get()->keyBy('id');
        $canManage = $service->canManage($request->user());
        $canWrite = (int) $record->session_year_id === app(SessionYearContext::class)->currentId() && app(SessionYearContext::class)->selectedId() === app(SessionYearContext::class)->currentId();
        $session = null;
        $candidates = collect();
        if ($request->filled('session')) {
            $data = $request->validate(['session' => ['required', 'integer', 'min:1']]);
            $session = $sessions->firstWhere('id', (int) $data['session']);
            abort_if(! $session, 404);
            $candidates = $service->candidates($request->user(), $record, $session);
        }

        return response()->view('leave.facilitators.show', compact('record', 'sessions', 'covers', 'groups', 'names', 'canManage', 'canWrite', 'session', 'candidates') + ['operationId' => (string) Str::uuid()])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, FacilitatorLeave $service)
    {
        $record = $service->request($request->user(), $request->except('files'), $request->file('files', []));

        return response()->json(['error' => false, 'message' => __('Facilitator leave request submitted. Session cover remains separate.'), 'redirect' => route('facilitator-leave.show', $record->id)])->header('Cache-Control', 'no-store, private');
    }

    public function decide(Request $request, FacilitatorLeave $service, int $absence)
    {
        $record = $service->decide($request->user(), $absence, $request->all());

        return response()->json(['error' => false, 'message' => __('Facilitator leave decision saved.'), 'redirect' => route('facilitator-leave.show', $record->id)])->header('Cache-Control', 'no-store, private');
    }

    public function assign(Request $request, FacilitatorLeave $service, int $absence, int $session)
    {
        $service->assign($request->user(), $absence, $session, $request->all());

        return response()->json(['error' => false, 'message' => __('Temporary activity session cover assigned. The original group appointment is retained.'), 'redirect' => route('facilitator-leave.show', $absence)])->header('Cache-Control', 'no-store, private');
    }

    public function download(Request $request, FacilitatorLeave $service, int $absence, int $file)
    {
        $record = $service->record($request->user(), $absence);
        $attachment = $record->attachments[$file] ?? null;
        abort_unless($attachment, 404);

        return Storage::disk('leave_private')->download($attachment['path'], $attachment['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
