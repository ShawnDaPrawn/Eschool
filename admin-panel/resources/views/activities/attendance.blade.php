<x-m3.card :title="__('Activity attendance')">
@if($session)
<h3>{{ $session['name'] }} · {{ $session['on_date'] }}</h3>
@include('activities.session-cover',['coverSession'=>$session])
<p>{{ __('Choose only the learners you are recording. Blank entries remain unrecorded.') }}</p>
@if($session['status']!=='cancelled' && $session['on_date']<=app(\App\Services\Activities\CommunityAuthority::class)->today())
@php($action='attendance-save')
@php($draft=$failedAction===$action ? $restored : null)
<form method="POST" action="{{ route('activity-groups.attendance-save',['group'=>$group['id'],'session'=>$session['id']]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$session])
@foreach($attendance as $learner=>$entry)
@php($submitted=collect($draft['entries'] ?? [])->firstWhere('learner_id',$learner))
<input type="hidden" name="entries[{{ $learner }}][learner_id]" value="{{ $learner }}"><input type="hidden" name="entries[{{ $learner }}][revision]" value="{{ $entry['revision'] }}">
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" :name="'entries['.$learner.'][status]'" :label="$entry['learner_name']" :options="[''=>__('Unrecorded'),'present'=>__('Present'),'absent'=>__('Absent'),'late'=>__('Late'),'excused'=>__('Excused')]" :value="$submitted['status'] ?? $entry['status']" />
@endforeach
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="reason" :label="__('Attendance reason')" :value="$draft['reason'] ?? ''" required minlength="3" maxlength="2000" />
<x-m3.button type="submit">{{ __('Save attendance') }}</x-m3.button>
</form>
@else<p>{{ __('Attendance opens on the session date. Cancelled sessions cannot record attendance.') }}</p>@endif
@endif
</x-m3.card>
