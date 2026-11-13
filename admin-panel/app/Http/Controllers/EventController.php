<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReadinessLink;
use App\Models\MultipleEvent;
use App\Models\SessionYear;
use App\Services\Platform\Files\FileNames;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        if (! Auth::user()->canAny(['event-list', 'event-create', 'event-edit', 'event-delete'])) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        $editingEvent = null;
        if ($request->filled('event_id')) {
            abort_unless($request->user()->can('event-edit'), 403);
            $request->validate(['event_id' => 'required|integer|min:1']);
            $editingEvent = Event::with('multipleEvent')->findOrFail((int) $request->input('event_id'));
        }

        return response(view('events.index', compact('session_years', 'editingEvent')));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->can('event-create'), 403);

        return $this->saveEvent($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show()
    {
        if (! Auth::user()->can('event-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';
        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }
        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];
        $sql = Event::with('multipleEvent')->where('id', '!=', 0)->where('session_year_id', $session_year_id);
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")->orwhere('title', 'LIKE', "%$search%")->orwhere('description', 'LIKE', "%$search%")->orwhere('start_date', 'LIKE', "%$search%"));
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '<a class="m3-button m3-button--text" href="'.e(route('event-readiness.show', $row->id)).'">'.e(__('Readiness')).'</a>';
            if (Auth::user()->can('event-edit')) {
                $operate .= '<a class="m3-button m3-button--text" href="'.e(route('events.index', ['event_id' => $row->id])).'">'.e(__('Edit event')).'</a>';
            }
            if ($row->type === 'multiple' && Auth::user()->can('event-list')) {
                $operate .= '<a class="m3-button m3-button--text" href="'.e(route('view_schedule.index', $row->id)).'">'.e(__('Programme')).'</a>';
            }
            if (Auth::user()->can('event-delete')) {
                $operate .= '<a class="m3-button m3-button--text" data-event-delete data-error-message="'.e(__('The event could not be deleted.')).'" data-confirm-message="'.e(__('Delete this event and its programme?')).'" href="'.e(route('events.destroy', $row->id)).'">'.e(__('Delete event')).'</a>';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['session_year_id'] = $row->session_year_id;
            $tempRow['title'] = $row->title;
            $tempRow['type'] = $row->type;
            $tempRow['image'] = $row->image;

            if ($row->start_time) {
                $tempRow['time'] = $row->start_time.' to '.$row->end_time;
            } else {
                $tempRow['time'] = null;
            }

            $tempRow['description'] = $row->description;

            if ($row->end_date) {
                $tempRow['date'] = date('d-m-Y', strtotime($row->start_date)).' to '.date('d-m-Y', strtotime($row->end_date));
            } else {
                $tempRow['date'] = date('d-m-Y', strtotime($row->start_date));
            }

            // if($row->type == 'multiple') {
            //     $tempRow['multipleEvents'] = $row->multipleEvent;
            //     $count = 1;
            //     $descriptions = [];
            //     $eventDetails = "<table class='event-table'><tr><th>No.</th><th>Title</th><th data-width='113'>Date</th><th>Timing</th><th>Description</th></tr>";
            //     if($row->multipleEvent->isNotEmpty())
            //     {
            //         foreach($row->multipleEvent as $event) {
            //             if($event){
            //                 $date = date('d-m-Y', strtotime($event->date));
            //                 $eventDetails .= "<tr><td>$count</td><td>$event->title</td><td>$date</td><td>$event->start_time to $event->end_time</td><td>$event->description</td></tr>";
            //                 $count++;
            //             }else{
            //                 $eventDetails = [];
            //             }

            //         }
            //         $descriptions[] = $eventDetails;
            //         $descriptions[] .= "</table>";
            //         $tempRow['schedule'] = implode('<br>', $descriptions);
            //     }else{
            //         $tempRow['schedule'] = null;
            //     }

            //     $tempRow['start_time'] = null;
            //     $tempRow['end_time'] = null;
            // }else {
            //     $tempRow['schedule'] = null;
            // }

            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request)
    {
        abort_unless($request->user()->can('event-edit'), 403);
        $request->validate(['edit_id' => ['required', 'integer', 'min:1']]);
        $routeId = $request->route('event');
        abort_if($routeId !== null && (int) $routeId !== (int) $request->input('edit_id'), 404);

        return $this->saveEvent($request, (int) $request->input('edit_id'));
    }

    /** Validate the entire programme before changing its parent or any item. */
    private function saveEvent(Request $request, ?int $id = null)
    {
        $editing = $id !== null;
        $typeField = $editing ? 'edit_event_type' : 'event_type';
        $dateField = $editing ? 'edit_date' : 'date';
        $timeField = $editing ? 'edit_time' : 'time';
        $timeRule = 'regex:/^(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)\s*-\s*(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)$/';
        $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg'],
            $typeField => ['required', 'in:single,multiple'],
            $dateField => ['nullable', 'required_if:'.$typeField.',single', 'date_format:Y-m-d'],
            $timeField => ['nullable', $timeRule],
            'session_year_id' => [$editing ? 'sometimes' : 'required', 'integer', 'min:1'],
            'date_range' => ['nullable', 'required_if:'.$typeField.',multiple', 'regex:/^\d{4}-\d{2}-\d{2} - \d{4}-\d{2}-\d{2}$/'],
            'edit_events' => ['nullable', 'array'], 'events' => ['nullable', 'array'],
            'edit_events.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'edit_events.*.title' => ['required', 'string', 'max:255'], 'events.*.title' => ['required', 'string', 'max:255'],
            'edit_events.*.date' => ['required', 'date_format:Y-m-d'], 'events.*.date' => ['required', 'date_format:Y-m-d'],
            'edit_events.*.timerange' => ['required', $timeRule], 'events.*.timerange' => ['required', $timeRule],
            'edit_events.*.description' => ['nullable', 'string'], 'events.*.description' => ['nullable', 'string'],
        ]);
        $type = $request->input($typeField);
        $existing = $editing ? (array) $request->input('edit_events', []) : [];
        $new = (array) $request->input('events', []);
        if ($type === 'multiple') {
            abort_if(count($existing) + count($new) === 0, 422, __('Add at least one programme item.'));
            [$start, $end] = explode(' - ', $request->input('date_range'));
            Validator::make(['start' => $start, 'end' => $end], ['start' => 'required|date_format:Y-m-d', 'end' => 'required|date_format:Y-m-d|after_or_equal:start'])->validate();
            foreach (array_merge($existing, $new) as $item) {
                abort_unless($item['date'] >= $start && $item['date'] <= $end, 422, __('date_must_be_within_specified_date_range'));
                [$from, $to] = explode(' - ', $item['timerange']);
                abort_unless($from < $to, 422, __('The end time must be after the start time.'));
            }
        } else {
            $start = $request->input($dateField);
            $end = null;
            if ($request->filled($timeField)) {
                [$from, $to] = explode(' - ', $request->input($timeField));
                abort_unless($from < $to, 422, __('The end time must be after the start time.'));
            }
        }

        return DB::transaction(function () use ($request, $id, $editing, $type, $start, $end, $existing, $new, $timeField) {
            $event = $editing ? Event::lockForUpdate()->findOrFail($id) : new Event;
            if (! $editing) {
                $event->session_year_id = SessionYear::findOrFail((int) $request->input('session_year_id'))->id;
            }
            // Children must belong to this exact parent, even within the same school.
            $children = $event->exists ? $event->multipleEvent()->lockForUpdate()->get()->keyBy('id') : collect();
            foreach ($existing as $item) {
                abort_unless($children->has((int) $item['id']), 404);
            }
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->type = $type;
            $event->start_date = $start;
            $event->end_date = $end;
            [$event->start_time, $event->end_time] = $type === 'single' && $request->filled($timeField) ? explode(' - ', $request->input($timeField)) : [null, null];
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $event->image = $file->storeAs('events', (string) Str::uuid().'-'.FileNames::forUpload($file), 'public');
            }
            $event->save();
            if ($type === 'single') {
                $event->multipleEvent()->delete();
            } else {
                foreach (array_merge($existing, $new) as $item) {
                    $row = isset($item['id']) ? $children->get((int) $item['id']) : new MultipleEvent;
                    [$from, $to] = explode(' - ', $item['timerange']);
                    $row->fill(['event_id' => $event->id, 'title' => $item['title'], 'date' => $item['date'], 'start_time' => $from, 'end_time' => $to, 'description' => $item['description'] ?? null])->save();
                }
            }

            return response()->json(['error' => false, 'message' => __($editing ? 'data_update_successfully' : 'data_store_successfully')]);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort_unless(Auth::user()->can('event-delete'), 403);
        DB::transaction(function () use ($id): void {
            $event = Event::lockForUpdate()->findOrFail($id);
            $readiness = EventReadinessLink::where('event_id', $id)->lockForUpdate()->first();
            abort_if($readiness && ($readiness->consent_request_id || $readiness->ticket_event_id || trim((string) $readiness->transport_notes) !== ''), 409,
                __('Review and remove this event’s linked records and transport notes before deleting it. Their source records are retained.'));
            $readiness?->delete();
            $event->delete();
        });

        return response()->json(['error' => false, 'message' => trans('data_delete_successfully')]);
    }

    public function deleteMultipleEvent($id)
    {
        if (! Auth::user()->can('event-delete')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        try {
            $multiEvent = MultipleEvent::find($id);
            $multiEvent->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function viewScheduleIndex($id)
    {
        if (! Auth::user()->can('event-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $multievents = MultipleEvent::where('event_id', $id)->get();
        $event = Event::where('id', $id)->firstOrFail();

        return view('events.view_schedule', compact('multievents', 'event'));
    }

    public function updateEvents(Request $request)
    {
        abort_unless($request->user()->can('event-edit'), 403);
        $data = $request->validate([
            'event_id' => 'required|integer|min:1',
            'edit_events' => 'required_without:events|array|min:1',
            'edit_events.*.id' => 'required|integer|min:1|distinct',
            'edit_events.*.title' => 'required|string|max:255',
            'edit_events.*.date' => 'required|date',
            'edit_events.*.description' => 'nullable|string',
            'edit_events.*.timerange' => ['required', 'regex:/^(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)\s*-\s*(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)$/'],
            'events' => 'required_without:edit_events|array|min:1',
            'events.*.title' => 'required|string|max:255',
            'events.*.date' => 'required|date',
            'events.*.description' => 'nullable|string',
            'events.*.timerange' => ['required', 'regex:/^(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)\s*-\s*(?:[01]\d|2[0-3]):(?:[0-5]\d):(?:[0-5]\d)$/'],
        ]);
        DB::transaction(function () use ($data): void {
            $parent = Event::query()->lockForUpdate()->findOrFail($data['event_id']);
            abort_unless($parent->type === 'multiple', 422, __('This event does not have a multiple-item programme.'));
            $existing = $parent->multipleEvent()->lockForUpdate()->get()->keyBy('id');
            foreach ($data['edit_events'] ?? [] as $item) {
                abort_unless($existing->has($item['id']), 404);
            }
            // Dates are bounded by the saved school event, never by hidden browser fields.
            foreach (array_merge($data['edit_events'] ?? [], $data['events'] ?? []) as $item) {
                [$from, $to] = array_map('trim', explode('-', $item['timerange']));
                abort_unless($from < $to, 422, __('The end time must be after the start time.'));
                $date = Carbon::parse($item['date'])->format('Y-m-d');
                if ($date < $parent->start_date || $date > $parent->end_date) {
                    throw ValidationException::withMessages(['events' => __('Programme dates must fall within the saved event dates.')]);
                }
            }
            foreach (['edit_events', 'events'] as $collection) {
                foreach ($data[$collection] ?? [] as $item) {
                    [$start, $end] = array_map('trim', explode('-', $item['timerange']));
                    $record = $collection === 'edit_events' ? $existing->get($item['id']) : new MultipleEvent;
                    $record->event_id = $parent->id;
                    $record->title = $item['title'];
                    $record->date = Carbon::parse($item['date'])->format('Y-m-d');
                    $record->start_time = $start;
                    $record->end_time = $end;
                    $record->description = $item['description'] ?? null;
                    $record->save();
                }
            }
        });

        return response()->json(['error' => false, 'message' => trans('data_update_successfully')]);
    }
}
