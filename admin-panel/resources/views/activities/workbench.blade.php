@extends('layouts.master')
@section('title', __('Activity groups'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/activity-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content activity-workbench">
<x-page.header :title="__('Activity groups')" :subtitle="__('Manage seasons, dated allocations, sessions and group materials.')" />
@include('academics.groups-status')
@if($conflict)<p role="alert">{{ __('This record changed. Compare your draft with the saved values before trying again.') }}</p>@endif
<form method="GET" action="{{ route('activity-groups.index') }}" class="activity-form" aria-label="{{ __('Activity form') }}">
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" type="select" :restore-old-input="false" name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')->all()" :value="$year->id" />
<x-m3.button type="submit" variant="tonal">{{ __('Open year') }}</x-m3.button>
</form>
<div class="activity-columns">
<x-m3.card :title="__('Groups and seasons')">
<a href="{{ route('activity-groups.index',['session_year_id'=>$year->id]) }}">{{ __('New activity group') }}</a>
<ul>@forelse($groups as $row)<li><a href="{{ route('activity-groups.index',['group_id'=>$row->id]) }}">{{ $row->name }} · {{ $row->season }} @if(!$row->active)({{ __('Archived') }})@endif</a></li>@empty<li>{{ __('No activity groups yet.') }}</li>@endforelse</ul>
</x-m3.card>
<fieldset class="activity-sections" @disabled($readOnly)>
@if($readOnly)<p role="status">{{ __('This school year is read-only. Select the current school year to make changes.') }}</p>@endif
@php($group=$detail['group'] ?? null)
@php($formAction=$group ? 'save' : 'create')
@php($draft=$failedAction===$formAction ? $restored : $group)
<x-m3.card :title="$group['name'] ?? __('New activity group')">
<form method="POST" action="{{ $group ? route('activity-groups.save',['group'=>$group['id']]) : route('activity-groups.create') }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['action'=>$formAction,'saved'=>$group])
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="name" :label="__('Group name')" :value="$draft['name'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="parent_id" :label="__('Parent group')" :options="[''=>__('No parent group')]+$groups->where('active',true)->where('id','!=',$group['id'] ?? 0)->pluck('name','id')->all()" :value="$draft['parent_id'] ?? ''" />
@foreach(['discipline'=>'Discipline','season'=>'Season'] as $key=>$label)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" :name="$key" :label="__($label)" :value="$draft[$key] ?? ''" required maxlength="100" />@endforeach
@include('activities.dates')
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="gender" :label="__('Gender eligibility')" :options="['any'=>__('Any'),'male'=>__('Male'),'female'=>__('Female')]" :value="$draft['gender'] ?? 'any'" />
@foreach(['minimum_grade'=>'Minimum grade (R = 0)','maximum_grade'=>'Maximum grade (R = 0)','minimum_age'=>'Minimum age','maximum_age'=>'Maximum age'] as $key=>$label)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="number" :name="$key" :label="__($label)" :value="$draft[$key] ?? ''" min="0" :max="str_contains($key,'grade') ? 12 : 25" />@endforeach
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="active" :label="__('Group availability')" :options="['1'=>__('Active'),'0'=>__('Archived')]" :value="(int) ($draft['active'] ?? true)" />
<x-m3.button type="submit">{{ __('Save group') }}</x-m3.button>
</form>
@if($conflict && $failedAction===$formAction)<h3>{{ __('Current saved values') }}</h3><dl>@foreach($group ?? [] as $key=>$value)<dt>{{ __($key) }}</dt><dd>{{ is_bool($value) ? ($value ? __('Yes') : __('No')) : $value }}</dd>@endforeach</dl>@endif
</x-m3.card>
@if($group)
<x-m3.card :title="__('Learner and staff allocations')">
<p>{{ __('Dates are inclusive. Cancel a future or current allocation without deleting its history.') }}</p>
@foreach(['membership','staff'] as $kind)
@php($action=$kind.'-save')
@php($saved=$edit===$kind ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<h3>{{ $kind==='staff' ? __('Allocate staff') : __('Allocate learner') }}</h3>
<form method="POST" action="{{ route('activity-groups.'.$action,['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
@php($personField=$kind==='staff' ? 'user_id' : 'learner_id')
@php($personNames=$kind==='staff' ? $staffNames : $learnerNames)
@if($saved)<p>{{ $personNames[$saved[$personField]] ?? __('Former school member') }}</p><input type="hidden" name="{{ $personField }}" value="{{ $saved[$personField] }}">@else<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" :name="$personField" :label="$kind==='staff' ? __('Staff member') : __('Learner')" :options="[''=>__('Choose a person')]+$personNames" :value="$draft[$personField] ?? ''" required />@endif
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="role" :label="__('Group role')" :options="$kind==='staff' ? ['facilitator'=>__('Facilitator'),'admin'=>__('Group administrator')] : ['member'=>__('Member'),'captain'=>__('Captain')]" :value="$draft['role'] ?? ($kind==='staff' ? 'facilitator' : 'member')" />
@if($kind==='staff')<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="cascade" :label="__('Descendant groups')" :options="['0'=>__('This group only'),'1'=>__('Include descendant groups as administrator')]" :value="(int) ($draft['cascade'] ?? false)" />@endif
@include('activities.dates')
@if($saved)<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="active" :label="__('Allocation availability')" :options="['1'=>__('Active'),'0'=>__('Cancelled')]" :value="(int) ($draft['active'] ?? true)" />@endif
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="reason" :label="__('Reason')" :value="$draft['reason'] ?? ''" required minlength="3" maxlength="2000" />
<x-m3.button type="submit">{{ __('Save allocation') }}</x-m3.button>
</form>
<ul>@foreach($kind==='staff' ? $roster['staff'] : $roster['memberships'] as $row)<li><a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'edit'=>$kind,'record_id'=>$row['id']]) }}">{{ $kind==='staff' ? ($staffNames[$row['user_id']] ?? __('Former school member')) : $row['learner_name'] }} · {{ __($row['role']) }} · {{ $row['starts_on'] }}–{{ $row['ends_on'] }} @if(!$row['active'])({{ __('Cancelled') }})@endif</a></li>@endforeach</ul>
@endforeach
</x-m3.card>
<x-m3.card :title="__('Sessions and attendance')">
@php($action='session-save')
@php($saved=$edit==='session' ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<form method="POST" action="{{ route('activity-groups.session-save',['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="date" name="on_date" :label="__('Session date')" :value="$draft['on_date'] ?? ''" required />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="name" :label="__('Session name')" :value="$draft['name'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="venue" :label="__('Venue')" :value="$draft['venue'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="status" :label="__('Session status')" :options="['scheduled'=>__('Scheduled'),'completed'=>__('Completed'),'cancelled'=>__('Cancelled')]" :value="$draft['status'] ?? 'scheduled'" />
<x-m3.button type="submit">{{ __('Save session') }}</x-m3.button>
</form>
<ul>@foreach($detail['sessions'] as $row)<li>{{ $row['on_date'] }} · {{ $row['name'] }} · {{ $row['venue'] }} · {{ __($row['status']) }} <a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'edit'=>'session','record_id'=>$row['id']]) }}">{{ __('Edit session') }}</a> <a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'session_id'=>$row['id']]) }}">{{ __('Open attendance') }}</a></li>@endforeach</ul>
@if($session)
<h3>{{ $session['name'] }} · {{ $session['on_date'] }}</h3>
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
<x-m3.card :title="__('Group materials')">
@php($action='material-save')
@php($saved=$edit==='material' ? $record : null)
@php($draft=$failedAction===$action ? $restored : $saved)
<form method="POST" action="{{ route('activity-groups.material-save',['group'=>$group['id'],'record'=>$saved['id'] ?? null]) }}" class="activity-form" aria-label="{{ __('Activity form') }}">@csrf
@include('activities.operation',['saved'=>$saved])
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" name="title" :label="__('Material title')" :value="$draft['title'] ?? ''" required maxlength="191" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="textarea" name="markdown" :label="__('Material Markdown')" :value="$draft['markdown'] ?? ''" required maxlength="20000" rows="8" />
<x-page.field :id="'activity-'.\Illuminate\Support\Str::uuid()" :restore-old-input="false" type="select" name="published" :label="__('Material visibility')" :options="['0'=>__('Private draft'),'1'=>__('Published to group members')]" :value="(int) ($draft['published'] ?? false)" />
<x-m3.button type="submit">{{ __('Save material') }}</x-m3.button>
</form>
@foreach($detail['materials'] as $row)<section><h3>{{ $row['title'] }}</h3><p>{{ $row['published'] ? __('Published') : __('Private draft') }}</p><div class="activity-markdown">{!! $row['html'] !!}</div><a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'edit'=>'material','record_id'=>$row['id']]) }}">{{ __('Edit material') }}</a></section>@endforeach
</x-m3.card>
@endif
</fieldset></div></div>
@endsection
